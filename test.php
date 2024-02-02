<?php
    function makeParallelRequests($url, $payloads) {
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
        for ($i = 0; $i < 1000; $i++) {
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

    // Example usage
    $url = 'https://www.healthreg-public.admin.ch/api/medreg/public/person';
    $payloads = [];
    $start_address = 22800;

    for ($i = $start_address; $i<$start_address + 1000; $i++) {
        $payloads[] = ['id' => $i];
    }
    
    $start_time = microtime(true);
    $results = makeParallelRequests($url, $payloads);
    $end_time = microtime(true);

    $total_time = $end_time - $start_time;

    echo "Execution time: {$total_time} seconds\n";

    // Handle the results
    foreach ($results as $result) {
        if ($result !== null) {
            print_r($result['name'] . "\n");
        } else {
            ;//echo "Error making one of the requests.\n";
        }
}
?>