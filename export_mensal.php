<?php
require 'libs/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

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
$export_data = [];

// Adicione a linha do cabeçalho ao export_data
$header = array_merge(['Estação', 'Mesorregião', 'Microrregião', 'Município', 'Bacia', 'Latitude', 'Longitude', 'Ano'], array_values($month_names), ['Acumulado']);
$export_data[] = $header;

if (isset($_POST['dataInicial']) && isset($_POST['dataFinal'])) {
    $dataInicialFormatUrl = date('Y-m-d', strtotime(str_replace('/', '-', $_POST["dataInicial"])));
    $dataFinalFormatUrl = date('Y-m-d', strtotime(str_replace('/', '-', $_POST["dataFinal"])));
    $dataInicial = DateTime::createFromFormat('Y-m-d', $dataInicialFormatUrl);
    $dataFinal = DateTime::createFromFormat('Y-m-d', $dataFinalFormatUrl);

    // Substitua a URL pelo endpoint correto
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

    $selectedMesorregiao = $_POST["mesorregiao"] ?? "Todas";
    $selectedMicrorregiao = $_POST["microrregiao"] ?? "Todas";
    $selectedMunicipio = $_POST["municipio"] ?? "Todos";
    $selectedBacia = $_POST["bacia"] ?? "Todas";

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
        $ano = $hora_leitura->format('Y');
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
                'ano' => $ano,
                'chuva_mensal' => array_fill(1, 12, 0)
            ];
        }

        $grouped_data[$codigo_gmmc]['chuva_mensal'][$mes_num] += $entry['total_chuva'];
    }

    foreach ($grouped_data as $codigo_gmmc => $data) {
        $latitude = "'" . (string)$data['latitude'] . "'"; // Prefixo para texto
        $longitude = "'" . (string)$data['longitude'] . "'"; // Prefixo para texto
        $linha = [
            $data['estacao'],
            $data['mesoregiao'],
            $data['microregiao'],
            $data['municipio'],
            $data['bacia'],
            $latitude,
            $longitude,
            $data['ano']
        ];

        $valor_chuva_acumulado = 0;
        foreach ($data['chuva_mensal'] as $mes => $chuva) {
            $chuva_formatado = '-';
            $data_mes = DateTime::createFromFormat('Y-m-d', $data['ano'] . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-01');

            if ($data_mes >= $dataInicial && $data_mes <= $dataFinal) {
                if ($chuva > 0) {
                    $chuva_formatado = number_format($chuva, 2, ',', '.');
                    $valor_chuva_acumulado += $chuva;
                }
            }

            $linha[] = $chuva_formatado;
        }

        $linha[] = $valor_chuva_acumulado > 0 ? number_format($valor_chuva_acumulado, 2, ',', '.') : '-';
        $export_data[] = $linha;
    }
}

$xlsx = SimpleXLSXGen::fromArray($export_data);
$xlsx->downloadAs('historico_mensal'. '_' .$dataInicialFormatUrl. '&'. $dataFinalFormatUrl .'xlsx');
?>
