<?php

    $med_psy_db_names = array(
                'id' => 'uid'
        ,       'name' => 'lastName'
        ,       'profession_textDe' => 'profession_textDe'
        ,       'profession_textFr' => 'profession_textFr'
        ,       'profession_textIt' => 'profession_textIt'
        ,       'profession_textEn' => 'profession_textEn'
        ,       '0_textDe' => 'cetTitles_textDe'
        ,       '0_textFr' => 'cetTitles_textFr'
        ,       '0_textIt' => 'cetTitles_textIt'
        ,       '0_textEn' => 'cetTitles_textEn'
        ,       'canton_textDe' => 'canton_textDe'
        ,       'canton_textFr' => 'canton_textFr'
        ,       'canton_textIt' => 'canton_textIt'
        ,       'canton_textEn' => 'canton_textEn'
    );


    $bet_db_names = array(
                'name' => 'companyName'
        ,       'id' => 'bag_id'
        ,       'reponsiblePersons_0_name' => 'familyName'
        ,       'reponsiblePersons_0_firstName' => 'firstName'
        ,       'reponsiblePersons_0_gln' => 'glnPerson'
        
    );


    // Function that changes the keys of the dictionary of the scraped data into the names of the columns in the tables, according to the mapping rules defined in the [med_psy/bet]_db_names dictionaries
    // Arguments: $dictionary_to_map: the dictionary that will change the keys, $register: used to define the correct mapping dictionary
    function map_names($dictionary_to_map, $register) {
        global $med_psy_db_names;
        global $bet_db_names;

        $newDictionary = array();

        if (in_array($register, ['medreg', 'psyreg'])) {
            $name_mapper = $med_psy_db_names;
        } else if ($register == 'betreg') {
            $name_mapper = $bet_db_names;
        }


        foreach ($dictionary_to_map as $key => $value) {
            if (isset($name_mapper[$key])) {
                $newKey = $name_mapper[$key];
            } else {
                $newKey = $key;
            }
            $newDictionary[$newKey] = $value;
        }   

        return $newDictionary;
    }
?>