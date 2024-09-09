<?php
// Passo 1: Realizar a requisição HTTP para obter os dados
// $url = "http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial=2024-07-01%2000:00:00&DataFinal=2024-07-01%2023:59:59";
$url = "http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial=2024-06-01%2012:00:00&DataFinal=2024-06-30%2012:00:00";
$json = file_get_contents($url);
$data = json_decode($json, true);

// Passo 2: Verificar se os dados foram carregados corretamente
if ($data === null) {
    die('Erro ao carregar os dados.');
}

echo "<pre>";
print_r($data);
echo "</pre>";

?>