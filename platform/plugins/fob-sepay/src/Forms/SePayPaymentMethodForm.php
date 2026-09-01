<?php

namespace FriendsOfBotble\SePay\Forms;

use Botble\Base\Facades\Assets;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Payment\Forms\PaymentMethodForm;
use Exception;
use FriendsOfBotble\SePay\SePayClient;
use Illuminate\Support\Facades\Log;

class SePayPaymentMethodForm extends PaymentMethodForm
{
    public function setup(): void
    {
        Assets::addScriptsDirectly('vendor/core/plugins/fob-sepay/js/settings.js');

        $client = new SePayClient();

        $this
            ->template('plugins/fob-sepay::forms.payment-method')
            ->paymentId(SEPAY_PAYMENT_METHOD_NAME)
            ->paymentName('SePay')
            ->paymentDescription('Thanh toán chuyển khoản ngân hàng với QR Code. Tự động xác nhận thanh toán bởi SePay.')
            ->paymentLogo(url('vendor/core/plugins/fob-sepay/images/sepay.png'))
            ->paymentUrl('https://sepay.vn')
            ->when($client->isConnected(), function (PaymentMethodForm $form) use ($client) {
                try {
                    $form->setData('profile', $client->profile());

                    $bankAccounts = collect($client->bankAccounts())
                        ->filter(fn ($item) => filled(data_get($item, 'id')))
                        ->mapWithKeys(function (array $item): array {
                            $bankName = data_get($item, 'bank.short_name')
                                ?? data_get($item, 'bank.shortName')
                                ?? data_get($item, 'bank_name')
                                ?? data_get($item, 'bank_short_name')
                                ?? 'Ngân hàng';
                            $accountNumber = data_get($item, 'account_number')
                                ?? data_get($item, 'account.number')
                                ?? data_get($item, 'number');
                            $accountHolder = data_get($item, 'account_holder_name')
                                ?? data_get($item, 'account.holder_name')
                                ?? data_get($item, 'holder_name');

                            return [$item['id'] => implode(' - ', array_filter([
                                $bankName,
                                $accountNumber,
                                $accountHolder,
                            ]))];
                        })
                        ->all();

                    // The API token can read bank accounts, while the legacy
                    // company endpoint is not available on the v2 User API.
                    // Add the essential configuration first so that a failed
                    // prefix lookup cannot hide the receiving-bank selector.
                    $form
                        ->add(
                            get_payment_setting_key('bank_account_id', SEPAY_PAYMENT_METHOD_NAME),
                            SelectField::class,
                            SelectFieldOption::make()
                                ->searchable()
                                ->choices($bankAccounts)
                                ->selected(get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME))
                                ->label('Tài khoản ngân hàng nhận tiền')
                        )
                        ->add(
                            get_payment_setting_key('bank_sub_account_id', SEPAY_PAYMENT_METHOD_NAME),
                            SelectField::class,
                            SelectFieldOption::make()
                                ->searchable()
                                ->wrapperAttributes(['style' => 'display: none'])
                                ->label('Tài khoản ảo')
                        )
                        ->add(
                            get_payment_setting_key('prefix', SEPAY_PAYMENT_METHOD_NAME),
                            TextField::class,
                            TextFieldOption::make()
                                ->label('Tiền tố mã thanh toán')
                                ->value(get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SDH'))
                                ->helperText('Nhập tiền tố dùng để nhận diện đơn, ví dụ: SDH.')
                        )
                        ->add(
                            get_payment_setting_key('bank_display', SEPAY_PAYMENT_METHOD_NAME),
                            SelectField::class,
                            SelectFieldOption::make()
                                ->label('Hiển thị tên ngân hàng')
                                ->choices([
                                    'full_name' => 'Tên đầy đủ',
                                    'short_name' => 'Tên ngắn',
                                    'full_name_short_name' => 'Tên đầy đủ + Tên ngắn',
                                ])
                                ->selected(get_payment_setting('bank_display', SEPAY_PAYMENT_METHOD_NAME, 'short_name'))
                        );

                    // User API v2 has no company/payment-code endpoint. The
                    // prefix above is deliberately a free-text setting.
                    return;
                    $paymentCodePrefixes = collect(data_get($company, 'configurations.payment_code_formats', []))
                        ->mapWithKeys(fn ($item) => [$item['prefix'] => $item['prefix']])
                        ->all();

                    $form
                        ->add(
                            get_payment_setting_key('bank_account_id', SEPAY_PAYMENT_METHOD_NAME),
                            SelectField::class,
                            SelectFieldOption::make()
                                ->searchable()
                                ->choices($bankAccounts)
                                ->selected(get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME))
                                ->label('Tài khoản ngân hàng')
                        )
                        ->add(
                            get_payment_setting_key('bank_sub_account_id', SEPAY_PAYMENT_METHOD_NAME),
                            SelectField::class,
                            SelectFieldOption::make()
                                ->searchable()
                                ->wrapperAttributes(['style' => 'display: none'])
                                ->label('Tài khoản ảo')
                        )
                        ->add(
                            get_payment_setting_key('prefix', SEPAY_PAYMENT_METHOD_NAME),
                            SelectField::class,
                            SelectFieldOption::make()
                                ->label('Tiền tố mã thanh toán')
                                ->searchable()
                                ->choices($paymentCodePrefixes)
                                ->selected(get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME))
                        )
                        ->add(
                            get_payment_setting_key('bank_display', SEPAY_PAYMENT_METHOD_NAME),
                            SelectField::class,
                            SelectFieldOption::make()
                                ->label('Hiển thị tên ngân hàng')
                                ->choices([
                                    'full_name' => 'Tên đầy đủ',
                                    'short_name' => 'Tên ngắn',
                                    'full_name_short_name' => 'Tên đầy đủ + Tên ngắn',
                                ])
                                ->selected(get_payment_setting('bank_display', SEPAY_PAYMENT_METHOD_NAME, 'short_name'))
                        );
                } catch (Exception $e) {
                    Log::error($e);
                }
            });
    }
}
