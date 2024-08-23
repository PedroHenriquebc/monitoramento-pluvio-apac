<?php
// Passo 1: Realizar a requisição HTTP para obter os dados
$url = "http://dados.apac.pe.gov.br:41120/blank_json_boletim_chuva_diaria/blank_json_boletim_chuva_diaria.php?DataInicial=2024-02-16%2000:00:00&DataFinal=2024-04-16%2000:00:00";
$json = file_get_contents($url);
$data = json_decode($json, true);

// Passo 2: Verificar se os dados foram carregados corretamente
if ($data === null) {
    die('Erro ao carregar os dados.');
}

echo "<pre>";
print_r($data);
echo "</pre>";

// Passo 3: Agrupar os dados por codigo_gmmc
// $groupedData = [];
// foreach ($data as $record) {
//     // Verificar se as chaves existem antes de acessar
//     if (isset($record['codigo_gmmc']) && isset($record['total_chuva'])) {
//         $codigo_gmmc = $record['codigo_gmmc'];
//         $total_chuva = $record['total_chuva'];
//         $data_leitura = $record['maxima_chuva']; // Usando a chave `maxima_chuva` como exemplo

//         if (!isset($groupedData[$codigo_gmmc])) {
//             $groupedData[$codigo_gmmc] = [];
//         }
//         $groupedData[$codigo_gmmc][] = [
//             'total_chuva' => $total_chuva,
//             'data_leitura' => $data_leitura,
//             'nome_estacao' => $record['nome_estacao'],
//             'municipio' => $record['municipio'],
//             // Adicione outras chaves conforme necessário
//         ];
//     }
// }

// Passo 4: Exibir os dados em uma tabela HTML
?>
<!-- <!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dados Agrupados por código_gmmc</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>codigo_gmmc</th>
                <th>nome_estacao</th>
                <th>municipio</th>
                <th>total_chuva</th>
                <th>maxima_chuva</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Exibir os dados agrupados
            // foreach ($groupedData as $codigo_gmmc => $records) {
            //     foreach ($records as $record) {
            //         echo "<tr>";
            //         echo "<td>$codigo_gmmc</td>";
            //         echo "<td>{$record['nome_estacao']}</td>";
            //         echo "<td>{$record['municipio']}</td>";
            //         echo "<td>{$record['total_chuva']}</td>";
            //         echo "<td>{$record['data_leitura']}</td>";
            //         echo "</tr>";
            //     }
            // }
            ?>
        </tbody>
    </table>
</body>
</html> -->