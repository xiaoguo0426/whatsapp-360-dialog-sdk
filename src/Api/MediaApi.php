<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\MediaHandleResponse;
use Dialog360\Response\MediaResponse;
use Dialog360\Response\UploadSessionResponse;
use Dialog360\Response\UploadSessionStatusResponse;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/**
 * 媒体 API 域：POST /media、GET|DELETE /{media-id}
 *
 * 另含分块续传上传（Resumable Upload，Meta Upload API 的 360dialog 代理）：
 * - POST /uploads：创建上传会话
 * - GET /upload:{session-id}：查询会话已接收偏移（断点续传）
 * - POST /upload:{session-id}：按 file_offset 上传分块
 * - POST /{session-id}：一步式上传（upload: 前缀会话ID）
 *
 * 续传上传完最后一个分块后返回 handle（h），目前主要用于更新头像等资料。
 *
 * @see https://docs.360dialog.com/docs/messaging/media/upload-retrieve-or-delete-media
 * @see https://docs.360dialog.com/docs/messaging-api/api-reference/media-uploads
 */
readonly class MediaApi
{
    /** 分块续传默认分块大小（4MB，须为 8KB 的整数倍） */
    public const DEFAULT_CHUNK_SIZE = 4 * 1024 * 1024;

    public function __construct(private ApiConnector $connector)
    {
    }

    /**
     * 上传媒体文件（Cloud API: POST /media）
     *
     * @param string $filePath 本地文件路径
     * @param string $mimeType MIME类型
     * @return string 返回媒体ID
     * @throws Dialog360Exception
     * @throws Dialog360ClientError
     */
    public function upload(string $filePath, string $mimeType): string
    {
        if (!file_exists($filePath)) {
            throw new Dialog360Exception('文件不存在: ' . $filePath);
        }

        // 验证文件大小和类型
        $this->validateMediaFile($filePath, $mimeType);

        $data = $this->connector->request('POST', '/media', [
            'multipart' => [
                [
                    'name' => 'messaging_product',
                    'contents' => 'whatsapp'
                ],
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => basename($filePath),
                    'type' => $mimeType
                ]
            ]
        ], '上传媒体文件');

        if (!isset($data['id']) || !is_string($data['id'])) {
            throw new Dialog360Exception('上传响应中缺少媒体ID');
        }

        return $data['id'];
    }

    /**
     * 获取媒体文件信息（Cloud API: GET /{media-id}）
     *
     * @throws Dialog360Exception
     * @throws Dialog360ClientError
     */
    public function getInfo(string $mediaId): MediaResponse
    {
        $data = $this->connector->get("/{$mediaId}", [], '获取媒体信息');
        return new MediaResponse($data);
    }

    /**
     * 下载媒体文件（Cloud API 两步：先取URL，再通过 v2 根域下载）
     *
     * @param string|null $savePath 提供时保存到本地
     * @return string 文件内容
     * @throws Dialog360Exception
     * @throws Dialog360ClientError
     */
    public function download(string $mediaId, ?string $savePath = null): string
    {
        try {
            $mediaInfo = $this->getInfo($mediaId);

            // 通过相对路径请求（自动带上 D360-API-KEY 头）
            $response = $this->connector->getRaw($this->extractDownloadPath($mediaInfo->getUrl()));
            $content = $response->getBody()->getContents();

            if ($savePath) {
                file_put_contents($savePath, $content);
            }

            return $content;
        } catch (RequestException $e) {
            throw new Dialog360Exception('下载媒体文件失败: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new Dialog360Exception('网络请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 通过下载URL直接下载媒体文件并保存
     *
     * @throws Dialog360Exception
     * @throws GuzzleException
     */
    public function downloadByUrl(string $downloadUrl, string $savePath): bool
    {
        // 通过相对路径请求（自动带上 D360-API-KEY 头）
        $response = $this->connector->getRaw($this->extractDownloadPath($downloadUrl));
        $content = $response->getBody()->getContents();

        return (bool)file_put_contents($savePath, $content);
    }

    /**
     * 删除媒体文件（Cloud API: DELETE /{media-id}）
     *
     * @throws Dialog360Exception
     * @throws Dialog360ClientError
     */
    public function delete(string $mediaId): bool
    {
        $this->connector->request('DELETE', "/{$mediaId}", [], '删除媒体文件');
        return true;
    }

    /**
     * 创建分块续传上传会话（Cloud API: POST /uploads）
     *
     * @param string $fileName 文件名（含扩展名）
     * @param int $fileLength 文件总字节数
     * @param string $mimeType MIME类型，如 image/jpeg
     * @return UploadSessionResponse 含会话ID与初始 file_offset
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function createUploadSession(string $fileName, int $fileLength, string $mimeType = ''): UploadSessionResponse
    {
        if ($fileLength <= 0) {
            throw new Dialog360Exception('文件长度必须大于 0');
        }

        $query = array_filter([
            'file_name' => $fileName,
            'file_length' => (string) $fileLength,
            'file_type' => $mimeType !== '' ? $mimeType : null,
        ], static fn ($value) => $value !== null);

        $data = $this->connector->request('POST', '/uploads?' . http_build_query($query), [], '创建上传会话');

        if (!isset($data['id']) || !is_string($data['id']) || $data['id'] === '') {
            throw new Dialog360Exception('创建上传会话响应中缺少会话ID');
        }

        return new UploadSessionResponse($data);
    }

    /**
     * 查询上传会话状态（Cloud API: GET /upload:{session-id}）
     *
     * 返回的 file_offset 即服务端已接收的字节数，断点续传时从该偏移继续上传。
     *
     * @param string $sessionId createUploadSession() 返回的会话ID
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function getUploadSessionStatus(string $sessionId): UploadSessionStatusResponse
    {
        $data = $this->connector->get($this->sessionUri($sessionId), [], '查询上传会话状态');
        return new UploadSessionStatusResponse($data);
    }

    /**
     * 上传一个分块（Cloud API: POST /upload:{session-id}）
     *
     * @param string $sessionId 上传会话ID
     * @param string $chunk 分块内容，整体上传时须为完整文件内容
     * @param int $offset 本分块起点的字节偏移（服务端已接收位置）
     * @return MediaHandleResponse 上传完最后一个分块后 getHandle() 返回文件句柄
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function uploadChunk(string $sessionId, string $chunk, int $offset = 0): MediaHandleResponse
    {
        if ($offset < 0) {
            throw new Dialog360Exception('分块偏移不能为负数');
        }

        $data = $this->connector->request('POST', $this->sessionUri($sessionId), [
            'headers' => [
                'file_offset' => (string) $offset,
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $chunk,
        ], '上传媒体分块');

        return new MediaHandleResponse($data);
    }

    /**
     * 一步式上传媒体（Cloud API: POST /{session-id}，会话ID带 upload: 前缀）
     *
     * 请求体直接携带完整文件内容，适合中小文件；返回的 handle 可用于更新头像等资料。
     *
     * @param string $sessionId 上传会话ID（如 upload:1234567890）
     * @param string $contents 完整文件内容
     * @param int $offset 本请求数据起点的字节偏移，默认 0
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function uploadWithSession(string $sessionId, string $contents, int $offset = 0): MediaHandleResponse
    {
        if ($offset < 0) {
            throw new Dialog360Exception('分块偏移不能为负数');
        }

        $data = $this->connector->request('POST', $this->connector->absoluteUri("/{$sessionId}"), [
            'headers' => [
                'file_offset' => (string) $offset,
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $contents,
        ], '上传媒体文件（会话模式）');

        return new MediaHandleResponse($data);
    }

    /**
     * 分块续传上传本地文件的便捷方法
     *
     * 自动完成 创建会话 → 逐块上传 → 返回 handle。
     * 中断后可用 getUploadSessionStatus($sessionId) 取回偏移，再自行从该偏移续传。
     *
     * @param string $filePath 本地文件路径
     * @param string $mimeType MIME类型，如 image/jpeg
     * @param int $chunkSize 分块字节数，须为 8KB（8192）的整数倍
     * @param string|null $sessionId 已有会话ID（断点续传），为 null 时新建会话
     * @return MediaHandleResponse 最后一个分块响应，含文件句柄 handle
     * @throws Dialog360ClientError
     * @throws Dialog360Exception
     */
    public function uploadResumable(
        string $filePath,
        string $mimeType,
        int    $chunkSize = self::DEFAULT_CHUNK_SIZE,
        ?string $sessionId = null
    ): MediaHandleResponse
    {
        if (!file_exists($filePath)) {
            throw new Dialog360Exception('文件不存在: ' . $filePath);
        }
        if ($chunkSize <= 0 || $chunkSize % 8192 !== 0) {
            throw new Dialog360Exception('分块大小必须为 8KB（8192 字节）的整数倍');
        }

        $this->validateMediaFile($filePath, $mimeType);

        $fileSize = (int) filesize($filePath);

        $offset = 0;
        if ($sessionId === null) {
            $session = $this->createUploadSession(basename($filePath), $fileSize, $mimeType);
            $sessionId = $session->getSessionId();
            $offset = $session->getFileOffset();
        } else {
            $offset = $this->getUploadSessionStatus($sessionId)->getFileOffset();
        }

        if ($offset > $fileSize) {
            throw new Dialog360Exception("服务端偏移 {$offset} 超过本地文件大小 {$fileSize}，文件与会话不匹配");
        }

        $handle = null;
        $stream = fopen($filePath, 'rb');
        if ($stream === false) {
            throw new Dialog360Exception('无法读取文件: ' . $filePath);
        }

        try {
            fseek($stream, $offset);
            while (!feof($stream)) {
                $chunk = fread($stream, $chunkSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }

                $handle = $this->uploadChunk($sessionId, $chunk, $offset);
                $offset += strlen($chunk);
            }
        } finally {
            fclose($stream);
        }

        if ($handle === null) {
            // 偏移已达文件末尾（会话可能已完成），返回当前状态
            return new MediaHandleResponse(['success' => $this->getUploadSessionStatus($sessionId)->isSuccess()]);
        }

        return $handle;
    }

    /**
     * 会话端点 URI：GET|POST /upload:{session-id}
     *
     * 必须拼成绝对 URI：路径首段的冒号会被 Guzzle 当作 host:port 解析而报错。
     */
    private function sessionUri(string $sessionId): string
    {
        return $this->connector->absoluteUri('/upload:' . $sessionId);
    }

    /**
     * Cloud API 指南：将 lookaside 主机替换为 waba-v2 根域后面的路径
     *
     * @throws Dialog360Exception
     */
    private function extractDownloadPath(string $downloadUrl): string
    {
        $parsed = parse_url($downloadUrl);
        if ($parsed === false || !isset($parsed['path'])) {
            throw new Dialog360Exception('媒体下载URL无效');
        }

        $path = $parsed['path'];
        if (isset($parsed['query'])) {
            $path .= '?' . $parsed['query'];
        }

        return $path;
    }

    /**
     * 验证媒体文件
     *
     * @throws Dialog360Exception
     */
    private function validateMediaFile(string $filePath, string $mimeType): void
    {
        $fileSize = filesize($filePath);

        // 根据文档定义的文件大小限制
        $sizeLimits = [
            'audio' => 16 * 1024 * 1024, // 16MB
            'image' => 5 * 1024 * 1024,  // 5MB
            'video' => 16 * 1024 * 1024, // 16MB
            'document' => 100 * 1024 * 1024, // 100MB
            'sticker' => 500 * 1024, // 500KB (动画贴纸)
        ];

        // 检查文件大小
        if ($fileSize > 100 * 1024 * 1024) { // 最大100MB
            throw new Dialog360Exception('文件大小超过100MB限制');
        }

        // 根据MIME类型检查特定限制
        if (str_starts_with($mimeType, 'audio/') && $fileSize > $sizeLimits['audio']) {
            throw new Dialog360Exception('音频文件大小超过16MB限制');
        }

        if (str_starts_with($mimeType, 'image/')) {
            if ($mimeType === 'image/webp' && $fileSize > $sizeLimits['sticker']) {
                throw new Dialog360Exception('贴纸文件大小超过500KB限制');
            }
            if ($mimeType !== 'image/webp' && $fileSize > $sizeLimits['image']) {
                throw new Dialog360Exception('图片文件大小超过5MB限制');
            }
        }

        if (str_starts_with($mimeType, 'video/') && $fileSize > $sizeLimits['video']) {
            throw new Dialog360Exception('视频文件大小超过16MB限制');
        }

        // 验证支持的MIME类型（支持带codecs参数的格式）
        $supportedTypes = [
            // 音频
            'audio/aac', 'audio/amr', 'audio/mpeg', 'audio/mp4', 'audio/ogg',
            'audio/ogg; codecs=opus', 'audio/ogg; codecs=vorbis',
            // 图片
            'image/jpeg', 'image/png', 'image/webp',
            // 视频
            'video/mp4', 'video/3gp',
            // 文档
            'text/plain', 'application/pdf', 'application/vnd.ms-powerpoint',
            'application/msword', 'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        // 检查基础MIME类型（忽略codecs参数）
        $baseMimeType = explode(';', $mimeType)[0];
        $isSupported = false;

        foreach ($supportedTypes as $supportedType) {
            $baseSupportedType = explode(';', $supportedType)[0];
            if ($baseMimeType === $baseSupportedType) {
                $isSupported = true;
                break;
            }
        }

        if (!$isSupported) {
            throw new Dialog360Exception('不支持的媒体类型: ' . $mimeType);
        }
    }
}
