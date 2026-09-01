<?php

namespace App\Services\RoboNeo;

use App\Exceptions\RoboNeoProtocolException;
use OSS\OssClient;
use Symfony\Component\Mime\MimeTypes;

class RoboNeoUploader
{
    public function upload(
        RoboNeoApiClient $api,
        string $roomId,
        string $filePath,
        string $originalName,
        string $type,
    ): array {
        $suffix = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $policy = $api->uploadPolicy($suffix);
        $strategy = $api->strategyPolicy($policy, $suffix);
        $oss = $this->normalizeStrategy($strategy);
        $contentType = MimeTypes::getDefault()->guessMimeType($filePath) ?? 'application/octet-stream';

        $client = new OssClient(
            $oss['access_key'],
            $oss['secret_key'],
            $this->normalizeEndpoint($oss['upload_host']),
            false,
            $oss['session_token'],
        );
        $options = [OssClient::OSS_CONTENT_TYPE => $contentType];

        if (filesize($filePath) > 5 * 1024 * 1024) {
            $options[OssClient::OSS_PART_SIZE] = 4 * 1024 * 1024;
            $client->multiuploadFile($oss['bucket'], $oss['key'], $filePath, $options);
        } else {
            $client->uploadFile($oss['bucket'], $oss['key'], $filePath, $options);
        }

        $url = $oss['access_url'] ?: $oss['data_url'];

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

    public function normalizeStrategy(array $response): array
    {
        $response = $response['data'] ?? $response;
        $entry = array_is_list($response) ? ($response[0] ?? []) : $response;
        $provider = $entry['order'][0] ?? 'oss';
        $config = $entry[$provider] ?? [];
        $credentials = $config['credentials'] ?? [];
        $normalized = [
            'access_key' => $credentials['access_key'] ?? '',
            'secret_key' => $credentials['secret_key'] ?? '',
            'session_token' => $credentials['session_token'] ?? '',
            'bucket' => $config['bucket'] ?? '',
            'upload_host' => $config['url'] ?? '',
            'key' => $config['key'] ?? '',
            'data_url' => $config['data_url'] ?? '',
            'access_url' => $config['access_url'] ?? '',
        ];

        foreach (['access_key', 'secret_key', 'session_token', 'bucket', 'upload_host', 'key'] as $required) {
            if ($normalized[$required] === '') {
                throw new RoboNeoProtocolException(
                    "Upload strategy response is missing {$required}.",
                    'invalid_upload_strategy',
                );
            }
        }

        return $normalized;
    }

    private function normalizeEndpoint(string $endpoint): string
    {
        return rtrim((string) preg_replace('#^https?://#i', '', $endpoint), '/');
    }
}
