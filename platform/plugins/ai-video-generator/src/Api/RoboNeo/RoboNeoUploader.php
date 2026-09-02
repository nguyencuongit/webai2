<?php

namespace Botble\AiVideoGenerator\Api\RoboNeo;

use OSS\OssClient;
use Symfony\Component\Mime\MimeTypes;

class RoboNeoUploader
{
    public function upload(RoboNeoApiClient $api, string $roomId, string $filePath, string $type): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RoboNeoProtocolException("RoboNeo upload file is not readable: {$filePath}", 'invalid_upload_file');
        }

        if (! in_array($type, ['image', 'video'], true)) {
            throw new RoboNeoProtocolException('RoboNeo asset type must be image or video.', 'invalid_asset_type');
        }

        $originalName = basename($filePath);
        $suffix = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($suffix === '') {
            throw new RoboNeoProtocolException('RoboNeo upload file has no extension.', 'missing_file_extension');
        }

        $strategy = $this->normalizeStrategy($api->strategyPolicy($api->uploadPolicy($suffix), $suffix));
        $client = new OssClient(
            $strategy['access_key'],
            $strategy['secret_key'],
            $this->normalizeEndpoint($strategy['upload_host']),
            false,
            $strategy['session_token'],
        );
        $options = [OssClient::OSS_CONTENT_TYPE => MimeTypes::getDefault()->guessMimeType($filePath) ?? 'application/octet-stream'];

        if (filesize($filePath) > 5 * 1024 * 1024) {
            $options[OssClient::OSS_PART_SIZE] = 4 * 1024 * 1024;
            $client->multiuploadFile($strategy['bucket'], $strategy['key'], $filePath, $options);
        } else {
            $client->uploadFile($strategy['bucket'], $strategy['key'], $filePath, $options);
        }

        $url = $strategy['access_url'] ?: $strategy['data_url'];

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RoboNeoProtocolException('Upload strategy returned no usable asset URL.', 'missing_asset_url');
        }

        $api->mediaCheck($type, $url);
        $dimensions = $type === 'image' ? @getimagesize($filePath) : false;
        $asset = [
            'url' => $url,
            'watermark_url' => $url,
            'thumbnail_url' => $type === 'video' ? $url.'&vframe/jpg/offset/0' : null,
            'ext' => $suffix === 'jpg' ? 'jpeg' : $suffix,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
        ];
        $asset['asset_id'] = $api->createAsset($roomId, $type, $asset);

        return $asset;
    }

    private function normalizeStrategy(array $response): array
    {
        $response = $response['data'] ?? $response;
        $entry = array_is_list($response) ? ($response[0] ?? []) : $response;
        $provider = $entry['order'][0] ?? 'oss';
        $config = $entry[$provider] ?? [];
        $credentials = $config['credentials'] ?? [];
        $strategy = [
            'access_key' => $credentials['access_key'] ?? '', 'secret_key' => $credentials['secret_key'] ?? '',
            'session_token' => $credentials['session_token'] ?? '', 'bucket' => $config['bucket'] ?? '',
            'upload_host' => $config['url'] ?? '', 'key' => $config['key'] ?? '',
            'data_url' => $config['data_url'] ?? '', 'access_url' => $config['access_url'] ?? '',
        ];

        foreach (['access_key', 'secret_key', 'session_token', 'bucket', 'upload_host', 'key'] as $key) {
            if ($strategy[$key] === '') {
                throw new RoboNeoProtocolException("Upload strategy response is missing {$key}.", 'invalid_upload_strategy');
            }
        }

        return $strategy;
    }

    private function normalizeEndpoint(string $endpoint): string
    {
        return rtrim((string) preg_replace('#^https?://#i', '', $endpoint), '/');
    }
}
