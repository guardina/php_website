<?php

    include "name_mapper.php";


    function get_data_from_gln($gln, $register) {

        $url = "";
        $payload = "";

        if (in_array($register, ['medreg', 'psyreg'])) {
            $url = "https://www.healthreg-public.admin.ch/api/$register/public/person/search";

            // Payload sent to the website with a POST request, in order to obtain information about the doctor
            $payload = array(
                /*"cetTitleKindIds" => null, 
                "city" => null, 
                "firstName" => null, 
                "genderId" => null,*/ 
                "gln" => $gln
                /*"houseNumber" => null, 
                "languageId" => null, 
                "name" => null, 
                "nationalityId" => null, 
                "permissionCantonId" => null, 
                "privateLawCetTitleKindIds" => null, 
                "professionalPracticeLicenseId" => null, 
                "professionId" => null, 
                "street" => null, 
                "zip" => null*/
            );

        } else if ($register == 'betreg') {
            $url = "https://www.healthreg-public.admin.ch/api/betreg/public/company/search";

            $payload = array(
                /*"city" => null, 
                "companyTypeId" => null,*/
                "glnCompany" => $gln
                /*"name" => null, 
                "permissionCantonId" => null, 
                "zip" => null*/
            );
        }


        if ($url == "") {
            echo '<p class="error"> Invalid register, please choose between one of the following: medreg, psyreg.</p>';
            return null;
        }
        


        $ch = curl_init();
    

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
            "Referer: https://www.healthreg-public.admin.ch/$register/search",
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-origin",
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0"
        );


        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    

        $output = curl_exec($ch);
        $result = json_decode($output, true);

        $id = $result['entries'];
        $id = $id[0];
        $id = $id['id'];


        $full_data_url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        $full_data_payload = array('id' => $id);


        curl_setopt($ch, CURLOPT_URL, $full_data_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($full_data_payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $full_output = curl_exec($ch);
        $full_result = json_decode($full_output, true);


        $null_less_dictionary = array();
        
        if ($full_output === false) {
            echo "Error: " . curl_error($ch);
        } else {

            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($http_status < 200 || $http_status >= 300) {
                return null;
            }


            $flatten_dictionary = flatten_list($full_result, '', array());
            

            foreach($flatten_dictionary as $k => $v) {
                if (!empty($v)){
                    echo $k . ' ' . $v . '<br>';
                }
            }

            // From the initial dictionary, we only extract the pairs key-value that actually have a value stored, as we cannot add empty values to a SQL table
            foreach($flatten_dictionary as $key => $value) {
                if (!empty($value)) {
                    $null_less_dictionary[$key] = $value;
                }
            }
        }

        curl_close($ch);

        return $null_less_dictionary;
    }



    // Recursive function to "flatten" nested dictionaries and stores the single values inside of the dictionar y, according to the key
    // Example: [key1 -> value1, key2 -> [key3 -> value3]] ===> key1 -> value1 / key2_key3 -> value3
    function flatten_list($list, $prefix, $resulting_dictionary) {

        $list_rejected = array('maxResultCount', 'tooManyResults', 'parentId', 'isActive', 'isNada', 'isBgmd', 'isEquivalent', 'isAknowledgeable', 'isFederal', '_id');

        foreach ($list as $key => $value) {
            if (is_array($value)) {
                $resulting_dictionary = flatten_list($value, $prefix . $key . '_', $resulting_dictionary);
            } else {
                $ignore = false;
                $string = $prefix . $key;
                foreach($list_rejected as $substring) {
                    if (strpos($string, $substring) !== false) {
                        $ignore = true;
                        break;
                    }
                }

                if (!$ignore) {
                    $resulting_dictionary[$string] = $value;
                }
            }
        }
        return $resulting_dictionary;
    }
?>