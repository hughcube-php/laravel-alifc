<?php

return [
    'default' => 'default',
    'clients' => [
        'default' => [
            'Endpoint' => env('FC_ENDPOINT'),
            'AccessKeyID' => env('FC_ACCESS_KEY_ID'),
            'AccessKeySecret' => env('FC_ACCESS_KEY_SECRET'),
            'RegionId' => env('FC_REGION_ID'),
            'AccountId' => env('FC_ACCOUNT_ID'),
            'Http' => [
                'proxy' => [
                    'http' => env('DEBUG_HTTP_PROXY'),
                    'https' => env('DEBUG_HTTP_PROXY'),
                ],
                'verify' => true == env('DEBUG_HTTP_VERIFY', true),
            ],
        ]
    ],

    'handlers' => [
        //'pre_freeze' => \HughCube\Laravel\Octane\Actions\WaitTaskCompleteAction::class,
        //'pre_stop' => \HughCube\Laravel\Octane\Actions\WaitTaskCompleteAction::class,
    ],
];
