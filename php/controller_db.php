<?php
    // CONNECTION TO DB
    function connect_to_db($databasename) {
        $servername = "localhost";
        $username = "debian";
        $password = "password";

        $conn = mysqli_connect($servername, $username, $password, $databasename);

        // Tries connection to the MYSQL server
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        return $conn;
    }


    function get_entry($data) {

        $firstName = $data['firstName'];
        $lastName = $data['lastName'];
        $gln = intval($data['gln']);

        $conn = connect_to_db("stammdaten_gln");

        $query = "SELECT * FROM med_gln m
                  /*INNER JOIN med_languages l
                  INNER JOIN med_permissionaddress pa
                  INNER JOIN med_permissions pe
                  INNER JOIN med_privatelawcettitles pl
                  INNER JOIN med_professions pr*/
                  WHERE 1";

        if ($firstName != null) {
            $query .= ' AND m.firstName = ?';
        }

        if ($lastName != null) {
            $query .= ' AND m.lastName = ?';
        }

        if ($gln != null) {
            $query .= ' AND m.gln = ?';
        }

        $query .= " ORDER BY m.effective_dt DESC LIMIT 1";

        $newQuery = mysqli_prepare($conn, $query);

        if ($firstName !== null) {
            mysqli_stmt_bind_param($newQuery, 's', $firstName);
        }
    
        if ($lastName !== null) {
            mysqli_stmt_bind_param($newQuery, 's', $lastName);
        }
    
        if ($gln !== null) {
            mysqli_stmt_bind_param($newQuery, 'i', $gln);
        }

        mysqli_stmt_execute($newQuery);
        $result = mysqli_stmt_get_result($newQuery);

        $resultsArray = mysqli_fetch_all($result, MYSQLI_ASSOC);

        mysqli_stmt_close($newQuery);
        mysqli_close($conn);

        return $resultsArray;

        /*foreach ($resultsArray as $res) {
            foreach($res as $k => $v) {
                echo $k . ' -> ' . $v . '<br>';
            }
        }*/
                  
    }


    // Function to retrieve all glns contained in the stammdaten_gln DB, specifically from the tables med_gln and psy_gln
    function get_glns($type) {

        $glns = array();

        $conn = connect_to_db("stammdaten_gln");

        $query = "SELECT gln FROM $type;";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $glns[] = $row["gln"];
            }
        }

        mysqli_close($conn);

        return $glns;
    }



    function get_existing_ids($register) {
        $conn = connect_to_db("stammdaten_gln");

        $query = "SELECT id FROM " . substr($register, 0, -3) . "_ids WHERE round_1 = 1 OR round_2 = 1";

        $existing_ids = [];

        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $existing_ids[] = $row['id'];
            }
        }     

        mysqli_close($conn);

        return $existing_ids;
    }


    function get_missing_ids($register) {
        $conn = connect_to_db("stammdaten_gln");

        $query = "SELECT id FROM " . substr($register, 0, -3) . "_ids WHERE round_1 = 0 AND (round_2 = 0 OR round_2 IS NULL)";

        $missing_ids = [];

        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $missing_ids[] = $row['id'];
            }
        }     

        mysqli_close($conn);

        return $missing_ids;
    }



    // Returns true if the provided id is present in the databse, such that we can avoid a bucket of requests. Only works when downloading the whole database the first time, as afterwards it could
    // skip newly added ids. For instance, if it's checking the ids between 1 and 100, this function will only check if 1 is in the DB and assume also the other values up to 100 are in the DB 
    // (if true is returned). If a new id 40 is added, it will not be checked. 
    // The function is used in case there was some problem while downloading the first time and the process was halted; we avoid trying to redownload the whole database.
    function bucket_already_exists_in_db($conn, $register, $start_id, $end_id) {
        if (in_array($register, ['medreg', 'psyreg'])) {
            $select_query = "SELECT id from " . substr($register, 0, -3) . "_gln WHERE id >= $start_id AND id < $end_id";
        } else if ($register == 'betreg') {
            $select_query = "SELECT bag_id from " . substr($register, 0, -3) . "_companyGln WHERE bag_id >= $start_id AND bag_id < $end_id";
        }
        
        $result = mysqli_query($conn, $select_query);

        return mysqli_num_rows($result) > 0;
    }

?>