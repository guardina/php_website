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

        foreach ($resultsArray as $res) {
            foreach($res as $k => $v) {
                echo $k . ' -> ' . $v . '<br>';
            }
        }
                  
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


    // TAKES 2 DICTIONARIES: FIRST IT MAPS THE SCRAPED NAMES TO DB NAMES, THEN ISERTS THEM INTO THE DB<

    /*

            // myDictionary has keys taken from the HTML response provided by the site, we need to map them to the column's names in the SQL table
            $columns = array();
            $values = array();

            $columns[] = 'gln';
            $values[] = "$gln";

            foreach ($null_less_dictionary as $key => $value) {
                if (isset($columnMapping[$key])) {
                    $columns[] = $columnMapping[$key];
                    $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
                }
            }
        


            
            $query = "INSERT INTO Doctors (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";


            mysqli_query($conn, $query);

            mysqli_close($conn);
            */
?>