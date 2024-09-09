<?php
require 'libs/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

// Processamento das datas e parâmetros
$dataInicialExplode = explode("-", $_POST["dataInicial"]);
$dataInicialFormat = $dataInicialExplode[2] . "/" . $dataInicialExplode[1] . "/" . $dataInicialExplode[0];
$dataInicialFormatUrl = $dataInicialExplode[0] . '-' . $dataInicialExplode[1] . '-' . $dataInicialExplode[2];
$dataInicial = DateTime::createFromFormat('d/m/Y', $dataInicialFormat);

$dataFinalExplode = explode("-", $_POST["dataFinal"]);
$dataFinalFormatUrl = $dataFinalExplode[0] . '-' . $dataFinalExplode[1] . '-' . $dataFinalExplode[2];
$dataFinalFormat = $dataFinalExplode[2] . "/" . $dataFinalExplode[1] . "/" . $dataFinalExplode[0];
$dataFinal = DateTime::createFromFormat('d/m/Y', $dataFinalFormat);

$selectedMesorregiao = $_POST["mesorregiao"] ?? "Todas";
$selectedMicrorregiao = $_POST["microrregiao"] ?? "Todas";
$selectedMunicipio = $_POST["municipio"] ?? "Todos";
$selectedBacia = $_POST["bacia"] ?? "Todas";
$exibirMensal = isset($_POST['tipoBoletimPeriodo']) && $_POST['tipoBoletimPeriodo'] === 'Mensal';

// URL e dados JSON
// $url = 'http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial='.$dataInicialFormatUrl.'%2000:00:00&DataFinal='.$dataFinalFormatUrl.'%2023:59:59';
$url = 'http://172.17.100.30:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial='.$dataInicialFormatUrl.'%2000:00:00&DataFinal='.$dataFinalFormatUrl.'%2023:59:59';
$json_data = file_get_contents($url);
$data = json_decode($json_data, true);

// Filtragem de dados
$filtered_data = array_filter($data, function($entry) use ($selectedMesorregiao, $selectedMicrorregiao, $selectedMunicipio, $selectedBacia) {
    $mesoregiaoMatch = ($selectedMesorregiao == "Todas" || $entry['mesoregiao'] == $selectedMesorregiao);
    $microregiaoMatch = ($selectedMicrorregiao == "Todas" || $entry['microregiao'] == $selectedMicrorregiao);
    $municipioMatch = ($selectedMunicipio == "Todos" || $entry['municipio'] == $selectedMunicipio);
    $baciaMatch = ($selectedBacia == "Todas" || $entry['bacia'] == $selectedBacia);

    return $mesoregiaoMatch && $microregiaoMatch && $municipioMatch && $baciaMatch;
});

// Ordenar os dados filtrados por mês (hora_leitura)
usort($filtered_data, function($a, $b) {
    $dateA = new DateTime($a['hora_leitura']);
    $dateB = new DateTime($b['hora_leitura']);
    return $dateA <=> $dateB;
});

$grouped_data = [];

foreach ($filtered_data as $entry) {
    $hora_leitura = new DateTime($entry['hora_leitura']);
    $ano_mes = $hora_leitura->format('Y-m');
    $codigo_gmmc = $entry['codigo_gmmc'];

    if (!isset($grouped_data[$ano_mes])) {
        $grouped_data[$ano_mes] = [];
    }

    if (!isset($grouped_data[$ano_mes][$codigo_gmmc])) {
        $grouped_data[$ano_mes][$codigo_gmmc] = [];
    }

    $grouped_data[$ano_mes][$codigo_gmmc][$hora_leitura->format('d')] = $entry['total_chuva'];
}

$header = [
    "Município", "Estação", "Latitude", "Longitude", "Microrregião", "Mesorregião", "Bacia", $exibirMensal ? "Ano" : "Ano/Mês"
];

if ($exibirMensal) {
    $header = array_merge($header, ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"]);
} else {
    for ($day = 1; $day <= 31; $day++) {
        $header[] = str_pad($day, 2, '0', STR_PAD_LEFT);
    }
}

$header[] = "Acumulado";

$rows = [];
$unique_entries = [];

foreach ($filtered_data as $entry) {
    $hora_leitura = new DateTime($entry['hora_leitura']);
    $ano_mes = $hora_leitura->format('Y-m');
    $codigo_gmmc = $entry['codigo_gmmc'];

    $latitude = "'" . (string)$entry['latitude'] . "'"; // Prefixo para texto
    $longitude = "'" . (string)$entry['longitude'] . "'"; // Prefixo para texto

    if (isset($unique_entries[$ano_mes][$codigo_gmmc])) {
        continue;
    }

    $unique_entries[$ano_mes][$codigo_gmmc] = true;

    $row = [
        $entry['municipio'],
        $entry['nome_estacao'],
        $latitude, // Valor tratado como texto
        $longitude, // Valor tratado como texto
        $entry['microregiao'],
        $entry['mesoregiao'],
        $entry['bacia'],
        $exibirMensal ? (new DateTime($ano_mes . '-01'))->format('Y') : $hora_leitura->format('Y/m')
    ];

    $valor_chuva_acumulado = 0;

    if ($exibirMensal) {
        $chuva_mensal = array_fill(1, 12, 0);

        foreach ($grouped_data as $mes => $estacoes) {
            foreach ($estacoes as $cod_gmmc => $dias) {
                if ($cod_gmmc === $codigo_gmmc) {
                    foreach ($dias as $dia => $valor) {
                        $data_dia = new DateTime("$mes-$dia");
                        $mes_num = (int) $data_dia->format('m');
                        if ($data_dia >= $dataInicial && $data_dia <= $dataFinal) {
                            $chuva_mensal[$mes_num] += $valor;
                        }
                    }
                }
            }
        }

        $primeiro_mes = (int) $dataInicial->format('m');
        $ultimo_mes = (int) $dataFinal->format('m');

        for ($mes = 1; $mes <= 12; $mes++) {
            if ($mes < $primeiro_mes || $mes > $ultimo_mes) {
                $row[] = '-';
            } else {
                $row[] = number_format($chuva_mensal[$mes], 2, ',', '');
                $valor_chuva_acumulado += $chuva_mensal[$mes];
            }
        }
    } else {
        for ($day = 1; $day <= 31; $day++) {
            $day_str = str_pad($day, 2, '0', STR_PAD_LEFT);
            $value = isset($grouped_data[$ano_mes][$codigo_gmmc][$day_str]) ? $grouped_data[$ano_mes][$codigo_gmmc][$day_str] : '-';
            $row[] = $value === '-' ? '-' : number_format($value, 2, ',', '');
            if ($value !== '-') {
                $valor_chuva_acumulado += $value;
            }
        }
    }

    $row[] = number_format($valor_chuva_acumulado, 2, ',', '');

    $rows[] = $row;
}

$final_data = array_merge([$header], $rows);
$xlsx = SimpleXLSXGen::fromArray($final_data);
// $xlsx->downloadAs('historico_diario.xlsx');
$xlsx->downloadAs('historico_pluvio_diario'. '_' .$dataInicialFormat. '&'. $dataFinalFormat .'.xlsx');
?>
