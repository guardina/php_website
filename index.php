<?php
    include "php/controller_db.php";
    include "php/file_loader.php";
    include "php/medreg_getters.php";
    include "php/refdata_getters.php";
    //include "download_ids.php";

    use Phppot\DataSource;
    use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

    require  ('vendor/autoload.php');
?>



<html>
<head>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>


<?php
    // Variables for the form
    $firstName = $lastName = $gln = $inputFile = "";
?>


<!------------------------------------------------------- FORMS ---------------------------------------------------------------------->


<!-- Form to input data or search inside DB -->
<form method="post">  
    First Name: <input type="text" name="firstName" value="<?php echo $firstName;?>">
    <br><br>
    Last Name: <input type="text" name="lastName" value="<?php echo $lastName;?>">
    <br><br>
    <div style="display: flex; gap: 50px;">
        GLN: <input type="text" name="gln" value="<?php echo $gln;?>">
        <input type="submit" class="button" name="get_from_DB" value="Search">
    </div>
    <br><br>
    <div style="display: flex; gap: 50px;">
        <input type="submit" class="button" name="search_gln_medreg" value="Search GLN (Medreg)">
        <select name="language" class="button" id="language">
	        <option value="">--- Choose a language ---</option>
	        <option value="De">Deutsch</option>
	        <option value="Fr">Français</option>
	        <option value="It">Italiano</option>
            <option value="En">English</option>
        </select>
    </div>
    <br><br>
    <input type="submit" class="button" name="search_gln_refdata" value="Search GLN (Refdata)">

</form> 


<!-- Form to print first 10 med_gln from database -->
<form method="post">
    <input type="submit" class="button" name="get_med_gln" value="Get Med gln">
</html>



<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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


<form method="post">
    <input type="submit" class="button" name="get_all_glns" value="Download">
    <br><br>
</html>

<html>
    <br><br>
</html>


<!------------------------------------------------------- PHP ---------------------------------------------------------------------->


<?php
    function print_info($gln, $data, $register) {

        echo '<br>Search gln [' . $gln . '] for register ' . $register . ':<br><br>';

        $language = filter_input(INPUT_POST, 'language', FILTER_SANITIZE_STRING);

        if ($language == "") {
            $language = "De";
        }

        if (empty($data)) {
            echo '<p class="error">Couldn\'t find data for the provided gln.</p>';
            return;
        }

        foreach($data as $k => $v) {
            if (check_language($k, $language)){
                echo $k . ': ' . $v . '<br>';
            }
        }
    }
?>


<?php
    
    if (!empty($_POST["firstName"])) {
        $firstName = test_input($_POST["firstName"]);
    }

    if (!empty($_POST["lastName"])) {
        $lastName = test_input($_POST["lastName"]);
    }

    if (!empty($_POST["gln"])) {
        $gln = test_input($_POST["gln"]);
    }


    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
        if (isset($_POST['search_gln_medreg'])) {
            if ($gln != "") {
                foreach(['medreg', 'psyreg', 'betreg'] as $register) {
                    $data = get_medreg_data_by_gln("$gln", $register);

                    print_info($gln, $data, $register);
                }

                
            } else {
                echo '<p class="error">Error! Invalid gln.</p>';
            } 

        } else if (isset($_POST['search_gln_refdata'])) {
            if ($gln != "") {
                echo '<br>Search gln [' . $gln . '] :<br><br>';

                downloadAll();
            } else {
                echo '<p class="error">Error! Invalid gln.</p>';
            }

        } else if (isset($_POST['get_from_DB'])) {
            if ($firstName == "" && $lastName == "" && $gln == "") {
                echo '<p class="error">Insert first name, last name or gln for a correct search!</p>';
            } else {
                $data = array();
                
                if ($firstName != "") {
                    $data['firstName'] = $firstName;
                }

                if ($lastName != "") {
                    $data['lastName'] = $lastName;
                }

                if ($gln != "") {
                    $data['gln'] = $gln;
                }

                get_entry($data);
            }
        } else if (isset($_POST['get_all_glns'])) {
            $curr_gln = 7601000000019;
            //$number_of_tries = 20;
            $glns_to_find = 5;

            while ($glns_to_find > 0 /*&& $number_of_tries > 0*/) {
                $gln_found = false;
                foreach(['medreg', 'psyreg', 'betreg'] as $register) {
                    $data = get_medreg_data_by_gln("$curr_gln", $register);

                    if (!empty($data)) {
                        print_info("$curr_gln", $data, $register);
                        if (!$gln_found) {
                            $gln_found = true;
                            $glns_to_find--;
                        }
                    }
                }
                $curr_gln++;
                //$number_of_tries--;
                echo "<br>";
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


    // Function that provided a key string and a language substring (De|Fr|It|En), returns true if the string is language related and the correct language is selected,
    // or if the key is not language related
    // Examples:
    // $language = Fr:
    // $key = genderFr => True / $key = genderDe => false / $key = firstName => true
    function check_language($key, $language) {
        if (preg_match('/(?:De|Fr|It|En)$/', $key) && preg_match('/[a-z]+'.$language.'$/', $key)) {
            return true;    
        } else if (!preg_match('/(?:De|Fr|It|En)$/', $key)) {
            return true; 
        }

        return false;
    }

?>