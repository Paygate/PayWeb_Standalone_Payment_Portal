<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */

/*
 * Copyright (c) 2024 Payfast (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

// Sessions used here only for the sticky page
session_start();
require_once '_env.php';

/**
 * Setup Monolog as the default logger
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

$logger = new Logger('PayGate_PW3_Logger');
$stream = new RotatingFileHandler('../logs/paygate_pw3.log');
$logger->pushHandler($stream);

// Setup date objects.
$today = new DateTime('');
try {
    $expireDate = new DateTime($expiry_date);
} catch (Exception $exception) {
    $logger->error('Exception: ', ['message' => $exception]);
}

// Output header html.
echo <<<EOT
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>$page_title</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="$url/css/style.css">
    </head>
    <body>
        <div class="container">
            <h1>$page_title</h1>
EOT;

// Disable payment portal if after expiry date.
if ($today->format('Y-m-d') >= $expireDate->format('Y-m-d')) {
    echo <<<EOT
            <h2>Payment Disabled</h2>";
            <p>Please contact $your_company for more information.<a/></p>";
        </div>
    </body>
</html>
EOT;
    die();
}
