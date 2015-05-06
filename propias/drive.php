<?php
require_once 'config-token.php';
//session_start();
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;

function download_turbine($url_compras,$url_gastos){
	$compras_data = file_get_contents($url_compras);
	file_put_contents("tmp_turbine/compras.csv",$compras_data);
	
	$gastos_data = file_get_contents($url_gastos);
	file_put_contents("tmp_turbine/gastos.csv",$gastos_data);
}

function get_flujo_avance($client,$url_compras,$url_gastos,$ingresos_archivo,$presupuestos_folder,$reembolsos_archivo){
	$_SESSION['presupuesto_mensajes']=array();
	$_SESSION['array_principal'] = array();
	$_SESSION['contador_errores'] = 0;
	
	$array_avance=array();
	$array_flujo=array();
	
	download_turbine($url_compras,$url_gastos);
	
	$_SESSION['presupuesto_mensajes'] = array_merge($_SESSION['presupuesto_mensajes'],array("GENERACION DEL FLUJO"));
	
	$array_flujo_gastos = get_gastos_from_csv('flujo');
	$array_flujo_compras = get_compras_from_csv('flujo');
	$array_flujo_reembolsos = get_reembolsos_from_drive($client,$reembolsos_archivo,'flujo');
	$array_flujo_ingresos = get_ingresos_from_drive($client,$ingresos_archivo);
	$array_flujo_presupuestos = get_presupuestos_from_drive($client,$presupuestos_folder);
	
	$_SESSION['presupuesto_mensajes'] = array_merge($_SESSION['presupuesto_mensajes'],array("GENERACION DEL AVANCE"));
	$array_avance_gastos = get_gastos_from_csv('avance');
	$array_avance_compras = get_compras_from_csv('avance');
	$array_avance_reembolsos = get_reembolsos_from_drive($client,$reembolsos_archivo,'avance');
	$array_avance_presupuestos = get_presupuestos_from_drive($client,$presupuestos_folder);
	
	
	$array_flujo=array_merge($array_flujo_gastos, $array_flujo_compras, $array_flujo_reembolsos, $array_flujo_ingresos,$array_flujo_presupuestos);
	$array_avance=array_merge($array_avance_gastos, $array_avance_compras, $array_avance_reembolsos,$array_avance_presupuestos);
	
	create_excel($array_flujo,$array_avance);	
}


function get_compras_from_csv($tipo_salida){
	$file = fopen("tmp_turbine/compras.csv","r");
	
	$array_mensajes_finales[] = "\tLeyendo documento: Compras";
	
	$contador_filas=0;
	$indice_general_salida=0;
	while(! feof($file)){
		$datos_csv = fgetcsv($file);
		
		//Ignoro las filas que tienen menos de 10 columnas
		if(count($datos_csv)>10){
			//Ignoro los encabezados en la fila 0
			if($contador_filas>0){
				//print_r($datos_csv);
				//Number, Employee, Cost centre, Supplier, Items, Currency, Net amount, Tax amount, Gross amount, State, Asked at, Notes, Additional info, Link to the request
				if(isset($datos_csv[2])){
					$array_cppr = explode("/", strtoupper($datos_csv[2]));
				}
				else{
					
					$array_mensajes_finales[] = "\tNo hay centro de costos en la fila".($contador_filas+1);
				}
				$estado="";
				//echo $datos_csv[9]. " | ";
				if(isset($datos_csv[9])){
					if($tipo_salida=='flujo'){
						switch(trim($datos_csv[9])){
							case "Budget";$estado="0-BUDGET";break;
							case "Pendiente";$estado="1-PENDIENTE";break;
							case "Solicitud Revisada";$estado="2-SOLICITUD REVISADA";break;
							case "Aprobado por Direccion";$estado="3-APROBADO POR DIRRECCION";break;
							case "Recibido";$estado="4-RECIBIDO";break;
							case "Aprobado para pagar";$estado="5-APROBADO PARA PAGAR";break;
							case "Pagado y Completado";$estado="6-PAGADO Y COMPLETADO";break;
							case "Rejected";$estado="7-REJECTED";break;
							//'Budget','Pendiente', 'Solicitud Revisada','Aprobado por Direccion', 'Recibido', 'Aprobado para pagar','Pagado y Completado','Rejected'
						}
					}
					if($tipo_salida=='avance'){
						switch(trim($datos_csv[9])){
							case "Budget";$estado="0-BUDGET";break;
							case "Solicitud Revisada";$estado="1-NO RECIBIDO/NO PAGADO";break;
							case "Aprobado por Direccion";$estado="1-NO RECIBIDO/NO PAGADO";break;
							case "Recibido";$estado="2-POR PAGAR";break;
							case "Aprobado para pagar";$estado="1-NO RECIBIDO/NO PAGADO";break;
							case "Pagado y Completado";$estado="3-COMPLETADO";break;
							//'Budget','Pendiente', 'Solicitud Revisada','Aprobado por Direccion', 'Recibido', 'Aprobado para pagar','Pagado y Completado','Rejected'
						}
					}
				}
				else{
					$array_mensajes_finales[] = "\tERROR: Fila-".($contador_filas+1)." No hay estado definido";
				}
				
				//Verifico la fecha en la $datos_csv[12] que es la aditional info
				$string_fecha_12 = check_es_fecha($datos_csv[12]);
				$string_fecha_10 = check_es_fecha($datos_csv[10]);
				
				$string_fecha = "";
				if($string_fecha_12){
					$string_fecha = $string_fecha_12;
				}
				else{
					//$array_mensajes_finales[] = "\tWARNING: Fila-".($contador_filas+1)." No se pudo extraer la fecha de Additional Info. Utilizando la fecha de creación de la orden en la ". $datos_csv[0];
					if($string_fecha_10){
						$string_fecha = $string_fecha_10;
					}
					else{
						$array_mensajes_finales[] = "\tERROR: Fila-".($contador_filas+1)." No hay fecha valida en Aditional Info o Asked At en la ". $datos_csv[0];						
						contar_errores();
					}
				}
				$date_fecha = date_create_from_format('d/m/Y', $string_fecha);
				if($tipo_salida=='flujo'){
					$date_now = date_create();
					if($date_fecha<$date_now){
						$date_fecha = $date_now;
					}
				}
				
				$date_askedat = date_format($date_fecha, "d/m/Y");
				$date_mes = date_format($date_fecha, "m/Y");
				$date_semana = date_format($date_fecha, "W-y");
				
				$proyecto = isset($array_cppr[0]) ? $array_cppr[0] : "" ;
				$producto = isset($array_cppr[1]) ? $array_cppr[1] : "" ; 
				$rubro = isset($array_cppr[3]) ? $array_cppr[3] : "" ;
				$descripcion =  $datos_csv[4];
				
				if($proyecto=="" || $producto=="" || $rubro==""){
				
				
					if($datos_csv[9]!='Rejected'){
						$array_mensajes_finales[] = "\tERROR: Fila-".($contador_filas+1)." No puede quedar vacío el [Proyecto|Producto|Rubro]". $datos_csv[0];
						contar_errores();
					}
				}
				if($datos_csv[9]!='Rejected'){
					$array_salida[$indice_general_salida]["type"]='REAL';
					$array_salida[$indice_general_salida]["i"]=strtoupper($datos_csv[0]);
					$array_salida[$indice_general_salida]["EMPLOYEE"]=strtoupper($datos_csv[1]);
					$array_salida[$indice_general_salida]["Supplier"]=strtoupper($datos_csv[3]);
					$array_salida[$indice_general_salida]["cuenta"]=isset($array_cppr[2]) ? $array_cppr[2] : "" ;
					$array_salida[$indice_general_salida]["proyecto"]=$proyecto;
					$array_salida[$indice_general_salida]["producto"]=$producto;
					$array_salida[$indice_general_salida]["rubro"]=$rubro;
					$array_salida[$indice_general_salida]["items"]=$descripcion;
					$array_salida[$indice_general_salida]["Currency"]=strtoupper($datos_csv[4]);
					$array_salida[$indice_general_salida]["Net amount"]=number_format($datos_csv[6], 2, '.', '');
					$array_salida[$indice_general_salida]["Tax amount"]=number_format($datos_csv[7], 2, '.', '');
					$array_salida[$indice_general_salida]["Gross amount"]=number_format($datos_csv[8], 2, '.', '');
					$array_salida[$indice_general_salida]["state"]=$estado;
					$array_salida[$indice_general_salida]["Asked at"]=$date_askedat;
					$array_salida[$indice_general_salida]["mes"]=$date_mes;
					$array_salida[$indice_general_salida]["semana"]=$date_semana;
					$array_salida[$indice_general_salida]["notes"]=strtoupper($datos_csv[11]);
					$array_salida[$indice_general_salida]["additional info"]=strtoupper($datos_csv[12]);
					$array_salida[$indice_general_salida]["link to request"]=utf8_decode(" ".$datos_csv[13]);
					
					$indice_general_salida++;
				}
			}
		}
		$contador_filas++;
	}
	$_SESSION['presupuesto_mensajes'] = array_merge($_SESSION['presupuesto_mensajes'],$array_mensajes_finales);
	//print_r($array_salida);
	return $array_salida;
	fclose($file);
}


//////////////////////////////////////////
function get_gastos_from_csv($tipo_salida){
	$file = fopen("tmp_turbine/gastos.csv","r");
	
	$array_mensajes_finales[] = "\tLeyendo documento: GASTOS";
	
	$contador_filas=0;
	$indice_general_salida=0;
	while(! feof($file)){
		$datos_csv = fgetcsv($file);
		
		//Ignoro las filas que tienen menos de 10 columnas
		if(count($datos_csv)>10){
			//Ignoro los encabezados en la fila 0
			if($contador_filas>0){
				//print_r($datos_csv);
				//Number, Employee, Cost centre, Supplier, Items, Currency, Net amount, Tax amount, Gross amount, State, Asked at, Notes, Additional info, Link to the request
				if(isset($datos_csv[2])){
					$array_cppr = explode("/", strtoupper($datos_csv[2]));
				}
				else{
					if($datos_csv[9]!='Rejected')
						$array_mensajes_finales[] = "\tNo hay centro de costos en la fila".($contador_filas+1);
				}
				//'Budget','Pendiente', 'Solicitud Revisada','Aprobado para pagar', 'Pagado','Comprobado y Completado','Rejected'
				$estado="";
				
				
				if(isset($datos_csv[9])){
					if($tipo_salida=='flujo'){
						switch($datos_csv[9]){
							case "Budget";$estado="0-BUDGET";break;
							case "Pendiente";$estado="1-PENDIENTE";break;
							case "Solicitud Revisada";$estado="2-SOLICITUD REVISADA";break;
							case "Aprobado para pagar";$estado="3-APROBADO PARA PAGAR";break;
							case "Pagado";$estado="4-PAGADO";break;
							case "Comprobado y Completado";$estado="5-COMPROBADO Y COMPLETADO";break;
							case "Rejected";$estado="6-REJECTED";break;
						}
					}
					if($tipo_salida=='avance'){
						switch($datos_csv[9]){
							case "Budget";$estado="0-BUDGET";break;
							case "Solicitud Revisada";$estado="1-NO RECIBIDO/NO PAGADO";break;
							case "Aprobado para pagar";$estado="1-NO RECIBIDO/NO PAGADO";break;
							case "Pagado";$estado="2-POR COMPROBAR";break;
							case "Comprobado y Completado";$estado="3-COMPLETADO";break;
						}
					}
				}
				else{
					$array_mensajes_finales[] = "\tERROR: Fila-".($contador_filas+1)." No hay estado definido";
				}
				
				//Verifico la fecha en la $datos_csv[12] que es la aditional info
				$string_fecha_12 = check_es_fecha($datos_csv[12]);
				$string_fecha_10 = check_es_fecha($datos_csv[10]);
				
				$string_fecha="";
				if($string_fecha_12!==0){
					$string_fecha = $string_fecha_12;
				}
				else{
					//$array_mensajes_finales[] = "\tWARNING: Fila-".($contador_filas+1)." No se pudo extraer la fecha de Additional Info. Utilizando la fecha de creación de la orden";
					if($string_fecha_10!==0){
						$string_fecha = $string_fecha_10;
					}
					else{
						$array_mensajes_finales[] = "\tERROR: Fila-".($contador_filas+1)." No hay fecha valida en Aditional Info o Asked At";
						contar_errores();
					}
				}

				
				$date_fecha = date_create_from_format('d/m/Y', $string_fecha);
				if($tipo_salida=='flujo'){
					$date_now = date_create();
					if($date_fecha<$date_now){
						$date_fecha = $date_now;
					}
				}
			
				$date_askedat = date_format($date_fecha, "d/m/Y");
				$date_mes = date_format($date_fecha, "m/Y");
				$date_semana = date_format($date_fecha, "W-y");

				$proyecto = isset($array_cppr[0]) ? $array_cppr[0] : "" ;
				$producto = isset($array_cppr[1]) ? $array_cppr[1] : "" ; 
				$rubro = isset($array_cppr[3]) ? $array_cppr[3] : "" ;
				$descripcion =  strtoupper($datos_csv[4]);
				if($proyecto=="" || $producto=="" || $rubro==""){
					if($datos_csv[9]!='Rejected'){
						$array_mensajes_finales[] = "\tERROR: Fila-".($contador_filas+1)." No puede quedar vacío el [Proyecto|Producto|Rubro] en la ". $datos_csv[0];
						contar_errores();
					}
				}

				if($datos_csv[9]!='Rejected'){
					$array_salida[$indice_general_salida]["type"]='REAL';
					$array_salida[$indice_general_salida]["i"]=$datos_csv[0];
					$array_salida[$indice_general_salida]["EMPLOYEE"]=strtoupper($datos_csv[1]);
					$array_salida[$indice_general_salida]["Supplier"]=strtoupper($datos_csv[3]);
					$array_salida[$indice_general_salida]["cuenta"]=isset($array_cppr[2]) ? $array_cppr[2] : "" ;
					$array_salida[$indice_general_salida]["proyecto"]=$proyecto;
					$array_salida[$indice_general_salida]["producto"]=$producto;
					$array_salida[$indice_general_salida]["rubro"]=$rubro;
					$array_salida[$indice_general_salida]["items"]=utf8_decode(" ".$descripcion);
					$array_salida[$indice_general_salida]["Currency"]=strtoupper($datos_csv[4]);
					$array_salida[$indice_general_salida]["Net amount"]=number_format($datos_csv[6], 2, '.', '');
					$array_salida[$indice_general_salida]["Tax amount"]=number_format($datos_csv[7], 2, '.', '');
					$array_salida[$indice_general_salida]["Gross amount"]=number_format($datos_csv[8], 2, '.', '');
					$array_salida[$indice_general_salida]["state"]=$estado;
					$array_salida[$indice_general_salida]["Asked at"]=$date_askedat;
					$array_salida[$indice_general_salida]["mes"]=$date_mes;
					$array_salida[$indice_general_salida]["semana"]=$date_semana;
					$array_salida[$indice_general_salida]["notes"]=strtoupper($datos_csv[11]);
					$array_salida[$indice_general_salida]["additional info"]=strtoupper($datos_csv[12]);
					$array_salida[$indice_general_salida]["link to request"]=utf8_decode(" ".$datos_csv[13]);
					
					$indice_general_salida++;
				}
			}
		}
		$contador_filas++;
	}
	$_SESSION['presupuesto_mensajes'] = array_merge($_SESSION['presupuesto_mensajes'],$array_mensajes_finales);
	
	return $array_salida;
	fclose($file);
}

function get_reembolsos_from_drive($client,$reembolsos_archivo,$tipo_salida){
	$array_salida=array();
	$array_mensajes_finales[]="Leyendo Reembolsos";
	$contador_errores=0;
	$fila_inicial=2;
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		$client->setAccessToken($_SESSION['access_token']);
		$service = new Google_Service_Drive($client);
		$accessToken = json_decode($client->getAccessToken(), true);
		
		$datos_ingresos = getFile($service, $reembolsos_archivo);
		$indice_general_salida=0;
		$serviceRequest = new DefaultServiceRequest($accessToken['access_token']);
		ServiceRequestFactory::setInstance($serviceRequest);
		
		$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
		$spreadsheetFeed = $spreadsheetService->getSpreadsheetById($datos_ingresos['id']); 
		$worksheetFeed = $spreadsheetFeed->getWorksheets();
		
		$array_mensajes_finales[] = "\tLeyendo documento: REEMBOLSOS 2015";
		$array_mensajes_finales[] = "\t\tHoja: Reembolsos reales";
		
		
		$worksheet = $worksheetFeed->getByTitle("reembolsos reales");
		$listFeed = $worksheet->getListFeed();
		foreach ($listFeed->getEntries() as $key => $entry) {
			$values_fila = $entry->getValues();
			$indice_fila = (int)$fila_inicial+(int)$key;
			$estado="";
			switch(strtoupper($values_fila['estado'])){
				case "PAGADO";
					if($tipo_salida=='flujo')
						$estado="4-PAGADO";
					if($tipo_salida=='avance')
						$estado="2-POR COMPROBAR";
					break;
			}
			$date_mes="";
			$date_askedat="";
			$date_semana="";
			$array_cppr = explode("/", strtoupper($values_fila['centrodecosto']));
			$string_fecha = check_es_fecha($values_fila['fechadereembolsosolictuddelrecurso']);
			if($string_fecha){
				$date_fecha = date_create_from_format('d/m/Y', $string_fecha);
				if($tipo_salida=='flujo'){
					$date_now = date_create();
					if($date_fecha<$date_now){
						$date_fecha = $date_now;
					}
				}
				$date_askedat = date_format($date_fecha, "d/m/Y");
				$date_mes = date_format($date_fecha, "m/Y");
				$date_semana = date_format($date_fecha, "W-y");
			}else{
				$array_mensajes_finales[] = "\t\t\tERROR: Formato no valido en la fecha de reembolso de la fila ".$indice_fila ." (dd/mm/AAAA)";
				contador_errores();
			}
			
			$string_fecha_maxima = check_es_fecha($values_fila['fechamáximaenlaquedebecomprobarelusuario']);
			if($string_fecha_maxima){
				$date_aditional = date_create_from_format('d/m/Y',$string_fecha_maxima);
			}
			else{
				//$array_mensajes_finales[] = "\t\t\tWARNING: Fila-".$indice_fila." La columna para la fecha máxima de reembolso esta vacía";
			}
			$net_amount = "";
			$tax_amount = "";
			$gross_amount = "";
			if($values_fila['totaldepositado']!=""){
				$net_amount = check_es_moneda($values_fila['totaldepositado']);
				if($net_amount=="error"){
					$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." El número e Total Depositado no tiene el formato correcto (\$xx,xxx.xx)";
					contar_errores();
				}
			}
			if($values_fila['totalutilizado']){
				$tax_amount = check_es_moneda($values_fila['totalutilizado']);
				if($tax_amount=="error"){
					$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." El número e Total Utilizado no tiene el formato correcto (\$xx,xxx.xx)";
					contar_errores();
				}
			}
			if($values_fila['diferenciadepositadovsutilidado']){
				$gross_amount = check_es_moneda($values_fila['diferenciadepositadovsutilidado']);
				if($gross_amount=="error"){
					$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." El número e Total Diferencia Depositada no tiene el formato correcto (\$xx,xxx.xx)";
					contar_errores();
				}
			}

			$array_salida[$indice_general_salida]["type"]='INGRESO';
			$array_salida[$indice_general_salida]["i"]=$values_fila['pc'];
			$array_salida[$indice_general_salida]["EMPLOYEE"]=strtoupper($values_fila['empleado']);
			$array_salida[$indice_general_salida]["Supplier"]=strtoupper($values_fila['supplier']);
			$array_salida[$indice_general_salida]["cuenta"]=$array_cppr[2];
			$array_salida[$indice_general_salida]["proyecto"]=$array_cppr[0];
			$array_salida[$indice_general_salida]["producto"]=$array_cppr[1];
			$array_salida[$indice_general_salida]["rubro"]=$array_cppr[3];
			$array_salida[$indice_general_salida]["items"]=utf8_decode(strtoupper($values_fila['descripcion']));
			$array_salida[$indice_general_salida]["Currency"]=$values_fila['moneda'];
			$array_salida[$indice_general_salida]["Net amount"]=number_format(-1*get_numeric_limpio($net_amount), 2, '.', '');
			$array_salida[$indice_general_salida]["Tax amount"]=number_format(get_numeric_limpio($tax_amount), 2, '.', '');
			$array_salida[$indice_general_salida]["Gross amount"]=number_format(-1*get_numeric_limpio($gross_amount), 2, '.', '');
			$array_salida[$indice_general_salida]["state"]=$estado;
			$array_salida[$indice_general_salida]["Asked at"]=$date_askedat;
			$array_salida[$indice_general_salida]["mes"]=$date_mes;
			$array_salida[$indice_general_salida]["semana"]=$date_semana;
			$array_salida[$indice_general_salida]["notes"]='';
			$array_salida[$indice_general_salida]["additional info"]=date_format($date_aditional, "d/m/Y");
			$array_salida[$indice_general_salida]["link to request"]=utf8_decode(" ".$values_fila['ligaalaordenoriginal']);
			
			$indice_general_salida++;
		}
		
		if($tipo_salida=='avance'){
			
			$worksheet = $worksheetFeed->getByTitle("reembolsos virtuales");
			$listFeed = $worksheet->getListFeed();
			foreach ($listFeed->getEntries() as $key => $entry) {
				$values_fila = $entry->getValues();
				$indice_fila = (int)$fila_inicial+(int)$key;
				$estado="";
				switch(strtoupper($values_fila['estado'])){
					case "PAGADO";
						if($tipo_salida=='flujo')
							$estado="4-PAGADO";
						if($tipo_salida=='avance')
							$estado="2-POR COMPROBAR";
						break;
				}
				$date_mes="";
				$date_askedat="";
				$date_semana="";
				
					$array_cppr = explode("/", strtoupper($values_fila['centrodecosto']));
				$string_fecha = check_es_fecha($values_fila['fechadeposito']);
				if($string_fecha){
					$date_fecha = date_create_from_format('d/m/Y', $string_fecha);
					$date_askedat = date_format($date_fecha, "d/m/Y");
					$date_mes = date_format($date_fecha, "m/Y");
					$date_semana = date_format($date_fecha, "W-y");
				}else{
					$array_mensajes_finales[] = "\t\t\tERROR: Formato no valido en la fecha de reembolso de la fila ".$indice_fila ." (dd/mm/AAAA)";
					contar_errores();
				}
				
				$string_fecha_maxima = check_es_fecha($values_fila['fechamáximaenlaquedebecomprobarelusuario']);
				if($string_fecha_maxima){
					$date_aditional = date_create_from_format('d/m/Y',$string_fecha_maxima);
				}
				else{
					//$array_mensajes_finales[] = "\t\t\tWARNING: Fila-".$indice_fila." La columna para la fecha máxima de reembolso esta vacía";
				}
				$net_amount = "";
				$tax_amount = "";
				$gross_amount = "";
				if($values_fila['depositado']!=""){
					$net_amount = check_es_moneda($values_fila['depositado']);
					if($net_amount=="error"){
						$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." El número e Total Depositado no tiene el formato correcto (\$xx,xxx.xx)";
						contar_errores();
					}
				}
				if($values_fila['utilizado']){
					$tax_amount = check_es_moneda($values_fila['utilizado']);
					if($tax_amount=="error"){
						$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." El número e Total Utilizado no tiene el formato correcto (\$xx,xxx.xx)";
						contar_errores();
					}
				}
				if($values_fila['diferencia']){
					$gross_amount = check_es_moneda($values_fila['diferencia']);
					if($gross_amount=="error"){
						$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." El número e Total Diferencia Depositada no tiene el formato correcto (\$xx,xxx.xx)";
						contar_errores();
					}
				}
				if($values_fila['centrodecosto']!=""){
					$array_salida[$indice_general_salida]["type"]='INGRESO';
					$array_salida[$indice_general_salida]["i"]=$values_fila['pc'];
					$array_salida[$indice_general_salida]["EMPLOYEE"]=strtoupper($values_fila['empleado']);
					$array_salida[$indice_general_salida]["Supplier"]=strtoupper($values_fila['supplier']);
					$array_salida[$indice_general_salida]["cuenta"]=$array_cppr[2];
					$array_salida[$indice_general_salida]["proyecto"]=$array_cppr[0];
					$array_salida[$indice_general_salida]["producto"]=$array_cppr[1];
					$array_salida[$indice_general_salida]["rubro"]=$array_cppr[3];
					$array_salida[$indice_general_salida]["items"]=utf8_decode(strtoupper($values_fila['descripcion']));
					$array_salida[$indice_general_salida]["Currency"]='MXN';
					$array_salida[$indice_general_salida]["Net amount"]=number_format(-1*get_numeric_limpio($net_amount), 2, '.', '');
					$array_salida[$indice_general_salida]["Tax amount"]=number_format(get_numeric_limpio($tax_amount), 2, '.', '');
					$array_salida[$indice_general_salida]["Gross amount"]=number_format(-1*get_numeric_limpio($gross_amount), 2, '.', '');
					$array_salida[$indice_general_salida]["state"]=$estado;
					$array_salida[$indice_general_salida]["Asked at"]=$date_askedat;
					$array_salida[$indice_general_salida]["mes"]=$date_mes;
					$array_salida[$indice_general_salida]["semana"]=$date_semana;
					$array_salida[$indice_general_salida]["notes"]='';
					$array_salida[$indice_general_salida]["additional info"]=date_format($date_aditional, "d/m/Y");
					$array_salida[$indice_general_salida]["link to request"]=utf8_decode(" ".$values_fila['ligaalaordenoriginal']);
				
				$indice_general_salida++;
				}
				else{
					//$array_mensajes_finales[] = "\t\t\WARNING: Fila-".$indice_fila." El centro de costos esta vacío";
				}
			}
		
		}
		
		$_SESSION['presupuesto_mensajes'] = array_merge($_SESSION['presupuesto_mensajes'],$array_mensajes_finales);
		return $array_salida;
	}
}

function get_ingresos_from_drive($client,$ingresos_archivo){
	$array_mensajes_finales[]="Leyendo Ingresos";
	$contador_errores=0;
	$fila_inicial=1;
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		$client->setAccessToken($_SESSION['access_token']);
		$service = new Google_Service_Drive($client);
		$accessToken = json_decode($client->getAccessToken(), true);
		
		$datos_ingresos = getFile($service, $ingresos_archivo);
		$indice_general_salida=0;
		//print_r($datos_ingresos);
		$serviceRequest = new DefaultServiceRequest($accessToken['access_token']);
		ServiceRequestFactory::setInstance($serviceRequest);
		
		$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
		$spreadsheetFeed = $spreadsheetService->getSpreadsheetById($datos_ingresos['id']); 
		$worksheetFeed = $spreadsheetFeed->getWorksheets();
		
		$array_mensajes_finales[] = "\tLeyendo documento: INGRESOS 2015";
		$array_mensajes_finales[] = "\t\tHoja: INGERSOS 2015";
		$worksheet = $worksheetFeed->getByTitle("INGRESOS 2015");
		$listFeed = $worksheet->getListFeed();
		foreach ($listFeed->getEntries() as $key => $entry) {
			
			$values_fila = $entry->getValues();
			$indice_fila = (int)$fila_inicial+(int)$key+1;
			switch($values_fila['estatus']){
				case "COBRADO";
					$estado="4-COBRADO";
					break;
				case "POR COBRAR";
					$estado="3-POR COBRAR";	
					break;
				case "POR FACTURAR";
					$estado="2-POR FACTURAR";
					break;
				case "POR COBRAR";
					$estado="1-POR CONTRATAR";
					break;
				case "CANCELADA";
					$estado="0-CANCELADA";
				break;
			}
		
			$date_askedat="";
			$date_mes="";
			$date_semana="";
			$string_fecha_maxima = check_es_fecha($values_fila['fechaprobableorealdepago']);
			if($string_fecha_maxima){
				$date_fecha = date_create_from_format('d/m/Y',$string_fecha_maxima);
				$date_askedat = date_format($date_fecha, "d/m/Y");
				$date_mes = date_format($date_fecha, "m/Y");
				$date_semana = date_format($date_fecha, "W-y");
			}
			else{
				$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." Error en el formato de la fecha probable de pago (dd/mm/yyyy)";
				contar_errores();
			}
			
			$string_fecha_facturacion = check_es_fecha($values_fila['fechadefacturacion']);
			if($string_fecha_facturacion){
				$date_fecha_facturacion = date_create_from_format('d/m/Y',$string_fecha_facturacion);
				$date_facturacion = date_format($date_fecha_facturacion, "d/m/Y");
			}
			else{
				//$array_mensajes_finales[] = "\t\t\tWARNING: Fila-".$indice_fila." Error en el formato de la fecha de facturación(dd/mm/yyyy)";
				//contar_errores();
			}
			
			
			
			$net_amount = "";
			$tax_amount = "";
			$gross_amount = "";
			if($values_fila['subtotal']!=""){
				$net_amount = check_es_moneda($values_fila['subtotal']);
				if($net_amount=="error"){
					$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." El número del Subtotal no tiene el formato correcto (\$xx,xxx.xx)";
					contar_errores();
				}
			}
			if($values_fila['iva']){
				$tax_amount = check_es_moneda($values_fila['iva']);
				if($tax_amount=="error"){
					$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." El número del IVA no tiene el formato correcto (\$xx,xxx.xx)";
					contar_errores();
				}
			}
			if($values_fila['total']){
				$gross_amount = check_es_moneda($values_fila['total']);
				if($gross_amount=="error"){
					$array_mensajes_finales[] = "\t\t\tERROR: Fila-".$indice_fila." El número del Total no tiene el formato correcto (\$xx,xxx.xx)";
					contar_errores();
				}
			}
			
			$array_salida[$indice_general_salida]["type"]='INGRESO';
			$array_salida[$indice_general_salida]["i"]=$values_fila['orden'];
			$array_salida[$indice_general_salida]["EMPLOYEE"]=$values_fila['tipo'];
			$array_salida[$indice_general_salida]["Supplier"]=$values_fila['cliente'];
			$array_salida[$indice_general_salida]["cuenta"]='';
			$array_salida[$indice_general_salida]["proyecto"]=$values_fila['proyecto'];
			$array_salida[$indice_general_salida]["producto"]='';
			$array_salida[$indice_general_salida]["rubro"]='';
			$array_salida[$indice_general_salida]["items"]=utf8_decode($values_fila['concepto']);
			$array_salida[$indice_general_salida]["Currency"]=$values_fila['moneda'];
			$array_salida[$indice_general_salida]["Net amount"]=number_format(-1*get_numeric_limpio($net_amount), 2, '.', '');
			$array_salida[$indice_general_salida]["Tax amount"]=number_format(get_numeric_limpio($tax_amount), 2, '.', '');
			$array_salida[$indice_general_salida]["Gross amount"]=number_format(-1*get_numeric_limpio($gross_amount), 2, '.', '');
			$array_salida[$indice_general_salida]["state"]=$estado;
			$array_salida[$indice_general_salida]["Asked at"]=$date_askedat;
			$array_salida[$indice_general_salida]["mes"]=$date_mes;
			$array_salida[$indice_general_salida]["semana"]=$date_semana;
			$array_salida[$indice_general_salida]["notes"]='';
			$array_salida[$indice_general_salida]["additional info"]=$date_facturacion;
			$array_salida[$indice_general_salida]["link to request"]='';
			
			$indice_general_salida++;
			
		}
		
		$_SESSION['presupuesto_mensajes'] = array_merge($_SESSION['presupuesto_mensajes'],$array_mensajes_finales);
		return $array_salida;
	}
}


function get_presupuestos_from_drive($client,$presupuestos_folder){

	$array_errores_finales = array();
	$array_mensajes_finales = array();
	
	
	
	$archivos=array();
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		$client->setAccessToken($_SESSION['access_token']);
		$service = new Google_Service_Drive($client);
		$accessToken = json_decode($client->getAccessToken(), true);
		
		//Para sacar los archivos de Drive
		$ids_archivos = makeArrayIDsWithFilesInFolder($service,$presupuestos_folder);
		
		$archivos=array();
		//Reviso los archivos y formo un array solo con los spreadsheets
		$array_mensajes_finales[] = "Leyendo los presupuestos...";
		foreach($ids_archivos as $archivo){
			$data = getFile($service, $archivo);
			if($data['mime_type']=='application/vnd.google-apps.spreadsheet'){
				$archivos[] = $data;
			}
		}
		$serviceRequest = new DefaultServiceRequest($accessToken['access_token']);
		ServiceRequestFactory::setInstance($serviceRequest);
		$contador_errores=0;
		$indice_general_salida =0;
		$indice_letra='A';
		foreach($archivos as $file){
			
			$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
			$spreadsheetFeed = $spreadsheetService->getSpreadsheetById($file['id']); 
			$worksheetFeed = $spreadsheetFeed->getWorksheets();
			$array_pestanas = $worksheetFeed->getAllTitle();
			
			$array_mensajes_finales[] = "\tLeyendo documento: ". $file['nombre'];
			foreach($array_pestanas as $pestana){
				$array_mensajes_finales[] = "\t\tHoja: ". $pestana;
				$worksheet = $worksheetFeed->getByTitle($pestana);
				$cellsheet = $worksheet->getCellFeed();
				
				//Banderas para los errores
				$flag_noes_presupuesto=0;

				$fila_inicial = get_firstRowWithValue(10,$cellsheet);
				//CHECK: Error la hoja no tiene valores en las primeras 10 filas
				if($fila_inicial <= 0){
					//$array_mensajes_finales[] = "\t\t\tWARNING: La hoja posiblemente no contiene un presupuesto";
					$flag_noes_presupuesto=1;
				}
				//Continuo con el proceso de análisis del documento
				else{
					//Ahora necesito encontrar la primera columna donde hay un valor de fecha valido
					$columna_fecha = get_firstColumnWithDate($fila_inicial,30,$cellsheet);
					if($columna_fecha <= 0){
						$array_mensajes_finales[] = "\t\t\tERROR: No se encontró una  celda con una fecha valida (dd/mm/AAAA) para comenzar el análisis";
						$contador_errores++;
					}
					
				}
				if(!$flag_noes_presupuesto){
					//Ahora busco la columna con el encabezado "Centro de Costos..."
					$columna_centro_costo=get_firstColumnCentroCosto($fila_inicial,$columna_fecha,$cellsheet);
					//Ahora busco la columna con el encabezado "Descripción..."
					$columna_descripcion=get_firstColumnDescripcion($fila_inicial,$columna_fecha,$cellsheet);
				}
				
				//Si es un posible presupuesto comienzo a sacar la info de la hoja y empiezo a armar el array final
				//Tengo los valores para $fila_inicial, $columna_centro_costo, $columna_fecha y con ellos comienzo a barrer todas las filas
				if($fila_inicial != 0 && $columna_centro_costo !=0 && $columna_fecha != 0 && $columna_descripcion !=0){
					//Contare el total de columnas con fechas a partir de la $columna_fecha hasta que exista que no tenga formato de fecha
					$array_fechas = get_arrayFechas($fila_inicial,$columna_fecha,$cellsheet);
					//print_r($array_fechas);
					if(empty($array_fechas)){
						$array_mensajes_finales[] = "\t\t\tERROR: Las fechas en la fila ".$fila_inicial." no cumplen con el formato (dd/mm/AAAA) para comenzar el análisis";
					}
					else{
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
									$date_fecha = date_create_from_format('d/m/Y', $fecha);
									
									//Temporal con el monto usado en la fecha indicada
									$celda_monto_fecha = $cellsheet->getCell($cont_filas,(int)$key_f);
									//Reviso si se creo por que si no se crea no contiene algún valor
									$monto_fecha="";
									if($celda_monto_fecha)
										$monto_fecha = get_numeric($celda_monto_fecha->getContent());

									
									//Descarto los valores en 0 o similares
									if($monto_fecha!="0" && $monto_fecha!="" && $monto_fecha!="0.00" && $monto_fecha!="FALSE" &&  $monto_fecha!="0,00"){
										//Asigno los valores anteriores
										$array_salida[$indice_general_salida]["type"]='BUDGET';
										$array_salida[$indice_general_salida]["i"]=$indice_letra.'-'.$indice_general_salida; //Se auto generar usando desde la A en adelante
										$array_salida[$indice_general_salida]["EMPLOYEE"]='';
										$array_salida[$indice_general_salida]["Supplier"]='';
										$array_salida[$indice_general_salida]["cuenta"]=$array_cppr[2];
										$array_salida[$indice_general_salida]["proyecto"]=$array_cppr[0];
										$array_salida[$indice_general_salida]["producto"]=$array_cppr[1];
										$array_salida[$indice_general_salida]["rubro"]=$array_cppr[3];
										$array_salida[$indice_general_salida]["items"]=utf8_decode(strtoupper($descripcion_value));
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
				}
				$indice_letra++;
			}
		}
		
		
		
		//print_r($array_mensajes_finales);
		//Variable de sesión para mandar todos los warning o errores encontrados
		
		$_SESSION['presupuesto_mensajes'] = array_merge($_SESSION['presupuesto_mensajes'],$array_mensajes_finales);
		
		return $array_salida;
		
		
	}
	
	
	
	
	
	
	
}


/*
Función que verifica si tenemos el token de drive para ser utilizado
@IN 
@OUT cliente de google para usar
*/
function check_token(){
	
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		$client = new Google_Client();
		$client->setAccessToken($_SESSION['access_token']);
		$service = new Google_Service_Drive($client);
		$accessToken = json_decode($client->getAccessToken(), true);
	
		//Por si el token expiro voy a obtener uno nuevo
		if($client->isAccessTokenExpired()){
			header('Location: ' . filter_var($redirect_uri.'oauth2callback.php', FILTER_SANITIZE_URL));
		}
		
		return $client;
	}
	else{
		header('Location: ' . filter_var($redirect_uri."oauth2callback.php", FILTER_SANITIZE_URL));
	}
	return 0;
}

/*
Función que verifica si tenemos el token de drive para ser utilizado
@IN cliente de google drive para spreadsheets
	id del identificador del folder del presupuesto
@OUT array con todos los meta datos de los archivos encontrados en el folder
*/
function get_spreadsheets_in_folder($client,$presupuestos_folder){
	$archivos=array();
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		$client->setAccessToken($_SESSION['access_token']);
		$service = new Google_Service_Drive($client);
		$accessToken = json_decode($client->getAccessToken(), true);
		
		//Para sacar los archivos de Drive
		$ids_archivos = makeArrayIDsWithFilesInFolder($service,$presupuestos_folder);
		
		//Reviso los archivos y formo un array solo con los spreadsheets
		foreach($ids_archivos as $archivo){
			$data = getFile($service, $archivo);
			if($data['mime_type']=='application/vnd.google-apps.spreadsheet'){
				$archivos[] = $data;
			}
		}
		return $archivos;
	}
	else{
		return $archivos;
	}

}



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
			$posible_fecha = date_create_from_format('d/m/Y', $contenido_celda);
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


/*
Función para buscar la primer columna con la palabra "Centro de Costos"
@IN integer con la fila donde se buscara la columna
	intenger numero maximo de columnas para buscar	
	cellsheetFeed todo lo que viene del api de DRIVE con el contenido de las celdas en la hoja
@OUT integer con el numero de la fila donde encontro el primer valor
*/
function get_firstColumnCentroCosto($fila_inicial,$columna_fecha,$cellsheet){
	$columna_actual=0;
	$flag_busqueda=1;
	do{
		$columna_actual++;
		$celda_temporal = $cellsheet->getCell($fila_inicial,$columna_actual);
		if($celda_temporal){
			$contenido_celda = $celda_temporal->getContent();
			$posicion = strpos(strtoupper($contenido_celda), 'CENTRO DE COSTOS');
			if($posicion !== false){
				$flag_busqueda=0;
			}
		}
	}while($flag_busqueda && $columna_actual < $columna_fecha);
	if($columna_actual == $columna_fecha){
		return 0;
	}
	return $columna_actual;
}


function get_numberColumnFromName($fila_inicial,$columna_fecha,$cellsheet,$nombre_columna){
	$columna_actual=0;
	$flag_busqueda=1;
	do{
		$columna_actual++;
		$celda_temporal = $cellsheet->getCell($fila_inicial,$columna_actual);
		if($celda_temporal){
			$contenido_celda = $celda_temporal->getContent();
			//print_online($contenido_celda);
			$posicion = strpos(strtoupper($contenido_celda), $nombre_columna);
			
			if($posicion !== false){
				$flag_busqueda=0;
			}
		}
	}while($flag_busqueda && $columna_actual < $columna_fecha);
	if($columna_actual == $columna_fecha){
		return 0;
	}
	return $columna_actual;
}


/*
Función para buscar la primer columna con la palabra "Descripcion" sin acentos
@IN integer con la fila donde se buscara la columna
	intenger numero maximo de columnas para buscar	
	cellsheetFeed todo lo que viene del api de DRIVE con el contenido de las celdas en la hoja
@OUT integer con el numero de la fila donde encontro el primer valor
*/

function get_firstColumnDescripcion($fila_inicial,$columna_fecha,$cellsheet){
	$columna_actual=0;
	$flag_busqueda=1;
	do{
		$columna_actual++;
		$celda_temporal = $cellsheet->getCell($fila_inicial,$columna_actual);
		if($celda_temporal){
			$contenido_celda = $celda_temporal->getContent();
			//print_online($contenido_celda);
			$posicion = strpos(strtoupper($contenido_celda), 'DESCRIPCION');
			
			if($posicion !== false){
				$flag_busqueda=0;
			}
		}
	}while($flag_busqueda && $columna_actual < $columna_fecha);
	if($columna_actual == $columna_fecha){
		return 0;
	}
	return $columna_actual;
}


/*
Función para obtener el array con todos los encabezados de fechas encontrados 
@IN integer con la fila donde se buscara la columna
	intenger numero inicial de la fecha encontrada o donde comenzaremos a buscar
	cellsheetFeed todo lo que viene del api de DRIVE con el contenido de las celdas en la hoja
@OUT array con todos los valores de fechas en formato "d/m/Y"
*/

function get_arrayFechas($fila_inicial,$columna_fecha,$cellsheet){
	$flag_tope_fecha=1;
	$total_fechas=0;
	$col_ini_fecha  = $columna_fecha;
	$array_fechas=array();
	do{
		//print_r($cellsheet);
		$celda_fecha = $cellsheet->getCell($fila_inicial,$col_ini_fecha);
		//print_r($celda_fecha);
		if($celda_fecha){
			$contenido_celda =  $celda_fecha->getContent();
			
			$string_fecha = check_es_fecha($contenido_celda);
			$posible_fecha="";
			//print_online($string_fecha."<br/>");
			if($string_fecha){
				$posible_fecha = date_create_from_format('d/m/Y', $string_fecha);
			}else{
				$flag_tope_fecha=0;
			}
			//Si no es una fecha en el formato establecido regresa nulo y por lo tanto descartamos la fila
			if($posible_fecha){
				$array_fechas[$col_ini_fecha] = date_format($posible_fecha, 'd/m/Y');
				$total_fechas++;
				$col_ini_fecha++;
			}
			else{
				$flag_tope_fecha=0;
			}
		}
		else{
			$flag_tope_fecha=0;
		}					
		
	}while($flag_tope_fecha);
	
	
	return $array_fechas;
}

function create_excel($array_flujo,$array_avance){
	
	$objPHPExcel = new \PHPExcel();

	$objPHPExcel->getProperties()->setCreator("Cinepop");
	$objPHPExcel->getProperties()->setLastModifiedBy("Cinepop");
	$objPHPExcel->getProperties()->setTitle("Flujo y Avance");
	$objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Test Document");
	$objPHPExcel->getProperties()->setDescription("Avance y Flujo Presupuestal");

	

	//Flujo
	//$objPHPExcel->createSheet(NULL, 0);
	$objPHPExcel->setActiveSheetIndex(0);
	$objPHPExcel->getActiveSheet()->setTitle('Flujo');
	
	$headers = array('TYPE','ID','EMPLOYEE','Supplier','Cuenta','Proyecto','Producto','Rubro','Descripcion','Currency','Net amount','Tax amount','Gross amount','State','Asked at','Mes','Semana','Notes','Additional info','Link to the request');
	$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
	$objPHPExcel->getActiveSheet()->getStyle('K:M')->getNumberFormat()->setFormatCode('0.00');
	
	$num_fila=1;
	foreach($array_flujo as $fila){
		$num_fila++;
		$objPHPExcel->getActiveSheet()->fromArray($fila, NULL, 'A'.$num_fila);
	}
	
	//Avance
	$objPHPExcel->createSheet(NULL, 1);
	$objPHPExcel->setActiveSheetIndex(1);
	$objPHPExcel->getActiveSheet()->setTitle('Avance');
	
	$headers = array('TYPE','ID','EMPLOYEE','Supplier','Cuenta','Proyecto','Producto','Rubro','Descripcion','Currency','Net amount','Tax amount','Gross amount','State','Asked at','Mes','Semana','Notes','Additional info','Link to the request');
	$objPHPExcel->getActiveSheet()->fromArray($headers, NULL, 'A1');
	$objPHPExcel->getActiveSheet()->getStyle('K:M')->getNumberFormat()->setFormatCode('0.00');
	
	$num_fila=1;
	foreach($array_avance as $fila){
		$num_fila++;
		$objPHPExcel->getActiveSheet()->fromArray($fila, NULL, 'A'.$num_fila);
	}
	
	

	
	$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	$objWriter->setPreCalculateFormulas(false);
	$objWriter->save('tmp_files/avance_flujo.xlsx');

}
  
?>