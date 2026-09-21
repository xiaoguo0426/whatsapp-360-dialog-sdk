-- ============================================================================
-- 360dialog WhatsApp Webhook 消息存储表
--
-- 设计说明:
--   1. 粒度: 每个 webhook payload 中的"单条消息(messages[])"或
--      "单条状态回执(statuses[])"展开存储为一行, 便于直接查询;
--      同时保留完整原始 payload(JSON) 用于追溯。
--   2. 两类事件共用一张表, 通过 webhook_type 区分:
--      - message: 入站消息(用户发给业务的文本/媒体/位置/按钮回复等)
--      - status : 状态回执(sent/delivered/read/failed/deleted)
--   3. 去重: 360dialog 在未收到 200 响应时会重试投递同一 payload,
--      使用 payload_hash(SHA256) + 数组下标 组成唯一索引,
--      配合 INSERT IGNORE 实现幂等入库。
--   4. 时间: webhook 中的 timestamp 为秒级 Unix 时间戳, 入库时转为
--      DATETIME(建议统一使用 UTC), received_at 记录入库时间。
--   5. 要求: MySQL 5.7.8+ (JSON 类型支持); InnoDB 默认 DYNAMIC 行格式。
-- ============================================================================

CREATE TABLE `whatsapp_webhook_messages` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增主键',

    -- ===== webhook 事件基础信息 =====
    `object_type`              VARCHAR(64)  NOT NULL DEFAULT 'whatsapp_business_account' COMMENT 'webhook 对象类型(object)',
    `waba_id`                  VARCHAR(64)  NOT NULL COMMENT 'WhatsApp Business Account ID(entry[].id)',
    `field`                    VARCHAR(32)  NOT NULL DEFAULT 'messages' COMMENT '变更字段(changes[].field)',

    -- ===== 号码元数据(metadata) =====
    `phone_number_id`          VARCHAR(64)  NOT NULL COMMENT '商业号码 ID(metadata.phone_number_id)',
    `display_phone_number`     VARCHAR(32)  NOT NULL COMMENT '展示号码(metadata.display_phone_number)',

    -- ===== 事件类型 =====
    `webhook_type`             VARCHAR(16)  NOT NULL COMMENT '事件类型: message=入站消息, status=状态回执',

    -- ===== 联系人信息 =====
    -- 入站消息: 取自 contacts[] 及 messages[].from
    -- 状态回执: 取自 statuses[].recipient_id / recipient_user_id
    `wa_id`                    VARCHAR(32)  DEFAULT NULL COMMENT '联系人 WhatsApp 账号(wa_id / recipient_id)',
    `user_id`                  VARCHAR(64)  DEFAULT NULL COMMENT '360dialog 用户 ID(user_id / recipient_user_id)',
    `contact_name`             VARCHAR(255) DEFAULT NULL COMMENT '联系人名称(contacts[].profile.name, 仅入站消息)',

    -- ===== 消息信息(messages[], 仅入站消息) =====
    `message_id`               VARCHAR(255) DEFAULT NULL COMMENT '消息 ID(wamid.xxx)',
    `message_type`             VARCHAR(32)  DEFAULT NULL COMMENT '消息类型: text/image/audio/video/document/sticker/location/contacts/interactive/template/reaction/button/reply 等',
    `from_number`              VARCHAR(32)  DEFAULT NULL COMMENT '发送方号码(messages[].from)',
    `message_body`             TEXT         DEFAULT NULL COMMENT '消息正文(text.body / caption 等, 便于直接检索)',
    `message_content`          JSON         DEFAULT NULL COMMENT '完整消息对象(messages[] 原始 JSON, 含 media/location/reaction/interactive 等结构)',
    `reply_to_message_id`      VARCHAR(255) DEFAULT NULL COMMENT '被回复消息 ID(context.id)',

    -- ===== 状态回执信息(statuses[], 仅状态回执) =====
    `status`                   VARCHAR(16)  DEFAULT NULL COMMENT '消息状态: sent/delivered/read/failed/deleted',
    `conversation_id`          VARCHAR(64)  DEFAULT NULL COMMENT '会话 ID(statuses[].conversation.id)',
    `conversation_origin_type` VARCHAR(32)  DEFAULT NULL COMMENT '会话来源(statuses[].conversation.origin.type, 如 service/marketing/utility/authentication)',
    `pricing_billable`         TINYINT(1)   DEFAULT NULL COMMENT '是否计费(statuses[].pricing.billable)',
    `pricing_model`            VARCHAR(32)  DEFAULT NULL COMMENT '计费模型(PMP/CBPF)',
    `pricing_category`         VARCHAR(32)  DEFAULT NULL COMMENT '计费类别(service/marketing/utility/authentication)',
    `pricing_type`             VARCHAR(64)  DEFAULT NULL COMMENT '计费类型(free_customer_service 等)',
    `error_code`               INT          DEFAULT NULL COMMENT '错误码(statuses[].errors[0].code, failed 时)',
    `error_title`              VARCHAR(255) DEFAULT NULL COMMENT '错误标题(statuses[].errors[0].title)',
    `error_message`            TEXT         DEFAULT NULL COMMENT '错误详情(statuses[].errors[0].message)',

    -- ===== 时间 =====
    `event_time`               DATETIME     DEFAULT NULL COMMENT '事件时间(messages[].timestamp 或 statuses[].timestamp 转换, 建议 UTC)',

    -- ===== 原始数据与去重 =====
    `raw_payload`              JSON         NOT NULL COMMENT '完整原始 webhook 请求体(便于追溯)',
    `payload_hash`             CHAR(64)     NOT NULL COMMENT '原始 payload 的 SHA256(webhook 重试去重)',
    `entry_index`              SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'entry 数组下标',
    `change_index`             SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'changes 数组下标',
    `item_index`               SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'messages/statuses 数组下标',
    `received_at`              DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'webhook 接收入库时间',

    PRIMARY KEY (`id`),

    -- 同一 payload 重试投递时, 各子项的 hash+下标 完全相同, 可实现幂等去重
    UNIQUE KEY `uk_dedup` (`payload_hash`, `entry_index`, `change_index`, `item_index`),

    -- 常用查询索引
    KEY `idx_message_id`     (`message_id`),                       -- 按消息 ID 查消息/全部状态
    KEY `idx_wa_id_time`     (`wa_id`, `event_time`),              -- 查某联系人的消息记录/时间线
    KEY `idx_phone_number`   (`phone_number_id`, `event_time`),    -- 按商业号码筛选
    KEY `idx_type_status`    (`webhook_type`, `status`),           -- 筛选失败消息等
    KEY `idx_event_time`     (`event_time`),                       -- 按时间范围归档/清理

    -- 如需按会话统计计费, 可启用以下索引
    -- KEY `idx_conversation` (`conversation_id`, `pricing_billable`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  COMMENT = '360dialog WhatsApp Webhook 消息表(入站消息 + 状态回执)';


-- ============================================================================
-- 示例: 入库你提供的状态回执 payload (webhook_type = 'status')
-- PHP 侧: $payloadHash = hash('sha256', $rawBody);
-- ============================================================================

INSERT IGNORE INTO `whatsapp_webhook_messages` (
    `object_type`, `waba_id`, `field`,
    `phone_number_id`, `display_phone_number`,
    `webhook_type`, `wa_id`, `user_id`,
    `message_id`, `status`, `event_time`,
    `conversation_id`, `conversation_origin_type`,
    `pricing_billable`, `pricing_model`, `pricing_category`, `pricing_type`,
    `raw_payload`, `payload_hash`,
    `entry_index`, `change_index`, `item_index`
) VALUES (
    'whatsapp_business_account', '398010513396791', 'messages',
    '411956318666448', '85222073123',
    'status', '8618819125362', 'CN.28008187432120614',
    'wamid.HBgNODYxODgxOTEyNTM2MhUCABEYEkVBMEI0NDE5MDE1ODhGMjUwNwA=', 'read', FROM_UNIXTIME(1789715674),
    'c3b037eae4f6ada2eb919302cc7e7572', 'service',
    0, 'PMP', 'service', 'free_customer_service',
    '{"object":"whatsapp_business_account","entry":[...原始payload全文...]}',
    SHA2('{"object":"whatsapp_business_account","entry":[...原始payload全文...]}', 256),
    0, 0, 0
);


-- ============================================================================
-- 常用查询示例
-- ============================================================================

-- 1. 查询某条消息的全部状态流转(sms 发送后追踪 sent -> delivered -> read)
-- SELECT status, event_time
-- FROM whatsapp_webhook_messages
-- WHERE message_id = 'wamid.HBgNODYxODgxOTEyNTM2MhUCABEYEkVBMEI0NDE5MDE1ODhGMjUwNwA='
--   AND webhook_type = 'status'
-- ORDER BY event_time;

-- 2. 查询某联系人最近的入站消息(客服会话记录)
-- SELECT message_type, message_body, event_time
-- FROM whatsapp_webhook_messages
-- WHERE wa_id = '8618819125362' AND webhook_type = 'message'
-- ORDER BY event_time DESC
-- LIMIT 50;

-- 3. 查询发送失败的消息及原因
-- SELECT message_id, error_code, error_title, error_message, event_time
-- FROM whatsapp_webhook_messages
-- WHERE webhook_type = 'status' AND status = 'failed'
-- ORDER BY event_time DESC;
