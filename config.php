<?php
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('949298386531-pbk4td6p6ga18e6diee9rifskto0ou0v.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/bossgpt-tool/callback.php');
$client->addScope("email");
$client->addScope("profile");
