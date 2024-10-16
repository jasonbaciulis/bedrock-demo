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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mailchimp' => [
        // Get your key in https://us1.admin.mailchimp.com/account/api/
        'key' => env('MAILCHIMP_API_KEY'),
        // You can find list/audience ids in https://us16.admin.mailchimp.com/lists/,
        // select list then Settings > Audience name and defaults.
        'lists' => [
            'newsletter' => env('MAILCHIMP_NEWSLETTER_LIST_ID'),
        ],
    ],

    'convertkit' => [
        // Get your key in https://app.convertkit.com/account_settings/advanced
        'key' => env('CONVERTKIT_API_KEY'),
        // In ConvertKit what you need is form_id.
        // You can grab it from the URL when editing your form.
        'lists' => [
            'newsletter' => env('CONVERTKIT_NEWSLETTER_LIST_ID'),
        ],
    ],
];
