<?php

    include_once "controller_db.php";
    include "name_mapper.php";
    include "medreg_HTTP_controller.php";

    $requests_per_bucket = 100;


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
                } else if ($name == 'poBox') {
                    $list[$name] = strval($list[$name]);
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

        if (in_array($register, ['medreg', 'psyreg'])) {
            $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        } else if ($register == 'betreg') {
            $url = "https://www.healthreg-public.admin.ch/api/betreg/public/company";
        }

        $bucket = 1;
        $count = 0;

        for ($i = 0; $i<$number_of_samples; $i+=$requests_per_bucket) {
            echo "[Bucket $bucket] Starting data download! ($register)\n";

            $curr_id = $existing_ids[$count];


            if (bucket_already_exists_in_db($conn, $register, $curr_id)) {
                echo "[Bucket $bucket] Already present in database ($register)\n\n";
                $bucket++;
                $count+=100;
                continue;
            }


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
                    continue;
                }

                $flatten_result = flatten_list($result, '', array());
                $flatten_result = map_names($flatten_result, $register);

                /*foreach ($flatten_result as $k => $v) {
                    echo $k . ": " . $v . "\n";
                }

                echo "\n\n";*/


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


                for ($i = 0; $i < count($keys_to_use); $i++) {
                    $entries = get_entries_by_names($flatten_result, $keys_to_use[$i]);
                    $query = "";
                    if (!empty($entries)) {
                        $table_name = $table_names_to_use[$i];

                        if (in_array($table_name, ['med_permissionAddress', 'med_cettitles', 'med_privatelawcettitles'])) {
                            $entries = map_names($entries, table_name : $table_name);
                        }
                        $columns = array_keys($entries);
                        $values = array_values($entries);

                        $formattedValue = format_values($values);

                        if ($table_name == 'med_gln' || $table_name == 'psy_gln') {
                            $query = "INSERT INTO " . $table_name . "(id, " . implode(', ', $columns) . ") VALUES ($curr_id, $formattedValue)";
                        } else if ($table_name == 'bet_companyGln') {
                            $query = "INSERT INTO " . $table_name . "(bag_id, " . implode(', ', $columns) . ") VALUES ($curr_id, $formattedValue)";
                        } else {
                            $query = "INSERT INTO " . $table_name . "(" . implode(', ', $columns) . ") VALUES ($formattedValue)";
                        }

                        //echo $query . "\n";
                    $conn->query($query);
                    }
                }

                echo "\n\n";

                $curr_id = $existing_ids[++$count];
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