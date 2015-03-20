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
			//print_online("\t\t\tFila: ".$fila_inicial. " | Columna: ".$columna_fecha);
			//Si es un posible presupuesto comienzo a sacar la info de la hoja y empiezo a armar el array final
			if(!$flag_noes_presupuesto){
				
			}
			
			/*if($pestana!='resumen total' && $pestana!='Resumen' && $pestana!='Movi-Centro' && $pestana!='Presupuesto Sistemas' &&  $pestana!='Presupuesto comercial' && $pestana!='Presupuesto RH' && $pestana!='Presupuesto Administrativo' && $pestana!='Presupuesto Operativo' && $pestana!='Presupuesto Dirección'){
				$worksheet = $worksheetFeed->getByTitle($pestana);
				$cellsheet = $worksheet->getCellFeed();
				
				//Saco la clave de la celda correspondiente
				//TODO: Verificar si realmente siempre todos los presupuestos tienen el contenido en la Fila 6 Columna 2
				$fila_inicial = 6;
				$col_cppr = 2;
				$col_des = 9;
				
				//Contare el total de columnas con fechas a partir de la U = 21 hasta que exista una celda vacia
				$fila_fecha = 5;
				$col_ini_fecha  = 21;
				$flag_tope_fecha=1;
				$total_fechas=0;
				$array_fechas=array();
				do{
					$celda_fecha = $cellsheet->getCell($fila_fecha,$col_ini_fecha);
					if($celda_fecha){
						$array_fechas[$col_ini_fecha] = $celda_fecha->getContent();
						$total_fechas++;
					}
					else{
						$flag_tope_fecha=0;
					}					
					$col_ini_fecha++;
				}while($flag_tope_fecha);
				
				//TODO: Comprobar que todos los valores sean fechas validas
				//print_r($array_fechas);
				//print "<br/>";
				
				do{
					//Temporal con la cadena de PROYECTO/PRODUCTO/CUENTA/RUBRO
					$celda_cppr = $cellsheet->getCell($fila_inicial,$col_cppr);
					//Temporal con la descripción de la fila
					$celda_descripcion = $cellsheet->getCell($fila_inicial,$col_des);
				
					if($celda_cppr && $celda_descripcion){
						$cppr = $celda_cppr->getContent(); 
						$array_cppr = explode("/", $cppr);
						$descripcion_value = $celda_descripcion->getContent();
						foreach($array_fechas as $key_f => $fecha){
							//Extraigo el datetime de la fecha extraida
							$date_fecha = date_create_from_format('m/d/Y', $fecha);
							//Temporal con el monto usado en la fecha indicada
							$celda_monto_fecha = $cellsheet->getCell($fila_inicial,(int)$key_f);
							$monto_fecha = $celda_monto_fecha->getContent();
							//TODO: Revisar que sea un flotante valido
							
							//Descarto los valores en 0 o similares
							if($monto_fecha!="0" && $monto_fecha!="" && $monto_fecha!="0.00" && $monto_fecha!="FALSE" &&  $monto_fecha!="0,00"){
								//Asigno los valores anteriores
								$array_salida[$indice_general_salida]["type"]='BUDGET';
								$array_salida[$indice_general_salida]["i"]='TT-'.$indice_general_salida; //TODO: Auto generarlos de momento dejo fijo
								$array_salida[$indice_general_salida]["proyecto"]=$array_cppr[0];
								$array_salida[$indice_general_salida]["producto"]=$array_cppr[1];
								$array_salida[$indice_general_salida]["cuenta"]=$array_cppr[2];
								$array_salida[$indice_general_salida]["rubro"]=$array_cppr[3];
								$array_salida[$indice_general_salida]["items"]=strtoupper($descripcion_value);
								$array_salida[$indice_general_salida]["Gross amount"]=get_numeric($monto_fecha);
								$array_salida[$indice_general_salida]["state"]='0-BUDGET';
								$array_salida[$indice_general_salida]["date"]=date_format($date_fecha, 'd-m-Y');;
								$array_salida[$indice_general_salida]["mes"]=strtolower(date_format($date_fecha, 'M-y'));
								$array_salida[$indice_general_salida]["semana"]=date_format($date_fecha, 'W');
								$array_salida[$indice_general_salida]["notes"]='';
								$array_salida[$indice_general_salida]["additional info"]='';
								$array_salida[$indice_general_salida]["link to request"]='';
								$array_salida[$indice_general_salida]["TYPE"]='';
								$array_salida[$indice_general_salida]["State"]='';
								$array_salida[$indice_general_salida]["ID"]='';
								$array_salida[$indice_general_salida]["EMPLOYEE"]='';
								$array_salida[$indice_general_salida]["Supplier"]='';
								$array_salida[$indice_general_salida]["Items"]='';
								$array_salida[$indice_general_salida]["Currency"]='MXN';
								$array_salida[$indice_general_salida]["Net amount"]='';
								$array_salida[$indice_general_salida]["Tax amount"]='';
								$array_salida[$indice_general_salida]["Notes"]='';
								$array_salida[$indice_general_salida]["Additional info"]='';
								$array_salida[$indice_general_salida]["Link to the request"]='';
							
								$indice_general_salida++;
							}
						}
					}
					$fila_inicial++;
				}while($celda_cppr);
			}
			*/
		}
	}
	/*
	foreach($array_salida as $array){
		print_r($array);
		print "<br/>";
		print "<br/>";
	}
	*/
	
	
	
	
	/*
	$listFeed = $worksheet->getListFeed();
	foreach ($listFeed->getEntries() as $entry) {
				$values = $entry->getValues();
				print_r($values);
				print "<br/>";
			}
			*/
	
	/*LISTADO DE TODO EL CONTENIDO
	foreach($archivos as $file){
		//Presento temporalmente el contenido
		print "ID: ".$file['id']."<br/>";
		print "Nombre: ".$file['nombre']."<br/>";
		print "Tipo: ".$file['mime_type']."<br/>";
		print "Descripción: ".$file['descripcion']."<br/>";
		print "Pestanas: ";
		
		//Traigo el Spreed correspondiente al ID
		$spreadsheetFeed = $spreadsheetService->getSpreadsheetById($file['id']); 	
		//Ahora me quedo con toda la estructura
		$worksheetFeed = $spreadsheetFeed->getWorksheets();
		//Ahora traigo el nombre de todas las pestanas
		$array_pestanas = $worksheetFeed->getAllTitle();
		print_r($array_pestanas);
		print "<br/>";
		//Presento el contenido dentro de cada pestana
		foreach($array_pestanas as $pestana){
			print "PESTANA: ". $pestana."<br/>";
			$worksheet = $worksheetFeed->getByTitle($pestana);
			$listFeed = $worksheet->getListFeed();	
			foreach ($listFeed->getEntries() as $entry) {
				$values = $entry->getValues();
				print_r($values);
				print "<br/>";
			}
			print "<br/>";
		}
		
		print "<br/>";
		print "<br/>";
	}
	*/
	
	
	
	
	/*
	
	$listFeed = $worksheet->getListFeed();
	
	foreach ($listFeed->getEntries() as $entry) {
		$values = $entry->getValues();
		print_r($values);
	}
	*/
	
	
}
//Si no tenemos el token lo vamos a pedir 
else{
	$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/MyPHPDrive/oauth2callback.php';
	header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
  
?>

