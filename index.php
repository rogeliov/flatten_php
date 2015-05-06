<?php
session_start();
require_once 'config-token.php';

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;

//Si ya tenemos el token no redireccionamos a la vista-principal.php
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	header('Location: ' . filter_var($redirect_uri."inicio.php", FILTER_SANITIZE_URL));
	
}
//Si no tenemos token invocamos oauth2callback para crearlo
else{
	header('Location: ' . filter_var($redirect_uri."oauth2callback.php", FILTER_SANITIZE_URL));
}
?>