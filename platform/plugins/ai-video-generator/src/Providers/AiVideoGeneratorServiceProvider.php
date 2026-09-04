<?php

namespace Botble\AiVideoGenerator\Providers;

use Botble\AiVideoGenerator\Commands\CleanExpiredGeneratedMediaCommand;
use Botble\AiVideoGenerator\Commands\CleanTemporaryMediaCommand;
use Botble\AiVideoGenerator\Commands\ReconcileExternalRoboNeoTasksCommand;
use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Models\AiVideoModelEndpoint;
use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Repositories\Eloquent\AiGenerationTaskRepository;
use Botble\AiVideoGenerator\Repositories\Eloquent\AiVideoApiTokenRepository;
use Botble\AiVideoGenerator\Repositories\Eloquent\AiVideoModelEndpointRepository;
use Botble\AiVideoGenerator\Repositories\Eloquent\CreditPackagePaymentRepository;
use Botble\AiVideoGenerator\Repositories\Eloquent\CustomerCreditRepository;
use Botble\AiVideoGenerator\Repositories\Eloquent\ExternalVideoTaskRepository;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoModelEndpointInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\CreditPackagePaymentInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\CustomerCreditInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\AiVideoGenerator\Services\AiGenerationService;
use Botble\AiVideoGenerator\Services\AiGenerationTaskStatusService;
use Botble\AiVideoGenerator\Services\AiGenerationWebhookService;
use Botble\AiVideoGenerator\Services\CreditPackagePaymentCompletionService;
use Botble\AiVideoGenerator\Services\CreditPackagePurchaseService;
use Botble\AiVideoGenerator\Services\CustomerCreditService;
use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoTaskPipelineService;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\CustomerRoboNeoTaskSource;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\ExternalRoboNeoTaskSource;
use Botble\AiVideoGenerator\Services\VideoLabDataService;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Supports\DashboardMenuItem;
use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Theme\Facades\ThemeOption;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;

class AiVideoGeneratorServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        config([
            'auth.guards.customer' => [
                'driver' => 'session',
                'provider' => 'customers',
            ],
            'auth.providers.customers' => [
                'driver' => 'eloquent',
                'model' => Customer::class,
            ],
            'auth.passwords.customers' => [
                'provider' => 'customers',
                'table' => 'ec_customer_password_resets',
                'expire' => 60,
            ],
        ]);

        $this->app->singleton(AiGenerationService::class);
        $this->app->singleton(AiGenerationTaskStatusService::class);
        $this->app->singleton(AiGenerationWebhookService::class);
        $this->app->singleton(CustomerCreditService::class);
        $this->app->singleton(CreditPackagePurchaseService::class);
        $this->app->singleton(CreditPackagePaymentCompletionService::class);
        $this->app->singleton(VideoLabDataService::class);
        $this->app->singleton(RoboNeoTaskPipelineService::class);
        $this->app->singleton(CustomerRoboNeoTaskSource::class);
        $this->app->singleton(ExternalRoboNeoTaskSource::class);

        $this->app->bind(AiGenerationTaskInterface::class, function () {
            return new AiGenerationTaskRepository(new AiGenerationTask);
        });

        $this->app->bind(AiVideoApiTokenInterface::class, function () {
            return new AiVideoApiTokenRepository(new AiVideoApiToken);
        });

        $this->app->bind(ExternalVideoTaskInterface::class, function () {
            return new ExternalVideoTaskRepository(new \Botble\AiVideoGenerator\Models\ExternalVideoTask);
        });

        $this->app->bind(AiVideoModelEndpointInterface::class, function () {
            return new AiVideoModelEndpointRepository(new AiVideoModelEndpoint);
        });

        $this->app->bind(CustomerCreditInterface::class, function () {
            return new CustomerCreditRepository(new Customer);
        });

        $this->app->bind(CreditPackagePaymentInterface::class, CreditPackagePaymentRepository::class);

        $this->commands([
            CleanTemporaryMediaCommand::class,
            CleanExpiredGeneratedMediaCommand::class,
            ReconcileExternalRoboNeoTasksCommand::class,
        ]);

        $this->app->afterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule
                ->command(ReconcileExternalRoboNeoTasksCommand::class)
                ->everyMinute()
                ->withoutOverlapping(5);
        });
    }

    public function boot(): void
    {
        $this
            ->setNamespace('plugins/ai-video-generator')
            ->loadAndPublishConfigurations(['general'])
            ->loadAndPublishConfigurations(['permissions'])
            ->loadRoutes()
            ->loadRoutes(['customer-auth'])
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadMigrations();

        if (class_exists('ApiHelper')) {
            $this->loadRoutes(['api']);
        }

        if (defined('PAYMENT_ACTION_PAYMENT_PROCESSED')) {
            add_action(PAYMENT_ACTION_PAYMENT_PROCESSED, function (array $data): void {
                if (($data['payment_channel'] ?? null) !== SEPAY_PAYMENT_METHOD_NAME) {
                    return;
                }

                app(CreditPackagePaymentCompletionService::class)->handle((string) ($data['charge_id'] ?? ''));
            }, 999);
        }

        add_filter('panel_sections', function (array $sections, string $groupId): array {
            if ($groupId !== 'settings') {
                return $sections;
            }

            return array_values(array_filter(
                $sections,
                fn ($section) => ! in_array($section->getId(), [
                    'settings.common',
                    'others.localization',
                    'settings.ecommerce',
                ], true)
            ));
        }, 999, 2);

        add_action(RENDERING_THEME_OPTIONS_PAGE, function (): void {
            foreach ([
                'opt-text-subsection-breadcrumb',
                'opt-text-subsection-page',
                'opt-text-subsection-ecommerce',
                'opt-text-subsection-ecommerce-slug',
                'opt-text-subsection-ecommerce-seo',
            ] as $sectionId) {
                ThemeOption::removeSection($sectionId, true);
            }
        }, 1);

        DashboardMenu::default()->beforeRetrieving(function (): void {
            DashboardMenu::make()
                // Hide legacy commerce/content administration that is not part of the AI Video back office.
                ->removeItem([
                    'cms-plugins-ecommerce',
                    'cms-plugins-product-specification',
                    'cms-core-page',
                    'cms-plugins-contact',
                    'cms-core-plugins',
                    'cms-core-tools',
                    'cms-core-theme',
                    'cms-core-appearance-custom-css',
                    'cms-core-appearance-custom-js',
                    'cms-core-appearance-custom-html',
                    'cms-core-appearance-robots-txt',
                ])
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-plugins-ai-video-generator')
                        ->priority(125)
                        ->name('plugins/ai-video-generator::ai-video-generator.menu')
                        ->icon('ti ti-video-plus')
                        ->route('ai-video-generator.index')
                        ->permissions('ai-video-generator.index')
                )
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-plugins-ai-video-generator-settings')
                        ->parentId('cms-plugins-ai-video-generator')
                        ->priority(10)
                        ->name('plugins/ai-video-generator::ai-video-generator.settings.title')
                        ->icon('ti ti-settings')
                        ->route('ai-video-generator.settings')
                        ->permissions('ai-video-generator.settings')
                )
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-plugins-ai-video-generator-tasks')
                        ->parentId('cms-plugins-ai-video-generator')
                        ->priority(5)
                        ->name('plugins/ai-video-generator::ai-video-generator.tasks.name')
                        ->icon('ti ti-list-details')
                        ->route('ai-video-generator.tasks.index')
                        ->permissions('ai-video-generator.index')
                )
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-plugins-ai-video-generator-external-tasks')
                        ->parentId('cms-plugins-ai-video-generator')
                        ->priority(5)
                        ->name('Lịch sử call API')
                        ->icon('ti ti-api')
                        ->route('ai-video-generator.external-tasks.index')
                        ->permissions('ai-video-generator.index')
                )
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-plugins-ai-video-generator-customers')
                        ->parentId('cms-plugins-ai-video-generator')
                        ->priority(6)
                        ->name('plugins/ai-video-generator::ai-video-generator.customers.name')
                        ->icon('ti ti-users')
                        ->route('ai-video-generator.customers.index')
                        ->permissions('ai-video-generator.customers.index')
                )
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-plugins-ai-video-generator-credit-packages')
                        ->parentId('cms-plugins-ai-video-generator')
                        ->priority(7)
                        ->name('plugins/ai-video-generator::ai-video-generator.credit_packages.name')
                        ->icon('ti ti-wallet')
                        ->route('ai-video-generator.credit-packages.index')
                        ->permissions('ai-video-generator.credit-packages.index')
                )
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-plugins-ai-video-generator-api-tokens')
                        ->parentId('cms-plugins-ai-video-generator')
                        ->priority(8)
                        ->name('API token')
                        ->icon('ti ti-key')
                        ->route('ai-video-generator.api-tokens.index')
                        ->permissions('ai-video-generator.api-tokens.index')
                )
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-plugins-ai-video-generator-content-posts')
                        ->parentId('cms-plugins-ai-video-generator')
                        ->priority(10)
                        ->name('Nội dung hiển thị')
                        ->icon('ti ti-article')
                        ->route('ai-video-generator.content-posts.index')
                        ->permissions('ai-video-generator.index')
                )
                ->registerItem(
                    DashboardMenuItem::make()
                        ->id('cms-plugins-ai-video-generator-model-endpoints')
                        ->parentId('cms-plugins-ai-video-generator')
                        ->priority(9)
                        ->name('Model AI / Endpoint')
                        ->icon('ti ti-api')
                        ->route('ai-video-generator.model-endpoints.index')
                        ->permissions('ai-video-generator.index')
                );
        });

        $this->app->booted(function (): void {
            if (
                defined('SOCIAL_LOGIN_MODULE_SCREEN_NAME') &&
                class_exists('Botble\SocialLogin\Facades\SocialService') &&
                Route::has('ai-video-generator.login')
            ) {
                \Botble\SocialLogin\Facades\SocialService::registerModule([
                    'guard' => 'customer',
                    'model' => Customer::class,
                    'login_url' => route('ai-video-generator.login'),
                    'redirect_url' => BaseHelper::getHomepageUrl() ?: url('/'),
                ]);
            }
        });

    }
}
