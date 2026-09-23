<?php

namespace Dialog360\Api;

use Dialog360\Exception\Dialog360ClientError;
use Dialog360\Exception\Dialog360Exception;
use Dialog360\Http\ApiConnector;
use Dialog360\Response\MediaResponse;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/**
 * 媒体 API 域：POST /media、GET|DELETE /{media-id}
 */
readonly class MediaApi
{
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
