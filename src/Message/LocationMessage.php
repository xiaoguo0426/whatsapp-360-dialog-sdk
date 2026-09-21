<?php

namespace Dialog360\Message;

/**
 * 位置消息（Location）
 *
 * 向 WhatsApp 用户发送位置标记，点击可在地图中查看。
 *
 * JSON 结构:
 * {
 *   "messaging_product": "whatsapp",
 *   "to": "<phone>",
 *   "type": "location",
 *   "location": {
 *     "longitude": <LONG_NUMBER>,   // 必填，经度
 *     "latitude": <LAT_NUMBER>,     // 必填，纬度
 *     "name": "<LOCATION_NAME>",    // 可选，位置名称
 *     "address": "<ADDRESS>"        // 可选，位置地址（仅在提供 name 时有效）
 *   }
 * }
 */
class LocationMessage implements MessageInterface
{
    private string $to;
    private float $longitude;
    private float $latitude;
    private ?string $name;
    private ?string $address;

    /**
     * @param string $to 接收者 WhatsApp 电话号码
     * @param float $longitude 经度，范围 -180 到 180
     * @param float $latitude 纬度，范围 -90 到 90
     * @param string|null $name 位置名称
     * @param string|null $address 位置地址，仅在提供 name 时有效
     */
    public function __construct(
        string $to,
        float $longitude,
        float $latitude,
        ?string $name = null,
        ?string $address = null
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw new \InvalidArgumentException('纬度必须在 -90 到 90 之间');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException('经度必须在 -180 到 180 之间');
        }

        // 官方规定：address 仅在提供 name 时才会显示
        if ($address !== null && $name === null) {
            throw new \InvalidArgumentException('address 仅在提供 name 时才能使用');
        }

        $this->to = $to;
        $this->longitude = $longitude;
        $this->latitude = $latitude;
        $this->name = $name;
        $this->address = $address;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return 'location';
    }

    public function toArray(): array
    {
        $location = [
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
        ];

        if ($this->name !== null) {
            $location['name'] = $this->name;
        }

        if ($this->address !== null) {
            $location['address'] = $this->address;
        }

        return [
            'type' => 'location',
            'location' => $location
        ];
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * 快捷创建仅含经纬度的位置消息
     */
    public static function coordinates(string $to, float $longitude, float $latitude): self
    {
        return new self($to, $longitude, $latitude);
    }

    /**
     * 快捷创建包含名称和地址的位置消息
     */
    public static function named(
        string $to,
        float $longitude,
        float $latitude,
        string $name,
        ?string $address = null
    ): self {
        return new self($to, $longitude, $latitude, $name, $address);
    }
}
