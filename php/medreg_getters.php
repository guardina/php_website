<?php

    include "name_mapper.php";
    use GuzzleHttp\Client;


    function get_medreg_data_by_gln($gln, $register) {

        $url = "";
        $payload = "";

        if (in_array($register, ['medreg', 'psyreg'])) {
            $url = "https://www.healthreg-public.admin.ch/api/$register/public/person/search";

            // Payload sent to the website with a POST request, in order to obtain information about the doctor
            $payload = array(
                        /*"cetTitleKindIds" => null
                ,       "city" => null
                ,       "firstName" => null
                ,       "genderId" => null
                ,*/      "gln" => $gln
                /*,      "houseNumber" => null
                ,       "languageId" => null
                ,       "name" => null
                ,       "nationalityId" => null
                ,       "permissionCantonId" => null
                ,       "privateLawCetTitleKindIds" => null
                ,       "professionalPracticeLicenseId" => null
                ,       "professionId" => null
                ,       "street" => null
                ,       "zip" => null*/
            );

        } else if ($register == 'betreg') {
            $url = "https://www.healthreg-public.admin.ch/api/betreg/public/company/search";

            $payload = array(
                /*      "city" => null
                ,       "companyTypeId" => null
                ,*/     "glnCompany" => $gln
                /*,     "name" => null
                ,       "permissionCantonId" => null
                ,       "zip" => null*/
            );
        }


        if ($url == "") {
            echo '<p class="error"> Invalid register, please choose between one of the following: medreg, psyreg.</p>';
            return null;
        }
        

        $client = new Client();
    

        // Extra options to add to the request (api-key is necessary to obtain the JSON response)
        $options = [
            'headers' => [
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'en-CH; en',
                'api-key' => 'AB929BB6-8FAC-4298-BC47-74509E45A10B',
                'Connection' => 'keep-alive',
                'Content-Type' => 'application/json',
                'Host' => 'www.healthreg-public.admin.ch',
                'Origin' => 'https://www.healthreg-public.admin.ch',
                'Referer' => "https://www.healthreg-public.admin.ch/$register/search",
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0'
                //"User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0"
            ],
        ];

        $response = $client->post($url, ['json' => $payload, 'headers' => $options['headers']]);

        $result = json_decode($response->getBody(), true);

        $id = $result['entries'][0]['id'];

        if ($id == null) {
            return null;
        }

        $full_data_payload = ['id' => $id];
    


        if (in_array($register, ['medreg', 'psyreg'])) {
            $full_data_url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
            $response = $client->post($full_data_url, ['json' => $full_data_payload, 'headers' => $options['headers']]);
        } else if ($register == 'betreg') {
            if ($id == null) {
                return null;
            }
            $full_data_url = "https://www.healthreg-public.admin.ch/api/betreg/public/company/$id";
            $response = $client->get($full_data_url, ['headers' => $options['headers']]);
        }

        $full_result = json_decode($response->getBody(), true);

        $null_less_dictionary = array();
        
        if ($full_result === false) {
            echo "Error: ";
        } else {

            $flatten_dictionary = flatten_list($full_result, '', array());

            $flatten_dictionary = map_names($flatten_dictionary, $register);

            $new_flatten_dictionary = array();

            foreach($flatten_dictionary as $k => $v) {
                $new_flatten_dictionary[remove_extra($k)] = $v;
            }
            

            /*foreach($new_flatten_dictionary as $k => $v) {
                if (!empty($v)){
                    echo $k . ':     ' . $v . '<br>';
                }
            }*/

            // From the initial dictionary, we only extract the pairs key-value that actually have a value stored, as we cannot add empty values to a SQL table
            foreach($new_flatten_dictionary as $key => $value) {
                if (!empty($value)) {
                    $null_less_dictionary[$key] = $value;
                }
            }
        }

        return $null_less_dictionary;
    }



    // Recursive function to "flatten" nested dictionaries and stores the single values inside of the dictionar y, according to the key
    // Example: [key1 -> value1, key2 -> [key3 -> value3]] ===> key1 -> value1 / key2_key3 -> value3
    function flatten_list($list, $prefix, $resulting_dictionary) {

        $list_rejected = array('maxResultCount', 'tooManyResults', 'parentId', 'isActive', 'isNada', 'isBgmd', 'isEquivalent', 'isAcknowledgeable', '_isAcknowledgement', 'isFederal', '_id');

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