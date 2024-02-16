<?php

    include_once "controller_db.php";
    include "name_mapper.php";

    $requests_per_bucket = 100;


    // Function that will do more HTTP requests at the same time. All results are then gathered. The number of requests at the same time is defined in $requests_per_bucket
    function makeParallelRequests($url, $payloads) {
        global $requests_per_bucket; 

        $handles = [];
        $results = [];

        $multiHandle = curl_multi_init();


        // The most important header option to keep is the api-key; withouth that, the response will be empty.
        // In case the request is empty even if the logic here is correct and the api-key is present, double check if the api-key was changed
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'en-CH; en',
            'api-key' => 'AB929BB6-8FAC-4298-BC47-74509E45A10B',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/json',
            'Host' => 'www.healthreg-public.admin.ch',
            'Origin' => 'https://www.healthreg-public.admin.ch',
            'Referer' => "https://www.healthreg-public.admin.ch/medreg/search",
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            //'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0'
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0"
        ];


        // Create cURL handles for each request
        for($i = 0; $i < $requests_per_bucket; $i++) {
            $ch = curl_init($url);
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payloads[$i]),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'api-key: AB929BB6-8FAC-4298-BC47-74509E45A10B']
            ];
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($multiHandle, $ch);
            $handles[] = $ch;
        }

        // Execute all cURL requests simultaneously
        $active = null;
        do {
            $status = curl_multi_exec($multiHandle, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        // Loop and retrieve the results
        while ($active && $status === CURLM_OK) {
            if (curl_multi_select($multiHandle) === -1) {
                usleep(100);
            }

            do {
                $status = curl_multi_exec($multiHandle, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);
        }

        // Retrieve the results and close handles
        foreach ($handles as $ch) {
            $results[] = json_decode(curl_multi_getcontent($ch), true);
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        // Close the multi cURL handler
        curl_multi_close($multiHandle);

        return $results;
    }


    // Function that puts every first and last name in the correct format (first letter uppercase, the rest lowercase)
    function format_name($string) {
        $names = explode(' ', $string);
    
        foreach ($names as &$name) {
            $lowercase_name = strtolower($name);
            $name = ucfirst($lowercase_name);
        }
        unset($name);
    
        $formatted_string = implode(' ', $names);
    
        return $formatted_string;
    }


    function format_date($string) {
        $dateObject = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $string);

        return $dateObject->format('Y-m-d H:i:s');
    }


    // Returns true if the provided id is present in the databse, such that we can avoid a bucket of requests. Only works when downloading the whole database the first time, as afterwards it could
    // skip newly added ids. For instance, if it's checking the ids between 1 and 100, this function will only check if 1 is in the DB and assume also the other values up to 100 are in the DB 
    // (if true is returned). If a new id 40 is added, it will not be checked. 
    // The function is used in case there was some problem while downloading the first time and the process was halted; we avoid trying to redownload the whole database.
    function already_exists_in_db($conn, $register, $id) {
        $select_query = "SELECT id from " . substr($register, 0, -3) . "_gln WHERE id=$id";
        $result = mysqli_query($conn, $select_query);

        return mysqli_num_rows($result) > 0;
    }


    // Function used to create a 1-dimensional array of the downloaded data and remove nested arrays.
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



    // $list can contain all possible keys and values obtainable from the MedReg website, this function returns only the selected keys (specified in $names)
    function get_entries_by_names($list, $names) {
        $entries = array();

        foreach($names as $name) {
            if (isset($list[$name]) && $list[$name] != "") {
                if ($name == 'firstName' || $name == 'lastName') {
                    $entries[$name] = format_name($list[$name]);
                } else if (strpos(strtolower($name), 'date') !== false) {
                    $entries[$name] = format_date($list[$name]);
                } else if ($name == 'selfDispensationEn' || $name == 'permissionBtmEn') {
                    $list[$name] = ($list[$name] == 'Yes' ? 1 : 0);
                } else {
                    $entries[$name] = $list[$name];
                }
            }
        }

        return $entries;
    }


    // Properly format strings and integers to be inserted inside a SQL table
    function format_values($values) {
        $formattedValue = "";
        foreach($values as $value) {
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



    function download_all_medreg_data($register, $number_of_samples) {

        $conn = connect_to_db("stammdaten_gln");

        $existing_ids = get_existing_ids($register);

        global $requests_per_bucket;

        $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        $bucket = 1;
        $total = 0;
        $count = 0;

        for ($i = 0; $i<$number_of_samples; $i+=$requests_per_bucket) {
            echo "[Bucket $bucket] Starting data download!\n";

            $curr_id = $existing_ids[$count];


            if (already_exists_in_db($conn, $register, $curr_id)) {
                echo "[Bucket $bucket] Already present in database\n\n";
                $bucket++;
                $count+=100;
                continue;
            }


            $payloads = [];
            for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                $payloads[] = ['id' => $existing_ids[$j]];
            }   
            

            $start_time = microtime(true);
            $results = makeParallelRequests($url, $payloads);
            $end_time = microtime(true);

            $total_time = $end_time - $start_time;

            foreach ($results as $result) {

                // HERE WE CAN TAKE THE DATA, AS RESULT HAS FIRST NAME, LAST NAME, AND SO ON

                $flatten_result = flatten_list($result, '', array());
                $flatten_result = map_names($flatten_result, $register);


                $med_gln_keys = array("gln", "lastName", "firstName", "genderDe", "genderFr", "genderIt", "genderEn", "yearOfBirth", "uid", "hasPermission", "hasProvider90Days");
                $med_languages_keys = array("gln", "languageDe", "languageFr", "languageIt", "languageEn");
                $med_nationalities_keys = array("gln", "nationalityDe", "nationalityFr", "nationalityIt", "nationalityEn");
                $med_professions_keys = array("gln", "professionDe", "professionFr", "professionIt", "professionEn", "diyplomaTypeDe", "diyplomaTypeFr", "diyplomaTypeIt", "diyplomaTypeEn", "issuanceDate", "issuanceCountryDe", "issuanceCountryFr", "issuanceCountryIt", "issuanceCountryEn", "dateMebeko", "providers90Days", " hasPermissionOtherThanNoLicence");
                $med_permissions_keys = array("gln", "professionEn", "permissionTypeDe", "permissionTypeFr", "permissionTypeIt", "permissionTypeEn", "permissionStateDe", "permissionStateFr", "permissionStateIt", "permissionStateEn", "permissionActivityStateDe", "permissionActivityStateFr", "permissionActivityStateIt", "permissionActivityStateEn", "cantonDe", "cantonFr", "cantonIt", "cantonEn", "dateDecision", "dateActivity", "restrictions");
                $med_permissionaddress_keys = array("gln", "professionEn", "dateDecision", "practiceCompanyName", "streetWithNumber", "zipCity", "zip", "city", "phoneNumber1", "phoneNumber2", "phoneNumber3", "faxnumber", "uid", "selfDispensationEn", "permissionBtmEn");
                $med_cettitles_keys = array("gln", "professionEn", "cetTitleTypeDe", "cetTitleTypeFr", "cetTitleTypeIt", "cetTitleTypeEn", "cetTitleKindDe", "cetTitleKindFr", "cetTitleKindit", "cetTitleKindEn", "issuanceCountryDe", "issuanceCountryFr", "issuanceCountryIt", "issuanceCountryEn", "issuanceDate", "dateMebeko");
                $med_privatelawcettitles_keys = array("gln", "professionEn", "privateLawCetTitleTypeDe", "privateLawCetTitleTypeFr", "privateLawCetTitleTypeIt", "privateLawCetTitleTypeEn", "privateLawCetTitleKindDe", "privateLawCetTitleKindFr", "privateLawCetTitleKindIt", "privateLawCetTitleKindEn", "issuanceDate");



                // Query for med_gln
                $entries = get_entries_by_names($flatten_result, $med_gln_keys);
                $query = "";
                if (!empty($entries)) {
                    $columns = array_keys($entries);
                    $values = array_values($entries);

                    $formattedValue = format_values($values);

                    $query = "INSERT INTO med_gln(id, " . implode(', ', $columns) . ") VALUES ($curr_id, $formattedValue)";
                $conn->query($query);
                }


                // Query for med_languages
                $entries = get_entries_by_names($flatten_result, $med_languages_keys);
                $query = "";
                if (!empty($entries)) {
                    $columns = array_keys($entries);
                    $values = array_values($entries);

                    $formattedValue = format_values($values);

                    $query = "INSERT INTO med_languages(" . implode(', ', $columns) . ") VALUES ($formattedValue)";
                $conn->query($query);
                }


                // Query for med_nationalities
                $entries = get_entries_by_names($flatten_result, $med_nationalities_keys);
                $query = "";
                if (!empty($entries)) {
                    $columns = array_keys($entries);
                    $values = array_values($entries);

                    $formattedValue = format_values($values);

                    $query = "INSERT INTO med_nationalities(" . implode(', ', $columns) . ") VALUES ($formattedValue)";
                    $conn->query($query);
                }


                // Query for med_professions
                $entries = get_entries_by_names($flatten_result, $med_professions_keys);
                $query = "";
                if (!empty($entries)) {
                    $columns = array_keys($entries);
                    $values = array_values($entries);

                    $formattedValue = format_values($values);

                    $query = "INSERT INTO med_professions(" . implode(', ', $columns) . ") VALUES ($formattedValue)";
                    $conn->query($query);
                }


                // Query for med_permissions
                $entries = get_entries_by_names($flatten_result, $med_permissions_keys);
                $query = "";
                if (!empty($entries)) {
                    $columns = array_keys($entries);
                    $values = array_values($entries);

                    $formattedValue = format_values($values);

                    $query = "INSERT INTO med_permissions(" . implode(', ', $columns) . ") VALUES ($formattedValue)";
                $conn->query($query);
                }


                // Query for med_permissionAddress
                $entries = get_entries_by_names($flatten_result, $med_permissionaddress_keys);
                $query = "";
                if (!empty($entries)) {
                    $entries = map_names($entries, table_name : 'med_permissionAddress');
                    $columns = array_keys($entries);
                    $values = array_values($entries);

                    $formattedValue = format_values($values);

                    $query = "INSERT INTO med_permissionAddress(" . implode(', ', $columns) . ") VALUES ($formattedValue)";
                $conn->query($query);
                }


                // Query for med_cetTitles
                $entries = get_entries_by_names($flatten_result, $med_cettitles_keys);
                $query = "";
                if (!empty($entries)) {
                    $entries = map_names($entries, table_name : 'med_cettitles');
                    $columns = array_keys($entries);
                    $values = array_values($entries);

                    $formattedValue = format_values($values);

                    $query = "INSERT INTO med_cetTitles(" . implode(', ', $columns) . ") VALUES ($formattedValue)";
                $conn->query($query);
                }


                // Query for med_privateLawCetTitles
                $entries = get_entries_by_names($flatten_result, $med_privatelawcettitles_keys);
                $query = "";
                if (!empty($entries)) {
                    $entries = map_names($entries, table_name : 'med_privatelawcettitles');
                    $columns = array_keys($entries);
                    $values = array_values($entries);

                    $formattedValue = format_values($values);

                    $query = "INSERT INTO med_privateLawCetTitles(" . implode(', ', $columns) . ") VALUES ($formattedValue)";
                $conn->query($query);
                }

                $curr_id = $existing_ids[$count++];
            }


            echo "[Bucket $bucket] Time elapsed: " . $total_time . "\n\n";

            $bucket++;
        }

        mysqli_close($conn);
    }


    if ($argc < 3) {
        echo "Usage: php download_ids.php <register> <number of samples>\n";
        echo "Possible registers: medreg, psyreg, betreg\n";
        echo "Range for samples: 100 - 200000\n";
        exit(1);
    }

    $register = $argv[1];
    $number_of_samples = $argv[2];
    download_all_medreg_data($register, $number_of_samples);
?>