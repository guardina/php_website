<?php

    include_once "controller_db.php";
    include "medreg_HTTP_controller.php";

    $requests_per_bucket = 100;


    function format_name($string) {
        $lowercase_string = strtolower($string);
        return ucfirst($lowercase_string);
    }



    function check_data_again($register, $number_of_samples, $buckets_to_check) {
        $conn = connect_to_db("stammdaten_gln");

        global $requests_per_bucket;

        if (in_array($register, ['medreg', 'psyreg'])) {
            $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        } else if ($register == 'betreg') {
            $url = "https://www.healthreg-public.admin.ch/api/betreg/public/company";
        }

        $bucket = 1;
        $total = 0;

        
        for ($i = 0; $i<$number_of_samples; $i+=$requests_per_bucket) {
            if (in_array($bucket, $buckets_to_check)) {
                echo "Rechecking bucket $bucket\n";

                if (in_array($register, ['medreg', 'psyreg'])) {
                    $payloads = [];
                    for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                        $payloads[] = ['id' => $j];
                    }   

                    $start_time = microtime(true);
                    $results = makeParallelRequests($url, $payloads, $register);
                    $end_time = microtime(true);

                } else if ($register == 'betreg') {
                    $urls = [];
                    $payloads = [];
                    for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                        $urls[] = $url . "/" . $j;
                    }   

                    $start_time = microtime(true);
                    $results = makeParallelRequests($urls, $payloads, $register);
                    $end_time = microtime(true);
                }
    
                $total_time = $end_time - $start_time;

                $curr_id = $i;
    
                foreach ($results as $result) {
    
                    $query = "UPDATE " . substr($register, 0, -3) . "_ids SET round_2 = ";
    
                    if ($result !== null) {
                        $query .= "1";
                    } else {
                        $query .= "0";
                    }

                    $query .= " WHERE id = $curr_id";
    
                    //$conn->query($query);

                    $curr_id++;
                }
    
                echo "\nTime elapsed: " . $total_time . "\n\n";
            }

            $bucket++;
        }

        mysqli_close($conn);
    }




    function check_ids($register, $number_of_samples) {

        $conn = connect_to_db("stammdaten_gln");

        global $requests_per_bucket;

        if (in_array($register, ['medreg', 'psyreg'])) {
            $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        } else if ($register == 'betreg') {
            $url = "https://www.healthreg-public.admin.ch/api/betreg/public/company";
        }
        
        $bucket = 1;
        $total = 0;
        $bucket_to_check_again = [];

        for ($i = 0; $i<$number_of_samples; $i+=$requests_per_bucket) {
            echo "[Bucket $bucket] Starting id check! ($register)\n";
            if (bucket_already_exists_in_db($conn, $register, $i, $i+$requests_per_bucket)) {
                echo "[Bucket $bucket] Already present in database ($register)\n\n";
                $bucket++;
                continue;
            }


            
            if (in_array($register, ['medreg', 'psyreg'])) {
                $payloads = [];
                for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                    $payloads[] = ['id' => $j];
                }   

                $start_time = microtime(true);
                $results = makeParallelRequests($url, $payloads, $register);
                $end_time = microtime(true);

            } else if ($register == 'betreg') {
                $urls = [];
                $payloads = [];
                for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                    $urls[] = $url . "/" . $j;
                }   

                $start_time = microtime(true);
                $results = makeParallelRequests($urls, $payloads, $register);
                $end_time = microtime(true);
            }
            
            

            

            $total_time = $end_time - $start_time;

            $curr_id = $i;

            foreach ($results as $result) {

                // HERE WE CAN TAKE THE DATA, AS RESULT HAS FIRST NAME, LAST NAME, AND SO ON

                $query = "INSERT INTO " . substr($register, 0, -3) . "_ids(id, bucket, round_1) VALUES ($curr_id, $bucket, ";

                if ($result !== null) {
                    $query .= "1)";
                } else {
                    $query .= "0)";
                    $empty_results++;
                }

                $conn->query($query);

                $curr_id++;
            }


            echo "[Bucket $bucket] Time elapsed: " . $total_time . "\n\n";

            $bucket++;
        }

        $to_check_again_query = "SELECT bucket FROM " . substr($register, 0, -3) . "_ids GROUP BY bucket HAVING SUM(round_1) = 0";
        $result = mysqli_query($conn, $to_check_again_query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $bucket_to_check_again[] = $row['bucket'];
            }
        }

        mysqli_close($conn);

        check_data_again($register, $number_of_samples, $bucket_to_check_again);
    }


    if ($argc < 3) {
        echo "Usage: php download_ids.php <register> <number of samples>\n";
        echo "Possible registers: medreg, psyreg, betreg\n";
        echo "Range for samples: 100 - 200000\n";
        exit(1);
    }

    $register = $argv[1];
    $number_of_samples = $argv[2];
    check_ids($register, $number_of_samples);
?>