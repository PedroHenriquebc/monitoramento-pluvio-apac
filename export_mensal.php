<?php
// Inclua o autoload básico para carregar as classes do PhpSpreadsheet
require 'libs/PhpSpreadsheet/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crie a planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Adicione dados à planilha
$sheet->setCellValue('A1', 'Estação');
$sheet->setCellValue('B1', 'Mesorregião');
$sheet->setCellValue('C1', 'Microrregião');
$sheet->setCellValue('D1', 'Município');
$sheet->setCellValue('E1', 'Bacia');
$sheet->setCellValue('F1', 'Latitude');
$sheet->setCellValue('G1', 'Longitude');
// Continue a adicionar colunas e dados conforme necessário

// Salve o arquivo como Excel
$writer = new Xlsx($spreadsheet);
$filename = 'relatorio_mensal.xlsx';

// Enviar o arquivo para download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit();
?>
