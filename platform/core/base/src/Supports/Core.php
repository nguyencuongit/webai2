<?php

namespace Botble\Base\Supports;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Core
{
    protected static array $coreFileData = [];
    private static ?self $instance = null;
    private Filesystem $files;
    private CacheRepository $cache;
    private string $coreDataFilePath;
    private string $licenseFilePath;

    public function __construct(CacheRepository $cache, Filesystem $files)
    {
        $this->cache = $cache;
        $this->files = $files;
        $this->coreDataFilePath = base_path('platform/core/core.json'); // Đường dẫn file chứa thông tin hệ thống
        $this->licenseFilePath = storage_path('.license'); // Đường dẫn file license
    }

    public static function make(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(app(CacheRepository::class), app(Filesystem::class));
        }
        return self::$instance;
    }

    public function verifyLicenseDirectly(): bool
    {
        return true; // Bypass kiểm tra license
    }

    public function activateLicense(string $license, string $client): bool
    {
        return true; // Bỏ qua kích hoạt license
    }

    public function verifyLicense(bool $timeBasedCheck = false): bool
    {
        return true; // Luôn hợp lệ
    }

    public function revokeLicense(string $license, string $client): bool
    {
        return true; // Không làm gì cả
    }

    public function deactivateLicense(): bool
    {
        return true; // Không cần hủy kích hoạt
    }

    public function getLicenseFile(): ?string
    {
        return null; // Bỏ kiểm tra file license
    }

    public function isSkippedLicenseReminder(): bool
    {
        return true; // Bỏ qua kiểm tra license reminder
    }

    public function getLicenseUrl(string $path = null): string
    {
        return 'https://your-custom-license-url.com'; // Fake URL để tránh lỗi
    }

    public function getLicenseFilePath(): string
    {
        return $this->licenseFilePath; // Trả về đường dẫn file license
    }

    public function getCoreFileData(): array
    {
        if (self::$coreFileData) {
            return self::$coreFileData;
        }

        return $this->getCoreFileDataFromDisk();
    }

    private function getCoreFileDataFromDisk(): array
    {
        try {
            if (!$this->files->exists($this->coreDataFilePath)) {
                return [
                    'productId' => 'CA20EC4D',
                    'source' => 'envato',
                    'apiUrl' => 'https://license.botble.com',
                    'apiKey' => 'CAF4B17F6D3F656125F9',
                    'version' => '1.3.1',
                    'marketplaceUrl' => 'https://marketplace.botble.com/api/v1',
                    'marketplaceToken' => 'fake-marketplace-token',
                    'minimumPhpVersion' => '8.2.0',
                ]; // Trả về dữ liệu mặc định nếu file không tồn tại
            }

            $data = json_decode($this->files->get($this->coreDataFilePath), true) ?: [];

            self::$coreFileData = $data;
            $this->cache->forever('core_file_data', $data);

            return $data;
        } catch (FileNotFoundException) {
            return [
                'productId' => 'CA20EC4D',
                'source' => 'envato',
                'apiUrl' => 'https://license.botble.com',
                'apiKey' => 'CAF4B17F6D3F656125F9',
                'version' => '1.3.1',
                'marketplaceUrl' => 'https://marketplace.botble.com/api/v1',
                'marketplaceToken' => 'fake-marketplace-token',
                'minimumPhpVersion' => '8.2.0',
            ]; // Trả về dữ liệu mặc định khi xảy ra lỗi
        }
    }
}
