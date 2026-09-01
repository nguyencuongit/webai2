<?php

return [
    'name' => 'AI Video Generator',
    'menu' => 'AI Video',
    'description' => 'Scaffold for connecting to an AI video generation API provider.',
    'settings' => [
        'title' => 'AI Video Settings',
        'description' => 'API credentials and provider options will be added here when the integration details are ready.',
    ],
    'tasks' => [
        'name' => 'AI Generation Tasks',
        'view' => 'Task #:id',
        'customer' => 'Customer',
        'customer_id' => 'Customer ID',
        'task_id' => 'Task ID',
        'status' => 'Status',
        'is_completed' => 'Completed',
        'completed_at' => 'Completed at',
        'generated' => 'Generated',
        'has_nsfw' => 'Has NSFW',
        'payload' => 'Payload',
    ],
];
