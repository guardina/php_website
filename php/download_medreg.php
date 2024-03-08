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


    function shorten_extra($string) {
        $patterns = '/.*[0-9]_+/';
        return preg_replace($patterns, '', $string);
    }



    function download_all_medreg_data($register, $mode, $number_of_samples, $start_from=0) {

        $conn = connect_to_db("stammdaten_gln");

        global $requests_per_bucket;

        if (in_array($register, ['medreg', 'psyreg'])) {
            $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        } else if ($register == 'betreg') {
            $url = "https://www.healthreg-public.admin.ch/api/betreg/public/company";
        }

        $bucket = ($start_from / $requests_per_bucket) + 1;

        for ($i = $start_from; $i<$number_of_samples; $i+=$requests_per_bucket) {
            $count = 0;
            $existing_ids = get_existing_ids($register);
            $missing_ids = get_missing_ids($register);


            if ($mode == 'download') {
                echo $i . " ---- " . $number_of_samples . "\n";
                echo "[Bucket $bucket] Starting data download! ($register)\n";

                if (bucket_already_exists_in_db($conn, $register, $i, $i+$requests_per_bucket)) {
                    echo "[Bucket $bucket] Already present in database ($register)\n\n";
                    $bucket++;
                    $count+=100;
                    continue;
                }

            } else if ($mode == 'update') {
                echo $i . " ---- " . count($existing_ids) . "\n";
                echo "[Bucket $bucket] Starting data update! ($register)\n";
            } else if ($mode == 'seek') {
                echo $i . " ---- " . count($missing_ids) . "\n";
                echo "[Bucket $bucket] Starting data seek! ($register)\n";
            }


            if (in_array($register, ['medreg', 'psyreg'])) {
                $payloads = [];
                if ($mode == 'download') {
                    for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                        $payloads[] = ['id' => $j];
                    }  
                } else if ($mode == 'update') {
                    if (count($existing_ids) - $i > $requests_per_bucket) {
                        for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                            //$payloads[] = ['id' => $existing_ids[$j]];
                            echo "$existing_ids[$j] -> $j\n";
                        }
                    } else {
                        $upper_limit = count($existing_ids) - $i;

                        for ($j = $i; $j < $upper_limit; $j++) {
                            //$payloads[] = ['id' => $existing_ids[$j]];
                            echo "$existing_ids[$j]\n";
                        }
                    } 

                } else if ($mode == 'seek') {
                    if (count($existing_ids) - $i > $requests_per_bucket) {
                        for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                            $payloads[] = ['id' => $missing_ids[$j]];
                            //echo "$missing_ids[$j]\n";
                        }
                    } else {
                        $upper_limit = count($missing_ids) - $i;

                        for ($j = $i; $j < $upper_limit; $j++) {
                            $payloads[] = ['id' => $missing_ids[$j]];
                            //echo "$missing_ids[$j]\n";
                        }
                    } 
                }
                 

                $start_time = microtime(true);
                $results = makeParallelRequests($url, $payloads, $register);
                $end_time = microtime(true);

            } else if ($register == 'betreg') {
                $urls = [];
                $payloads = [];
                if ($mode == 'download') {
                    for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                        $urls[] = $url . "/" . $j;
                    }  
                } else if ($mode == 'update') {
                    if (count($existing_ids) - $i > $requests_per_bucket) {
                        for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                            //$urls[] = $url . "/" . $existing_ids[$j];
                            echo "$url/$existing_ids[$j]\n";
                        }
                    } else {
                        $upper_limit = count($existing_ids) - $i;

                        for ($j = $i; $j < $upper_limit; $j++) {
                            //$urls[] = $url . "/" . $existing_ids[$j];
                            echo "$url/$existing_ids[$j]\n";
                        }
                    } 
                } else if ($mode == 'seek') {
                    if (count($missing_ids) - $i > $requests_per_bucket) {
                        for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                            //$urls[] = $url . "/" . $missing_ids[$j];
                            echo "$url/$missing_ids[$j]\n";
                        }
                    } else {
                        $upper_limit = count($missing_ids) - $i;

                        for ($j = $i; $j < $upper_limit; $j++) {
                            //$urls[] = $url . "/" . $missing_ids[$j];
                            echo "$url/$missing_ids[$j]\n";
                        }
                    } 
                }
                 

                $start_time = microtime(true);
                $results = makeParallelRequests($urls, $payloads, $register);
                $end_time = microtime(true);
            }

            $total_time = $end_time - $start_time;



            if ($register == 'medreg') {
                $tab_infix = 'med';
                $data_map_d = [
                    'gln' => [],
                    'nationalities' => [],
                    'languages' => [],
                    'professions' => [],
                    'cetTitles' => [],
                    'privateLawCetTitles' => [],
                    'permissions' => [],
                    'permissionAddress' => [],
                ];
            } elseif ($register == 'psyreg') {
                $tab_infix = 'psy';
                $data_map_d = [
                    'gln' => [],
                    'nationalities' => [],
                    'languages' => [],
                    'diplomas' => [],
                    'cetTitles' => [],
                    'permissions' => [],
                    'permissionAddresses' => [],
                ];
            } elseif ($register == 'betreg') {
                $tab_infix = 'bet';
                $data_map_d = [
                    'companyGln' => [],
                    'responsiblePersons' => [],
                ];
            }


            foreach ($results as $person) {


                // HERE WE CAN TAKE THE DATA, AS RESULT HAS FIRST NAME, LAST NAME, AND SO ON

                if ($person == null) {
                    continue;
                }


                try {
                    if (in_array($register, ['medreg', 'psyreg'])) {
                        if (isset($person['gln']) && $person['gln'] != null) {
                            $data_map_d['gln'][] = [
                                'gln' => (int)$person['gln'],
                                'id' => $person['id'],
                                'lastName' => format_name($person['name']),
                                'firstName' => format_name($person['firstName']),
                                'genderDe' => $person['gender']['textDe'],
                                'genderFr' => $person['gender']['textFr'],
                                'genderIt' => $person['gender']['textIt'],
                                'genderEn' => $person['gender']['textEn'],
                                'yearOfBirth' => $person['yearOfBirth'],
                                'uid' => $person['uid'],
                                'hasPermission' => $person['hasPermission'],
                                'hasProvider90Days' => $person['hasProvider90Days']
                            ];

                            $unique_nationalities = array();
                            $filtered_nationalities = array();

                            foreach($person['nationalities'] as $nat) {
                                if (!in_array($nat['textEn'], $unique_nationalities)) {
                                    $filtered_nationalities[] = $nat;
                                    $unique_nationalities[] = $nat['textEn'];
                                }
                            }
                
                            foreach ($filtered_nationalities as $nat) {
                                $data_map_d['nationalities'][] = [
                                    'gln' => (int)$person['gln'],
                                    'nationalityDe' => $nat['textDe'],
                                    'nationalityFr' => $nat['textFr'],
                                    'nationalityIt' => $nat['textIt'],
                                    'nationalityEn' => $nat['textEn']
                                ];
                            }
                
                            foreach ($person['languageSkills'] as $lang) {
                                $data_map_d['languages'][] = [
                                    'gln' => (int)$person['gln'],
                                    'languageDe' => $lang['textDe'],
                                    'languageFr' => $lang['textFr'],
                                    'languageIt' => $lang['textIt'],
                                    'languageEn' => $lang['textEn']
                                ];
                            }
                        }
                    }
            
                    if ($register == 'medreg') {
                        if (isset($person['gln']) && $person['gln'] != null) {
                            foreach ($person['professions'] as $prof) {
                                $data_map_d['professions'][] = [
                                    'gln' => (int)$person['gln'],
                                    'professionDe' => $prof['profession']['textDe'],
                                    'professionFr' => $prof['profession']['textFr'],
                                    'professionIt' => $prof['profession']['textIt'],
                                    'professionEn' => $prof['profession']['textEn'],
                                    'diplomaTypeDe' => $prof['diplomaType']['textDe'],
                                    'diplomaTypeFr' => $prof['diplomaType']['textFr'],
                                    'diplomaTypeIt' => $prof['diplomaType']['textIt'],
                                    'diplomaTypeEn' => $prof['diplomaType']['textEn'],
                                    'issuanceDate' => isset($prof['issuanceDate']) ? format_date($prof['issuanceDate']) : null,
                                    'issuanceCountryDe' => $prof['issuanceCountry']['textDe'],
                                    'issuanceCountryFr' => $prof['issuanceCountry']['textFr'],
                                    'issuanceCountryIt' => $prof['issuanceCountry']['textIt'],
                                    'issuanceCountryEn' => $prof['issuanceCountry']['textEn'],
                                    'dateMebeko' => isset($prof['dateMebeko']) ? format_date($prof['dateMebeko']) : null,
                                    'providers90Days' => json_encode($prof['providers90Days']),
                                    'hasPermissionOtherThanNoLicence' => $prof['hasPermissionOtherThantNoLicence']
                                ];
                            }
        
                            foreach ($prof['cetTitles'] as $title_nr => $title) {
                                $data_map_d['cetTitles'][] = [
                                    'gln' => (int)$person['gln'],
                                    'title_nr' => $title_nr + 1,
                                    'professionEn' => $prof['profession']['textEn'],
                                    'titleTypeDe' => $title['cetTitleType']['textDe'],
                                    'titleTypeFr' => $title['cetTitleType']['textFr'],
                                    'titleTypeIt' => $title['cetTitleType']['textIt'],
                                    'titleTypeEn' => $title['cetTitleType']['textEn'],
                                    'titleKindDe' => $title['cetTitleKind']['textDe'],
                                    'titleKindFr' => $title['cetTitleKind']['textFr'],
                                    'titleKindIt' => $title['cetTitleKind']['textIt'],
                                    'titleKindEn' => $title['cetTitleKind']['textEn'],
                                    'issuanceCountryDe' => $title['issuanceCountry']['textDe'],
                                    'issuanceCountryFr' => $title['issuanceCountry']['textFr'],
                                    'issuanceCountryIt' => $title['issuanceCountry']['textIt'],
                                    'issuanceCountryEn' => $title['issuanceCountry']['textEn'],
                                    'issuanceDate' => isset($title['issuanceDate']) ? format_date($title['issuanceDate']) : null,
                                    'dateMebeko' => isset($title['dateMebeko']) ? format_date($title['dateMebeko']) : null
                                ];
                            }
                            
                            foreach ($prof['privateLawCetTitles'] as $pr_title_nr => $title) {
                                $data_map_d['privateLawCetTitles'][] = [
                                    'gln' => (int)$person['gln'],
                                    'pr_title_nr' => $pr_title_nr + 1,
                                    'professionEn' => $prof['profession']['textEn'],
                                    'titleTypeDe' => $title['privateLawCetTitleType']['textDe'],
                                    'titleTypeFr' => $title['privateLawCetTitleType']['textFr'],
                                    'titleTypeIt' => $title['privateLawCetTitleType']['textIt'],
                                    'titleTypeEn' => $title['privateLawCetTitleType']['textEn'],
                                    'titleKindDe' => $title['privateLawCetTitleKind']['textDe'],
                                    'titleKindFr' => $title['privateLawCetTitleKind']['textFr'],
                                    'titleKindIt' => $title['privateLawCetTitleKind']['textIt'],
                                    'titleKindEn' => $title['privateLawCetTitleKind']['textEn'],
                                    'issuanceDate' => isset($title['issuanceDate']) ? format_date($title['issuanceDate']) : null
                                ];
                            }
                            
                            foreach ($prof['permissions'] as $perm_nr => $perm) {
                                $data_map_d['permissions'][] = [
                                    'gln' => (int)$person['gln'],
                                    'perm_nr' => $perm_nr + 1,
                                    'professionEn' => $prof['profession']['textEn'],
                                    'permissionTypeDe' => $perm['permissionType']['textDe'],
                                    'permissionTypeFr' => $perm['permissionType']['textFr'],
                                    'permissionTypeIt' => $perm['permissionType']['textIt'],
                                    'permissionTypeEn' => $perm['permissionType']['textEn'],
                                    'permissionStateDe' => isset($perm['permissionState']) ? $perm['permissionState']['textDe'] : null,
                                    'permissionStateFr' => isset($perm['permissionState']) ? $perm['permissionState']['textFr'] : null,
                                    'permissionStateIt' => isset($perm['permissionState']) ? $perm['permissionState']['textIt'] : null,
                                    'permissionStateEn' => isset($perm['permissionState']) ? $perm['permissionState']['textEn'] : null,
                                    'permissionActivityStateDe' => isset($perm['permissionActivityState']) ? $perm['permissionActivityState']['textDe'] : null,
                                    'permissionActivityStateFr' => isset($perm['permissionActivityState']) ? $perm['permissionActivityState']['textFr'] : null,
                                    'permissionActivityStateIt' => isset($perm['permissionActivityState']) ? $perm['permissionActivityState']['textIt'] : null,
                                    'permissionActivityStateEn' => isset($perm['permissionActivityState']) ? $perm['permissionActivityState']['textEn'] : null,
                                    'cantonDe' => $perm['canton']['textDe'],
                                    'cantonFr' => $perm['canton']['textFr'],
                                    'cantonIt' => $perm['canton']['textIt'],
                                    'cantonEn' => $perm['canton']['textEn'],
                                    'dateDecision' => isset($perm['dateDecision']) ? format_date($perm['dateDecision']) : null,
                                    'dateActivity' => isset($perm['dateActivity']) ? format_date($perm['dateActivity']) : null,
                                    'restrictions' => json_encode($perm['restrictions'])
                                ];
                            
                                foreach ($perm['addresses'] as $addr_nr => $addr) {
                                    $phone_nrs = array_column($addr['phoneNumbers'], 'phoneNumber');
                            
                                    $data_map_d['permissionAddress'][] = [
                                        'gln' => (int)$person['gln'],
                                        'perm_nr' => $perm_nr + 1,
                                        'addr_nr' => $addr_nr + 1,
                                        'professionEn' => $prof['profession']['textEn'],
                                        'practiceCompanyName' => $addr['practiceCompanyName'],
                                        'streetWithNumber' => $addr['streetWithNumber'],
                                        'zipCity' => $addr['zipCity'],
                                        'zip' => $addr['zip'],
                                        'city' => $addr['city'],
                                        'phoneNumber1' => isset($phone_nrs[0]) ? $phone_nrs[0] : null,
                                        'phoneNumber2' => isset($phone_nrs[1]) ? $phone_nrs[1] : null,
                                        'phoneNumber3' => isset($phone_nrs[2]) ? $phone_nrs[2] : null,
                                        'faxNumber' => $addr['faxNumber'],
                                        'uid' => $addr['uid'],
                                        'selfDispensation' => isset($addr['selfDispensation']) ? ($addr['selfDispensation']['textDe'] == 'Ja') : null,
                                        'permissionBtm' => isset($addr['permissionBtm']) ? ($addr['permissionBtm']['textDe'] == 'Ja') : null
                                    ];
                                }
                            }
                        } 
                    }
            
                    if ($register == 'psyreg') {
                        if (isset($person['gln']) && $person['gln'] != null) {
                            foreach ($person['diplomas'] as $diploma) {
                                $data_map_d['diplomas'][] = [
                                    'gln' => (int) $person['gln'],
                                    'professionDe' => $diploma['profession']['textDe'],
                                    'professionFr' => $diploma['profession']['textFr'],
                                    'professionIt' => $diploma['profession']['textIt'],
                                    'professionEn' => $diploma['profession']['textEn'],
                                    'diplomaTypeDe' => isset($diploma['diplomaType']) ? $diploma['diplomaType']['textDe'] : null,
                                    'diplomaTypeFr' => isset($diploma['diplomaType']) ? $diploma['diplomaType']['textFr'] : null,
                                    'diplomaTypeIt' => isset($diploma['diplomaType']) ? $diploma['diplomaType']['textIt'] : null,
                                    'diplomaTypeEn' => isset($diploma['diplomaType']) ? $diploma['diplomaType']['textEn'] : null,
                                    'issuanceDate' => isset($diploma['issuanceDate']) ? format_date($diploma['issuanceDate']) : null,
                                    'issuanceCountryDe' => isset($diploma['issuanceCountry']) ? $diploma['issuanceCountry']['textDe'] : null,
                                    'issuanceCountryFr' => isset($diploma['issuanceCountry']) ? $diploma['issuanceCountry']['textFr'] : null,
                                    'issuanceCountryIt' => isset($diploma['issuanceCountry']) ? $diploma['issuanceCountry']['textIt'] : null,
                                    'issuanceCountryEn' => isset($diploma['issuanceCountry']) ? $diploma['issuanceCountry']['textEn'] : null,
                                    'datePsyko' => isset($diploma['datePsyko']) ? format_date($diploma['datePsyko']) : null
                                ];
                            }
                            
                            foreach ($person['cetTitles'] as $title_nr => $title) {
                                $data_map_d['cetTitles'][] = [
                                    'gln' => (int) $person['gln'],
                                    'title_nr' => $title_nr + 1,
                                    'titleTypeDe' => isset($title['cetTitleType']) ? $title['cetTitleType']['textDe'] : null,
                                    'titleTypeFr' => isset($title['cetTitleType']) ? $title['cetTitleType']['textFr'] : null,
                                    'titleTypeIt' => isset($title['cetTitleType']) ? $title['cetTitleType']['textIt'] : null,
                                    'titleTypeEn' => isset($title['cetTitleType']) ? $title['cetTitleType']['textEn'] : null,
                                    'titleKindDe' => isset($title['cetTitleKind']) ? $title['cetTitleKind']['textDe'] : null,
                                    'titleKindFr' => isset($title['cetTitleKind']) ? $title['cetTitleKind']['textFr'] : null,
                                    'titleKindIt' => isset($title['cetTitleKind']) ? $title['cetTitleKind']['textIt'] : null,
                                    'titleKindEn' => isset($title['cetTitleKind']) ? $title['cetTitleKind']['textEn'] : null,
                                    'issuanceCountryDe' => isset($title['issuanceCountry']) ? $title['issuanceCountry']['textDe'] : null,
                                    'issuanceCountryFr' => isset($title['issuanceCountry']) ? $title['issuanceCountry']['textFr'] : null,
                                    'issuanceCountryIt' => isset($title['issuanceCountry']) ? $title['issuanceCountry']['textIt'] : null,
                                    'issuanceCountryEn' => isset($title['issuanceCountry']) ? $title['issuanceCountry']['textEn'] : null,
                                    'issuanceDate' => isset($title['issuanceDate']) ? format_date($title['issuanceDate']) : null,
                                    'datePsyko' => isset($title['datePsyko']) ? format_date($title['datePsyko']) : null,
                                    'cetCourseDe' => isset($title['cetCourse']) ? $title['cetCourse']['textDe'] : null,
                                    'cetCourseFr' => isset($title['cetCourse']) ? $title['cetCourse']['textFr'] : null,
                                    'cetCourseIt' => isset($title['cetCourse']) ? $title['cetCourse']['textIt'] : null,
                                    'cetCourseEn' => isset($title['cetCourse']) ? $title['cetCourse']['textEn'] : null,
                                    'cetCourseName' => $title['cetCourseName'],
                                    'organisationName' => isset($title['organisation']) ? $title['organisation']['name'] : null,
                                    'organisationZip' => isset($title['organisation']) ? $title['organisation']['zip'] : null,
                                    'organisationCity' => isset($title['organisation']) ? $title['organisation']['city'] : null,
                                    'additionalIssuanceCountry' => $title['additionalIssuanceCountry'],
                                    'additionalIssuanceDate' => $title['additionalIssuanceDate'],
                                    'additionalCetCourse' => $title['additionalCetCourse'],
                                    'additionalOrganisation' => $title['additionalOrganisation'],
                                    'providers90Days' => json_encode($title['providers90Days']),
                                    'hasPermissionOtherThanNoLicence' => $title['hasPermissionOtherThantNoLicence']
                                ];
                            
                                foreach ($title['permissions'] as $perm_nr => $perm) {
                                    $data_map_d['permissions'][] = [
                                        'gln' => (int) $person['gln'],
                                        'title_nr' => $title_nr + 1,
                                        'perm_nr' => $perm_nr + 1,
                                        'legalBasisDe' => $perm['legalBasis']['textDe'],
                                        'legalBasisFr' => $perm['legalBasis']['textFr'],
                                        'legalBasisIt' => $perm['legalBasis']['textIt'],    
                                        'legalBasisEn' => $perm['legalBasis']['textEn'],
                                        'permissionStateDe' => isset($perm['permissionState']) ? $perm['permissionState']['textDe'] : null,
                                        'permissionStateFr' => isset($perm['permissionState']) ? $perm['permissionState']['textFr'] : null,
                                        'permissionStateIt' => isset($perm['permissionState']) ? $perm['permissionState']['textIt'] : null,
                                        'permissionStateEn' => isset($perm['permissionState']) ? $perm['permissionState']['textEn'] : null,
                                        'cantonDe' => $perm['canton']['textDe'],
                                        'cantonFr' => $perm['canton']['textFr'],
                                        'cantonIt' => $perm['canton']['textIt'],
                                        'cantonEn' => $perm['canton']['textEn'],
                                        'timeLimitationDate' => isset($perm['timeLimitationDate']) ? format_date($perm['timeLimitationDate']) : null,
                                        'dateDecision' => isset($perm['dateDecision']) ? format_date($perm['dateDecision']) : null,
                                        'restrictions' => json_encode($perm['restrictions'])
                                    ];
                            
                                    foreach ($perm['addresses'] as $addr_nr => $addr) {
                                        $data_map_d['permissionAddresses'][] = [
                                            'gln' => (int) $person['gln'],
                                            'title_nr' => $title_nr + 1,
                                            'perm_nr' => $perm_nr + 1,
                                            'addr_nr' => $addr_nr + 1,
                                            'practiceCompanyName' => $addr['practiceCompanyName'],
                                            'streetWithNumber' => $addr['streetWithNumber'],
                                            'addition1' => $addr['addition1'],
                                            'addition2' => $addr['addition2'],
                                            'zipCity' => $addr['zipCity'],
                                            'zip' => $addr['zip'],
                                            'city' => $addr['city'],
                                            'phoneNumber' => $addr['phoneNumber'],
                                            'email' => $addr['email']
                                        ];
                                    }
                                }
                            }
                            
                        }
                    }
                        
            
                    if ($register == 'betreg') {
                        if (isset($person['id']) && $person['id'] != null) {
                            $data_map_d['companyGln'][] = [
                                'bag_id' => $person['id'],
                                'glnCompany' => isset($person['glnCompany']) ? (int) $person['glnCompany'] : null,
                                'companyName' => $person['name'],
                                'additionalName' => $person['additionalName'],
                                'streetWithNumber' => $person['streetWithNumber'],
                                'poBox' => $person['poBox'],
                                'zip' => $person['zip'],
                                'zipCity' => $person['zipCity'],
                                'city' => $person['city'],
                                'cantonDe' => $person['canton']['textDe'],
                                'cantonFr' => $person['canton']['textFr'],
                                'cantonIt' => $person['canton']['textIt'],
                                'cantonEn' => $person['canton']['textEn'],
                                'companyTypeDe' => $person['companyType']['textDe'],
                                'companyTypeFr' => $person['companyType']['textFr'],
                                'companyTypeIt' => $person['companyType']['textIt'],
                                'companyTypeEn' => $person['companyType']['textEn'],
                                'permissionBtmDe' => $person['permissionBtm']['textDe'],
                                'permissionBtmFr' => $person['permissionBtm']['textFr'],
                                'permissionBtmIt' => $person['permissionBtm']['textIt'],
                                'permissionBtmEn' => $person['permissionBtm']['textEn']
                            ];
                            
                            foreach ($person['responsiblePersons'] as $resp) {
                                $data_map_d['responsiblePersons'][] = [
                                    'bag_id' => $person['id'],
                                    'glnPerson' => $resp['gln'],
                                    'familyName' => format_name($resp['name']),
                                    'firstName' => format_name($resp['firstName'])
                                ];
                            }
                        }
                        
                    }
                } catch (Exception $e) {

                }

            }


            foreach ($data_map_d as $table => $values) {
                foreach($values as $value) {
                    foreach ($value as $k => $v) {
                        if ($v == "" || $v == null || $v =='[]') {
                            unset($value[$k]);
                        }
                    }

                    $columns = array_keys($value);
                    $myValues = array_values($value);


                    $formatted_values = format_values($myValues);

                    if ($mode == 'download') {
                        $query = "INSERT INTO " . $tab_infix . "_" . $table . "(" . implode(', ', $columns) . ") VALUES ($formatted_values)";
                        //echo "$query\n";
                        $conn->query($query);
                    } else if ($mode == 'update') {
                        $query_update = "UPDATE " . $tab_infix . "_gln SET expiry_dt = " . funca() . " WHERE id = " . $value['id'];
                    } else if ($mode == 'seek') {
                        $query_insert = "INSERT INTO " . $tab_infix . "_" . $table . "(" . implode(', ', $columns) . ") VALUES ($formatted_values)";
                        echo "$query_insert\n";
                        //$conn->query($query_insert);

                        $query_update = "UPDATE " . $tab_infix . "_ids SET round_1 = 1 WHERE id = " . $value['id'];
                        echo "$query_update\n";
                        //$conn->query($query_update    );
                    }


                    
                }
                //echo "done\n";
                //echo "\n\n";
            }




            echo "[Bucket $bucket] Time elapsed: " . $total_time . "\n\n";

            $bucket++;
        }

        mysqli_close($conn);
    }



    function check_for_new_data($register) {
        $conn = connect_to_db('stammdaten_gln');

        $existing_ids = get_existing_ids($register);

        echo "Checking numbers for register $register:\n";
        /*foreach($existing_ids as $id) {
            echo "$id\n";
        }*/
        echo count($existing_ids) . " existing!\n";

        echo "\n\n";
    }



    function update_data($register) {
        //$conn = connect_to_db('stammdaten_gln');

        $missing_ids = get_missing_ids($register);

        echo "Updating numbers for register $register:\n";
        /*foreach($missing_ids as $id) {
            echo "$id\n";
        }*/
        echo count($missing_ids) . " missing!\n";

        echo "\n\n";
    }
    


    if ($argc < 3) {
        echo "Usage: php download_ids.php <register> <number of samples> <starting_index>\n";
        echo "Possible registers: medreg, psyreg, betreg\n";
        echo "Range for samples: 100 - 200000\n";
        exit(1);
    }

    if ($argv[4] == 'test') {
        foreach(['medreg', 'psyreg', 'betreg'] as $register) {
            //check_for_new_data($register);
            //update_data($register);
            download_all_medreg_data($register, $mode, $number_of_samples, $start_from);
        }
    } else {
        $register = $argv[1];
        $mode = $argv[2];
        $number_of_samples = $argv[3];
        $start_from = $argv[4];
        download_all_medreg_data($register, $mode, $number_of_samples, $start_from);
    }
?>