<?php

return [
    'default' => 'default',
    'clients' => [
        'default' => [
            'AccessKeyID' => md5(random_bytes(100)),
            'AccessKeySecret' => md5(random_bytes(100)),
            'RegionId' => md5(random_bytes(100)),
            'AccountId' => md5(random_bytes(100)),
            'Options' => [
                /** http Options */
            ],
        ],
        'default2' => [
            'alibabaCloud' => null,
        ],
        'default3' => [
            'alibabaCloud' => 'default',
        ],
    ],
];
