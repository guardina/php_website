<style>
.error {color: #FF0000;}
</style>

<?php
    $firstNameErr = $lastNameErr = $ageErr = "";
    $firstName = $lastName = "";
    $age = 0;

    if (empty($_POST["firstName"])) {
        $firstNameErr = "First name is required";
    } else {
        $firstName = test_input($_POST["firstName"]);
    }

    if (empty($_POST["lastName"])) {
        $lastNameErr = "Last name is required";
    } else {
        $lastName = test_input($_POST["lastName"]);
    }

    if (empty($_POST["age"])) {
        $ageErr = "Age is required";
    } else {
        $age = test_input($_POST["age"]);
    }



    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
?>



<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
  <p><span class="error">* required field</span></p>
  First Name: <input type="text" name="firstName" value="<?php echo $name;?>">
  <span class="error">* <?php echo $firstNameErr;?></span>
  <br><br>
  Last Name: <input type="text" name="lastName" value="<?php echo $email;?>">
  <span class="error">* <?php echo $lastNameErr;?></span>
  <br><br>
  Age: <input type="number" min="0" max="100" name="age" value="<?php echo $website;?>">
  <span class="error">* <?php echo $ageErr;?></span>
  <br><br>
  <input type="submit" name="submit" value="ADD">  
</form>


<?php

$servername = "localhost";
$username = "debian";
$password = "password";
$databasename = "myDB";

$conn = mysqli_connect($servername, $username, $password, $databasename);

// Tries connection to the MYSQL server
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


// Runs MYSQL commands

/*
SOME EXAMPLES:

 - CREATE DB --> "CREATE DATABASE myDB"

 - CREATE TABLE --> "CREATE TABLE People (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(30) NOT NULL,
    lastname VARCHAR(30) NOT NULL,
    age INT(3) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"

 - DELETE TABLE --> "DROP TABLE [IF EXISTS] People"

 - INSERT IN TABLE --> "INSERT INTO People (firstname, lastname, age)
    VALUES ('Alex', 'Guardini', '25')"

 - DELETE FROM TABLE --> "DELETE FROM People WHERE id= 2"

*/



$sql = "INSERT INTO People (firstname, lastname, age)
        VALUES ('$firstName', '$lastName', '$age')";

if (mysqli_query($conn, $sql)) {
    echo "Insertion complete!";
  } else {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}



mysqli_close($conn);
?>