<?php

namespace Dialog360\Response;

/**
 * GET /whatsapp_business_profile 响应（WhatsApp Business 主页资料）。
 *
 * 响应体形如：
 * {
 *   "data": [
 *     {
 *       "messaging_product": "whatsapp",
 *       "about": "string",
 *       "address": "string",
 *       "description": "string",
 *       "email": "string",
 *       "profile_picture_url": "string",
 *       "vertical": "RETAIL",
 *       "websites": ["https://example.com"]
 *     }
 *   ]
 * }
 */
class BusinessProfileResponse
{
    private array $data;

    private string $messagingProduct;

    private string $about;

    private string $address;

    private string $description;

    private string $email;

    private string $profilePictureUrl;

    private string $vertical;

    private array $websites;

    public function __construct(array $data)
    {
        $this->data = $data;

        // data 为数组，取第一个 profile 对象
        $profile = $data['data'][0] ?? [];

        $this->messagingProduct = (string) ($profile['messaging_product'] ?? '');
        $this->about = (string) ($profile['about'] ?? '');
        $this->address = (string) ($profile['address'] ?? '');
        $this->description = (string) ($profile['description'] ?? '');
        $this->email = (string) ($profile['email'] ?? '');
        $this->profilePictureUrl = (string) ($profile['profile_picture_url'] ?? '');
        $this->vertical = (string) ($profile['vertical'] ?? '');
        $this->websites = $profile['websites'] ?? [];
    }

    /**
     * 获取消息产品标识（whatsapp）
     */
    public function getMessagingProduct(): string
    {
        return $this->messagingProduct;
    }

    /**
     * 获取主页简介（about 文本）
     */
    public function getAbout(): string
    {
        return $this->about;
    }

    /**
     * 获取公司地址
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * 获取公司描述
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * 获取联系邮箱
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * 获取头像 URL
     */
    public function getProfilePictureUrl(): string
    {
        return $this->profilePictureUrl;
    }

    /**
     * 获取行业分类（如 RETAIL）
     */
    public function getVertical(): string
    {
        return $this->vertical;
    }

    /**
     * 获取网站列表
     *
     * @return array
     */
    public function getWebsites(): array
    {
        return $this->websites;
    }

    /**
     * 获取原始响应数据
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'messaging_product' => $this->messagingProduct,
            'about' => $this->about,
            'address' => $this->address,
            'description' => $this->description,
            'email' => $this->email,
            'profile_picture_url' => $this->profilePictureUrl,
            'vertical' => $this->vertical,
            'websites' => $this->websites,
        ];
    }
}
