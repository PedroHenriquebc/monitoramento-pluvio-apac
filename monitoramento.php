<?php
// 1. Fazer a requisição ao JSON
$url = "http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial=2024-08-01%2000:00:00&DataFinal=2024-08-10%2023:59:59";
$json_data = file_get_contents($url);
$data = json_decode($json_data, true);

// 2. Processar os dados usando hora_leitura
$grouped_data = [];

foreach ($data as $entry) {
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

// 3. Organizar os dias como colunas e exibir o resultado
echo "<table border='1'>";
echo "<tr><th>Ano/Mês</th><th>Estação</th>";

for ($day = 1; $day <= 31; $day++) {
    echo "<th>" . str_pad($day, 2, '0', STR_PAD_LEFT) . "</th>";
}

echo "<th>Acumulado</th>";

echo "</tr>";

foreach ($grouped_data as $ano_mes => $estacoes) {
    foreach ($estacoes as $estacao => $dias) {
        echo "<tr>";
        echo "<td>$ano_mes</td>";
        echo "<td>$estacao</td>";
        $valor_chuva_acumulado = 0;

        for ($day = 1; $day <= 31; $day++) {
            $day_str = str_pad($day, 2, '0', STR_PAD_LEFT);
            echo "<td>" . (isset($dias[$day_str]) ? $dias[$day_str] : '') . "</td>";
            $valor_chuva_acumulado = $valor_chuva_acumulado + (isset($dias[$day_str]) ? $dias[$day_str] : 0);
        }

        echo "<td>" . $valor_chuva_acumulado ."</td>";
        echo "</tr>";
    }
}

echo "</table>";
?>