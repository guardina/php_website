<?php
    function save_medreg_doctor_by_gln($gln, $myDictionary, $columnMapping) {

        $url = "https://www.healthreg-public.admin.ch/api/medreg/public/person/search";


        $ch = curl_init();
    
    
        // ARRAY FOR MEDREG
        // Payload sent to the website with a POST request, in order to obtain information about the doctor
        $data = array(
                "cetTitleKindIds" => null, 
                "city" => null, 
                "firstName" => null, 
                "genderId" => null, 
                "gln" => $gln, 
                "houseNumber" => null, 
                "languageId" => null, 
                "name" => null, 
                "nationalityId" => null, 
                "permissionCantonId" => null, 
                "privateLawCetTitleKindIds" => null, 
                "professionalPracticeLicenseId" => null, 
                "professionId" => null, 
                "street" => null, 
                "zip" => null
        );
    
        


        // OPTIONS FOR THE MEDREG
        // Extra options to add to the request (api-key is necessary to obtain the JSON response)
        $options = array (
            "Accept: application/json, text/plain, */*",
            "Accept-Encoding: gzip, deflate, br",
            "Accept-Language: en-CH; en",
            "api-key: AB929BB6-8FAC-4298-BC47-74509E45A10B",
            "Connection: keep-alive",
            "Content-Type: application/json",
            "Host: www.healthreg-public.admin.ch",
            "Origin: https://www.healthreg-public.admin.ch",
            "Referer: https://www.healthreg-public.admin.ch/medreg/search",
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-origin",
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0"
        );



        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options);

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