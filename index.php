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
    <input type="submit" class="button" name="search_gln" value="Search GLN">
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
        if (isset($_POST['search_gln'])) {
            if ($gln != "") {
                foreach(['medreg', 'psyreg', 'betreg'] as $register) {

                    echo '<br>Search gln [' . $gln . '] for register ' . $register . ':<br><br>';
                    $data = get_data_from_gln("$gln", $register);

                    if (empty($data)) {
                        echo '<p class="error">Couldn\'t find data for the provided gln.</p>';
                        continue;
                    }
                }

                
            } else {
                echo '<p class="error">Error! Invalid gln.</p>';
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

?>