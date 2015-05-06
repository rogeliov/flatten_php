<?php 
	session_start();
	if(!isset($_SESSION['contador_errores']))$_SESSION['contador_errores'] = 0;
	if(!isset($_SESSION['presupuesto_mensajes']))$_SESSION['presupuesto_mensajes'] = array();
	if(!isset($_SESSION['array_principal']))$_SESSION['array_principal'] = array();
	require_once 'config-token.php';
	
	//use Google\Spreadsheet\DefaultServiceRequest;
	//use Google\Spreadsheet\ServiceRequestFactory;
	
	$client = check_token();
	$archivos_presupuestos=array();
	
	if(isset($_GET['button'])){
		$boton = $_GET['button'];
		if($boton=="btn_generar_avance"){
			get_flujo_avance($client,$url_compras,$url_gastos,$ingresos_archivo,$presupuestos_folder,$reembolsos_archivo);
		}
	}else{
		$_SESSION['presupuesto_mensajes']=array();
	}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<script src="js/jquery-1.11.2.js"></script>
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/bootstrap-theme.min.css">
	<script src="js/bootstrap.min.js"></script>
	
	
	<script type="text/javascript">
	function actualiza_valor(valorActualizar) {
		$("#barra_avance").css('width',valorActualizar + '%');
	}
	

	</script>
	
	<title>Avance presupuestal</title>
</head>
<body>
<div class="container">
	<div class="row">
		<div class="col-md-12">
		<h1 class="text-center">Generación de Avance Presupuestal y Flujo</h1>
		</div>
	</div>
	<div  class="row" style="padding-top:10px;">
		<div class="col-md-12 text-center">
			<textarea id="text_mensajes" class="form-control" style="min-height:500px;  max-height:500px;">
			<?php 
				if(isset($_SESSION['presupuesto_mensajes'])){
					if($_SESSION['contador_errores']){$_SESSION['presupuesto_mensajes']['error']="TOTAL DE ERRORES: " . $_SESSION['contador_errores'];}
					foreach($_SESSION['presupuesto_mensajes'] as $renglon){
						echo $renglon."\n";
					}
					$_SESSION['presupuesto_mensajes']=array();
				}
			?>
			</textarea>
		</div>
	</div>
	
	<div  class="row" style="padding-top:10px;">
		<div class="col-md-12 text-center">
			<div   class="progress progress-striped active">
				<div id="barra_avance" class="progress-bar" role="progressbar"  aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"  style="width: 0%">
				<label id="lbl_proceso_ejecucion"></label>
				<span class="sr-only">45% completado</span>
				</div>
			
			</div>
		
		</div>
	</div>

	<div class="row">
		<div class="col-md-12 text-center">
		<button id="btn_generar_avance" name="btn_generar_avance" onClick='location.href="?button=btn_generar_avance"' class="btn btn-success">Generar Avance Presupuestal y Flujo</button>
		</div>
	</div>
</div>

<?php
if(isset($_GET['button'])){
		$boton = $_GET['button'];
		if($boton=="btn_generar_avance"){
		//	get_flujo($client,$url_compras,$url_gastos,$ingresos_archivo,$presupuestos_folder,$reembolsos_archivo);
			
			if(isset($_SESSION['array_principal'])){
				$output = fopen('tmp_files/flatten.csv', 'w');
				//Mando los encabezados
				fputcsv($output, array('TYPE','ID','EMPLOYEE','Supplier','Cuenta','Proyecto','Producto','Rubro','Descripcion','Currency','Net amount','Tax amount','Gross amount','State','Asked at','Mes','Semana','Notes','Additional info','Link to the request'));
				foreach($_SESSION['array_principal'] as $array){
					fputcsv($output, $array);
				}
				fclose($output);
			}
		}
	}
	

?>
<script type="text/javascript">

$(document).ready(function(){
	$('#lbl_proceso_ejecucion').text('');
	$("#btn_generar_avance").click(function(){
		$("#barra_avance").show();
		$('#lbl_proceso_ejecucion').text('');
		$("#barra_avance").css('width',contador + '%');		
		alert('algo');
		
	});
});

</script>
</body>
</html>


    


