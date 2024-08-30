<?php
require_once 'libs/SimpleXLSXGen.php';
use Shuchkin\SimpleXLSXGen;
?>
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
// Definição dos nomes dos meses
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

// Inicializa a variável $grouped_data
$grouped_data = [];

if (isset($_POST['dataInicial']) && isset($_POST['dataFinal'])) {
    $dataInicialExplode = explode("-", $_POST["dataInicial"]);
    $dataInicialFormat = isset($dataInicialExplode[2]) && isset($dataInicialExplode[1]) ? $dataInicialExplode[2] . "/" . $dataInicialExplode[1] . "/" . $dataInicialExplode[0] : null;
    $dataInicialFormatUrl = $dataInicialFormat ? $dataInicialExplode[0] . '-' . $dataInicialExplode[1] . '-' . $dataInicialExplode[2] : null;
    $dataInicial = $dataInicialFormat ? DateTime::createFromFormat('d/m/Y', $dataInicialFormat) : null;

    $dataFinalExplode = explode("-", $_POST["dataFinal"]);
    $dataFinalFormatUrl = isset($dataFinalExplode[2]) && isset($dataFinalExplode[1]) ? $dataFinalExplode[0] . '-' . $dataFinalExplode[1] . '-' . $dataFinalExplode[2] : null;
    $dataFinalFormat = $dataFinalFormatUrl ? $dataFinalExplode[2] . "/" . $dataFinalExplode[1] . "/" . $dataFinalExplode[0] : null;
    $dataFinal = $dataFinalFormat ? DateTime::createFromFormat('d/m/Y', $dataFinalFormat) : null;
} else {
    echo "Datas não definidas no formulário.";
    // exit;
}

// $grouped_data = [dataInicialFormatUrl, $dataFinalFormatUrl];

// Requisição ao JSON
$url = 'http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial=' . $dataInicialFormatUrl . '%2000:00:00&DataFinal=' . $dataFinalFormatUrl . '%2023:59:59';
// $url = 'http://172.17.100.30:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial='.$dataInicialFormatUrl.'%2000:00:00&DataFinal='.$dataFinalFormatUrl.'%2023:59:59';
$json_data = file_get_contents($url);

if ($json_data === false) {
    echo "Erro ao acessar os dados.";
    exit;
}

$data = json_decode($json_data, true);

if ($data === null) {
    echo "Erro ao decodificar os dados.";
    exit;
}

// Filtros do formulário
$selectedMesorregiao = $_POST["mesorregiao"] ?? "Todas";
$selectedMicrorregiao = $_POST["microrregiao"] ?? "Todas";
$selectedMunicipio = $_POST["municipio"] ?? "Todos";
$selectedBacia = $_POST["bacia"] ?? "Todas";

// Verificação dos dados
if (!is_array($data)) {
    echo "Dados inválidos.";
    exit;
}

// Filtragem dos dados
$filtered_data = array_filter($data, function ($entry) use ($selectedMesorregiao, $selectedMicrorregiao, $selectedMunicipio, $selectedBacia, $dataInicial, $dataFinal) {
    $mesoregiaoMatch = ($selectedMesorregiao == "Todas" || $entry['mesoregiao'] == $selectedMesorregiao);
    $microregiaoMatch = ($selectedMicrorregiao == "Todas" || $entry['microregiao'] == $selectedMicrorregiao);
    $municipioMatch = ($selectedMunicipio == "Todos" || $entry['municipio'] == $selectedMunicipio);
    $baciaMatch = ($selectedBacia == "Todas" || $entry['bacia'] == $selectedBacia);
    $hora_leitura = new DateTime($entry['hora_leitura']);

    return $mesoregiaoMatch && $microregiaoMatch && $municipioMatch && $baciaMatch && $hora_leitura >= $dataInicial && $hora_leitura <= $dataFinal;
});

// Agrupamento dos dados mensais
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
            'latitude' => $entry['latitude'],
            'longitude' => $entry['longitude'], 
            'chuva_mensal' => array_fill(1, 12, 0)
        ];
    }

    $mes_num = (int) $hora_leitura->format('m');
    $grouped_data[$codigo_gmmc]['chuva_mensal'][$mes_num] += $entry['total_chuva'];
}


// Verifica se o botão foi clicado
if (isset($_POST['download_excel'])) {
    // Inicializa a variável $grouped_data
    $grouped_data = [];

    // Inicializa as variáveis das datas
    $dataInicialFormatUrl = $dataFinalFormatUrl = null;

    if (isset($_POST['dataInicial']) && isset($_POST['dataFinal'])) {
        $dataInicialExplode = explode("-", $_POST["dataInicial"]);
        $dataInicialFormat = isset($dataInicialExplode[2]) && isset($dataInicialExplode[1]) ? $dataInicialExplode[2] . "/" . $dataInicialExplode[1] . "/" . $dataInicialExplode[0] : null;
        $dataInicialFormatUrl = $dataInicialFormat ? $dataInicialExplode[0] . '-' . $dataInicialExplode[1] . '-' . $dataInicialExplode[2] : null;
        $dataInicial = $dataInicialFormat ? DateTime::createFromFormat('d/m/Y', $dataInicialFormat) : null;

        $dataFinalExplode = explode("-", $_POST["dataFinal"]);
        $dataFinalFormatUrl = isset($dataFinalExplode[2]) && isset($dataFinalExplode[1]) ? $dataFinalExplode[0] . '-' . $dataFinalExplode[1] . '-' . $dataFinalExplode[2] : null;
        $dataFinalFormat = $dataFinalFormatUrl ? $dataFinalExplode[2] . "/" . $dataFinalExplode[1] . "/" . $dataFinalExplode[0] : null;
        $dataFinal = $dataFinalFormat ? DateTime::createFromFormat('d/m/Y', $dataFinalFormat) : null;
    } else {
        echo "Datas não definidas no formulário.";
        exit;
    }

    // Requisição ao JSON
    $url = 'http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial=' . $dataInicialFormatUrl . '%2000:00:00&DataFinal=' . $dataFinalFormatUrl . '%2023:59:59';
    // $url = 'http://172.17.100.30:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial='.$dataInicialFormatUrl.'%2000:00:00&DataFinal='.$dataFinalFormatUrl.'%2023:59:59';
    $json_data = file_get_contents($url);

    if ($json_data === false) {
        echo "Erro ao acessar os dados.";
        exit;
    }

    $data = json_decode($json_data, true);

    if ($data === null) {
        echo "Erro ao decodificar os dados.";
        exit;
    }

    // Filtros do formulário
    $selectedMesorregiao = $_POST["mesorregiao"] ?? "Todas";
    $selectedMicrorregiao = $_POST["microrregiao"] ?? "Todas";
    $selectedMunicipio = $_POST["municipio"] ?? "Todos";
    $selectedBacia = $_POST["bacia"] ?? "Todas";

    // Verificação dos dados
    if (!is_array($data)) {
        echo "Dados inválidos.";
        exit;
    }

    // Filtragem dos dados
    $filtered_data = array_filter($data, function ($entry) use ($selectedMesorregiao, $selectedMicrorregiao, $selectedMunicipio, $selectedBacia, $dataInicial, $dataFinal) {
        $mesoregiaoMatch = ($selectedMesorregiao == "Todas" || $entry['mesoregiao'] == $selectedMesorregiao);
        $microregiaoMatch = ($selectedMicrorregiao == "Todas" || $entry['microregiao'] == $selectedMicrorregiao);
        $municipioMatch = ($selectedMunicipio == "Todos" || $entry['municipio'] == $selectedMunicipio);
        $baciaMatch = ($selectedBacia == "Todas" || $entry['bacia'] == $selectedBacia);
        $hora_leitura = new DateTime($entry['hora_leitura']);

        return $mesoregiaoMatch && $microregiaoMatch && $municipioMatch && $baciaMatch && $hora_leitura >= $dataInicial && $hora_leitura <= $dataFinal;
    });

    foreach ($filtered_data as $entry) {
    $hora_leitura = new DateTime($entry['hora_leitura']);
    $ano_mes = $hora_leitura->format('Y-m');
    $estacao = $entry['nome_estacao'];
    $codigo_gmmc = $entry['codigo_gmmc'];

    $latitude = "'" . (string)$entry['latitude'] . "'"; // Prefixo para texto
    $longitude = "'" . (string)$entry['longitude'] . "'"; // Prefixo para texto

    if (!isset($grouped_data[$codigo_gmmc])) {
        $grouped_data[$codigo_gmmc] = [
            'estacao' => $estacao,
            'mesoregiao' => $entry['mesoregiao'],
            'microregiao' => $entry['microregiao'],
            'municipio' => $entry['municipio'],
            'bacia' => $entry['bacia'],
            'latitude' =>  $latitude,
            'longitude' => $longitude,
            'chuva_mensal' => array_fill(1, 12, 0), // Inicializa todos os meses com 0
            'acumulado' => 0 // Inicializa acumulado com 0
        ];
    }

    $mes_num = (int) $hora_leitura->format('m');
    $grouped_data[$codigo_gmmc]['chuva_mensal'][$mes_num] += $entry['total_chuva'];
}

// Verifica se há dados para exportar
if (empty($grouped_data)) {
    echo "Não há dados para gerar o Excel.";
} else {
    $data_excel = [];

    // Adiciona cabeçalhos
    $headers = ['Estação', 'Mesorregião', 'Microrregião', 'Município', 'Bacia', 'Latitude', 'Longitude', 'Ano', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro', 'Acumulado'];
    $data_excel[] = $headers;

    // Adiciona dados
    foreach ($grouped_data as $codigo_gmmc => $info) {
        $valor_chuva_acumulado = array_sum($info['chuva_mensal']); // Calcula o total acumulado
        $row = [
            $info['estacao'],
            $info['mesoregiao'],
            $info['microregiao'],
            $info['municipio'],
            $info['bacia'],
            $info['latitude'],
            $info['longitude'],
            date('Y'), // Ano atual
        ];

        // Adiciona os valores mensais
        for ($i = 1; $i <= 12; $i++) {
            $chuva = $info['chuva_mensal'][$i];
            $row[] = $chuva > 0 ? number_format($chuva, 2, ',', '.') : '-';
        }

        // Adiciona o valor acumulado na última coluna
        $row[] = number_format($valor_chuva_acumulado, 2, ',', '.');

        $data_excel[] = $row;
    }

    // Criação do arquivo Excel
    $xlsx = SimpleXLSXGen::fromArray($data_excel);
    $filename = "monitoramento_mensal_" . date('Ymd') . ".xlsx";

    try {
        $xlsx = SimpleXLSXGen::fromArray($data_excel);
        if (!$xlsx) {
            throw new Exception('Falha ao criar o arquivo Excel.');
        }
        $xlsx->downloadAs('teste.xlsx');
        exit;
    } catch (Exception $e) {
        echo 'Erro ao gerar o arquivo Excel: ' . $e->getMessage();
    }

    // Envia o arquivo para download
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    readfile($filename);
    unlink($filename); // Remove o arquivo após o download
    exit;
}
}

// Exibição da tabela
echo "<table border='1'>";
echo "<tr>";
echo "<th>Estação</th>";
echo "<th>Mesorregião</th>";
echo "<th>Microrregião</th>";
echo "<th>Município</th>";
echo "<th>Bacia</th>";
echo "<th>Latitude</th>";
echo "<th>Longitude</th>";
echo "<th>Ano</th>";

foreach ($month_names as $month) {
    echo "<th>$month</th>";
}

echo "<th>Acumulado</th>";
echo "</tr>";

foreach ($grouped_data as $codigo_gmmc => $data) {
    $valor_chuva_acumulado = array_sum($data['chuva_mensal']);
    $ano = $dataInicial->format('Y');

    echo "<tr>";
    echo "<td>" . $data['estacao'] . "</td>";
    echo "<td>" . $data['mesoregiao'] . "</td>";
    echo "<td>" . $data['microregiao'] . "</td>";
    echo "<td>" . $data['municipio'] . "</td>";
    echo "<td>" . $data['bacia'] . "</td>";
    echo "<td>" . $data['latitude'] . "</td>";
    echo "<td>" . $data['longitude'] . "</td>";
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