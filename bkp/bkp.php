<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento de Chuvas</title>
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
    <div>
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
    $selectedMesorregiao = $_POST["mesorregiao"] ?? "Todas";
    $selectedMicrorregiao = $_POST["microrregiao"] ?? "Todas";
    $selectedMunicipio = $_POST["municipio"] ?? "Todos";
    $selectedBacia = $_POST["bacia"] ?? "Todas";
    $exibirMensal = isset($_POST['tipoBoletimPeriodo']) && $_POST['tipoBoletimPeriodo'] === 'Mensal';

    // Filtragem dos dados
    $filtered_data = array_filter($data, function($entry) use ($selectedMesorregiao, $selectedMicrorregiao, $selectedMunicipio, $selectedBacia) {
        $mesoregiaoMatch = ($selectedMesorregiao == "Todas" || $entry['mesoregiao'] == $selectedMesorregiao);
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

    // Organizar os dias ou meses como colunas e exibir o resultado
    echo "<table border='1'>";
    echo "<tr><th>Estação</th><th>Mesorregião</th><th>Microrregião</th><th>Município</th><th>Bacia</th><th>";

    if ($exibirMensal) {
        echo "Ano</th><th>Janeiro</th><th>Fevereiro</th><th>Março</th><th>Abril</th><th>Maio</th><th>Junho</th><th>Julho</th><th>Agosto</th><th>Setembro</th><th>Outubro</th><th>Novembro</th><th>Dezembro";
    } else {
        echo "Ano/Mês</th>";
        for ($day = 1; $day <= 31; $day++) {
            echo "<th>" . str_pad($day, 2, '0', STR_PAD_LEFT) . "</th>";
        }
    }

    echo "<th>Acumulado</th></tr>";

    $unique_entries = [];

    foreach ($filtered_data as $entry) {
        $hora_leitura = new DateTime($entry['hora_leitura']);
        $ano_mes = $hora_leitura->format('Y-m');
        $estacao = $entry['nome_estacao'];
        $codigo_gmmc = $entry['codigo_gmmc'];

        if (isset($unique_entries[$ano_mes][$codigo_gmmc])) {
            continue; // Pula entradas repetidas com o mesmo código_gmmc no mesmo mês/ano
        }

        $unique_entries[$ano_mes][$codigo_gmmc] = true;

        $valor_chuva_acumulado = 0;
        echo "<tr>";
        echo "<td>" . $estacao . "</td>";
        echo "<td>" . $entry['mesoregiao'] . "</td>";
        echo "<td>" . $entry['microregiao'] . "</td>";
        echo "<td>" . $entry['municipio'] . "</td>";
        echo "<td>" . $entry['bacia'] . "</td>";
        echo "<td>";
        if ($exibirMensal) {
            $ano = (new DateTime($ano_mes . '-01'))->format('Y');
            echo $ano;
        } else {
            echo $hora_leitura->format('Y/m'); // Exibe apenas o ano e o mês
        }
        echo "</td>";

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
                    echo "<td>-</td>";
                } else {
                    echo "<td>" . number_format($chuva_mensal[$mes], 2, ',', '') . "</td>";
                    $valor_chuva_acumulado += $chuva_mensal[$mes];
                }
            }
        } else {
            // Para o tipo Diário, a coluna "Ano/Mês" exibe apenas o ano e o mês.
            for ($day = 1; $day <= 31; $day++) {
                $day_str = str_pad($day, 2, '0', STR_PAD_LEFT);
                $value = isset($grouped_data[$ano_mes][$codigo_gmmc][$day_str]) ? $grouped_data[$ano_mes][$codigo_gmmc][$day_str] : '-';
                echo "<td>" . ($value === '-' ? '-' : number_format($value, 2, ',', '')) . "</td>";
                if ($value !== '-') {
                    $valor_chuva_acumulado += $value;
                }
            }
        }

        echo "<td>" . number_format($valor_chuva_acumulado, 2, ',', '') . "</td>";
        echo "</tr>";
    }

    echo "</table>";
    ?>
    </div>
</body>
</html>