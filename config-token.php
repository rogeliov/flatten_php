<?php
//Bibliotecas usadas
require 'vendor/autoload.php';
require_once 'propias/drive.php';
require_once 'propias/check_errores.php';
//session_start();


//Variables globales para OAuth 2.0
//LOCAL DESARROLLO

$clientId = '166812253810-gg40f3a5e94blpb3k1263knjb7jvcnrp.apps.googleusercontent.com';
$clientSecret = 'T1tkHmK-lsO_EV3xYEFdjQSv';
$clientKeyJson = 'local-key.json';
$redirect_general = 'http://' . $_SERVER['HTTP_HOST'] . '/MyPHPDrive/';


//Produccion
/*
$clientId = '166812253810-2v6gbrgeja96scapnio4qjitu4mm71t5.apps.googleusercontent.com';
$clientSecret = 'ja0_53pvbOjkyK4FzsGO2hDX';
$clientKeyJson = 'produccion.json';
$redirect_general = 'http://' . $_SERVER['HTTP_HOST'] . '/MyPHPDrive/';
*/
//Variables globales para el uso de spreadsheets
$presupuestos_folder= '0B9IF70UV9P0nUGdqakktTFNIb3c';
$ingresos_archivo	= '1_mbgfrKZYgAyht0fQA8CDekoAUjUbh8yQObD2ZtL99w';
$reembolsos_archivo = '1WLum8VEjfDvaqJm4EWlLPl46EbWub9Q17x7Z5POGr5s';

$url_gastos = "https://hormiga.turbinehq.com/employees/7442/external_csv.csv?demand_kind=expenses&demand_state%5B%5D=filtering&demand_state%5B%5D=first&demand_state%5B%5D=pending&demand_state%5B%5D=rejected&demand_state%5B%5D=payment&demand_state%5B%5D=completed&demand_state%5B%5D=second&demand_state%5B%5D=filtering&token=cc54d675e01150329549399ef74042981c03c7d0";
$url_compras = "https://hormiga.turbinehq.com/employees/7442/external_csv.csv?demand_kind=purchases&token=a1159cc06707b68bbe42f89142b6e0fb3c792eb6";

//Variables para el uso del token de la autenticación
$scopes_google=array(
	'https://spreadsheets.google.com/feeds',
	'https://www.googleapis.com/auth/drive',
	'https://www.googleapis.com/auth/drive.file',
	'https://www.googleapis.com/auth/drive.readonly',
	'https://www.googleapis.com/auth/drive.metadata.readonly',
	'https://www.googleapis.com/auth/drive.appdata',
	'https://www.googleapis.com/auth/drive.apps.readonly',
	);

?>

