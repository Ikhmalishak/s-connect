<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],



    'power_automate' => [
        'trigger_url' => env('POWER_AUTOMATE_TRIGGER_URL'),
        'approval_result_url' => env('POWER_AUTOMATE_APPROVAL_RESULT_URL'),
        'polling_url' => env('POWER_AUTOMATE_POLLING_URL'),
        'cleanup_url' => 'https://my-approval-app-m91z.vercel.app/api/cleanup',
        'inspection_trigger_url' => env('POWER_AUTOMATE_INSPECTION_TRIGGER_URL'),
        'inspection_polling_url' => env('POWER_AUTOMATE_INSPECTION_POLLING_URL'),
        'shipping_trigger_url' => env('POWER_AUTOMATE_SHIPPING_TRIGGER_URL'),
        'loading_approvals_url' => env('VERCEL_LOADING_APPROVALS_URL', 'https://my-approval-app-m91z.vercel.app/api/loading-approvals'),
        'inspection_approvals_url' => env('VERCEL_INSPECTION_APPROVALS_URL', 'https://my-approval-app-m91z.vercel.app/api/inspection-approvals'),
        'shipping_approvals_url' => env('VERCEL_SHIPPING_APPROVALS_URL', 'https://my-approval-app-m91z.vercel.app/api/shipping-approvals'),
    ],

    'external_approvals' => [
        'url' => env('EXTERNAL_APPROVALS_URL'),
        'base_url' => env('EXTERNAL_APPROVALS_BASE_URL'),
        'secret' => env('EXTERNAL_APPROVALS_SECRET'),
    ],

];
