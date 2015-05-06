<?php 
	session_start();
	require_once 'config-token.php';

	$client = check_token();
	$archivos_presupuestos=get_spreadsheets_in_folder($client,$presupuestos_folder);
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<script src="js/jquery-1.11.2.js"></script>
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/bootstrap-theme.min.css">
	<script src="js/bootstrap.min.js"></script>
	<title>Avance presupuestal y flujo</title>
</head>
<body>
<div class="container">
	<div class="row">
		<div class="col-md-12">
		<h1 class="text-center">Generación de Avance Presupuestal y Flujo</h1>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<table class="table table-bordered">
				<thead>
					<tr class="active">
						<th>Presupuestos 2015</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($archivos_presupuestos as $presupuesto):?>
					<tr>
						<td>
						<?php echo $presupuesto['nombre'];?>
						</td>
					</tr>
					<?php endforeach;?>
				</tbody>
			</table>
		</div>
		<div class="col-md-6">
			<table class="table table-bordered">
				<thead>
					<tr class="active">
						<th>Turbine Anticipos</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
						<a href="https://hormiga.turbinehq.com/employees/7442/external_csv.csv?demand_kind=expenses&demand_state%5B%5D=filtering&demand_state%5B%5D=first&demand_state%5B%5D=pending&demand_state%5B%5D=rejected&demand_state%5B%5D=payment&demand_state%5B%5D=completed&demand_state%5B%5D=second&demand_state%5B%5D=filtering&token=cc54d675e01150329549399ef74042981c03c7d0">Descargar aquí</a>
						</td>
					</tr>
				</tbody>
			</table>
			
			<table class="table table-bordered">
				<thead>
					<tr class="active">
						<th>Turbine Compras</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
						<a href="https://hormiga.turbinehq.com/employees/7442/external_csv.csv?demand_kind=purchases&token=a1159cc06707b68bbe42f89142b6e0fb3c792eb6">Descargar aquí</a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	
	<div class="row">
		<div class="col-md-12 text-center">
		<a href="vista-principal.php " class="btn btn-success">Generar Avance Presupuestal y Flujo</a>
		</div>
	</div>
</div>
</body>
</html>


    


