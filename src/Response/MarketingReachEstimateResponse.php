<?php

namespace Dialog360\Response;

/**
 * GET /marketing/reachestimate 响应（营销消息触达估算）。
 *
 * 响应体形如：
 * {
 *   "estimates": [
 *     {
 *       "bid_amount": ...,
 *       "deliveries_lower_bound": ...,
 *       "deliveries_upper_bound": ...,
 *       "cost_lower_bound": ...,
 *       "cost_upper_bound": ...,
 *       "users": 1000
 *     }
 *   ],
 *   "waba_currency": "..."
 * }
 */
class MarketingReachEstimateResponse
{
    private array $data;

    private array $estimates;

    private string $currency;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->estimates = is_array($data['estimates'] ?? null) ? $data['estimates'] : [];
        $this->currency = (string) ($data['waba_currency'] ?? '');
    }

    /**
     * 获取触达估算列表，每项形如
     * ['bid_amount' => ..., 'deliveries_lower_bound' => ..., 'deliveries_upper_bound' => ...,
     *  'cost_lower_bound' => ..., 'cost_upper_bound' => ..., 'users' => ...]
     *
     * @return array
     */
    public function getEstimates(): array
    {
        return $this->estimates;
    }

    /**
     * 获取 WABA 货币单位（如 USD）
     */
    public function getCurrency(): string
    {
        return $this->currency;
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
            'estimates' => $this->estimates,
            'waba_currency' => $this->currency,
        ];
    }
}
