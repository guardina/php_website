<?php
     function get_refdata_data_by_gln($gln) {

        $url = "https://refdatabase.refdata.ch/Viewer/SearchPartnerByGln?Lang=de";

        $ch = curl_init();

        $data = array(
                    'SearchGln' => $gln
        ,           'Sort' => ''
        ,           'NewSort' => ''
        ,           'IsAscending' => 'False'
        ,           'Reset' => 'False'
        );


        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    

        $output = curl_exec($ch);
        
        $dom = new DOMDocument();
        @$dom->loadHTML($output);

        $xpath = new DOMXPath($dom);


        $key_table = $xpath->query('//table[@id="GVResult"]')->item(0);

        if ($key_table) {
            foreach ($key_table->getElementsByTagName('tr') as $row) {
                foreach ($row->getElementsByTagName('td') as $cell) {
                    echo 'Cell Content: ' . $cell->nodeValue . '<br>';
                }
            }
        }


        $value_table = $xpath->query('//table[@id="GVResultb"]')->item(0);

        if ($value_table) {
            foreach ($value_table->getElementsByTagName('tr') as $row) {
                foreach ($row->getElementsByTagName('td') as $cell) {
                    echo 'Cell Content: ' . $cell->nodeValue . '<br>';
                }
            }
        }

        
        

        if ($output === false) {
            echo "Error: " . curl_error($ch);
        } else {
            
        }

        curl_close($ch);
    }
?>