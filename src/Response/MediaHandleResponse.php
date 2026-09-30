<?php

namespace Dialog360\Response;

/**
 * POST /upload:{session-id} 与 POST /{session-id} 响应（分块续传）。
 *
 * 上传完最后一个分块后返回 {"h": "<handle>", "success": true}。
 * handle 目前仅用于头像等资料的更新（如 profile picture）。
 */
class MediaHandleResponse
{
    private array $data;

    private string $handle;

    private bool $success;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->handle = (string) ($data['h'] ?? '');
        $this->success = (bool) ($data['success'] ?? $this->handle !== '');
    }

    /**
     * 获取文件句柄 handle（上传全部字节后返回）
     */
    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * 本次分块（或整体上传）是否成功
     */
    public function isSuccess(): bool
    {
        return $this->success;
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
            'handle' => $this->handle,
            'success' => $this->success,
        ];
    }
}
