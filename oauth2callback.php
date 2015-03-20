<?php
require 'vendor/autoload.php';

$clientId = '166812253810-gg40f3a5e94blpb3k1263knjb7jvcnrp.apps.googleusercontent.com';
$clientSecret = 'T1tkHmK-lsO_EV3xYEFdjQSv';
$clientKeyJson = 'local-key.json';
$redirectUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/MyPHPDrive';

session_start();

$client = new Google_Client();
$client = new Google_Client();
$client->setAuthConfigFile($clientKeyJson);
$client->setRedirectUri($redirectUrl.'/oauth2callback.php');
$client->addScope('https://spreadsheets.google.com/feeds');
$client->addScope('https://www.googleapis.com/auth/drive');
$client->addScope('https://www.googleapis.com/auth/drive.file');
$client->addScope('https://www.googleapis.com/auth/drive.readonly');
$client->addScope('https://www.googleapis.com/auth/drive.metadata.readonly');
$client->addScope('https://www.googleapis.com/auth/drive.appdata');
$client->addScope('https://www.googleapis.com/auth/drive.apps.readonly');

if (!isset($_GET['code'])) {
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/MyPHPDrive';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}