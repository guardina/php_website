<?php

    include "../php/controller_db.php";

    function map_xml_to_list() {
        $xml = simplexml_load_file('refdata_partner_2024-01-25.xml');
        
        if ($xml === false) {
            die('Failed to load XML file: ' . $this->fpath);
        }
        
        $ns = 'http://refdatabase.refdata.ch/Partner_out';
        
        $data_dicts = [];
        $roles_dicts = [];

        
        foreach ($xml->xpath(".//*[local-name()='ITEM' and namespace-uri()='{$ns}']") as $item) {
            $gln = (int)$item->GLN;
            
            $data_dict = [
                'gln' => $gln,
                'effective_dt' => explode('T', (string)$item['DT'])[0],
                'status_date' => explode('.', (string)$item->STDATE)[0],
                'ptype' => (string)$item->PTYPE,
                'status' => (string)$item->STATUS,
                'lang' => (string)$item->LANG,
                'descr1' => (string)$item->DESCR1,
                'descr2' => (string)$item->DESCR2
            ];

            $data_dicts[] = $data_dict;
            
            foreach ($item->xpath(".//*[local-name()='ROLE' and namespace-uri()='{$ns}']") as $i => $role) {
                $roles_dict = [
                    'gln' => $gln,
                    'role_nr' => $i + 1,
                    'effective_dt' => $data_dict['effective_dt']
                ];

                foreach (['TYPE', 'STREET', 'STRNO', 'POBOX', 'ZIP', 'CITY', 'CTN', 'CNTRY'] as $attr) {
                    $val = $role->{$attr};
                    $roles_dict[strtolower($attr)] = isset($val) ? (string)$val : null;
                }

                $roles_dicts[] = $roles_dict;
            }
        }
        
        return [$data_dicts, $roles_dicts];
    }



    function format_date($string) {
        $dateObject = DateTime::createFromFormat('Y-m-d\TH:i:s', $string);

        return $dateObject->format('Y-m-d H:i:s');
    }



    function format_values($keys, $values) {
        $formattedValue = "";

        $count = 0;
        for($i = 0; $i < count($values); $i++) {
            $key = $keys[$i];
            $value = $values[$i];

            if (preg_match('/date/', strtolower($key))) {
                $value = format_date($value);
            }

            if (is_numeric($value)) {
                $formattedValue .= $value . ", ";
            } else {
                $value = str_replace('"', '\"', $value);
                $formattedValue .= "\"" . $value . "\", ";
            }
        }

        $formattedValue = rtrim($formattedValue, ", ") . "";

        return $formattedValue;
    }





    function save_data_in_db() {

        $conn = connect_to_db('stammdaten_gln');

        $results = map_xml_to_list();

        $datas_dict = $results[0];
        $roles_dict = $results[1];

        $count = 0;

        for ($i = 0; $i < count($datas_dict); $i++) {
            $datas = $datas_dict[$i];
            $roles = $roles_dict[$i];

            foreach($datas as $key => $data) {
                if ($data == "" || $data == null || $data =='[]') {
                    unset($datas[$key]);
                }
            }

            $data_columns = array_keys($datas);
            $data_values = array_values($datas);
            $data_formattedValues = format_values($data_columns, $data_values);

            $query = "INSERT INTO refdata_partner(" . implode(', ', $data_columns) . ") VALUES ($data_formattedValues)";
            echo "$query\n";
            $conn->query($query);


            foreach($roles as $key => $role) {
                if ($role == "" || $role == null || $role =='[]') {
                    unset($roles[$key]);
                }
            }

            $role_columns = array_keys($roles);
            $role_values = array_values($roles);
            $role_formattedValues = format_values($role_columns, $role_values);

            $query = "INSERT INTO refdata_partner_role(" . implode(', ', $role_columns) . ") VALUES ($role_formattedValues)";
            echo "$query\n";
            $conn->query($query);

            echo "\n\n";
        }

        mysqli_close($conn);
    }


    save_data_in_db();

?>