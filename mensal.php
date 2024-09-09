<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
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
        $dataInicialExplode = explode("-", $_POST["dataInicial"]);
        $dataInicialFormat = $dataInicialExplode[2] . "/" . $dataInicialExplode[1] . "/" . $dataInicialExplode[0];
        $dataFinalExplode = explode("-", $_POST["dataFinal"]);
        $dataFinalFormat = $dataFinalExplode[2] . "/" . $dataFinalExplode[1] . "/" . $dataFinalExplode[0];
        
        $dataInicialFormatUrl = date('Y-m-d', strtotime(str_replace('/', '-', $_POST["dataInicial"])));
        $dataFinalFormatUrl = date('Y-m-d', strtotime(str_replace('/', '-', $_POST["dataFinal"])));
        $dataInicial = DateTime::createFromFormat('Y-m-d', $dataInicialFormatUrl);
        $dataFinal = DateTime::createFromFormat('Y-m-d', $dataFinalFormatUrl);

        // Garantir que o horário seja comparável, adicionando hora inicial e final corretamente
        $dataInicial->setTime(0, 0, 0);
        $dataFinal->setTime(23, 59, 59);

        // $url = 'http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial=' . $dataInicialFormatUrl . '%2000:00:00&DataFinal=' . $dataFinalFormatUrl . '%2023:59:59';
        $url = 'http://172.17.100.30:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial='.$dataInicialFormatUrl.'%2012:00:00&DataFinal='.$dataFinalFormatUrl.'%2012:00:00';
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

            // Comparação de hora_leitura dentro do intervalo incluindo o mesmo dia
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
} else {
    header("Location: http://dados.apac.pe.gov.br:41120/boletins/historico-pluvio/");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Pluviométrico Mensal</title>
    <link rel="icon" type="image/x-icon" href="icons8-rain-48.png">
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

        p {
            color: #929292;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            table, th, td {
                font-size: 12px;
            }
        }

        /* From Uiverse.io by barisdogansutcu */ 
        .button {
        position: relative;
        width: 150px;
        height: 40px;
        cursor: pointer;
        display: flex;
        margin: auto;
        align-items: center;
        border: 1px solid #17795E;
        background-color: #209978;
        overflow: hidden;
        
        }

        .button, .button__icon, .button__text {
        transition: all 0.3s;
        }

        .button .button__text {
        transform: translateX(22px);
        color: #fff;
        font-weight: 600;
        }

        .button .button__icon {
        position: absolute;
        transform: translateX(109px);
        height: 100%;
        width: 35px;
        background-color: #17795E;
        display: flex;
        align-items: center;
        justify-content: center;
        }

        .button .svg {
        width: 20px;
        fill: #fff;
        }

        .button:hover {
        background: #17795E;
        }

        .button:hover .button__text {
        color: transparent;
        }

        .button:hover .button__icon {
        width: 148px;
        transform: translateX(0);
        }

        .button:active .button__icon {
        background-color: #146c54;
        }

        .button:active {
        border: 1px solid #146c54;
        }

    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="logo3_apac_2024.png" alt="Logo ou Imagem">
    <p><?php echo 'Histórico Pluviométrico Mensal  -  ' . $dataInicialFormat . ' à ' . $dataFinalFormat; ?></p>
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
        
        <!-- <button type="submit" name="download_excel" style="padding: 10px 20px; font-size: 16px; background-color: #679dd6; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
            Baixar Excel
        </button> -->

        
        <button class="button" type="submit" name="download_excel">
            <span class="button__text">Download</span>
            <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 35 35" id="bdd05811-e15d-428c-bb53-8661459f9307" data-name="Layer 2" class="svg"><path d="M17.5,22.131a1.249,1.249,0,0,1-1.25-1.25V2.187a1.25,1.25,0,0,1,2.5,0V20.881A1.25,1.25,0,0,1,17.5,22.131Z"></path><path d="M17.5,22.693a3.189,3.189,0,0,1-2.262-.936L8.487,15.006a1.249,1.249,0,0,1,1.767-1.767l6.751,6.751a.7.7,0,0,0,.99,0l6.751-6.751a1.25,1.25,0,0,1,1.768,1.767l-6.752,6.751A3.191,3.191,0,0,1,17.5,22.693Z"></path><path d="M31.436,34.063H3.564A3.318,3.318,0,0,1,.25,30.749V22.011a1.25,1.25,0,0,1,2.5,0v8.738a.815.815,0,0,0,.814.814H31.436a.815.815,0,0,0,.814-.814V22.011a1.25,1.25,0,1,1,2.5,0v8.738A3.318,3.318,0,0,1,31.436,34.063Z"></path></svg></span>
        </button>   

    </form>
</div>

<!-- Exibição da tabela -->
<table border='1'>
    <tr>
        <th>Município</th>
        <th>Estação</th>
        <th>Latitude</th>
        <th>Longitude</th>
        <th>Microrregião</th>
        <th>Mesorregião</th>
        <th>Bacia</th>
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
            <td><?php echo $data['municipio']; ?></td>
            <td><?php echo $data['estacao']; ?></td>
            <td><?php echo $data['latitude']; ?></td>
            <td><?php echo $data['longitude']; ?></td>
            <td><?php echo $data['microregiao']; ?></td>
            <td><?php echo $data['mesoregiao']; ?></td>
            <td><?php echo $data['bacia']; ?></td>
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
