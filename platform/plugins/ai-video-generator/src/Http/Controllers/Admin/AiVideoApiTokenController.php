<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Exports\AiVideoApiTokenTemplateExport;
use Botble\AiVideoGenerator\Forms\AiVideoApiTokenForm;
use Botble\AiVideoGenerator\Http\Requests\CreateAiVideoApiTokenRequest;
use Botble\AiVideoGenerator\Http\Requests\UpdateAiVideoApiTokenRequest;
use Botble\AiVideoGenerator\Http\Requests\UpdateRoboNeoProxyPoolRequest;
use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Models\KiotProxyKey;
use Botble\AiVideoGenerator\Models\KiotProxyTaskLease;
use Botble\AiVideoGenerator\Services\RoboNeo\KiotProxy\KiotProxyManager;
use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoProxyPoolSettings;
use Botble\AiVideoGenerator\Tables\AiVideoApiTokenTable;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AiVideoApiTokenController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()->add('API token', route('ai-video-generator.api-tokens.index'));
    }

    public function index(AiVideoApiTokenTable $table, RoboNeoProxyPoolSettings $proxyPoolSettings)
    {
        $this->pageTitle('API token');

        if ($table->request()->ajax() && $table->request()->wantsJson()) {
            return $table->renderTable();
        }

        $proxyPool = $proxyPoolSettings->asText();

        $kiotProxyKeys = collect();

        if (Schema::hasTable('ai_video_kiot_proxy_keys')) {
            $query = KiotProxyKey::query()->oldest('id');

            if (Schema::hasTable('ai_video_kiot_proxy_leases')) {
                $query->withCount([
                    'taskLeases as active_task_leases_count' => fn ($query) => $query->where('lease_until', '>', now()),
                ]);
            }

            $kiotProxyKeys = $query->get();
        }

        return view('plugins/ai-video-generator::api-tokens.index', [
            'table' => $table,
            'proxyPool' => $proxyPool,
            'proxyCount' => count($proxyPoolSettings->all()),
            'kiotProxyKeys' => $kiotProxyKeys,
        ]);
    }

    public function updateProxyPool(
        UpdateRoboNeoProxyPoolRequest $request,
        RoboNeoProxyPoolSettings $proxyPoolSettings,
    ): RedirectResponse {
        $proxyPoolSettings->replace($request->proxyUrls());

        return redirect()
            ->route('ai-video-generator.api-tokens.index')
            ->with('success_msg', 'Đã cập nhật proxy pool RoboNeo. Cấu hình mới có hiệu lực ngay.');
    }

    public function storeKiotProxyKey(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'proxy_key' => ['required', 'string', 'max:1000'],
            'region' => ['required', 'in:random,bac,trung,nam'],
            'max_concurrent_tasks' => ['required', 'integer', 'min:1', 'max:15'],
        ]);
        $proxyKey = trim((string) $data['proxy_key']);
        $duplicate = KiotProxyKey::query()->get(['id', 'proxy_key'])->contains(
            static fn (KiotProxyKey $key): bool => hash_equals(
                hash('sha256', (string) $key->proxy_key),
                hash('sha256', $proxyKey),
            ),
        );

        if ($duplicate) {
            return redirect()->back()->with('error_msg', 'KiotProxy key này đã tồn tại.');
        }

        KiotProxyKey::query()->create([
            'name' => trim((string) $data['name']),
            'proxy_key' => $proxyKey,
            'region' => $data['region'],
            'max_concurrent_tasks' => (int) $data['max_concurrent_tasks'],
            'is_active' => true,
            'health_status' => 'healthy',
        ]);

        return redirect()
            ->route('ai-video-generator.api-tokens.index')
            ->with('success_msg', 'Đã thêm KiotProxy key. Key được mã hóa trong database.');
    }

    public function toggleKiotProxyKey(KiotProxyKey $kiotProxyKey): RedirectResponse
    {
        if ($kiotProxyKey->is_active && $this->activeKiotProxyLeases($kiotProxyKey) > 0) {
            return redirect()->back()->with('error_msg', 'Không thể tắt key đang phục vụ task RoboNeo.');
        }

        $kiotProxyKey->update(['is_active' => ! $kiotProxyKey->is_active]);

        return redirect()->back()->with('success_msg', 'Đã cập nhật trạng thái KiotProxy key.');
    }

    public function updateKiotProxyCapacity(Request $request, KiotProxyKey $kiotProxyKey): RedirectResponse
    {
        $data = $request->validate([
            'max_concurrent_tasks' => ['required', 'integer', 'min:1', 'max:15'],
        ]);
        $active = $this->activeKiotProxyLeases($kiotProxyKey);
        $capacity = (int) $data['max_concurrent_tasks'];

        if ($capacity < $active) {
            return redirect()->back()->with(
                'error_msg',
                sprintf('Key đang phục vụ %d task; giới hạn mới không được thấp hơn số task đang chạy.', $active),
            );
        }

        $kiotProxyKey->update(['max_concurrent_tasks' => $capacity]);

        return redirect()->back()->with('success_msg', 'Đã cập nhật số task tối đa cho KiotProxy key.');
    }

    public function rotateKiotProxyKey(
        KiotProxyKey $kiotProxyKey,
        KiotProxyManager $manager,
    ): RedirectResponse {
        try {
            $endpoint = $manager->rotateNow($kiotProxyKey);
        } catch (\Throwable $exception) {
            return redirect()->back()->with('error_msg', $exception->getMessage());
        }

        return redirect()->back()->with(
            'success_msg',
            sprintf('Đã lấy IP KiotProxy mới (%s).', $endpoint->fingerprint),
        );
    }

    public function destroyKiotProxyKey(
        KiotProxyKey $kiotProxyKey,
        KiotProxyManager $manager,
    ): RedirectResponse {
        try {
            $manager->releaseKey($kiotProxyKey);
            KiotProxyTaskLease::query()->where('kiot_proxy_key_id', $kiotProxyKey->getKey())->delete();
            $kiotProxyKey->delete();
        } catch (\Throwable $exception) {
            return redirect()->back()->with('error_msg', $exception->getMessage());
        }

        return redirect()->back()->with('success_msg', 'Đã xóa KiotProxy key.');
    }

    public function create()
    {
        $this->pageTitle('Thêm API token');

        return AiVideoApiTokenForm::create()
            ->setUrl(route('ai-video-generator.api-tokens.store'))
            ->renderForm();
    }

    public function store(CreateAiVideoApiTokenRequest $request)
    {
        $apiToken = AiVideoApiToken::query()->create($request->validated());

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('ai-video-generator.api-tokens.index'))
            ->setNextUrl(route('ai-video-generator.api-tokens.edit', $apiToken))
            ->withCreatedSuccessMessage();
    }

    public function edit(AiVideoApiToken $apiToken)
    {
        $this->pageTitle('Sửa API token');

        return AiVideoApiTokenForm::createFromModel($apiToken)
            ->setValidatorClass(UpdateAiVideoApiTokenRequest::class)
            ->setUrl(route('ai-video-generator.api-tokens.update', $apiToken))
            ->setMethod('PUT')
            ->renderForm();
    }

    public function update(AiVideoApiToken $apiToken, UpdateAiVideoApiTokenRequest $request)
    {
        $apiToken->fill($request->validated());
        $apiToken->save();

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('ai-video-generator.api-tokens.index'))
            ->withUpdatedSuccessMessage();
    }

    public function destroy(AiVideoApiToken $apiToken)
    {
        return DeleteResourceAction::make($apiToken);
    }

    public function importForm()
    {
        $this->pageTitle('Nhập API token từ Excel');

        return view('plugins/ai-video-generator::api-tokens.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $sheet = Excel::toCollection(new \stdClass, $request->file('file'))->first();

        if (! $sheet || $sheet->isEmpty()) {
            return redirect()->back()->with('error_msg', 'File Excel không có dữ liệu.');
        }

        $headers = collect($sheet->shift() ?? [])
            ->map(fn ($value) => Str::snake(trim((string) $value)))
            ->all();

        if (! in_array('name', $headers, true) || ! in_array('token_api', $headers, true)) {
            return redirect()->back()->with('error_msg', 'File phải có hai cột: name và token_api.');
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $seenTokens = [];

        foreach ($sheet->values() as $index => $row) {
            $rowNumber = $index + 2;
            $values = $row instanceof \Illuminate\Support\Collection ? $row->all() : (array) $row;
            $values = array_slice(array_pad($values, count($headers), null), 0, count($headers));
            $data = Arr::only(array_combine($headers, $values) ?: [], ['name', 'token_api']);
            $data = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $data);

            if (collect($data)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            $token = (string) ($data['token_api'] ?? '');
            $name = (string) ($data['name'] ?? '');

            if ($name === '' || $token === '') {
                $errors[] = "Dòng {$rowNumber}: name và token_api là bắt buộc.";

                continue;
            }

            if (mb_strlen($name) > 255 || mb_strlen($token) > 255) {
                $errors[] = "Dòng {$rowNumber}: name hoặc token_api dài quá 255 ký tự.";

                continue;
            }

            if (isset($seenTokens[$token]) || AiVideoApiToken::query()->where('token_api', $token)->exists()) {
                $skipped++;
                $seenTokens[$token] = true;

                continue;
            }

            AiVideoApiToken::query()->create([
                'name' => $name,
                'token_api' => $token,
                'webhook_secret' => $token,
                'status' => true,
            ]);
            $seenTokens[$token] = true;
            $created++;
        }

        $message = "Đã thêm {$created} token".($skipped ? ", bỏ qua {$skipped} token trùng" : '').'.';

        return redirect()
            ->route('ai-video-generator.api-tokens.import.form')
            ->with('success_msg', $message)
            ->with('import_errors', $errors);
    }

    public function downloadTemplate()
    {
        return Excel::download(new AiVideoApiTokenTemplateExport, 'api-tokens-template.xlsx');
    }

    private function activeKiotProxyLeases(KiotProxyKey $key): int
    {
        if (! Schema::hasTable('ai_video_kiot_proxy_leases')) {
            return $key->leased_by && $key->lease_until?->isFuture() ? 1 : 0;
        }

        return KiotProxyTaskLease::query()
            ->where('kiot_proxy_key_id', $key->getKey())
            ->where('lease_until', '>', now())
            ->count();
    }
}
