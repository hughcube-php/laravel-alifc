<?php

return [
    "default" => "default",

    "clients" => [
        "default" => [
            "accessKey" => env("ALIYUN_ACCESS_KEY"),
            "accessKeySecret" => env("ALIYUN_ACCESS_KEY_SECRET"),
            "regionId" => env("ALIYUN_REGION"),
            "accountId" => env("ALIYUN_ACCOUNT")
        ]
    ]
];
