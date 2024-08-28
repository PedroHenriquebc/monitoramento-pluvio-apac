<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento Mensal</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }

        table {
            width: 50%; 
            border-collapse: collapse;
            text-align: center;
            margin: 50px;
            background-color: #fff;
            border-radius: 8px;
        }

        th, td {
            border: 1px solid #000000; 
            padding: 8px;
            text-align: center;
            margin: auto;
        }
        
        th {
            background-color: #679dd6;
            color: #fff;
            text-align: center;
            margin: auto;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 14px;
        }

        img {
            max-width: 500px; /* Ajuste a largura da imagem conforme necessário */
            display: block;
            margin: 20px auto;
        }
        
        td {
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        tr:last-child td {
            border-bottom: none;
        }

        th, td:first-child {
            border-left: none;
        }

        td:last-child {
            font-weight: bold;
            color: #679dd6;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            table, th, td {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="logo3_apac_2024.png" alt="Logo ou Imagem">
</div>

<table id="tabelaMensal">
<?php
// Configuração das datas
$dataInicialExplode = explode("-", $_POST["dataInicial"]);
$dataInicialFormat = $dataInicialExplode[2] . "/" . $dataInicialExplode[1] . "/" . $dataInicialExplode[0];
$dataInicialFormatUrl = $dataInicialExplode[0] .'-'. $dataInicialExplode[1] .'-'. $dataInicialExplode[2];
$dataInicial = DateTime::createFromFormat('d/m/Y', $dataInicialFormat);

$dataFinalExplode = explode("-", $_POST["dataFinal"]);
$dataFinalFormatUrl = $dataFinalExplode[0] .'-'. $dataFinalExplode[1] .'-'. $dataFinalExplode[2];
$dataFinalFormat = $dataFinalExplode[2] . "/" . $dataFinalExplode[1] . "/" . $dataFinalExplode[0];
$dataFinal = DateTime::createFromFormat('d/m/Y', $dataFinalFormat);

// Requisição ao JSON
$url = 'http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial='.$dataInicialFormatUrl.'%2000:00:00&DataFinal='.$dataFinalFormatUrl.'%2023:59:59';
$json_data = file_get_contents($url);
$data = json_decode($json_data, true);

// Filtros do formulário
$selectedMesorregiao = $_POST["mesorregiao"] ?? "Todas";
$selectedMicrorregiao = $_POST["microrregiao"] ?? "Todas";
$selectedMunicipio = $_POST["municipio"] ?? "Todos";
$selectedBacia = $_POST["bacia"] ?? "Todas";

// Filtragem dos dados
$filtered_data = array_filter($data, function($entry) use ($selectedMesorregiao, $selectedMicrorregiao, $selectedMunicipio, $selectedBacia, $dataInicial, $dataFinal) {
    $mesoregiaoMatch = ($selectedMesorregiao == "Todas" || $entry['mesoregiao'] == $selectedMesorregiao);
    $microregiaoMatch = ($selectedMicrorregiao == "Todas" || $entry['microregiao'] == $selectedMicrorregiao);
    $municipioMatch = ($selectedMunicipio == "Todos" || $entry['municipio'] == $selectedMunicipio);
    $baciaMatch = ($selectedBacia == "Todas" || $entry['bacia'] == $selectedBacia);
    $hora_leitura = new DateTime($entry['hora_leitura']);

    return $mesoregiaoMatch && $microregiaoMatch && $municipioMatch && $baciaMatch && $hora_leitura >= $dataInicial && $hora_leitura <= $dataFinal;
});

// Agrupamento dos dados mensais
$grouped_data = [];
foreach ($filtered_data as $entry) {
    $hora_leitura = new DateTime($entry['hora_leitura']);
    $ano_mes = $hora_leitura->format('Y-m');
    $estacao = $entry['nome_estacao'];
    $codigo_gmmc = $entry['codigo_gmmc'];

    if (!isset($grouped_data[$codigo_gmmc])) {
        $grouped_data[$codigo_gmmc] = [
            'estacao' => $estacao,
            'mesoregiao' => $entry['mesoregiao'],
            'microregiao' => $entry['microregiao'],
            'municipio' => $entry['municipio'],
            'bacia' => $entry['bacia'],
            'latitude' => $entry['latitude'], // Adicionado
            'longitude' => $entry['longitude'], // Adicionado
            'chuva_mensal' => array_fill(1, 12, 0)
        ];
    }

    $mes_num = (int) $hora_leitura->format('m');
    $grouped_data[$codigo_gmmc]['chuva_mensal'][$mes_num] += $entry['total_chuva'];
}

// Nomes dos meses
$month_names = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
];

// Exibição dos dados
echo "<table border='1'>";
echo "<tr><th>Estação</th><th>Mesorregião</th><th>Microrregião</th><th>Município</th><th>Bacia</th><th>Latitude</th><th>Longitude</th><th>Ano</th>";

foreach ($month_names as $month) {
    echo "<th>" . $month . "</th>";
}

echo "<th>Acumulado</th></tr>";

foreach ($grouped_data as $codigo_gmmc => $data) {
    $valor_chuva_acumulado = array_sum($data['chuva_mensal']);
    $ano = $dataInicial->format('Y'); // O ano da data inicial é usado como referência

    echo "<tr>";
    echo "<td>" . $data['estacao'] . "</td>";
    echo "<td>" . $data['mesoregiao'] . "</td>";
    echo "<td>" . $data['microregiao'] . "</td>";
    echo "<td>" . $data['municipio'] . "</td>";
    echo "<td>" . $data['bacia'] . "</td>";
    echo "<td>" . $data['latitude'] . "</td>"; // Adicionado
    echo "<td>" . $data['longitude'] . "</td>"; // Adicionado
    echo "<td>" . $ano . "</td>";

    foreach ($month_names as $mes_num => $mes_name) {
        if ($mes_num < (int) $dataInicial->format('m') || $mes_num > (int) $dataFinal->format('m')) {
            echo "<td>-</td>";
        } else {
            $chuva = $data['chuva_mensal'][$mes_num];
            echo "<td>" . ($chuva > 0 ? number_format($chuva, 2, ',', '') : '-') . "</td>";
        }
    }

    echo "<td>" . number_format($valor_chuva_acumulado, 2, ',', '') . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
        </tbody>
    </table>
</body>
</html>
