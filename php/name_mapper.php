<?php

    $med_psy_db_names = array(
                'name' => 'lastName'
    );


    $bet_db_names = array(
                'name' => 'companyName'
        ,       'id' => 'bag_id'
        ,       'responsiblePersons_0_name' => 'familyName'
        ,       'responsiblePersons_0_firstName' => 'firstName'
        ,       'responsiblePersons_0_gln' => 'glnPerson'
        
    );


    $med_cettitles_names = array(
                'cetTitleTypeDe' => 'titleTypeDe'
        ,       'cetTitleTypeFr' => 'titleTypeFr'
        ,       'cetTitleTypeIt' => 'titleTypeIt'
        ,       'cetTitleTypeEn' => 'titleTypeEn'
        ,       'cetTitleKindDe' => 'titleKindDe'
        ,       'cetTitleKindFr' => 'titleKindFr'
        ,       'cetTitleKindIt' => 'titleKindIt'
        ,       'cetTitleKindEn' => 'titleKindEn'
    );

    $med_privatelawcettitles_names = array(
                'privateLawCetTitleTypeDe' => 'titleTypeDe'
        ,       'privateLawCetTitleTypeFr' => 'titleTypeFr'
        ,       'privateLawCetTitleTypeIt' => 'titleTypeIt'
        ,       'privateLawCetTitleTypeEn' => 'titleTypeEn'
        ,       'privateLawCetTitleKindDe' => 'titleKindDe'
        ,       'privateLawCetTitleKindFr' => 'titleKindFr'
        ,       'privateLawCetTitleKindIt' => 'titleKindIt'
        ,       'privateLawCetTitleKindEn' => 'titleKindEn'
    );

    $med_permissionaddress_names = array(
                'selfDispensationEn' => 'selfDispensation'
        ,       'permissionBtmEn' => 'permissionBtm'
    );


    // Function that changes the keys of the dictionary of the scraped data into the names of the columns in the tables, according to the mapping rules defined in the [med_psy/bet]_db_names dictionaries
    // Arguments: $dictionary_to_map: the dictionary that will change the keys, $register: used to define the correct mapping dictionary
    function map_names($dictionary_to_map, $register = '', $table_name = NULL) {
        global $med_psy_db_names;
        global $bet_db_names;
        global $med_cettitles_names;
        global $med_privatelawcettitles_names;
        global $med_permissionaddress_names;

        $newDictionary = array();

            if ($register != '') {
                if (in_array($register, ['medreg', 'psyreg'])) {
                    $name_mapper = $med_psy_db_names;
                } else if ($register == 'betreg') {
                    $name_mapper = $bet_db_names;
                }

            } else {
                if ($table_name == 'med_permissionAddress') {
                    $name_mapper = $med_permissionaddress_names;
                } else if ($table_name == 'med_cetTitles') {
                    $name_mapper = $med_cettitles_names;
                } else if ($table_name == 'med_privateLawCetTitles') {
                    $name_mapper = $med_privatelawcettitles_names;
                }
            }

        


        foreach ($dictionary_to_map as $key => $value) {
            //echo "$key -> $value\n";
            if (isset($name_mapper[$key])) {
                $newKey = $name_mapper[$key];
            } else {
                $newKey = $key;
            }
            //$newDictionary[$newKey] = $value;
            //echo "BEFORE:\n $newKey -> $value\n";
            $newDictionary[remove_extra($newKey)] = $value;
            //echo "AFTER :\n " . remove_extra($newKey) . " -> $value\n";
        }   

        return $newDictionary;
    }


    // Function to remove extra wording when scraping information online (E.g. professions_0_profession_textIt => professionIt)
    function remove_extra($word) {
        $newWord = preg_replace('/nationalities_[0-9]/', 'nationality', $word);
        $newWord = preg_replace('/phoneNumbers_0/', 'phoneNumber1', $newWord);
        $newWord = preg_replace('/phoneNumbers_1/', 'phoneNumber2', $newWord);
        $newWord = preg_replace('/phoneNumbers_2/', 'phoneNumber3', $newWord);

        $patterns = array('/_text/', '/Skills_[0-9]/', '/.*0_+/');
        return preg_replace($patterns, '', $newWord);
    }

?>