<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\BusinessProfileResponse;
use Dialog360\Response\SetBusinessProfileResponse;

/**
 * WhatsApp Business 主页资料 API 域：/whatsapp_business_profile
 *
 * - GET /whatsapp_business_profile：查询主页资料（fields 指定返回字段）
 * - POST /whatsapp_business_profile：更新主页资料
 *
 * 更新头像需先通过 Resumable Upload API 上传取得 profile_picture_handle。
 *
 * @see https://docs.360dialog.com/docs/messaging-api/api-reference/profile
 */
readonly class ProfileApi
{
    /** GET 查询可指定的字段 */
    public const FIELDS = [
        'messaging_product',
        'about',
        'address',
        'description',
        'email',
        'profile_picture_url',
        'websites',
        'vertical',
    ];

    /** POST 更新可设置的字段（messaging_product 自动填充，无需传入） */
    public const UPDATABLE_FIELDS = [
        'about',
        'address',
        'description',
        'email',
        'profile_picture_handle',
        'vertical',
        'websites',
    ];

    /** vertical（行业分类）合法取值 */
    public const VERTICALS = [
        'OTHER',
        'AUTO',
        'BEAUTY',
        'APPAREL',
        'EDU',
        'ENTERTAIN',
        'EVENT_PLAN',
        'FINANCE',
        'GROCERY',
        'GOVT',
        'HOTEL',
        'HEALTH',
        'NONPROFIT',
        'PROF_SERVICES',
        'RETAIL',
        'TRAVEL',
        'RESTAURANT',
        'ALCOHOL',
        'ONLINE_GAMBLING',
        'PHYSICAL_GAMBLING',
        'OTC_DRUGS',
    ];

    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 查询主页资料（Cloud API: GET /whatsapp_business_profile）
     *
     * @param array|null $fields 需要返回的字段列表，取值见 ProfileApi::FIELDS；
     *                           null 时由 API 返回默认字段
     * @return BusinessProfileResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function get(?array $fields = null): BusinessProfileResponse
    {
        $options = [];
        if ($fields !== null) {
            $this->assertKnownFields($fields, self::FIELDS, 'fields');
            $options['query'] = ['fields' => implode(',', $fields)];
        }

        $data = $this->connector->get('/whatsapp_business_profile', $options, '获取主页资料');
        return new BusinessProfileResponse($data);
    }

    /**
     * 更新主页资料（Cloud API: POST /whatsapp_business_profile）
     *
     * @param array $fields 要更新的字段（键见 ProfileApi::UPDATABLE_FIELDS），例如：
     *                      ['about' => '...', 'email' => '...', 'websites' => ['https://example.com'],
     *                       'vertical' => 'RETAIL']
     * @return SetBusinessProfileResponse
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function update(array $fields): SetBusinessProfileResponse
    {
        $this->assertKnownFields(array_keys($fields), self::UPDATABLE_FIELDS, 'fields');
        if (isset($fields['vertical'])) {
            $this->assertVertical((string) $fields['vertical']);
        }

        $payload = ['messaging_product' => 'whatsapp'];
        foreach ($fields as $key => $value) {
            $payload[$key] = $this->normalizeFieldValue((string) $key, $value);
        }

        $data = $this->connector->request('POST', '/whatsapp_business_profile', ['json' => $payload], '更新主页资料');

        return new SetBusinessProfileResponse($data);
    }

    /**
     * 校验字段名是否在允许列表内
     *
     * @param array $keys 字段名列表
     * @param array $allowed 允许的字段名
     * @param string $label 参数名（用于异常消息）
     */
    private function assertKnownFields(array $keys, array $allowed, string $label): void
    {
        foreach ($keys as $key) {
            if (!is_string($key) || !in_array($key, $allowed, true)) {
                throw new \InvalidArgumentException(sprintf(
                    '%s 含未知字段"%s"，允许的字段：%s',
                    $label,
                    is_scalar($key) ? (string) $key : gettype($key),
                    implode(', ', $allowed)
                ));
            }
        }
    }

    /**
     * 校验 vertical 取值
     */
    private function assertVertical(string $vertical): void
    {
        if (!in_array($vertical, self::VERTICALS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'vertical 取值"%s"不合法，允许的取值：%s',
                $vertical,
                implode(', ', self::VERTICALS)
            ));
        }
    }

    /**
     * 规范化单个字段值：文本字段标量化，websites 必须为非空字符串数组
     */
    private function normalizeFieldValue(string $key, mixed $value): mixed
    {
        if ($key === 'websites') {
            if (!is_array($value)) {
                throw new \InvalidArgumentException('websites 必须为数组');
            }
            foreach ($value as $index => $url) {
                if (!is_string($url) || trim($url) === '') {
                    throw new \InvalidArgumentException(sprintf('websites[%s] 必须为非空字符串', $index));
                }
            }

            return $value;
        }

        if (!is_scalar($value)) {
            throw new \InvalidArgumentException(sprintf('%s 必须为标量值', $key));
        }

        return (string) $value;
    }
}
