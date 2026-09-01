<?php

namespace Tests\Unit;

use App\Exceptions\RoboNeoProtocolException;
use App\Services\RoboNeo\RoboNeoUploader;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoboNeoUploaderTest extends TestCase
{
    #[Test]
    public function it_normalizes_temporary_oss_credentials(): void
    {
        $result = app(RoboNeoUploader::class)->normalizeStrategy([[
            'order' => ['oss'],
            'oss' => [
                'credentials' => [
                    'access_key' => 'access',
                    'secret_key' => 'secret',
                    'session_token' => 'session',
                ],
                'bucket' => 'bucket',
                'url' => 'https://oss-cn-beijing.aliyuncs.com',
                'key' => 'path/video.mp4',
                'data_url' => 'https://cdn.test/path/video.mp4',
            ],
        ]]);

        $this->assertSame('access', $result['access_key']);
        $this->assertSame('path/video.mp4', $result['key']);
        $this->assertSame('https://cdn.test/path/video.mp4', $result['data_url']);
    }

    #[Test]
    public function it_rejects_incomplete_upload_credentials(): void
    {
        $this->expectException(RoboNeoProtocolException::class);

        app(RoboNeoUploader::class)->normalizeStrategy([['order' => ['oss'], 'oss' => []]]);
    }
}
