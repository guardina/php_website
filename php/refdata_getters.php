<?php
     function save_refdata_doctor_by_gln($gln, $myDictionary, $columnMapping) {

        $url = "https://refdatabase.refdata.ch/Viewer/SearchPartnerByGln?Lang=de";

        $ch = curl_init();

        $data = array(
            'SearchGln' => $gln,
            'Sort' => '',
            'NewSort' => '',
            'IsAscending' => 'False',
            'Reset' => 'False'
        );
        



        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    

        $output = curl_exec($ch);
        $result = json_decode($output, true);


        

        if ($output === false) {
            echo "Error: " . curl_error($ch);
        } else {
            get_single_element($result, '', $myDictionary);

            $conn = connect_to_db("myDB");


            // From the initial dictionary, we only extract the pairs key-value that actually have a value stored, as we cannot add empty values to a SQL table
            $non_empty_dict = array();

            foreach($myDictionary as $key => $value) {
                if (!empty($value)) {
                    $non_empty_dict[$key] = $value;
                }
            }


            // myDictionary has keys taken from the HTML response provided by the site, we need to map them to the column's names in the SQL table
            $columns = array();
            $values = array();

            $columns[] = 'gln';
            $values[] = "$gln";

            foreach ($non_empty_dict as $key => $value) {
                if (isset($columnMapping[$key])) {
                    $columns[] = $columnMapping[$key];
                    $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
                }
            }
        


            
            $query = "INSERT INTO Doctors (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";


            mysqli_query($conn, $query);

            mysqli_close($conn);
        }

        curl_close($ch);
    }
?>