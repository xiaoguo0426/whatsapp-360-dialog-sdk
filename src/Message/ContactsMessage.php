<?php

namespace Dialog360\Message;

/**
 * 联系人消息（Contacts）
 *
 * 根据 360dialog 官方文档:
 * @see https://docs.360dialog.com/docs/messaging/message-types/contacts
 *
 * JSON 结构:
 * {
 *   "messaging_product": "whatsapp",
 *   "to": "<phone>",
 *   "type": "contacts",
 *   "contacts": [ { "name": { "formatted_name": "..." }, ... } ]
 * }
 */
class ContactsMessage implements MessageInterface
{
    /**
     * 单条消息允许的最大联系人数（官方限制 257）
     */
    private const MAX_CONTACTS = 257;

    private string $to;
    private array $contacts;

    /**
     * @param string $to 接收者 WhatsApp 电话号码
     * @param array $contacts 联系人数组，每个联系人必须包含 name.formatted_name 字段，
     *                        可选字段: addresses, birthday, emails, org, phones, urls
     */
    public function __construct(string $to, array $contacts)
    {
        $this->to = $to;

        if (empty($contacts)) {
            throw new \InvalidArgumentException('联系人数组不能为空');
        }

        if (count($contacts) > self::MAX_CONTACTS) {
            throw new \InvalidArgumentException('单条消息最多只能包含 ' . self::MAX_CONTACTS . ' 个联系人');
        }

        foreach (array_values($contacts) as $index => $contact) {
            if (!is_array($contact) || !isset($contact['name']['formatted_name'])) {
                throw new \InvalidArgumentException(
                    sprintf('第 %d 个联系人缺少必填字段 name.formatted_name', $index + 1)
                );
            }
        }

        $this->contacts = array_values($contacts);
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return 'contacts';
    }

    public function toArray(): array
    {
        return [
            'type' => 'contacts',
            'contacts' => $this->contacts
        ];
    }

    public function getContacts(): array
    {
        return $this->contacts;
    }

    /**
     * 快捷创建只包含一个联系人的消息
     */
    public static function single(string $to, array $contact): self
    {
        return new self($to, [$contact]);
    }

    /**
     * 构建姓名对象（必填 formatted_name）
     */
    public static function name(
        string $formattedName,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $middleName = null,
        ?string $prefix = null,
        ?string $suffix = null
    ): array {
        return array_filter([
            'formatted_name' => $formattedName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'prefix' => $prefix,
            'suffix' => $suffix,
        ], fn ($value) => $value !== null);
    }

    /**
     * 构建电话对象
     *
     * @param string $type 标准值: CELL, MAIN, IPHONE, HOME, WORK
     */
    public static function phone(string $phone, string $type = 'CELL', ?string $waId = null): array
    {
        return array_filter([
            'phone' => $phone,
            'type' => $type,
            'wa_id' => $waId,
        ], fn ($value) => $value !== null);
    }

    /**
     * 构建邮箱对象
     *
     * @param string $type 标准值: HOME, WORK
     */
    public static function email(string $email, string $type = 'WORK'): array
    {
        return [
            'email' => $email,
            'type' => $type,
        ];
    }

    /**
     * 构建地址对象
     *
     * @param string $type 标准值: HOME, WORK
     */
    public static function address(
        ?string $street = null,
        ?string $city = null,
        ?string $state = null,
        ?string $zip = null,
        ?string $country = null,
        ?string $countryCode = null,
        string $type = 'HOME'
    ): array {
        return array_filter([
            'street' => $street,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'country' => $country,
            'country_code' => $countryCode,
            'type' => $type,
        ], fn ($value) => $value !== null);
    }

    /**
     * 构建组织对象
     */
    public static function org(
        ?string $company = null,
        ?string $department = null,
        ?string $title = null
    ): array {
        return array_filter([
            'company' => $company,
            'department' => $department,
            'title' => $title,
        ], fn ($value) => $value !== null);
    }

    /**
     * 构建网址对象
     *
     * @param string $type 标准值: HOME, WORK
     */
    public static function url(string $url, string $type = 'WORK'): array
    {
        return [
            'url' => $url,
            'type' => $type,
        ];
    }
}
