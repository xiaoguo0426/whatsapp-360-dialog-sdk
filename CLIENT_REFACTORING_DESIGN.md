# Dialog360Client 分层重构设计方案

## 1. 现状与问题

`src/Dialog360Client.php`（581 行）单一类承载了全部 6 个 API 域的 15 个公共方法：

| API 域 | 方法 | 行为 |
|---|---|---|
| Messages | `sendMessage()` | 带重试 |
| Health | `getHealthStatus()` | 不重试 |
| Media | `uploadMedia()` `getMediaInfo()` `downloadMediaFile()` `downloadMedia()` `deleteMedia()` + 私有 `validateMediaFile()` | 上传带重试，其余不重试 |
| Templates | `getTemplates()` | 不重试 |
| Webhook | `getWebhookUrl()` `setWebhookUrl()` `getWabaWebhookUrl()` `setWabaWebhookUrl()` | set 带重试 |
| 已废弃 | `getPhoneNumberInfo()` `getApiKeyInfo()` | 直接抛异常 |

**问题清单：**

1. **重试循环复制粘贴 4 次**：`sendMessage` / `uploadMedia` / `setWebhookUrl` / `setWabaWebhookUrl` 的 `while ($attempts < ...)` 结构几乎逐行相同
2. **复制粘贴导致的错误消息错乱**（现存 bug）：
   - `setWebhookUrl` 重试耗尽抛的是"**发送消息**失败，已重试3次"
   - `setWabaWebhookUrl` 同样抛"**发送消息**失败"
   - `getTemplates` 的异常消息是"获取**媒体信息**失败"
   - `getWabaWebhookUrl` 同样是"获取**媒体信息**失败"
3. **重试语义不一致**：`sendMessage` 已实现"4xx 即时返回不重试"（v2 修复），但另外 3 个重试方法对 400 仍无脑重试 3 次 + sleep 6 秒
4. **42 行注释死代码**（`setSandboxWebhookUrl`）夹杂在文件中间
5. 门面持续膨胀：营销平台将新增 Block Users 等 API，继续堆在同一类里不可维护

## 2. 重构目标与原则

1. **按 API 资源域拆分**为独立 Api 类，单一职责
2. **传输层下沉**：重试、错误分类、JSON 解析收敛到一处（消除 4 份拷贝）
3. **零破坏向后兼容**：`Dialog360Client` 全部公共方法签名不变，现有 15 个 examples 与 28 个测试**一行不改**即可通过
4. 顺手修复问题清单中的错误消息错乱与重试语义不一致

## 3. 分层架构

```
应用代码 / examples / tests
        │
        ▼
┌──────────────────────────────────────────────┐
│  Dialog360Client（门面，保持向后兼容）          │
│  · 构造配置 + 创建 ApiConnector               │
│  · 域访问器: messages() media() webhook() ...  │
│  · 全部旧公共方法 → 一行委托 + @deprecated     │
└──────────────┬───────────────────────────────┘
               │ 组合
┌──────────────▼───────────────────────────────┐
│  Api 域类（按 360dialog API 资源划分）          │
│  Api/MessagesApi    POST /messages            │
│  Api/MediaApi       POST /media, GET|DEL /{id}│
│  Api/WebhookApi     /v1/configs/webhook,      │
│                     /waba_webhook             │
│  Api/TemplateApi    /v1/configs/templates     │
│  Api/HealthApi      /health_status            │
│  职责: 组装参数 + 转换 Response 对象            │
└──────────────┬───────────────────────────────┘
               │ 组合
┌──────────────▼───────────────────────────────┐
│  Http/ApiConnector（传输层）                    │
│  · 统一重试: 5xx/网络错误 → 指数退避重试        │
│  · 统一 4xx: 抛 Dialog360ClientError(带响应体)  │
│  · 统一 JSON 解析 / 原始响应透出               │
└──────────────────────────────────────────────┘
```

## 4. 核心类设计

### 4.1 `Http/ApiConnector` — 传输层（消除 4 份重试拷贝）

```php
namespace Dialog360\Http;

class ApiConnector
{
    public function __construct(
        private Client $httpClient,
        private int $retryAttempts
    ) {}

    /**
     * 带重试的 JSON 请求（核心方法，统一所有重试语义）
     *
     * 成功 → 返回解析后的 array
     * 4xx  → 抛 Dialog360ClientError（携带 API 错误响应体，不重试）
     * 5xx / 网络错误 → 指数退避重试，耗尽后抛 Dialog360Exception
     *
     * @param string $action 动作描述，用于异常消息（如"上传媒体文件"）
     */
    public function request(string $method, string $uri, array $options, string $action): array;

    /** 不重试的 GET（幂等查询），4xx 同样抛 Dialog360ClientError */
    public function get(string $uri, array $options, string $action): array;

    /** 原始请求（媒体下载等二进制场景），返回 PSR-7 ResponseInterface */
    public function getRaw(string $uri): ResponseInterface;
}
```

重试循环只在这里出现一次，`sleep(pow(2, $attempts))` 退避策略、`RequestException`/`GuzzleException` 分类处理全部收敛。

### 4.2 `Exception/Dialog360ClientError` — 4xx 客户端错误

```php
namespace Dialog360\Exception;

class Dialog360ClientError extends Dialog360Exception
{
    public static function fromResponse(ResponseInterface $response, RequestException $previous): self;

    /** API 返回的错误体（如 {"errors":[{"code":..., "message":...}]}），供上层解析 */
    public function getResponseBody(): array;

    public function getStatusCode(): int;
}
```

引入它是关键决策：让"4xx 不重试、但携带错误详情"成为可被上层捕获的**异常类型**，各 Api 域类自行决定处理方式（默认抛给用户 / MessagesApi 转成 MessageResponse）。

### 4.3 Api 域类（以 MessagesApi / MediaApi 为例）

```php
namespace Dialog360\Api;

class MessagesApi
{
    public function __construct(private ApiConnector $connector) {}

    public function send(MessageInterface $message): MessageResponse
    {
        $payload = $message->toArray();
        $payload['messaging_product'] = 'whatsapp';
        $payload['recipient_type'] = $payload['recipient_type'] ?? 'individual';
        $payload['to'] = $message->getTo();

        try {
            $data = $this->connector->request('POST', '/messages', ['json' => $payload], '发送消息');
        } catch (Dialog360ClientError $e) {
            // 保留既有行为：4xx 解析 API 错误详情，返回 isSuccess=false 的响应
            return new MessageResponse($e->getResponseBody());
        }

        return new MessageResponse($data);
    }
}

class MediaApi
{
    public function upload(string $filePath, string $mimeType): string;      // 含 validateMediaFile
    public function getInfo(string $mediaId): MediaResponse;
    public function download(string $mediaId, ?string $savePath = null): string;
    public function downloadByUrl(string $downloadUrl, string $savePath): bool;
    public function delete(string $mediaId): bool;
    private function validateMediaFile(string $filePath, string $mimeType): void;  // 随域迁移
}
```

`WebhookApi`（`getUrl`/`setUrl`/`getWabaUrl`/`setWabaUrl`）、`TemplateApi`（`list`）、`HealthApi`（`status`）同理。

### 4.4 `Dialog360Client` — 门面（~170 行）

```php
class Dialog360Client
{
    private ApiConnector $connector;
    private ?MessagesApi $messages = null;   // 惰性创建 + 复用
    private ?MediaApi $media = null;
    // ...

    /** 域访问器（新用法入口） */
    public function messages(): MessagesApi;
    public function media(): MediaApi;
    public function webhook(): WebhookApi;
    public function templates(): TemplateApi;
    public function health(): HealthApi;

    /** ===== 向后兼容层（@deprecated，委托实现） ===== */

    /** @deprecated 请使用 $client->messages()->send() */
    public function sendMessage(MessageInterface $message): MessageResponse
    {
        return $this->messages()->send($message);
    }

    /** @deprecated 请使用 $client->media()->upload() */
    public function uploadMedia(string $filePath, string $mimeType): string
    {
        return $this->media()->upload($filePath, $mimeType);
    }
    // ... 其余公共方法同样一行委托
}
```

## 5. 新旧用法对照

```php
// 旧用法（继续可用，IDE 会提示 deprecated）
$client->sendMessage($message);
$client->uploadMedia($path, $mime);
$client->setWebhookUrl('https://...');

// 新用法（推荐）
$client->messages()->send($message);
$client->media()->upload($path, $mime);
$client->webhook()->setUrl('https://...');
$client->templates()->list();
```

## 6. 重构带来的行为改进

| 项 | 重构前 | 重构后 |
|---|---|---|
| `setWebhookUrl` 收到 400 | 重试 3 次 + sleep 6 秒后抛错 | 立即抛 `Dialog360ClientError`（含 API 错误详情） |
| `setWebhookUrl` 重试耗尽消息 | "发送消息失败，已重试3次"（错乱） | "设置 Webhook 失败，已重试3次" |
| `getTemplates` 异常消息 | "获取媒体信息失败"（错乱） | "获取模板失败" |
| 死代码 `setSandboxWebhookUrl`（42 行注释） | 保留 | 删除 |
| 新增 API（如 Block Users） | 继续膨胀门面 | 新增一个 Api 类 + 一个域访问器 |

## 7. 目录结构对比

```
重构前                          重构后
src/                            src/
├── Dialog360Client.php (581行) ├── Dialog360Client.php (~170行, 门面)
├── Message/ (9类)              ├── Http/
├── Response/                   │   └── ApiConnector.php (~110行)
└── Exception/                  ├── Api/
                                │   ├── MessagesApi.php (~60行)
                                │   ├── MediaApi.php (~200行)
                                │   ├── WebhookApi.php (~70行)
                                │   ├── TemplateApi.php (~50行)
                                │   └── HealthApi.php (~30行)
                                ├── Exception/
                                │   ├── Dialog360Exception.php
                                │   └── Dialog360ClientError.php (新增)
                                ├── Message/ (9类,不动)
                                └── Response/ (不动)
```

## 8. 实施步骤（安全顺序，每步测试通过再进下一步）

1. **新增** `Exception/Dialog360ClientError.php`（无风险）
2. **新增** `Http/ApiConnector.php` + `ApiConnectorTest`（纯新增，锁定重试/4xx/5xx 语义）
3. **逐域新增 Api 类**（Media → Messages → Webhook → Template → Health），每个域类落地后先写独立单测，`Dialog360Client` 尚不动
4. **门面切换**：`Dialog360Client` 旧方法改为一行委托，删除原实现与死代码 → 跑全量 `Dialog360ClientTest`（28 个用例零修改全部通过 = 兼容性证明）
5. 旧方法标注 `@deprecated`，更新 README 用法示例
6. 发版：minor 版本（无破坏）+ 变更日志说明迁移路径

## 9. 测试策略

- **兼容性测试**：现有 `Dialog360ClientTest` 一行不改，作为门面行为的回归套件
- **新增 `ApiConnectorTest`**：4xx 立即抛 `Dialog360ClientError`、5xx 重试、网络错误重试、重试耗尽异常消息、`getRaw` 透传
- **新增按域测试**（可选）：`MessagesApiTest` 等直接测域类，脱离门面
- MockHandler + HandlerStack 注入模式不变（现有 setUp 结构完全适用）
