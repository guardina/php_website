<?php

require_once __DIR__ . '/vendor/autoload.php';



function nextColumn($column) {
    $length = strlen($column);
    $result = '';
    $carry = 1;
    
    for ($i = $length - 1; $i >= 0; $i--) {
        $ascii = ord($column[$i]) + $carry;
        
        if ($ascii > ord('Z')) {
            $ascii -= 26;
            $carry = 1;
        } else {
            $carry = 0;
        }
        
        $result = chr($ascii) . $result;
    }
    
    if ($carry) {
        $result = 'A' . $result;
    }
    
    return $result;
}







$directory = "excels/";

foreach (scandir($directory) as $filename) {
    if (pathinfo($filename, PATHINFO_EXTENSION) == 'xlsx' || pathinfo($filename, PATHINFO_EXTENSION) == 'xls') {
        $file_path = $directory . $filename;

        $data = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $sheet = $data->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        echo "$highestColumn\n";
        $column_names = [];

        $currentColumn = 'A';

        if ($filename == '2024 Restmittel.xlsx') {
            $row = 14;
        } else if ($filename == 'neu VB in Arbeit Kursanmeldungen 2023_2024.xlsx') {
            $row = 4;
        } else {
            $row = 1;
        }

        while ($currentColumn != $highestColumn) {
            $cellValue = $sheet->getCell($currentColumn . $row)->getValue();
            //echo "Processing column: $cellValue\n"; // Debugging output
            $column_names[] = $sheet->getCell($currentColumn . $row)->getValue();
            $currentColumn = nextColumn($currentColumn);
        }

        echo "File: $filename\n";
        echo "Column names: " . implode(", ", $column_names) . "\n";
    }
}

?>
