<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Forms\AiVideoApiTokenForm;
use Botble\AiVideoGenerator\Exports\AiVideoApiTokenTemplateExport;
use Botble\AiVideoGenerator\Http\Requests\CreateAiVideoApiTokenRequest;
use Botble\AiVideoGenerator\Http\Requests\UpdateAiVideoApiTokenRequest;
use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Tables\AiVideoApiTokenTable;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AiVideoApiTokenController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()->add('API token', route('ai-video-generator.api-tokens.index'));
    }

    public function index(AiVideoApiTokenTable $table)
    {
        $this->pageTitle('API token');

        return $table->renderTable();
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
}
