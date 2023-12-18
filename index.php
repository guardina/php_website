<?php echo file_get_contents("html/header.html"); ?>
<?php echo file_get_contents("html/body.html"); ?>

<?php 
$servername = "localhost";
$username = "debian";
$password = "password";
$databasename = "myDB";

$conn = mysqli_connect($servername, $username, $password, $databasename);

// Tries connection to the MYSQL server
//if (!$conn) {
//    die("Connection failed: " . mysqli_connect_error());
//}

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

/*
$sql = "DELETE FROM People WHERE id= 2";
if (mysqli_query($conn, $sql)) {
    echo "Table \"People\" created successfully";
} else {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}
*/
mysqli_close($conn);
?>