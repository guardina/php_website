<!-- Color for error messages -->
<style>
.error {color: #FF0000;}
</style>

<html>
<head>
    <link rel="icon" href="data:,">
</head>
<body>

<?php
    // Variables for the form
    $firstNameErr = $lastNameErr = $ageErr = $glnErr = "";
    $firstName = $lastName = $gln = $inputFile = "";
    $age = 0;


    // Dictionary to later store information scraped from medical registers
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


    // Mapping dictionary used to map the keys scraped from medical registers to DB entries
    $columnMapping = array(
        '0_name' => 'lastName',
        '0_firstName' => 'firstName',
        'profession_isActive' => 'isActive',
        'profession_textDe' => 'profession_textDe', 
        'profession_textFr' => 'profession_textFr',
        'profession_textIt' => 'profession_textIt',
        'profession_textEn' => 'profession_textEn',
        '0_textDe' => 'cetTitles_textDe', 
        '0_textFr' => 'cetTitles_textFr',
        '0_textIt' => 'cetTitles_textIt',
        '0_textEn' => 'cetTitles_textEn',
        'canton_textDe' => 'canton_textDe', 
        'canton_textFr' => 'canton_textFr',
        'canton_textIt' => 'canton_textIt',
        'canton_textEn' => 'canton_textEn'
);
?>


<!-- Form to input data or search inside DB -->
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
                save_doctor_by_gln("$gln", $myDictionary, $columnMapping);
            } else {
                echo "Error! Invalid gln.";
            } 
        }
    }



    ///// FUNCTIONS /////


    // Function to properly format the input data from the form
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }


    
    // Recursive function to "flatten" nested dictionaries and stores the single values inside of the dictionary, according to the key
    // Example: [key1 -> value1, key2 -> [key3 -> value3]] ===> key1 -> value1 / key2_key3 -> value3
    function get_single_element($list, $prefix = '', &$myDictionary) {

        $list_accepted = array('0_name', '0_firstName', 'profession_isActive', 'profession_textDe', 'profession_textFr', 'profession_textIt', 'profession_textEn', '0_textDe', '0_textFr', '0_textIt', '0_textEn', 'canton_textDe', 'canton_textFr', 'canton_textIt', 'canton_textEn'); 

        foreach ($list as $key => $value) {
            if (is_array($value)) {
                get_single_element($value, $key . '_', $myDictionary);
            } else {
                $string = $prefix . $key;
                if (in_array($string, $list_accepted)) {
                    $myDictionary[$string] = $value;
                    //echo $prefix . $key . ': ' . $value . '<br>';
                } else {
                    ;
                }
            }
        }

    }




    // Function that scrapes online for information about a doctor, given a specific gln
    function save_doctor_by_gln($gln, $myDictionary, $columnMapping) {

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
        // Payload sent to the website with a POST request, in order to obtain information about the doctor
        $data = array(
                "cetTitleKindIds" => null, 
                "city" => null, 
                "firstName" => null, 
                "genderId" => null, 
                "gln" => $gln, 
                "houseNumber" => null, 
                "languageId" => null, 
                "name" => null, 
                "nationalityId" => null, 
                "permissionCantonId" => null, 
                "privateLawCetTitleKindIds" => null, 
                "professionalPracticeLicenseId" => null, 
                "professionId" => null, 
                "street" => null, 
                "zip" => null
        );
    
        


        // OPTIONS FOR THE MEDREG
        // Extra options to add to the request (api-key is necessary to obtain the JSON response)
        $options = array (
            "Accept: application/json, text/plain, */*",
            "Accept-Encoding: gzip, deflate, br",
            "Accept-Language: en-CH; en",
            "api-key: AB929BB6-8FAC-4298-BC47-74509E45A10B",
            "Connection: keep-alive",
            "Content-Type: application/json",
            "Host: www.healthreg-public.admin.ch",
            "Origin: https://www.healthreg-public.admin.ch",
            "Referer: https://www.healthreg-public.admin.ch/medreg/search",
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-origin",
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0"
        );



        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    

        $output = curl_exec($ch);
        $result = json_decode($output, true);


        

        if ($output === false) {
            echo "Error: " . curl_error($ch);
        } else {
            get_single_element($result, '', $myDictionary);

            $conn = connect_to_db("myDB");


            // From the initial dictionary, we only extract the pairs key-value that actually have a value stored, as we cannot add empty values to a SQL table
            $non_empty_dict = array();

            foreach($myDictionary as $key => $value) {
                if (!empty($value)) {
                    $non_empty_dict[$key] = $value;
                }
            }


            // myDictionary has keys taken from the HTML response provided by the site, we need to map them to the column's names in the SQL table
            $columns = array();
            $values = array();

            $columns[] = 'gln';
            $values[] = "$gln";

            foreach ($non_empty_dict as $key => $value) {
                if (isset($columnMapping[$key])) {
                    $columns[] = $columnMapping[$key];
                    $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
                }
            }
        


            
            $query = "INSERT INTO Doctors (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";


            mysqli_query($conn, $query);

            mysqli_close($conn);
        }

            curl_close($ch);
    }






    // CONNECTION TO DB

    function connect_to_db($databasename) {
        $servername = "localhost";
        $username = "debian";
        $password = "password";
        //$databasename = "myDB";

        $conn = mysqli_connect($servername, $username, $password, $databasename);

        // Tries connection to the MYSQL server
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        return $conn;
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
?>