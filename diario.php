<?php
require_once 'libs/SimpleXLSXGen.php';
use Shuchkin\SimpleXLSXGen;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento Diário</title>
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
            max-width: 500px; 
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

<!-- Botão para gerar e baixar o Excel -->
<div style="text-align: center; margin-bottom: 20px;">
    <form method="post">
        <input type="hidden" name="dataInicial" value="<?php echo htmlspecialchars($_POST['dataInicial'] ?? ''); ?>">
        <input type="hidden" name="dataFinal" value="<?php echo htmlspecialchars($_POST['dataFinal'] ?? ''); ?>">
        <input type="hidden" name="mesorregiao" value="<?php echo htmlspecialchars($_POST['mesorregiao'] ?? 'Todas'); ?>">
        <input type="hidden" name="microrregiao" value="<?php echo htmlspecialchars($_POST['microrregiao'] ?? 'Todas'); ?>">
        <input type="hidden" name="municipio" value="<?php echo htmlspecialchars($_POST['municipio'] ?? 'Todos'); ?>">
        <input type="hidden" name="bacia" value="<?php echo htmlspecialchars($_POST['bacia'] ?? 'Todas'); ?>">
        
        <button type="submit" name="download_excel" style="padding: 10px 20px; font-size: 16px; background-color: #679dd6; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
            Baixar Excel
        </button>
    </form>
</div>

<?php

// Inicializa a variável $grouped_data
$grouped_data = [];

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

// Agrupamento dos dados diários
$grouped_data = [];
foreach ($filtered_data as $entry) {
    $hora_leitura = new DateTime($entry['hora_leitura']);
    $ano_mes = $hora_leitura->format('Y-m');
    $estacao = $entry['nome_estacao'];
    $codigo_gmmc = $entry['codigo_gmmc'];

    if (!isset($grouped_data[$ano_mes])) {
        $grouped_data[$ano_mes] = [];
    }

    if (!isset($grouped_data[$ano_mes][$codigo_gmmc])) {
        $grouped_data[$ano_mes][$codigo_gmmc] = [];
    }

    // Armazena o valor da chuva no dia específico
    $grouped_data[$ano_mes][$codigo_gmmc][$hora_leitura->format('d')] = $entry['total_chuva'];
}

if (isset($_POST['download_excel'])) {
    if (empty($grouped_data)) {
        echo "Não há dados para gerar o Excel.";
    } else {
        $data_excel = [];

        // Adiciona cabeçalhos
        $headers = ['Estação', 'Mesorregião', 'Microrregião', 'Município', 'Bacia', 'Latitude', 'Longitude', 'Ano'];
        for ($i = 1; $i <= 31; $i++) {
            $headers[] = "$i";
        }
        $data_excel[] = $headers;

        // Adiciona dados ao Excel
        foreach ($grouped_data as $ano_mes => $stations) {
            foreach ($stations as $codigo_gmmc => $days) {
                $row = [
                    $stations[$codigo_gmmc]['nome_estacao'] ?? '',
                    $stations[$codigo_gmmc]['mesoregiao'] ?? '',
                    $stations[$codigo_gmmc]['microregiao'] ?? '',
                    $stations[$codigo_gmmc]['municipio'] ?? '',
                    $stations[$codigo_gmmc]['bacia'] ?? '',
                    $stations[$codigo_gmmc]['latitude'] ?? '',
                    $stations[$codigo_gmmc]['longitude'] ?? '',
                    $ano_mes
                ];

                // Adiciona os valores diários de precipitação
                for ($i = 1; $i <= 31; $i++) {
                    $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $row[] = $days[$day] ?? '0,00';
                }

                $data_excel[] = $row;
            }
        }

        try {
            // Cria o arquivo Excel e faz o download
            $xlsx = SimpleXLSXGen::fromArray($data_excel);
            $filename = "monitoramento_mensal_" . date('Ymd') . ".xlsx";
            $xlsx->downloadAs($filename);
            exit;
        } catch (Exception $e) {
            echo 'Erro ao gerar o arquivo Excel: ' . $e->getMessage();
        }
    }
}

// Exibe a tabela na página
if (!empty($grouped_data)) {
    echo "<table>";
    echo "<tr><th>Data</th><th>Estação</th><th>Mesorregião</th><th>Microrregião</th><th>Município</th><th>Bacia</th><th>Total de Chuva</th></tr>";
    foreach ($grouped_data as $ano_mes => $stations) {
        foreach ($stations as $codigo_gmmc => $days) {
            foreach ($days as $day => $total_chuva) {
                echo "<tr>";
                echo "<td>$ano_mes-$day</td>";
                echo "<td>{$stations[$codigo_gmmc]['nome_estacao']}</td>";
                echo "<td>{$stations[$codigo_gmmc]['mesoregiao']}</td>";
                echo "<td>{$stations[$codigo_gmmc]['microregiao']}</td>";
                echo "<td>{$stations[$codigo_gmmc]['municipio']}</td>";
                echo "<td>{$stations[$codigo_gmmc]['bacia']}</td>";
                echo "<td>{$total_chuva}</td>";
                echo "</tr>";
            }
        }
    }
    echo "</table>";
} else {
    echo "<p>Nenhum dado encontrado para o período e filtros selecionados.</p>";
}
?>
</body>
</html>