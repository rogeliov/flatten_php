<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<?php


require 'vendor/autoload.php';
require_once 'propias/drive.php';
require_once 'propias/check_errores.php';

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;


//Variables globales para OAuth 2.0
$clientId = '166812253810-gg40f3a5e94blpb3k1263knjb7jvcnrp.apps.googleusercontent.com';
$clientSecret = 'T1tkHmK-lsO_EV3xYEFdjQSv';
$clientKeyJson = 'local-key.json';
$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/MyPHPDrive/';

//Variables globales para el uso de spreadsheets
$presupuestos_folder='0B9IF70UV9P0nUGdqakktTFNIb3c';
  
session_start();
$client = new Google_Client();
 $client->setAuthConfigFile($clientKeyJson);

$client->addScope('https://spreadsheets.google.com/feeds');
$client->addScope('https://www.googleapis.com/auth/drive');
$client->addScope('https://www.googleapis.com/auth/drive.file');
$client->addScope('https://www.googleapis.com/auth/drive.readonly');
$client->addScope('https://www.googleapis.com/auth/drive.metadata.readonly');
$client->addScope('https://www.googleapis.com/auth/drive.appdata');
$client->addScope('https://www.googleapis.com/auth/drive.apps.readonly');

//Si ya tenemos el token de autenticación
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	$client->setAccessToken($_SESSION['access_token']);
	$service = new Google_Service_Drive($client);
	$accessToken = json_decode($client->getAccessToken(), true);
	
	
	
	//Por si el token expiro voy a obtener uno nuevo
	if($client -> isAccessTokenExpired()){
			header('Location: ' . filter_var($redirect_uri.'oauth2callback.php', FILTER_SANITIZE_URL));
    }
	
	//Para sacar los archivos de Drive
	$ids_archivos = makeArrayIDsWithFilesInFolder($service,$presupuestos_folder);
	$archivos=array();
	
	//Reviso los archivos y formo un array solo con los spreadsheets
	foreach($ids_archivos as $archivo){
		$data = getFile($service, $archivo);
		if($data['mime_type']=='application/vnd.google-apps.spreadsheet'){
			$archivos[] = $data;
		}
	}
	$serviceRequest = new DefaultServiceRequest($accessToken['access_token']);
	ServiceRequestFactory::setInstance($serviceRequest);
	
	//Inicializo el array final con un indice para contarm las filas finales
	$indice_general_salida=0;
	$array_salida = array();
	
	//Loop donde se revisa cada uno de los archivos encontrados en la carpeta de presupuestos
	print_online ("Leyendo presupuestos...<br/>");
	
	//Banderas para los errores
	$contador_errores=0;
	
	$indice_letra='A';
	foreach($archivos as $file){
		print_online ("Leyendo el documento: ". $file['nombre']. "");
		
		$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
		$spreadsheetFeed = $spreadsheetService->getSpreadsheetById($file['id']); 
		$worksheetFeed = $spreadsheetFeed->getWorksheets();
		$array_pestanas = $worksheetFeed->getAllTitle();
		foreach($array_pestanas as $pestana){
			$worksheet = $worksheetFeed->getByTitle($pestana);
			$cellsheet = $worksheet->getCellFeed();
			
			//Banderas para los errores
			$flag_noes_presupuesto=0;

			print_online ("\t Hoja: ". $pestana);
			$fila_inicial = get_firstRowWithValue(10,$cellsheet);
			//CHECK: Error la hoja no tiene valores en las primeras 10 filas
			if($fila_inicial <= 0){
				print_online ("\t\tERROR: La hoja posiblemente no contiene un presupuesto");
				$flag_noes_presupuesto=1;
				$contador_errores++;
			}
			//Continuo con el proceso de análisis del documento
			else{
				//Ahora necesito encontrar la primera columna donde hay un valor de fecha valido
				$columna_fecha = get_firstColumnWithDate($fila_inicial,30,$cellsheet);
				if($columna_fecha <= 0){
					print_online("\t\t\t\tERROR: No se encontró una  celda con una fecha valida (mm/dd/AAAA) para comenzar el análisis");
					$contador_errores++;
				}
				
			}
			if(!$flag_noes_presupuesto){
				//Ahora busco la columna con el encabezado "Centro de Costos..."
				$columna_centro_costo=get_firstColumnCentroCosto($fila_inicial,$columna_fecha,$cellsheet);
				//Ahora busco la columna con el encabezado "Descripcion..."
				$columna_descripcion=get_firstColumnDescripcion($fila_inicial,$columna_fecha,$cellsheet);
				
			}
			
			//Si es un posible presupuesto comienzo a sacar la info de la hoja y empiezo a armar el array final
			//Tengo los valores para $fila_inicial, $columna_centro_costo, $columna_fecha y con ellos comienzo a barrer todas las filas
			if($fila_inicial != 0 && $columna_centro_costo !=0 && $columna_fecha != 0 && $columna_descripcion !=0){
				//Contare el total de columnas con fechas a partir de la $columna_fecha hasta que exista que no tenga formato de fecha
				//print_online("\t\t\t\tI: ".$fila_inicial . " | F: ". $columna_fecha." | C:".$columna_centro_costo. " | D: ".$columna_descripcion);
				
				$array_fechas = get_arrayFechas($fila_inicial,$columna_fecha,$cellsheet);
				//print_r($array_fechas);
				
				
				//Comienzo con la extracción de datos
				
				$cont_filas = $fila_inicial+1;
				do{
					//Temporal con la cadena de PROYECTO/PRODUCTO/CUENTA/RUBRO
					$celda_cppr = $cellsheet->getCell($cont_filas,$columna_centro_costo);
					//Temporal con la descripción de la fila
					$celda_descripcion = $cellsheet->getCell($cont_filas,$columna_descripcion);
					
					if($celda_cppr && $celda_descripcion){
						$cppr = $celda_cppr->getContent(); 
						$array_cppr = explode("/", $cppr);
						$descripcion_value = sanear_string($celda_descripcion->getContent());
						
						foreach($array_fechas as $key_f => $fecha){
							//Extraigo el datetime de la fecha 
							//print_online($fecha);
							$date_fecha = date_create_from_format('m/d/Y', $fecha);
							//Temporal con el monto usado en la fecha indicada
							//print_online($cont_filas .", ".$key_f);
							$celda_monto_fecha = $cellsheet->getCell($cont_filas,(int)$key_f);
							//Reviso si se creo por que si no se crea no contiene algún valor
							$monto_fecha="";
							if($celda_monto_fecha)
								$monto_fecha = get_numeric($celda_monto_fecha->getContent());

							
							//Descarto los valores en 0 o similares
							if($monto_fecha!="0" && $monto_fecha!="" && $monto_fecha!="0.00" && $monto_fecha!="FALSE" &&  $monto_fecha!="0,00"){
								//Asigno los valores anteriores
								//print_r($array_cppr);
								$array_salida[$indice_general_salida]["type"]='BUDGET';
								$array_salida[$indice_general_salida]["i"]=$indice_letra.'-'.$indice_general_salida; //Se auto generar usando desde la A en adelante
								$array_salida[$indice_general_salida]["EMPLOYEE"]='';
								$array_salida[$indice_general_salida]["Supplier"]='';
								$array_salida[$indice_general_salida]["cuenta"]=$array_cppr[2];
								$array_salida[$indice_general_salida]["proyecto"]=$array_cppr[0];
								$array_salida[$indice_general_salida]["producto"]=$array_cppr[1];
								$array_salida[$indice_general_salida]["rubro"]=$array_cppr[3];
								$array_salida[$indice_general_salida]["items"]=strtoupper($descripcion_value);
								$array_salida[$indice_general_salida]["Currency"]='';
								$array_salida[$indice_general_salida]["Net amount"]='';
								$array_salida[$indice_general_salida]["Tax amount"]='';
								$array_salida[$indice_general_salida]["Gross amount"]=$monto_fecha;
								$array_salida[$indice_general_salida]["state"]='0-BUDGET';
								$array_salida[$indice_general_salida]["Asked at"]=(string)date_format($date_fecha, "d/m/Y");;
								$array_salida[$indice_general_salida]["mes"]=(string)date_format($date_fecha, "m/Y");
								$array_salida[$indice_general_salida]["semana"]=(string)date_format($date_fecha, "W-y");
								$array_salida[$indice_general_salida]["notes"]='';
								$array_salida[$indice_general_salida]["additional info"]='';
								$array_salida[$indice_general_salida]["link to request"]='';
								
								$indice_general_salida++;
							}
						}
					}
					$cont_filas++;
					
				}while($celda_cppr);
				
			}
			
			
			/*
			foreach($array_salida as $array){
				print_r($array);
				print "<br/>";
				print "<br/>";
			}
			*/
			
			
			
		}
		$indice_letra++;
		//Creo el csv de salida
			$output = fopen('tmp_files/flatten.csv', 'w');

			//Mando los encabezados
			fputcsv($output, array('TYPE','ID','EMPLOYEE','Supplier','Cuenta','Proyecto','Producto','Rubro','Descripcion','Currency','Net amount','Tax amount','Gross amount','State','Asked at','Mes','Semana','Notes','Additional info','Link to the request'));
			foreach($array_salida as $array){
				fputcsv($output, $array);
			}
			
			fclose($output);
			print_online("TERMINADO..");
	}
	
}
//Si no tenemos el token lo vamos a pedir 
else{
	$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/MyPHPDrive/oauth2callback.php';
	header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
  
?>

