<?php

    include_once "controller_db.php";
    include "name_mapper.php";

    $requests_per_bucket = 100;

    function makeParallelRequests($url, $payloads) {
        global $requests_per_bucket; 

        $handles = [];
        $results = [];

        // Initialize multi cURL handler
        $multiHandle = curl_multi_init();


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
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0'
            //"User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0"
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


    // NEED TO CHANGE / DELETE
    function format_name($string) {
        $lowercase_string = strtolower($string);
        return ucfirst($lowercase_string);
    }


    // NEED TO CHANGE / DELETE
    function already_exists_in_db($conn, $register, $id) {
        $select_query = "SELECT id from " . substr($register, 0, -3) . "_ids WHERE id=$id";
        $result = mysqli_query($conn, $select_query);

        return mysqli_num_rows($result) > 0;
    }


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




    function get_entries_by_names($list, $names) {
        $entries = array();

        foreach($names as $name) {
            $entries[] = $list[$name];
        }

        return $entries;
    }



    function download_all_medreg_data($register, $number_of_samples) {

        $conn = connect_to_db("stammdaten_gln");

        $existing_ids = get_existing_ids($register);

        global $requests_per_bucket;

        $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        $bucket = 1;
        $total = 0;

        for ($i = 0; $i<$number_of_samples; $i+=$requests_per_bucket) {
            echo "[Bucket $bucket] Starting data download!\n";

            $payloads = [];
            for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                $payloads[] = ['id' => $existing_ids[$j]];
            }   
            

            $start_time = microtime(true);
            $results = makeParallelRequests($url, $payloads);
            $end_time = microtime(true);

            $total_time = $end_time - $start_time;

            $count = 0;

            foreach ($results as $result) {

                // HERE WE CAN TAKE THE DATA, AS RESULT HAS FIRST NAME, LAST NAME, AND SO ON

                $flatten_result = flatten_list($result, '', array());
                $flatten_result = map_names($flatten_result, $register);

                $curr_id = $existing_ids[$count++];


                $med_gln_keys = array("gln", "lastName", "firstName", "genderDe", "genderFr", "genderIt", "genderEn", "yearOfBirth", "uid", "hasPermission", "hasProvider90Days");
                $med_permissionaddress_keys = array("gln", "professionEn", "dateDecision", "practiceCompanyName", "streetWithNumber", "zipCity", "zip", "city", "phoneNumber1", "phoneNumber2", "phoneNumber3", "faxnumber", "uid", "selfDispensationEn", "permissionBtmEn");
                $med_permissions_keys = array("gln", "professionEn", "permissionTypeDe", "permissionTypeFr", "permissionTypeIt", "permissionTypeEn", "permissionStateDe", "permissionStateFr", "permissionStateIt", "permissionStateEn", "permissionActivityStateDe", "permissionActivityStateFr", "permissionActivityStateIt", "permissionActivityStateEn", "cantonDe", "cantonFr", "cantonIt", "cantonEn", "dateDecision", "dateActivity", "restrictions");
                $med_languages_keys = array("gln", "languageDe", "languageFr", "languageIt", "languageEn");
                $med_nationalities_keys = array("gln", "nationalityDe", "nationalityFr", "nationalityIt", "nationalityEn");
                $med_cettitles_keys = array("gln", "professionEn", "cetTitleTypeDe", "cetTitleTypeFr", "cetTitleTypeIt", "cetTitleTypeEn", "cetTitleKindDe", "cetTitleKindFr", "cetTitleKindit", "cetTitleKindEn", "issuanceCountryDe", "issuanceCountryFr", "issuanceCountryIt", "issuanceCountryEn", "issuanceDate", "dateMebeko");
                $med_privatelawcettitles_keys = array("gln", "professionEn", "privateLawCetTitleTypeDe", "privateLawCetTitleTypeFr", "privateLawCetTitleTypeIt", "privateLawCetTitleTypeEn", "privateLawCetTitleKindDe", "privateLawCetTitleKindFr", "privateLawCetTitleKindIt", "privateLawCetTitleKindEn", "issuanceDate");





                // Query for med_gln
                $entries = get_entries_by_names($flatten_result, $med_gln_keys);
                $query = "";
                if (count($entries) > 0) {
                    $query = "INSERT INTO med_gln(gln, lastName, firstname, genderDe, genderFr, genderIt, genderEn, yearOfBirth, uid, hasPermission, hasProvider90Days) VALUES (" . implode(', ', $entries) . ")";
                //$conn->query($query);
                }
        
                echo $query . "\n\n";


                // Query for med_permissionaddress
                $entries = get_entries_by_names($flatten_result, $med_permissionaddress_keys);
                $query = "";
                if (count($entries) > 0) {
                    $query = "INSERT INTO med_permissionaddress(gln, professionEn, dateDecision, practiceCompanyName, streetWithNumber, zipCity, zip, city, phoneNumber1, phoneNumber2, phoneNumber3, faxNumber, uid, selfDispensation, permissionBtm) VALUES (" . implode(', ', $entries) . ")";
                //$conn->query($query);
                }
        
                echo $query . "\n\n";


                // Query for med_permissions
                $entries = get_entries_by_names($flatten_result, $med_permissions_keys);
                $query = "";
                if (count($entries) > 0) {
                    $query = "INSERT INTO med_permissions(gln, professionEn, permissionTypeDe, permissionTypeFr, permissionTypeIt, permissionTypeEn, permissionStateDe, permissionStateFr, permissionStateIt, permissionStateEn, permissionActivityStateDe, permissionActivityStateFr, permissionActivityStateIt, permissionActivityStateEn, cantonDe, cantonFr, cantonIt, cantonEn, dateDecision, dateActivity, restrictions) VALUES (" . implode(', ', $entries) . ")";
                //$conn->query($query);
                }
        
                echo $query . "\n\n";


                // Query for med_languages
                $entries = get_entries_by_names($flatten_result, $med_languages_keys);
                $query = "";
                if (count($entries) > 0) {
                    $query = "INSERT INTO med_languages(gln, languageDe, languageFr, languageIt, languageEn) VALUES (" . implode(', ', $entries) . ")";
                //$conn->query($query);
                }
        
                echo $query . "\n\n";


                // Query for med_nationalities
                $entries = get_entries_by_names($flatten_result, $med_nationalities_keys);
                $query = "";
                if (count($entries) > 0) {
                    $query = "INSERT INTO med_nationalities(gln, nationalityDe, nationalityFr, nationalityIt, nationalityEn) VALUES (" . implode(', ', $entries) . ")";
                //$conn->query($query);
                }
        
                echo $query . "\n\n";


                // Query for med_cettitles
                $entries = get_entries_by_names($flatten_result, $med_cettitles_keys);
                $query = "";
                if (count($entries) > 0) {
                    $query = "INSERT INTO med_cettitles(gln, professionEn, titleTypeDe, titleTypeFr, titleTypeIt, titleTypeEn, titleKindDe, titleKindFr, titleKindIt, titleKindEn, issuanceCountryDe, issuanceCountryFr, issuanceCountryIt, issuanceCountryEn, issuanceDate, dateMebeko) VALUES (" . implode(', ', $entries) . ")";
                //$conn->query($query);
                }
        
                echo $query . "\n\n";


                // Query for med_privatelawcettitles
                $entries = get_entries_by_names($flatten_result, $med_privatelawcettitles_keys);
                $query = "";
                if (count($entries) > 0) {
                    $query = "INSERT INTO med_privatelawcettitles(gln, professionEn, titleTypeDe, titleTypeFr, titleTypeIt, titleTypeEn, titleKindDe, titleKindFr, titleKindIt, titleKindEn, issuanceDate) VALUES (" . implode(', ', $entries) . ")";
                //$conn->query($query);
                }
        
                echo $query . "\n\n\n\n";





                /*foreach(get_entries_by_names($flatten_result, $med_gln_keys) as $v) {
                    echo $v . "\n";
                }
                echo "\n\n\n";*/

                /*foreach($flatten_result as $k => $v) {
                    echo $k . ": " . $v . "\n";
                }

                echo "\n\n";*/




                /*$query = "INSERT INTO " . substr($register, 0, -3) . "_ids(id, bucket, round_1) VALUES ($curr_id, $bucket, ";

                if ($result !== null) {
                    $query .= "1)";
                } else {
                    $query .= "0)";
                }*/

                //$conn->query($query);

                $curr_id++;
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