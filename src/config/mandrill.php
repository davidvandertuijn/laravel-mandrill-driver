<?php

return [

    'url' => env('MANDRILL_URL', 'https://mandrillapp.com/api/1.0'),
    'from_email' => env('MAIL_FROM_ADDRESS'),
    'from_name' => env('MAIL_FROM_NAME') ?? 'Mandrill Mailer.',

];
