<?php

    include_once "php/controller_db.php";

    $requests_per_bucket = 100;

    function makeParallelRequests($url, $payloads) {
        global $requests_per_bucket; 

        $handles = [];
        $results = [];

        // Initialize multi cURL handler
        $multiHandle = curl_multi_init();


        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'en-CH; en',
            'api-key' => 'AB929BB6-8FAC-4298-BC47-74509E45A10B',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/json',
            'Host' => 'www.healthreg-public.admin.ch',
            'Origin' => 'https://www.healthreg-public.admin.ch',
            'Referer' => "https://www.healthreg-public.admin.ch/medreg/search",
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0'
            //"User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0"
        ];


        // Create cURL handles for each request
        for($i = 0; $i < $requests_per_bucket; $i++) {
            $ch = curl_init($url);
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payloads[$i]),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'api-key: AB929BB6-8FAC-4298-BC47-74509E45A10B']
            ];
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($multiHandle, $ch);
            $handles[] = $ch;
        }

        // Execute all cURL requests simultaneously
        $active = null;
        do {
            $status = curl_multi_exec($multiHandle, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        // Loop and retrieve the results
        while ($active && $status === CURLM_OK) {
            if (curl_multi_select($multiHandle) === -1) {
                usleep(100);
            }

            do {
                $status = curl_multi_exec($multiHandle, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);
        }

        // Retrieve the results and close handles
        foreach ($handles as $ch) {
            $results[] = json_decode(curl_multi_getcontent($ch), true);
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        // Close the multi cURL handler
        curl_multi_close($multiHandle);

        return $results;
    }


    function format_name($string) {
        $lowercase_string = strtolower($string);
        return ucfirst($lowercase_string);
    }


    function already_exists_in_db($conn, $register, $id) {
        $select_query = "SELECT id from " . substr($register, 0, -3) . "_ids WHERE id=$id";
        $result = mysqli_query($conn, $select_query);

        return mysqli_num_rows($result) > 0;
    }



    function check_data_again($register, $number_of_samples, $buckets_to_check) {
        $conn = connect_to_db("stammdaten_gln");

        global $requests_per_bucket;

        $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        $bucket = 1;
        $total = 0;

        if (in_array($bucket, $buckets_to_check)) {
            echo "Rechecking bucket $bucket\n";
            for ($i = 0; $i<$number_of_samples; $i+=$requests_per_bucket) {
                $payloads = [];
                for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                    $payloads[] = ['id' => $j];
                }   
                
    
                $start_time = microtime(true);
                $results = makeParallelRequests($url, $payloads);
                $end_time = microtime(true);
    
                $total_time = $end_time - $start_time;

                $curr_id = $i;
    
                foreach ($results as $result) {
    
                    $query = "INSERT INTO substr($register, 0, -3)_ids (round_2) VALUES (";
    
                    if ($result !== null) {
                        $query .= "1)";
                    } else {
                        $query .= "0)";
                    }

                    $query .= " WHERE id = $curr_id";
    
                    $conn->query($query);

                    $curr_id++;
                }
    
                $bucket++;
    
                echo "\n\nTime elapsed: " . $total_time . "\n\n";
            }
        }

        mysqli_close($conn);
    }




    function check_ids($register, $number_of_samples) {

        $conn = connect_to_db("stammdaten_gln");

        global $requests_per_bucket;

        $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        $bucket = 1;
        $total = 0;
        $bucket_to_check_again = [];

        for ($i = 0; $i<$number_of_samples; $i+=$requests_per_bucket) {
            echo "[Bucket $bucket] Starting id check!\n";
            if (already_exists_in_db($conn, $register, $i)) {
                echo "[Bucket $bucket] Already present in database\n\n";
                $bucket++;
                continue;
            }

            $payloads = [];
            for ($j = $i; $j < $bucket*$requests_per_bucket; $j++) {
                $payloads[] = ['id' => $j];
            }   
            

            $start_time = microtime(true);
            $results = makeParallelRequests($url, $payloads);
            $end_time = microtime(true);

            $total_time = $end_time - $start_time;

            $curr_id = $i;
            $empty_results = 0;

            foreach ($results as $result) {

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

        $to_check_again_query = "SELECT bucket FROM " . substr($register, 0, -3) . "_ids GROUP BY bucket HAVING COUNT(round_1) = 0";
        $result = mysqli_query($conn, $to_check_again_query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo $row . "\n";
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