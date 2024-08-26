<?php
// Passo 1: Realizar a requisição HTTP para obter os dados
$url = "http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial=2024-02-16%2000:00:00&DataFinal=2024-04-16%2000:00:00";
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

<?php
 $dataInicialExplode = explode("-", $_POST["dataInicial"]);
    $dataInicialFormat = $dataInicialExplode[2] . "/" . $dataInicialExplode[1] . "/" . $dataInicialExplode[0];
    $dataInicialFormatUrl = $dataInicialExplode[0] .'-'. $dataInicialExplode[1] .'-'. $dataInicialExplode[2];
    $dataInicial = DateTime::createFromFormat('d/m/Y', $dataInicialFormat);

    if(isset($_POST["dataFinal"]) and $_POST["dataFinal"] != null) {
        $dataFinalExplode = explode("-", $_POST["dataFinal"]);
        $dataFinalFormatUrl = $dataFinalExplode[0] .'-'. $dataFinalExplode[1] .'-'. $dataFinalExplode[2];
        $dataFinalFormat = $dataFinalExplode[2] . "/" . $dataFinalExplode[1] . "/" . $dataFinalExplode[0];

        $dataFinal = DateTime::createFromFormat('d/m/Y', $dataFinalFormat);
        $intervalo = $dataInicial->diff($dataFinal);
    }

    <?php
$dataInicialExplode = explode("-", $_POST["dataInicial"]);
$dataInicialFormat = $dataInicialExplode[2] . "/" . $dataInicialExplode[1] . "/" . $dataInicialExplode[0];
$dataInicialFormatUrl = $dataInicialExplode[0] .'-'. $dataInicialExplode[1] .'-'. $dataInicialExplode[2];
$dataInicial = DateTime::createFromFormat('d/m/Y', $dataInicialFormat);

if(isset($_POST["dataFinal"]) and $_POST["dataFinal"] != null) {
    $dataFinalExplode = explode("-", $_POST["dataFinal"]);
    $dataFinalFormatUrl = $dataFinalExplode[0] .'-'. $dataFinalExplode[1] .'-'. $dataFinalExplode[2];
    $dataFinalFormat = $dataFinalExplode[2] . "/" . $dataFinalExplode[1] . "/" . $dataFinalExplode[0];

    $dataFinal = DateTime::createFromFormat('d/m/Y', $dataFinalFormat);
    $intervalo = $dataInicial->diff($dataFinal);
}

// 1. Fazer a requisição ao JSON
$url = 'http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial='.$dataInicialFormatUrl.'%2000:00:00&DataFinal='.$dataFinalFormatUrl.'%2023:59:59';
$json_data = file_get_contents($url);
$data = json_decode($json_data, true);

// 2. Processar os dados usando hora_leitura
$grouped_data = [];

foreach ($data as $entry) {
    $hora_leitura = new DateTime($entry['hora_leitura']);
    $ano_mes = $hora_leitura->format('Y-m');
    $estacao = $entry['nome_estacao'];
    $dia = $hora_leitura->format('d');
    $microrregiao = $entry['microregiao'];
    $bacia = $entry['bacia'];
    $mesoregiao = $entry['mesoregiao'];
    $municipio = $entry['municipio'];

    if (!isset($grouped_data[$ano_mes])) {
        $grouped_data[$ano_mes] = [];
    }

    if (!isset($grouped_data[$ano_mes][$estacao])) {
        $grouped_data[$ano_mes][$estacao] = [
            'dias' => [],
            'microrregiao' => $microrregiao,
            'bacia' => $bacia,
            'mesoregiao' => $mesoregiao,
            'municipio' => $municipio
        ];
    }

    // Armazena o valor da chuva no dia específico
    $grouped_data[$ano_mes][$estacao]['dias'][$dia] = $entry['total_chuva'];
}

// 3. Organizar os dias como colunas e exibir o resultado
echo "<table border='1'>";
echo "<tr><th>Ano/Mês</th><th>Estação</th><th>Microrregião</th><th>Bacia</th><th>Mesorregião</th><th>Município</th>";

for ($day = 1; $day <= 31; $day++) {
    echo "<th>" . str_pad($day, 2, '0', STR_PAD_LEFT) . "</th>";
}

echo "<th>Acumulado</th>";
echo "</tr>";

foreach ($grouped_data as $ano_mes => $estacoes) {
    foreach ($estacoes as $estacao => $dados) {
        echo "<tr>";
        echo "<td>$ano_mes</td>";
        echo "<td>$estacao</td>";
        echo "<td>" . $dados['microrregiao'] . "</td>";
        echo "<td>" . $dados['bacia'] . "</td>";
        echo "<td>" . $dados['mesoregiao'] . "</td>";
        echo "<td>" . $dados['municipio'] . "</td>";
        $valor_chuva_acumulado = 0;

        for ($day = 1; $day <= 31; $day++) {
            $day_str = str_pad($day, 2, '0', STR_PAD_LEFT);
            echo "<td>" . (isset($dados['dias'][$day_str]) ? $dados['dias'][$day_str] : '') . "</td>";
            $valor_chuva_acumulado += isset($dados['dias'][$day_str]) ? $dados['dias'][$day_str] : 0;
        }

        echo "<td>" . $valor_chuva_acumulado . "</td>";
        echo "</tr>";
    }
}

echo "</table>";
?>
