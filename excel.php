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


function isExcelDate($value) {
    return is_numeric($value) && $value > 40000 && $value < 50000 && floor($value) == $value;
}


function excelDateToUnixTimestamp($excelDate) {
    $unixEpoch = strtotime('1899-12-30 00:00:00'); 
    
    if ($excelDate >= 60) {
        $excelDate++;
    }

    $unixTimestamp = ($excelDate - 1) * 86400 + $unixEpoch;
    return date('Y-m-d', $unixTimestamp);
}




function writeOnHTML($sheet, $filename, $currentColumn, $currentRow){
    if ($sheet->getCell($currentColumn . $currentRow)->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
        $cellValue = $sheet->getCell($currentColumn . $currentRow)->getCalculatedValue();
    } else if (isExcelDate($sheet->getCell($currentColumn . $currentRow)->getValue())) {
        $cellValue = excelDateToUnixTimestamp($sheet->getCell($currentColumn . $currentRow)->getValue());
    } else {
        $cellValue = $sheet->getCell($currentColumn . $currentRow)->getValue();
    }
    

    $cellColor = $sheet->getStyle($currentColumn . $currentRow)->getFill()->getStartColor()->getRGB();

    if ($cellValue == null || $cellValue == "") {
        return "<td></td>"; 
    }


    if ($filename == 'neu VB in Arbeit Kursanmeldungen 2023_2024.xlsx' && $currentRow >= 198 && $currentRow <= 206) {
        return "<td style='color: $cellColor;'>$cellValue</td>";
    } else {
        return "<td>$cellValue</td>";
    }
}



$directory = "excels/";

$html = "<html><head><title>Extracted information</title></head><body>";

foreach (scandir($directory) as $filename) {
    if (pathinfo($filename, PATHINFO_EXTENSION) == 'xlsx' || pathinfo($filename, PATHINFO_EXTENSION) == 'xls') {
        $file_path = $directory . $filename;

        $data = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $sheet = $data->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $column_names = [];

        $currentColumn = 'A';

        if ($filename == '2024 Restmittel.xlsx') {
            $titleRow = 14;
        } else if ($filename == 'neu VB in Arbeit Kursanmeldungen 2023_2024.xlsx') {
            $titleRow = 4;
        } else {
            $titleRow = 1;
        }

        $html .= "<table>";
        $html .= "<p>$filename</p>";

        $html .= "<tr>";

        $html .= "<th>ID</th>";

        while ($currentColumn != $highestColumn) {
            $titleCellValue = $sheet->getCell($currentColumn . $titleRow)->getValue();
            $html .= "<th>$titleCellValue</th>";    
            $column_names[] = $titleCellValue;

            $currentColumn = nextColumn($currentColumn);
        }

        $html .= "</tr>";

        $currentRow = $titleRow+1;


        for ($row = $highestRow; $row >= 1; $row--) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            if (!empty($cellValue)) {
                $lastRow = $row;
                break;
            }
        }

        
        while ($currentRow <= $lastRow) {
            $html .= "<tr>";
            $html .= "<td>$currentRow</td>";
            $currentColumn = 'A';
            $empty = true;
            while ($currentColumn != $highestColumn) {
                $html .= writeOnHTML($sheet, $filename, $currentColumn, $currentRow);
                
                $currentColumn = nextColumn($currentColumn);
            }

            $html .= writeOnHTML($sheet, $filename, $currentColumn, $currentRow);
            $currentRow++;

            if (!$empty) {
                echo "\n";
            }
            $html .= "</tr>";
        }
    }

    $html .= "</table>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    $html .= "<br>";
    
}

$html .= "</body></html>";

file_put_contents('extracted_information.html', $html);

?>