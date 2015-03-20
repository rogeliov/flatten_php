<?php
require 'vendor/autoload.php';

/*
Función que forma un Array con todos los ID de los archivos encontrados dentro de un folder en DRIVE
@IN Servicio de autentificado de drive 
	String del ID para el folder
@OUT Array con los ID de todos los archivos encontrados
*/
function makeArrayIDsWithFilesInFolder($service, $folderId) {
	$pageToken = NULL;
	$array_ids = array();
	do {
		try {
			$parameters = array();
			if ($pageToken) {
			$parameters['pageToken'] = $pageToken;
			}
			$children = $service->children->listChildren($folderId, $parameters);
			foreach ($children->getItems() as $child) {
			$array_ids[] = $child->getId();
			}
			$pageToken = $children->getNextPageToken();
		} catch (Exception $e) {
			print "An error occurred: " . $e->getMessage();
			$pageToken = NULL;
		}
	} while ($pageToken);

	return $array_ids;
}

/*
Función extraer descompner el XML de los archivos de DRIVE en un array
@IN Servicio de autentificado de drive
	String del ID para la identificación del arvhivo
@OUT Array del archivo mostrado
*/
function getFile($service, $fileId) {
	$array_archivo = array();
	try {
		$file = $service->files->get($fileId);
		//Si encuentra el archivo lo divido y guardo en un array
		$array_archivo['id'] = $fileId;
		$array_archivo['nombre'] = $file->getTitle();
		$array_archivo['mime_type'] = $file->getMimeType();
		$array_archivo['descripcion'] = $file->getDescription();
	} catch (Exception $e) {
		echo "An error occurred: " . $e->getMessage();
	}
	return $array_archivo;
}

/*
Función para imprimir el array de archivos que vienen de drive
@IN Array con archivos 
@OUT
*/
function print_arrayArchivos($archivos){
	foreach($archivos as $file){
		print "ID: ".$file['id']."<br/>";
		print "Nombre: ".$file['nombre']."<br/>";
		print "Tipo: ".$file['mime_type']."<br/>";
		print "Descripción: ".$file['descripcion']."<br/>";
		print "<br/>";
	}
}

/*
Función para buscar en las primeras N filas indicadas algún var_dump
@IN integer con el maximo numero de filas a buscar
	cellsheetFeed proveniente de drive
@OUT integer con el numero de la fila donde encontro el primer valor
*/
function get_firstRowWithValue($max_fila ,$cellsheet){
	$flag_busqueda=1;
	$fila_inicial=0;
	do{
		$fila_inicial++;
		$celda_inicial = $cellsheet->getCell($fila_inicial,1);
		if($celda_inicial){
			//print_online("Contenido: ".$celda_inicial->getContent()." | ");
			$flag_busqueda=0;
		}
		//print_online("I: ".$fila_inicial." | F: ".$flag_busqueda ."<br/>");
	}while($flag_busqueda && $fila_inicial < $max_fila);
	if($fila_inicial == $max_fila){
		return 0;
	}
	return $fila_inicial;
	//print_online("Pestana: ".$pestana." | FILA: ".$fila_inicial."<br/>");
}


/*
Función para buscar la primer columna con una posible fecha para empezar a armar el documento
@IN integer con la fila donde se buscara la columna
	intenger numero maximo de columnas para buscar	
	cellsheetFeed todo lo que viene del api de DRIVE con el contenido de las celdas en la hoja
@OUT integer con el numero de la fila donde encontro el primer valor
*/
function get_firstColumnWithDate($fila_inicial, $max_columna, $cellsheet){

	//$max_columna=30;
	$columna_actual=0;
	$flag_busqueda=1;
	do{
		$columna_actual++;
		$celda_temporal = $cellsheet->getCell($fila_inicial,$columna_actual);
		if($celda_temporal){
			$contenido_celda = $celda_temporal->getContent();
			$posible_fecha = date_create_from_format('m/j/Y', $contenido_celda);
			//Si no es una fecha en el formato establecido regresa nulo y por lo tanto descartamos la fila
			if($posible_fecha){
				$flag_busqueda=0;
			}
		}
	}while($flag_busqueda && $columna_actual < $max_columna);
	if($columna_actual==$max_columna){
		return 0;
	}
	return $columna_actual;
}	
  
?>