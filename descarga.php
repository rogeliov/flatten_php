<?php
$url_compras="https://hormiga.turbinehq.com/employees/7442/external_csv.csv?demand_kind=expenses&demand_state%5B%5D=filtering&demand_state%5B%5D=first&demand_state%5B%5D=pending&demand_state%5B%5D=rejected&demand_state%5B%5D=payment&demand_state%5B%5D=completed&demand_state%5B%5D=second&demand_state%5B%5D=filtering&token=cc54d675e01150329549399ef74042981c03c7d0";
$compras_data = file_get_contents($url_compras);
file_put_contents("tmp_turbine/gastos_ss.csv",$compras_data);
//var_dump($compras_data);

?>