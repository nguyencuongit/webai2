<?php

use Botble\Base\Facades\AdminHelper;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\AiVideoGenerator\Http\Controllers'], function (): void {
    AdminHelper::registerRoutes(function (): void {
        Route::group(['prefix' => 'ai-video-generator', 'as' => 'ai-video-generator.', 'permission' => 'ai-video-generator.index'], function (): void {
            Route::get('/', [
                'as' => 'index',
                'uses' => 'AiVideoGeneratorController@index',
            ]);

            Route::match(['GET', 'POST'], 'tasks', [
                'as' => 'tasks.index',
                'uses' => 'Admin\AiVideoTaskController@index',
                'permission' => 'ai-video-generator.index',
            ]);

            Route::get('tasks/{task}', [
                'as' => 'tasks.show',
                'uses' => 'Admin\AiVideoTaskController@show',
                'permission' => 'ai-video-generator.index',
            ]);

            Route::match(['GET', 'POST'], 'external-tasks', [
                'as' => 'external-tasks.index',
                'uses' => 'Admin\ExternalVideoTaskController@index',
                'permission' => 'ai-video-generator.index',
            ]);

            Route::get('external-tasks/{task}', [
                'as' => 'external-tasks.show',
                'uses' => 'Admin\ExternalVideoTaskController@show',
                'permission' => 'ai-video-generator.index',
            ]);

            Route::get('customers', [
                'as' => 'customers.index',
                'uses' => 'Admin\AiVideoCustomerController@index',
                'permission' => 'ai-video-generator.customers.index',
            ]);

            Route::post('customers/table', [
                'as' => 'customers.table',
                'uses' => 'Admin\AiVideoCustomerController@index',
                'permission' => 'ai-video-generator.customers.index',
            ]);

            Route::get('customers/create', [
                'as' => 'customers.create',
                'uses' => 'Admin\AiVideoCustomerController@create',
                'permission' => 'ai-video-generator.customers.create',
            ]);

            Route::post('customers', [
                'as' => 'customers.store',
                'uses' => 'Admin\AiVideoCustomerController@store',
                'permission' => 'ai-video-generator.customers.create',
            ]);

            Route::get('customers/{customer}/edit', [
                'as' => 'customers.edit',
                'uses' => 'Admin\AiVideoCustomerController@edit',
                'permission' => 'ai-video-generator.customers.edit',
            ]);

            Route::put('customers/{customer}', [
                'as' => 'customers.update',
                'uses' => 'Admin\AiVideoCustomerController@update',
                'permission' => 'ai-video-generator.customers.edit',
            ]);

            Route::delete('customers/{customer}', [
                'as' => 'customers.destroy',
                'uses' => 'Admin\AiVideoCustomerController@destroy',
                'permission' => 'ai-video-generator.customers.destroy',
            ]);

            Route::get('credit-packages', [
                'as' => 'credit-packages.index',
                'uses' => 'Admin\AiVideoCreditPackageController@index',
                'permission' => 'ai-video-generator.credit-packages.index',
            ]);

            Route::post('credit-packages/table', [
                'as' => 'credit-packages.table',
                'uses' => 'Admin\AiVideoCreditPackageController@index',
                'permission' => 'ai-video-generator.credit-packages.index',
            ]);

            Route::get('credit-packages/create', [
                'as' => 'credit-packages.create',
                'uses' => 'Admin\AiVideoCreditPackageController@create',
                'permission' => 'ai-video-generator.credit-packages.create',
            ]);

            Route::post('credit-packages', [
                'as' => 'credit-packages.store',
                'uses' => 'Admin\AiVideoCreditPackageController@store',
                'permission' => 'ai-video-generator.credit-packages.create',
            ]);

            Route::get('credit-packages/{creditPackage}/edit', [
                'as' => 'credit-packages.edit',
                'uses' => 'Admin\AiVideoCreditPackageController@edit',
                'permission' => 'ai-video-generator.credit-packages.edit',
            ]);

            Route::put('credit-packages/{creditPackage}', [
                'as' => 'credit-packages.update',
                'uses' => 'Admin\AiVideoCreditPackageController@update',
                'permission' => 'ai-video-generator.credit-packages.edit',
            ]);

            Route::delete('credit-packages/{creditPackage}', [
                'as' => 'credit-packages.destroy',
                'uses' => 'Admin\AiVideoCreditPackageController@destroy',
                'permission' => 'ai-video-generator.credit-packages.destroy',
            ]);

            Route::get('api-tokens', ['as' => 'api-tokens.index', 'uses' => 'Admin\AiVideoApiTokenController@index', 'permission' => 'ai-video-generator.api-tokens.index']);
            Route::post('api-tokens/table', ['as' => 'api-tokens.table', 'uses' => 'Admin\AiVideoApiTokenController@index', 'permission' => 'ai-video-generator.api-tokens.index']);
            Route::get('api-tokens/import', ['as' => 'api-tokens.import.form', 'uses' => 'Admin\AiVideoApiTokenController@importForm', 'permission' => 'ai-video-generator.api-tokens.index']);
            Route::post('api-tokens/import', ['as' => 'api-tokens.import', 'uses' => 'Admin\AiVideoApiTokenController@import', 'permission' => 'ai-video-generator.api-tokens.create']);
            Route::get('api-tokens/import/template', ['as' => 'api-tokens.import.template', 'uses' => 'Admin\AiVideoApiTokenController@downloadTemplate', 'permission' => 'ai-video-generator.api-tokens.index']);
            Route::get('api-tokens/create', ['as' => 'api-tokens.create', 'uses' => 'Admin\AiVideoApiTokenController@create', 'permission' => 'ai-video-generator.api-tokens.create']);
            Route::post('api-tokens', ['as' => 'api-tokens.store', 'uses' => 'Admin\AiVideoApiTokenController@store', 'permission' => 'ai-video-generator.api-tokens.create']);
            Route::get('api-tokens/{apiToken}/edit', ['as' => 'api-tokens.edit', 'uses' => 'Admin\AiVideoApiTokenController@edit', 'permission' => 'ai-video-generator.api-tokens.edit']);
            Route::put('api-tokens/{apiToken}', ['as' => 'api-tokens.update', 'uses' => 'Admin\AiVideoApiTokenController@update', 'permission' => 'ai-video-generator.api-tokens.edit']);
            Route::delete('api-tokens/{apiToken}', ['as' => 'api-tokens.destroy', 'uses' => 'Admin\AiVideoApiTokenController@destroy', 'permission' => 'ai-video-generator.api-tokens.destroy']);

            Route::get('model-endpoints', ['as' => 'model-endpoints.index', 'uses' => 'Admin\AiVideoModelEndpointController@index']);
            Route::post('model-endpoints/table', ['as' => 'model-endpoints.table', 'uses' => 'Admin\AiVideoModelEndpointController@index']);
            Route::get('model-endpoints/create', ['as' => 'model-endpoints.create', 'uses' => 'Admin\AiVideoModelEndpointController@create']);
            Route::post('model-endpoints', ['as' => 'model-endpoints.store', 'uses' => 'Admin\AiVideoModelEndpointController@store']);
            Route::get('model-endpoints/{aiVideoModelEndpoint}/edit', ['as' => 'model-endpoints.edit', 'uses' => 'Admin\AiVideoModelEndpointController@edit']);
            Route::put('model-endpoints/{aiVideoModelEndpoint}', ['as' => 'model-endpoints.update', 'uses' => 'Admin\AiVideoModelEndpointController@update']);
            Route::delete('model-endpoints/{aiVideoModelEndpoint}', ['as' => 'model-endpoints.destroy', 'uses' => 'Admin\AiVideoModelEndpointController@destroy']);

            Route::get('content-posts', ['as' => 'content-posts.index', 'uses' => 'Admin\AiContentPostController@index']);
            Route::post('content-posts/table', ['as' => 'content-posts.table', 'uses' => 'Admin\AiContentPostController@index']);
            Route::get('content-posts/create', ['as' => 'content-posts.create', 'uses' => 'Admin\AiContentPostController@create']);
            Route::post('content-posts', ['as' => 'content-posts.store', 'uses' => 'Admin\AiContentPostController@store']);
            Route::get('content-posts/{aiContentPost}/edit', ['as' => 'content-posts.edit', 'uses' => 'Admin\AiContentPostController@edit']);
            Route::put('content-posts/{aiContentPost}', ['as' => 'content-posts.update', 'uses' => 'Admin\AiContentPostController@update']);
            Route::delete('content-posts/{aiContentPost}', ['as' => 'content-posts.destroy', 'uses' => 'Admin\AiContentPostController@destroy']);

            Route::get('settings', [
                'as' => 'settings',
                'uses' => 'AiVideoGeneratorController@settings',
                'permission' => 'ai-video-generator.settings',
            ]);
        });
    });
});
