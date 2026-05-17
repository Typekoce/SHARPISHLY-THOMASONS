<?php

// Database credentials
define('DB_NAME','sharpishly_db');
define('DB_USER','vboxuser');
define('DB_PASS','gemini');
define('DB_HOST','127.0.0.1');

// Development mode
define('APP_DEV','development');

// config/env.php
// Load mock defaults but allow real environment variables to override

define('GMAIL_USER', getenv('GMAIL_USER') ?: 'mock.gmail.user@example.com');
define('GMAIL_PASS', getenv('GMAIL_PASS') ?: 'mock-gmail-password');
define('GMAIL_FROM', getenv('GMAIL_FROM') ?: 'no-reply@example.com');

define('HOTMAIL_USER', getenv('HOTMAIL_USER') ?: 'mock.hotmail.user@example.com');
define('HOTMAIL_PASS', getenv('HOTMAIL_PASS') ?: 'mock-hotmail-password');
define('HOTMAIL_FROM', getenv('HOTMAIL_FROM') ?: 'no-reply-hotmail@example.com');

define('ZOHO_USER', getenv('ZOHO_USER') ?: 'mock.zoho.user@example.com');
define('ZOHO_PASS', getenv('ZOHO_PASS') ?: 'mock-zoho-password');
define('ZOHO_FROM', getenv('ZOHO_FROM') ?: 'no-reply-zoho@example.com');
