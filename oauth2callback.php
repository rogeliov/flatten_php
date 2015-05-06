<?php
session_start();
require_once 'config-token.php';

$client = new Google_Client();
$client->setAuthConfigFile($clientKeyJson);
$client->setRedirectUri($redirect_general.'oauth2callback.php');

$client->setScopes($scopes_google);

if (!isset($_GET['code'])) {
	$auth_url = $client->createAuthUrl();
	header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
	$client->authenticate($_GET['code']);
	$_SESSION['access_token'] = $client->getAccessToken();
	header('Location: ' . filter_var($redirect_general, FILTER_SANITIZE_URL));
}