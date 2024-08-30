<?php
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

$grouped_data = [];

if (isset($_POST['dataInicial']) && isset($_POST['dataFinal'])) {
    $dataInicialFormatUrl = date('Y-m-d', strtotime(str_replace('/', '-', $_POST["dataInicial"])));
    $dataFinalFormatUrl = date('Y-m-d', strtotime(str_replace('/', '-', $_POST["dataFinal"])));
    $dataInicial = DateTime::createFromFormat('Y-m-d', $dataInicialFormatUrl);
    $dataFinal = DateTime::createFromFormat('Y-m-d', $dataFinalFormatUrl);

    $url = 'http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial=' . $dataInicialFormatUrl . '%2000:00:00&DataFinal=' . $dataFinalFormatUrl . '%2023:59:59';
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

    $selectedMesorregiao = $_POST["mesorregiao"] ?? "Todas";
    $selectedMicrorregiao = $_POST["microrregiao"] ?? "Todas";
    $selectedMunicipio = $_POST["municipio"] ?? "Todos";
    $selectedBacia = $_POST["bacia"] ?? "Todas";

    if (!is_array($data)) {
        echo "Dados inválidos.";
        exit;
    }

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
        $mes_num = (int) $hora_leitura->format('m');
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

        $grouped_data[$codigo_gmmc]['chuva_mensal'][$mes_num] += $entry['total_chuva'];
    }
}
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
    <form method="post" action="export_mensal.php">
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

<!-- Exibição da tabela -->
<table border='1'>
    <tr>
        <th>Estação</th>
        <th>Mesorregião</th>
        <th>Microrregião</th>
        <th>Município</th>
        <th>Bacia</th>
        <th>Latitude</th>
        <th>Longitude</th>
        <th>Ano</th>

        <?php foreach ($month_names as $month): ?>
            <th><?php echo $month; ?></th>
        <?php endforeach; ?>

        <th>Acumulado</th>
    </tr>

    <?php foreach ($grouped_data as $codigo_gmmc => $data): ?>
        <?php
        $valor_chuva_acumulado = array_sum($data['chuva_mensal']);
        $ano = $dataInicial->format('Y');
        ?>
        <tr>
            <td><?php echo $data['estacao']; ?></td>
            <td><?php echo $data['mesoregiao']; ?></td>
            <td><?php echo $data['microregiao']; ?></td>
            <td><?php echo $data['municipio']; ?></td>
            <td><?php echo $data['bacia']; ?></td>
            <td><?php echo $data['latitude']; ?></td>
            <td><?php echo $data['longitude']; ?></td>
            <td><?php echo $ano; ?></td>
            
            <?php foreach ($data['chuva_mensal'] as $chuva): ?>
                <td><?php echo $chuva > 0 ? number_format($chuva, 2, ',', '.') : '-'; ?></td>
            <?php endforeach; ?>

            <td><?php echo $valor_chuva_acumulado > 0 ? number_format($valor_chuva_acumulado, 2, ',', '.') : '-'; ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
