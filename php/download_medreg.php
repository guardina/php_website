<?php

    include_once "controller_db.php";
    include "name_mapper.php";
    include "medreg_HTTP_controller.php";

    $requests_per_bucket = 100;
    $can_have_multiple = array('permissions', 'cetTitles', 'privateLawCetTitles', 'addresses');


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


    // Function used to create a 1-dimensional array of the downloaded data and remove nested arrays.
    function flatten_list($list, $prefix, $resulting_dictionary, $increase_by_one = false) {

        global $can_have_multiple;

        $list_rejected = array('maxResultCount', 'tooManyResults', 'parentId', 'isActive', 'isNada', 'isBgmd', 'isEquivalent', 'isAcknowledgeable', '_isAcknowledgement', 'isFederal', '_id');
        

        foreach ($list as $key => $value) {
            if ($increase_by_one) {
                ++$key;
            }

            if (is_array($value)) {
                if (in_array($key, $can_have_multiple)) {
                    $resulting_dictionary = flatten_list($value, $prefix . $key . '_', $resulting_dictionary, true);
                } else {
                    $resulting_dictionary = flatten_list($value, $prefix . $key . '_', $resulting_dictionary, false);
                }
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


        /*foreach ($resulting_dictionary as $k => $v) {
            echo $k . " -- " . $v . "\n";
        }

        echo "\n\n";*/

        return $resulting_dictionary;
    }



    // $list can contain all possible keys and values obtainable from the MedReg website, this function returns only the selected keys (specified in $names)
    function get_entries_by_names($list, $names) {
        $entries = array();

        foreach($names as $name) {
            foreach ($list as $key => $value) {
                if (strpos($key, $name) !== false && $list[$key] != "") {
                    if ($name == 'firstName' || $name == 'lastName' || $name == 'familyName') {
                        $entries[$key] = format_name($list[$key]);
                    } else if (strpos(strtolower($name), 'date') !== false) {
                        $entries[$key] = format_date($list[$key]);
                    } else if ($name == 'selfDispensationEn' || $name == 'permissionBtmEn') {
                        $entries[$key] = ($list[$key] == 'Yes' ? 1 : 0);
                    } else if ($name == 'poBox') {
                        $entries[$key] = strval($list[$key]);
                    } else {
                        $entries[$key] = $list[$key];
                    }
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


    function check_if_id_needed($table_name, $count, $columns, $formattedValue) {
        if ($table_name == 'med_gln' || $table_name == 'psy_gln') {
            $query = "INSERT INTO " . $table_name . "(id, " . implode(', ', $columns) . ") VALUES ($count, $formattedValue)";
        } else if ($table_name == 'bet_companyGln' || $table_name == 'bet_responsiblePersons') {
            $query = "INSERT INTO " . $table_name . "(bag_id, " . implode(', ', $columns) . ") VALUES ($count, $formattedValue)";
        } else {
            $query = "INSERT INTO " . $table_name . "(" . implode(', ', $columns) . ") VALUES ($formattedValue)";
        }

        return $query;
    }


    function shorten_extra($string) {
        $patterns = '/.*[0-9]_+/';
        return preg_replace($patterns, '', $string);
    }



    function download_all_medreg_data($register, $number_of_samples) {

        global $can_have_multiple;

        $conn = connect_to_db("stammdaten_gln");

        //$existing_ids = get_existing_ids($register);

        global $requests_per_bucket;

        if (in_array($register, ['medreg', 'psyreg'])) {
            $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        } else if ($register == 'betreg') {
            $url = "https://www.healthreg-public.admin.ch/api/betreg/public/company";
        }

        $bucket = 1;
        $count = 0;

        for ($i = 0; $i<$number_of_samples; $i+=$requests_per_bucket) {
            echo $i . " ---- " . $number_of_samples . "\n";
            echo "[Bucket $bucket] Starting data download! ($register)\n";

            //$curr_id = $existing_ids[$count];


            /*if (bucket_already_exists_in_db($conn, $register, $i, $i+$requests_per_bucket)) {
                echo "[Bucket $bucket] Already present in database ($register)\n\n";
                $bucket++;
                $count+=100;
                continue;
            }*/


            if (in_array($register, ['medreg', 'psyreg'])) {
                $payloads = [];
                for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                    $payloads[] = ['id' => $j];
                }   

                $start_time = microtime(true);
                $results = makeParallelRequests($url, $payloads, $register);
                $end_time = microtime(true);

            } else if ($register == 'betreg') {
                $urls = [];
                $payloads = [];
                for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                    $urls[] = $url . "/" . $j;
                }   

                $start_time = microtime(true);
                $results = makeParallelRequests($urls, $payloads, $register);
                $end_time = microtime(true);
            }

            $total_time = $end_time - $start_time;

            foreach ($results as $result) {

                // HERE WE CAN TAKE THE DATA, AS RESULT HAS FIRST NAME, LAST NAME, AND SO ON

                if ($result == null) {
                    $count++;
                    continue;
                }

                $flatten_result = flatten_list($result, '', array());
                $flatten_result = map_names($flatten_result, $register);

                /*foreach ($flatten_result as $k => $v) {
                    echo $k . ": " . $v . "\n";
                }

                echo "\n\n";*/


                $multiple_max_values = [];

                foreach ($can_have_multiple as $key) {
                    $maxNumber = -1;
                    foreach ($flatten_result as $res_key => $res_val) {
                        if (preg_match("/". $key . "_(\d+)/", $res_key, $matches)) {
                            $number = intval($matches[1]);
                            $maxNumber = max($maxNumber, $number);
                        }
                    }
                    $multiple_max_values[$key] = $maxNumber;
                }

                $need_addr_nr = ['med_permissionAddress', 'psy_permissionAddress'];
                $need_perm_nr = ['med_permissions', 'med_permissionAddress', 'psy_permissions', 'psy_permissionAddress'];
                $need_pr_title_nr = ['med_privateLawCetTitles'];
                $need_title_nr = ['med_cetTitles', 'psy_cetTitles', 'psy_permissions', 'psy_permissionAddress'];

                $keys_to_use = [];
                $table_names_to_use = [];

                if ($register == 'medreg') {
                    $keys_to_use[] = array("gln", "lastName", "firstName", "genderDe", "genderFr", "genderIt", "genderEn", "yearOfBirth", "uid", "hasPermission", "hasProvider90Days");
                    $keys_to_use[] = array("gln", "languageDe", "languageFr", "languageIt", "languageEn");
                    $keys_to_use[] = array("gln", "nationalityDe", "nationalityFr", "nationalityIt", "nationalityEn");
                    $keys_to_use[] = array("gln", "professionDe", "professionFr", "professionIt", "professionEn", "diyplomaTypeDe", "diyplomaTypeFr", "diyplomaTypeIt", "diyplomaTypeEn", "issuanceDate", "issuanceCountryDe", "issuanceCountryFr", "issuanceCountryIt", "issuanceCountryEn", "dateMebeko", "providers90Days", " hasPermissionOtherThanNoLicence");
                    $keys_to_use[] = array("gln", "professionEn", "permissionTypeDe", "permissionTypeFr", "permissionTypeIt", "permissionTypeEn", "permissionStateDe", "permissionStateFr", "permissionStateIt", "permissionStateEn", "permissionActivityStateDe", "permissionActivityStateFr", "permissionActivityStateIt", "permissionActivityStateEn", "cantonDe", "cantonFr", "cantonIt", "cantonEn", "dateDecision", "dateActivity", "restrictions");
                    $keys_to_use[] = array("gln", "professionEn", "dateDecision", "practiceCompanyName", "streetWithNumber", "zipCity", "zip", "city", "phoneNumber1", "phoneNumber2", "phoneNumber3", "faxnumber", "uid", "selfDispensationEn", "permissionBtmEn");
                    $keys_to_use[] = array("gln", "professionEn", "cetTitleTypeDe", "cetTitleTypeFr", "cetTitleTypeIt", "cetTitleTypeEn", "cetTitleKindDe", "cetTitleKindFr", "cetTitleKindit", "cetTitleKindEn", "issuanceCountryDe", "issuanceCountryFr", "issuanceCountryIt", "issuanceCountryEn", "issuanceDate", "dateMebeko");
                    $keys_to_use[] = array("gln", "professionEn", "privateLawCetTitleTypeDe", "privateLawCetTitleTypeFr", "privateLawCetTitleTypeIt", "privateLawCetTitleTypeEn", "privateLawCetTitleKindDe", "privateLawCetTitleKindFr", "privateLawCetTitleKindIt", "privateLawCetTitleKindEn", "issuanceDate");

                    $table_names_to_use = ["med_gln", "med_languages", "med_nationalities", "med_professions", "med_permissions", "med_permissionAddress", "med_cetTitles", "med_privateLawCetTitles"];

                } else if ($register == 'psyreg') {
                    $keys_to_use[] = array("gln", "lastName", "firstName", "genderDe", "genderFr", "genderIt", "genderEn", "yearOfBirth", "uid", "hasPermission", "hasProvider90Days");
                    $keys_to_use[] = array("gln", "languageDe", "languageFr", "languageIt", "languageEn");
                    $keys_to_use[] = array("gln", "nationalityDe", "nationalityFr", "nationalityIt", "nationalityEn");
                    $keys_to_use[] = array("gln", "professionDe", "professionFr", "professionIt", "professionEn", "diyplomaTypeDe", "diyplomaTypeFr", "diyplomaTypeIt", "diyplomaTypeEn", "issuanceDate", "issuanceCountryDe", "issuanceCountryFr", "issuanceCountryIt", "issuanceCountryEn", "datePsyko");
                    $keys_to_use[] = array("gln", "professionDe", "professionFr", "professionIt", "professionEn", "titleTypeDe", "titleTypeFr", "titleTypeIt", "titleTypeEn", "titleKindDe", "titleKindFr", "titleKindit", "titleKindEn", "issuanceCountryDe", "issuanceCountryFr", "issuanceCountryIt", "issuanceCountryEn", "issuanceDate", "datePsyko", "cetCourseDe", "cetCourseFr", "cetCourseIt", "cetCourseEn", "cetCourseName", "organisationName", "organisationZip", "organisationCity", "additionalIssuanceCountry", "additionalIssuanceDate", "additionalCetCourse", "additionalOrganisation", "providers90Days", "hasPermissionOtherThanNoLicence");
                    $keys_to_use[] = array("gln", "legalBasisDe", "legalBasisFr", "legalBasisIt", "legalBasisEn", "permissionStateDe", "permissionStateFr", "permissionStateIt", "permissionStateEn", "cantonDe", "cantonFr", "cantonIt", "cantonEn", "timeLimitationDate", "dateDecision", "restrictions");
                    $keys_to_use[] = array("gln", "practiceCompanyName", "streetWithNumber", "addition1", "addition2", "zipCity", "zip", "city", "phoneNumber", "email");

                    $table_names_to_use = ["psy_gln", "psy_languages", "psy_nationalities", "psy_diplomas", "psy_cetTitles", "psy_permissions", "psy_permissionAddress"];

                } else if ($register == 'betreg'){
                    $keys_to_use[] = array("glnCompany", "companyName", "additionalName", "streetWithNumber", "poBox", "zip", "zipCity", "city", "cantonDe", "cantonFr", "cantonIt", "cantonEn", "companyTypeDe", "companyTypeFr", "companyTypeIt", "companyTypeEn", "permissionBtmDe", "permissionBtmFr", "permissionBtmIt", "permissionBtmEn");
                    $keys_to_use[] = array("glnPerson", "familyName", "firstName");

                    $table_names_to_use = ["bet_companyGln", "bet_responsiblePersons"];
                }


                for ($k = 0; $k < count($keys_to_use); $k++) {
                    $entries = get_entries_by_names($flatten_result, $keys_to_use[$k]);
                    $query = "";
                    if (!empty($entries)) {
                        $table_name = $table_names_to_use[$k];

                        $requires_addr = in_array($table_name, $need_addr_nr);
                        $requires_perm = in_array($table_name, $need_perm_nr);
                        $requries_pr_title = in_array($table_name, $need_pr_title_nr);
                        $requires_titles = in_array($table_name, $need_title_nr);

                        if (in_array($table_name, ['med_permissionAddress', 'med_cettitles', 'med_privatelawcettitles'])) {
                            $entries = map_names($entries, table_name : $table_name);
                        }

                        if ($table_name == 'med_permissions') {
                            for ($perm_nr = 1; $perm_nr <= $multiple_max_values['permissions']; $perm_nr++) {
                                $entries['perm_nr'] = $perm_nr;

                                $newEntries = [];
                                foreach ($entries as $key => $value) {
                                    if (strpos($key, 'permissions') !== false && !preg_match("/permissions_$perm_nr/", $key)) {
                                        continue;
                                    }
                                    $newEntries[shorten_extra($key)] = $value;
                                }

                                $columns = array_keys($newEntries);
                                $values = array_values($newEntries);

                                $formattedValue = format_values($values);

                                $query = check_if_id_needed($table_name, $count, $columns, $formattedValue);
                                //echo $query . "\n";
                                $conn->query($query);
                            }

                            //echo "\n";

                        } else if ($table_name == 'med_permissionAddress') {
                            for ($perm_nr = 1; $perm_nr <= $multiple_max_values['permissions']; $perm_nr++) {
                                $entries['perm_nr'] = $perm_nr;

                                $newEntries = [];
                                foreach ($entries as $key => $value) {
                                    if (strpos($key, 'permissions') !== false && !preg_match("/permissions_$perm_nr/", $key)) {
                                        continue;
                                    }
                                    $newEntries[$key] = $value;
                                }


                                for ($addr_nr = 1; $addr_nr <= $multiple_max_values['addresses']; $addr_nr++) {
                                    $newEntries['addr_nr'] = $addr_nr;

                                    $newEntries2 = [];
                                    foreach ($newEntries as $key2 => $val2) {
                                        if (strpos($key2, 'addresses') !== false && !preg_match("/addresses_$addr_nr/", $key2)) {
                                            continue;
                                        }
                                        $newEntries2[shorten_extra($key2)] = $val2;
                                    }
                                }

                                $columns = array_keys($newEntries2);
                                $values = array_values($newEntries2);
                                $formattedValue = format_values($values);

                                $query = check_if_id_needed($table_name, $count, $columns, $formattedValue);
                                //echo $query . "\n";
                                $conn->query($query);
                            }
                            
                            //echo "\n";

                        } else if ($table_name == 'med_cetTitles') {
                            for ($title_nr = 1; $title_nr <= $multiple_max_values['cetTitles']; $title_nr++) {
                                $entries['title_nr'] = $title_nr;

                                $newEntries = [];
                                foreach ($entries as $key => $value) {
                                    if (strpos($key, 'cetTitles') !== false && !preg_match("/cetTitles_$title_nr/", $key)) {
                                        continue;
                                    }
                                    $newEntries[shorten_extra($key)] = $value;
                                }
                                
                                $columns = array_keys($newEntries);
                                $values = array_values($newEntries);

                                $formattedValue = format_values($values);

                                $query = check_if_id_needed($table_name, $count, $columns, $formattedValue);
                                //echo $query . "\n";
                                $conn->query($query);
                            }

                            //echo "\n";

                        } else if ($table_name == 'med_privateLawCetTitles') {
                            for ($pr_title_nr = 1; $pr_title_nr <= $multiple_max_values['privateLawCetTitles']; $pr_title_nr++) {
                                $entries['pr_title_nr'] = $pr_title_nr;

                                $newEntries = [];
                                foreach ($entries as $key => $value) {
                                    if (strpos($k, 'privateLawCetTitles') !== false && !preg_match("/privateLawCetTitles_$pr_title_nr/", $key)) {
                                        continue;
                                    }
                                    $newEntries[shorten_extra($key)] = $value;
                                }

                                $columns = array_keys($newEntries);
                                $values = array_values($newEntries);

                                $formattedValue = format_values($values);

                                $query = check_if_id_needed($table_name, $count, $columns, $formattedValue);
                                //echo $query . "\n";
                                $conn->query($query);
                            }

                            //echo "\n";

                        } else if ($table_name == 'psy_permissions') {
                            

                        } else if ($table_name == 'psy_permissionAddress') {
                            

                        } else if ($table_name == 'psy_cetTitles') {
                            
                        } else {
                            $columns = array_keys($entries);
                            $values = array_values($entries);

                            $formattedValue = format_values($values);

                            $query = check_if_id_needed($table_name, $count, $columns, $formattedValue);
                            //echo $query . "\n";
                            $conn->query($query);
                        }

                    }
                }
                $count++;

                echo "\n\n";

                //$curr_id = $existing_ids[++$count];
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