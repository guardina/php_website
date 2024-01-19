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