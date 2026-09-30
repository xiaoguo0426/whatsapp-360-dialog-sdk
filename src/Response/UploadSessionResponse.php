<?php

namespace Dialog360\Response;

/**
 * POST /uploads 响应（创建分块续传上传会话）。
 *
 * 成功时返回 {"id": "<session_id>"}。
 */
class UploadSessionResponse
{
    private array $data;

    private string $sessionId;

    private int $fileOffset;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->sessionId = (string) ($data['id'] ?? '');
        $this->fileOffset = (int) ($data['file_offset'] ?? 0);
    }

    /**
     * 获取上传会话ID（用于后续 upload:{session-id} 请求）
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * 获取当前已接收的字节偏移
     */
    public function getFileOffset(): int
    {
        return $this->fileOffset;
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
