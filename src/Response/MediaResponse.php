<?php

namespace Dialog360\Response;

class MediaResponse
{
    private array $data;
    private string $mediaId;
    private string $url;
    private string $mimeType;
    private string $sha256;
    private int $fileSize;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->mediaId = $data['id'] ?? '';
        $this->url = $data['url'] ?? '';
        $this->mimeType = $data['mime_type'] ?? '';
        $this->sha256 = $data['sha256'] ?? '';
        $this->fileSize = $data['file_size'] ?? 0;
    }

    /**
     * 获取媒体ID
     */
    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    /**
     * 获取下载URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * 获取MIME类型
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * 获取SHA256哈希
     */
    public function getSha256(): string
    {
        return $this->sha256;
    }

    /**
     * 获取文件大小
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * 获取文件扩展名
     */
    public function getFileExtension(): string
    {
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            'video/mp4' => 'mp4',
            'video/avi' => 'avi',
            'video/mov' => 'mov',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt'
        ];

        return $mimeToExt[$this->mimeType] ?? 'bin';
    }

    /**
     * 检查是否为图片
     */
    public function isImage(): bool
    {
        return strpos($this->mimeType, 'image/') === 0;
    }

    /**
     * 检查是否为音频
     */
    public function isAudio(): bool
    {
        return strpos($this->mimeType, 'audio/') === 0;
    }

    /**
     * 检查是否为视频
     */
    public function isVideo(): bool
    {
        return strpos($this->mimeType, 'video/') === 0;
    }

    /**
     * 检查是否为文档
     */
    public function isDocument(): bool
    {
        return strpos($this->mimeType, 'application/') === 0 || strpos($this->mimeType, 'text/') === 0;
    }

    /**
     * 获取原始响应数据
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            'media_id' => $this->mediaId,
            'url' => $this->url,
            'mime_type' => $this->mimeType,
            'sha256' => $this->sha256,
            'file_size' => $this->fileSize,
            'file_extension' => $this->getFileExtension(),
            'data' => $this->data
        ];
    }
} 