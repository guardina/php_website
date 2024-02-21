<?php

    $requests_per_bucket = 100;

    // Function that will do more HTTP requests at the same time. All results are then gathered. The number of requests at the same time is defined in $requests_per_bucket
    function makeParallelRequests($url, $payloads, $register) {
        global $requests_per_bucket; 

        $handles = [];
        $results = [];

        $multiHandle = curl_multi_init();


        // The most important header option to keep is the api-key; withouth that, the response will be empty.
        // In case the request is empty even if the logic here is correct and the api-key is present, double check if the api-key was changed

        if (in_array($register, ['medreg', 'psyreg'])) {
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

        } else if ($register == 'betreg') {
            for ($i = 0; $i < $requests_per_bucket; $i++) {
                $ch = curl_init($url[$i]);
                $options = [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'api-key: AB929BB6-8FAC-4298-BC47-74509E45A10B']
                ];
                curl_setopt_array($ch, $options);
                curl_multi_add_handle($multiHandle, $ch);
                $handles[] = $ch;
            }
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
?>