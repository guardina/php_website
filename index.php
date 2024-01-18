<?php
    include "php/controller_db.php";
    include "php/file_loader.php";
    include "php/medreg_getters.php";
    include "php/refdata_getters.php";
?>



<html>
<head>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" type="text/css" href="css/style.css">
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


<!------------------------------------------------------- FORMS ---------------------------------------------------------------------->


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


<!-- Form to print first 10 med_gln from database -->
<form method="post">
    <input type="submit" class="button" name="get_med_gln" value="Get Med gln">
</html>



<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text Box Example</title>
    <style>
        .text-box {
            border: 2px solid #000;
            padding: 10px;
            margin: 10px;
            width: 200px;
        }
    </style>
</head>
<body>

    <?php
        $text = "";

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            if (isset($_POST["get_med_gln"])) {
                $glns = get_glns("med_gln");

                for ($i = 0; $i < 10; $i++) {
                    $text = $text . $glns[$i] . "<br>";
                }
            }
        }
    ?>

    <div class="text-box">
        <?php echo $text; ?>
    </div>

</body>
</html>



<!-- Form to print first 10 psy_gln from database -->
<form method="post">
    <input type="submit" class="button" name="get_psy_gln" value="Get Psy gln">
</html>



<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text Box Example</title>
    <style>
        .text-box {
            border: 2px solid #000;
            padding: 10px;
            margin: 10px;
            width: 200px;
        }
    </style>
</head>
<body>

    <?php
        $text = "";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            if (isset($_POST["get_psy_gln"])) {
                $glns = get_glns("psy_gln");

                for ($i = 0; $i < 10; $i++) {
                    $text = $text . $glns[$i] . "<br>";
                }
            }
        }
    ?>

    <div class="text-box">
        <?php echo $text; ?>
    </div>

</body>
</html>


<!------------------------------------------------------- PHP ---------------------------------------------------------------------->




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
                save_medreg_doctor_by_gln("$gln", $myDictionary, $columnMapping);
            } else {
                echo "Error! Invalid gln.";
            } 
        }
    }



    // ------------------------------------------------------- FUNCTIONS ----------------------------------------------------------------------


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

?>