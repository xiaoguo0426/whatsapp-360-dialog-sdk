<?php

namespace Dialog360\Response;

/**
 * GET /upload:{session-id} 响应（查询分块续传会话状态）。
 *
 * 成功时返回 {"id": "<session_id>", "file_offset": 1024}。
 */
class UploadSessionStatusResponse
{
    private array $data;

    private string $sessionId;

    private int $fileOffset;

    private bool $success;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->sessionId = (string) ($data['id'] ?? '');
        $this->fileOffset = (int) ($data['file_offset'] ?? 0);
        $this->success = $this->sessionId !== '';
    }

    /**
     * 获取上传会话ID
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * 获取服务端已接收的字节偏移（续传时从这里继续）
     */
    public function getFileOffset(): int
    {
        return $this->fileOffset;
    }

    /**
     * 是否拿到了有效的会话响应
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
            'session_id' => $this->sessionId,
            'file_offset' => $this->fileOffset,
        ];
    }
}
