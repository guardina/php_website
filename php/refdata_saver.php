<?php
    $xmlFile = 'refdata_partner_2024-01-25.xml';

    $xml = simplexml_load_file($xmlFile);

    if ($xml === false) {
        $error = libxml_get_last_error();
        die('Failed to load XML file: ' . $xmlFile . ' - ' . $error->message);
    }
    
    /*foreach ($xml->children() as $item) {
        echo $item->getName();
    }*/
?>