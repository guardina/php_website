<?php
    //METHOD TO WRITE DATA FROM THE DB TO THE FILE 'output.csv'

    /*
    $myfile = fopen("output.xlsx", "w");
    $sql = "SELECT * FROM People";

    $result = mysqli_query($conn, $sql);

    try {
        fwrite($myfile, "id,firstname,lastname,age,reg_date\n");
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $line = $row["id"] . "," . $row["firstname"] . "," . $row["lastname"] . "," . $row["age"] . "," . $row["reg_date"] . "\n";
                fwrite($myfile, $line);
            }
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    } catch (mysqli_sql_exception $e) {
        $error = $e->getMessage();
        echo $error;
    }
    */
?>