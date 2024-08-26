<?php
// Configuração das datas
$dataInicialExplode = explode("-", $_POST["dataInicial"]);
$dataInicialFormat = $dataInicialExplode[2] . "/" . $dataInicialExplode[1] . "/" . $dataInicialExplode[0];
$dataInicialFormatUrl = $dataInicialExplode[0] .'-'. $dataInicialExplode[1] .'-'. $dataInicialExplode[2];
$dataInicial = DateTime::createFromFormat('d/m/Y', $dataInicialFormat);

if (isset($_POST["dataFinal"]) && $_POST["dataFinal"] != null) {
    $dataFinalExplode = explode("-", $_POST["dataFinal"]);
    $dataFinalFormatUrl = $dataFinalExplode[0] .'-'. $dataFinalExplode[1] .'-'. $dataFinalExplode[2];
    $dataFinalFormat = $dataFinalExplode[2] . "/" . $dataFinalExplode[1] . "/" . $dataFinalExplode[0];

    $dataFinal = DateTime::createFromFormat('d/m/Y', $dataFinalFormat);
    $intervalo = $dataInicial->diff($dataFinal);
}

// Requisição ao JSON
$url = 'http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial='.$dataInicialFormatUrl.'%2000:00:00&DataFinal='.$dataFinalFormatUrl.'%2023:59:59';
$json_data = file_get_contents($url);
$data = json_decode($json_data, true);

// Filtros do formulário
$selectedMesoregiao = $_POST["mesoregiao"] ?? "Todas";
$selectedMicrorregiao = $_POST["microrregiao"] ?? "Todas";
$selectedMunicipio = $_POST["municipio"] ?? "Todos";
$selectedBacia = $_POST["bacia"] ?? "Todas";

// Filtragem dos dados
$filtered_data = array_filter($data, function($entry) use ($selectedMesoregiao, $selectedMicrorregiao, $selectedMunicipio, $selectedBacia) {
    $mesoregiaoMatch = ($selectedMesoregiao == "Todas" || $entry['mesoregiao'] == $selectedMesoregiao);
    $microregiaoMatch = ($selectedMicrorregiao == "Todas" || $entry['microregiao'] == $selectedMicrorregiao);
    $municipioMatch = ($selectedMunicipio == "Todos" || $entry['municipio'] == $selectedMunicipio);
    $baciaMatch = ($selectedBacia == "Todas" || $entry['bacia'] == $selectedBacia);

    return $mesoregiaoMatch && $microregiaoMatch && $municipioMatch && $baciaMatch;
});

// Processar os dados usando hora_leitura
$grouped_data = [];

foreach ($filtered_data as $entry) {
    $hora_leitura = new DateTime($entry['hora_leitura']);
    $ano_mes = $hora_leitura->format('Y-m');
    $estacao = $entry['nome_estacao'];
    $dia = $hora_leitura->format('d');

    if (!isset($grouped_data[$ano_mes])) {
        $grouped_data[$ano_mes] = [];
    }

    if (!isset($grouped_data[$ano_mes][$estacao])) {
        $grouped_data[$ano_mes][$estacao] = [];
    }

    // Armazena o valor da chuva no dia específico
    $grouped_data[$ano_mes][$estacao][$dia] = $entry['total_chuva'];
}

// Organizar os dias como colunas e exibir o resultado
echo "<table border='1'>";
echo "<tr><th>Mesorregião</th><th>Microrregião</th><th>Município</th><th>Bacia</th><th>Ano/Mês</th><th>Estação</th>";

for ($day = 1; $day <= 31; $day++) {
    echo "<th>" . str_pad($day, 2, '0', STR_PAD_LEFT) . "</th>";
}

echo "<th>Acumulado</th>";

echo "</tr>";

foreach ($filtered_data as $entry) {
    $hora_leitura = new DateTime($entry['hora_leitura']);
    $ano_mes = $hora_leitura->format('Y-m');
    $estacao = $entry['nome_estacao'];
    $dia = $hora_leitura->format('d');
    $valor_chuva_acumulado = 0;

    // Calcula o acumulado para a linha atual
    $total_chuva_dia = isset($grouped_data[$ano_mes][$estacao][$dia]) ? $grouped_data[$ano_mes][$estacao][$dia] : 0;
    $valor_chuva_acumulado += $total_chuva_dia;

    echo "<tr>";
    echo "<td>" . $entry['mesoregiao'] . "</td>";
    echo "<td>" . $entry['microregiao'] . "</td>";
    echo "<td>" . $entry['municipio'] . "</td>";
    echo "<td>" . $entry['bacia'] . "</td>";
    echo "<td>$ano_mes</td>";
    echo "<td>$estacao</td>";

    for ($day = 1; $day <= 31; $day++) {
        $day_str = str_pad($day, 2, '0', STR_PAD_LEFT);
        $value = isset($grouped_data[$ano_mes][$estacao][$day_str]) ? $grouped_data[$ano_mes][$estacao][$day_str] : '';
        echo "<td>" . $value . "</td>";
        $valor_chuva_acumulado += ($value !== '' ? $value : 0);
    }

    echo "<td>" . $valor_chuva_acumulado . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
