<?php

namespace Dialog360\Message;

/**
 * 文档消息（Document）
 *
 * 消息显示文档图标，WhatsApp 用户可点击下载。
 *
 * 官方支持的文档类型（最大 100MB）:
 * - Text        .txt   (text/plain)
 * - Excel       .xls   (application/vnd.ms-excel)
 * - Excel       .xlsx  (application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)
 * - Word        .doc   (application/msword)
 * - Word        .docx  (application/vnd.openxmlformats-officedocument.wordprocessingml.document)
 * - PowerPoint  .ppt   (application/vnd.ms-powerpoint)
 * - PowerPoint  .pptx  (application/vnd.openxmlformats-officedocument.presentationml.presentation)
 * - PDF         .pdf   (application/pdf)
 *
 * JSON 结构:
 * {
 *   "messaging_product": "whatsapp",
 *   "to": "<phone>",
 *   "type": "document",
 *   "document": {
 *     "id": "<MEDIA_ID>",          // 使用已上传媒体时必填
 *     "link": "<MEDIA_URL>",       // 使用托管媒体时必填（不推荐）
 *     "caption": "<CAPTION>",      // 可选，最多 1024 字符
 *     "filename": "<FILENAME>"     // 可选，带扩展名
 *   }
 * }
 */
class DocumentMessage implements MessageInterface
{
    /**
     * caption 最大长度（字符）
     */
    private const MAX_CAPTION_LENGTH = 1024;

    /**
     * 官方支持的文档扩展名
     */
    private const SUPPORTED_EXTENSIONS = [
        'txt', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx', 'pdf'
    ];

    private string $to;
    private ?string $mediaId;
    private ?string $link;
    private ?string $caption;
    private ?string $filename;

    /**
     * mediaId 与 link 必须且只能提供其一（互斥）
     *
     * @param string $to 接收者 WhatsApp 电话号码
     * @param string|null $mediaId 已上传媒体的 ID（官方推荐）
     * @param string|null $link 托管在公开服务器上的媒体 URL（不推荐，性能较差）
     * @param string|null $caption 文档说明文字，最多 1024 字符
     * @param string|null $filename 带扩展名的文件名，客户端根据扩展名显示对应的文件类型图标
     */
    public function __construct(
        string $to,
        ?string $mediaId = null,
        ?string $link = null,
        ?string $caption = null,
        ?string $filename = null
    ) {
        $hasMediaId = $mediaId !== null && $mediaId !== '';
        $hasLink = $link !== null && $link !== '';

        if ($hasMediaId === $hasLink) {
            throw new \InvalidArgumentException('必须且只能提供 mediaId 或 link 之一');
        }

        if ($caption !== null && mb_strlen($caption) > self::MAX_CAPTION_LENGTH) {
            throw new \InvalidArgumentException('caption 不能超过 ' . self::MAX_CAPTION_LENGTH . ' 个字符');
        }

        if ($filename !== null && !$this->isSupportedFilename($filename)) {
            throw new \InvalidArgumentException(
                '不支持的文档扩展名: ' . $filename . '，官方支持: ' . implode(', ', self::SUPPORTED_EXTENSIONS)
            );
        }

        $this->to = $to;
        $this->mediaId = $hasMediaId ? $mediaId : null;
        $this->link = $hasLink ? $link : null;
        $this->caption = $caption;
        $this->filename = $filename;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getType(): string
    {
        return 'document';
    }

    public function toArray(): array
    {
        $document = [];

        // 二选一：已上传媒体用 id，托管媒体用 link
        if ($this->mediaId !== null) {
            $document['id'] = $this->mediaId;
        } else {
            $document['link'] = $this->link;
        }

        if ($this->caption !== null) {
            $document['caption'] = $this->caption;
        }

        if ($this->filename !== null) {
            $document['filename'] = $this->filename;
        }

        return [
            'type' => 'document',
            'document' => $document
        ];
    }

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * 通过已上传媒体的 ID 创建文档消息（官方推荐方式）
     *
     * @param string $to 接收者 WhatsApp 电话号码
     * @param string $mediaId 已上传媒体的 ID（可通过 Dialog360Client::uploadMedia() 获取）
     * @param string|null $caption 文档说明文字，最多 1024 字符
     * @param string|null $filename 带扩展名的文件名
     */
    public static function fromMediaId(
        string $to,
        string $mediaId,
        ?string $caption = null,
        ?string $filename = null
    ): self {
        return new self($to, $mediaId, null, $caption, $filename);
    }

    /**
     * 通过托管在公开服务器上的文档 URL 创建消息
     *
     * @param string $to 接收者 WhatsApp 电话号码
     * @param string $link 文档的公开 URL
     * @param string|null $caption 文档说明文字，最多 1024 字符
     * @param string|null $filename 带扩展名的文件名
     */
    public static function fromUrl(
        string $to,
        string $link,
        ?string $caption = null,
        ?string $filename = null
    ): self {
        return new self($to, null, $link, $caption, $filename);
    }

    /**
     * 检查文件名扩展名是否在官方支持列表中
     */
    private function isSupportedFilename(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, self::SUPPORTED_EXTENSIONS, true);
    }
}
