<style>
.error {color: #FF0000;}
</style>

<?php
    $firstNameErr = $lastNameErr = $ageErr = $glnErr = "";
    $firstName = $lastName = $gln = $inputFile = "";
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

    if (empty($_POST["gln"])) {
        $glnErr = "GLN is required";
    } else {
        $gln = test_input($_POST["gln"]);
    }

    if(empty($_FILES)) {
        $inputFile = $_FILES["file"]["name"];
        echo $inputFile;
    } 


    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    function getName() {
        echo "salve";
    }
?>



<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
  <p><span class="error">* required field</span></p>
  First Name: <input type="text" name="firstName" value="<?php echo $firstName;?>">
  <span class="error">* <?php echo $firstNameErr;?></span>
  <br><br>
  Last Name: <input type="text" name="lastName" value="<?php echo $lastName;?>">
  <span class="error">* <?php echo $lastNameErr;?></span>
  <br><br>
  Age: <input type="number" min="0" max="100" name="age" value="<?php echo $age;?>">
  <span class="error">* <?php echo $ageErr;?></span>
  <br><br>
  GLN: <input type="text" name="gln" value="<?php echo $gln;?>">
  <span class="error">* <?php echo $glnErr;?></span>
  <br><br>
  <input type="submit" name="submit" value="SEARCH">  
  <br><br>
  <!-- <input type="button" name="getName" value="Get name" onclick="getName()"> -->
</form>

<div>
	<input type="file" name="file" id="file" class="file" accept=".xls,.xlsx,.csv">
</div>


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



//METHOD TO WRITE DATA FROM THE DB TO THE FILE 'output.csv'

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




/*
URLS:
MedReg = https://www.healthreg-public.admin.ch/api/medreg/public/person/search
PsyReg = https://www.healthreg-public.admin.ch/api/psyreg/public/person/search
BetReg = https://www.healthreg-public.admin.ch/api/betreg/public/person/search

Example gln = 7601000180100
*/


// FIRST ATTEMPT TO GET DATA FROM MEDREG


$start_gln = 7601000180100;
$url = "https://www.healthreg-public.admin.ch/api/medreg/public/person/search";
$counter = 0;
$counter2 = 0;



$data = array('gln' => $start_gln);
$context = stream_context_create(array(
    'http' => array(
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode($data)
    )
));

$result = file_get_contents($url, FALSE, $context);
if ($result === FALSE) {
    echo $counter2++  . "Person not found!\n";
} else {
    $counter++;
}

echo $result;
$resultData = json_decode($result, TRUE);
echo $resultData;



/*
for ($i = 0; $i <= 100; $i++) {
    $data = ['gnl' => $start_gln+$i];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === false) {
        echo $counter2++  . "Person not found!\n";
    } else {
        var_dump($result);
        $counter++;
    }

    
    if ($counter == 10) {
        break;
    }
}
*/




mysqli_close($conn);
?>