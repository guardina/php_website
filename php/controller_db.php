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
?>