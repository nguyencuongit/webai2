<?php

return [
    [
        'name' => 'AI Video Generator',
        'flag' => 'ai-video-generator.index',
    ],
    [
        'name' => 'Settings',
        'flag' => 'ai-video-generator.settings',
        'parent_flag' => 'ai-video-generator.index',
    ],
    [
        'name' => 'Tasks',
        'flag' => 'ai-video-generator.tasks.index',
        'parent_flag' => 'ai-video-generator.index',
    ],
    [
        'name' => 'Customers',
        'flag' => 'ai-video-generator.customers.index',
        'parent_flag' => 'ai-video-generator.index',
    ],
    [
        'name' => 'Create customers',
        'flag' => 'ai-video-generator.customers.create',
        'parent_flag' => 'ai-video-generator.customers.index',
    ],
    [
        'name' => 'Edit customers',
        'flag' => 'ai-video-generator.customers.edit',
        'parent_flag' => 'ai-video-generator.customers.index',
    ],
    [
        'name' => 'Delete customers',
        'flag' => 'ai-video-generator.customers.destroy',
        'parent_flag' => 'ai-video-generator.customers.index',
    ],
    [
        'name' => 'Credit packages',
        'flag' => 'ai-video-generator.credit-packages.index',
        'parent_flag' => 'ai-video-generator.index',
    ],
    [
        'name' => 'Create credit packages',
        'flag' => 'ai-video-generator.credit-packages.create',
        'parent_flag' => 'ai-video-generator.credit-packages.index',
    ],
    [
        'name' => 'Edit credit packages',
        'flag' => 'ai-video-generator.credit-packages.edit',
        'parent_flag' => 'ai-video-generator.credit-packages.index',
    ],
    [
        'name' => 'Delete credit packages',
        'flag' => 'ai-video-generator.credit-packages.destroy',
        'parent_flag' => 'ai-video-generator.credit-packages.index',
    ],
    [
        'name' => 'API tokens',
        'flag' => 'ai-video-generator.api-tokens.index',
        'parent_flag' => 'ai-video-generator.index',
    ],
    [
        'name' => 'Create API tokens',
        'flag' => 'ai-video-generator.api-tokens.create',
        'parent_flag' => 'ai-video-generator.api-tokens.index',
    ],
    [
        'name' => 'Edit API tokens',
        'flag' => 'ai-video-generator.api-tokens.edit',
        'parent_flag' => 'ai-video-generator.api-tokens.index',
    ],
    [
        'name' => 'Delete API tokens',
        'flag' => 'ai-video-generator.api-tokens.destroy',
        'parent_flag' => 'ai-video-generator.api-tokens.index',
    ],
];
