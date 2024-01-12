<style>
.error {color: #FF0000;}
</style>

<html>
<head>
    <link rel="icon" href="data:,">
</head>
<body>

<?php
    $firstNameErr = $lastNameErr = $ageErr = $glnErr = "";
    $firstName = $lastName = $gln = $inputFile = "";
    $age = 0;
?>


<form method="post">  
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
    <input type="submit" class="button" name="get_gln" value="Get name">  
    <br><br>
    <input type="submit" class="button" name="save" value="Save">
</form>






<?php
    
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


    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
        if (isset($_POST['get_gln'])) {
            if ($gln != "") {
                get_doctor_by_gln("$gln");
            } else {
                echo "Error! Invalid gln.";
            } 
        }
        
        if (isset($_POST['save'])) {
            save_doctor("$gln");
        }
    }


    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    
    $myDictionary = array(
        '0_name' => '',
        '0_firstName' => '',
        'profession_isActive' => '',
        'profession_textDe' => '', 
        'profession_textFr' => '',
        'profession_textIt' => '',
        'profession_textEn' => '',
        '0_textDe' => '', 
        '0_textFr' => '',
        '0_textIt' => '',
        '0_textEn' => '',
        'canton_textDe' => '', 
        'canton_textFr' => '',
        'canton_textIt' => '',
        'canton_textEn' => ''
    );


    function save_doctor($gln) {
        global $myDictionary;

        foreach ($myDictionary as $key => $value) {
            echo $key . ": " . $value . "<br>";
        }
    }

    
    function get_single_element($list, $prefix = '') {

        $list_accepted = array('0_name', '0_firstName', 'profession_isActive', 'profession_textDe', 'profession_textFr', 'profession_textIt', 'profession_textEn', '0_textDe', '0_textFr', '0_textIt', '0_textEn', 'canton_textDe', 'canton_textFr', 'canton_textIt', 'canton_textEn');
        global $myDictionary;

        foreach ($list as $key => $value) {
            if (is_array($value)) {
                get_single_element($value, $key . '_');
            } else {
                $string = $prefix . $key;
                if (in_array($string, $list_accepted)) {
                    $myDictionary[$string] = $value;
                    echo $prefix . $key . ':' . $value . '<br>';
                } else {
                    ;
                }
            }
        }
    }





    function get_doctor_by_gln($gln) {

        //$url = "https://refdatabase.refdata.ch/Viewer/SearchPartnerByGln?Lang=de";
        $url = "https://www.healthreg-public.admin.ch/api/medreg/public/person/search";


        $ch = curl_init();


        // ARRAY FOR REFDATA
        /*
        $data = array(
            'SearchGln' => $gln,
            'Sort' => '',
            'NewSort' => '',
            'IsAscending' => 'False',
            'Reset' => 'False'
        );
        */


    
    
        // ARRAY FOR MEDREG
        
        $data = array(
                'cetTitleKindIds' => null, 
                'city' => null, 
                'firstName' => null, 
                'genderId' => null, 
                'gln' => $gln, 
                'houseNumber' => null, 
                'languageId' => null, 
                'name' => null, 
                'nationalityId' => null, 
                'permissionCantonId' => null, 
                'privateLawCetTitleKindIds' => null, 
                'professionalPracticeLicenseId' => null, 
                'professionId' => null, 
                'street' => null, 
                'zip' => null
        );
    
        


        // OPTIONS FOR THE MEDREG
        $options = array (
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: en-CH; en',
            'api-key: AB929BB6-8FAC-4298-BC47-74509E45A10B',
            'Connection: keep-alive',
            'Content-Type: application/json',
            'Host: www.healthreg-public.admin.ch',
            'Origin: https://www.healthreg-public.admin.ch',
            'Referer: https://www.healthreg-public.admin.ch/medreg/search',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0'
        );


        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    

        $output = curl_exec($ch);
        $result = json_decode($output, true);


        

        if ($output === false) {
            echo 'Error: ' . curl_error($ch);
        } else {
            get_single_element($result);
        }

        curl_close($ch);
    }






    // CONNECTION TO DB


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

    mysqli_close($conn);
?>