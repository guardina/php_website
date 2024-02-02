<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class BAGAPIClient
{
    private $api_key;
    private $debug;
    //private $ua;
    private $use_proxy;
    private $proxies;
    private $session;

    public function __construct($debug = false, $api_key = '', $use_proxy = false)
    {
        $this->api_key = $api_key;
        $this->debug = $debug;
        //$this->ua = new \FakeUserAgent\UserAgent();
        $this->use_proxy = $use_proxy;
        if ($use_proxy) {
            $this->proxies = $this->_getProxyList(); // Will contain proxies [ip, port]
        }
        $this->session = $this->_getNewSession();
    }

    public function searchGLN($gln)
    {
        $req_content = [
            "name" => null,
            "firstName" => null,
            "gln" => strval($gln),
            "professionId" => 1,
            // Add other parameters as needed
        ];

        $url = "https://www.healthreg-public.admin.ch/api/medreg/public/person/search";
        $res = $this->session->post($url, ['json' => $req_content]);
        $soup = new Crawler($res->getBody()->getContents());

        $entry = json_decode($soup->filter('p')->text(), true);

        if ($this->debug) {
            // print($res->getStatusCode());
            // print_r($res->getHeaders());
            print_r($entry);
            // print_r($res->getBody());
        }

        return [$entry, $res];
    }

    public function getDataWithBAGPersonID($register, $person_id)
    {

        $req_content = [
            "id" => $person_id
        ];

        $url = "";

        if (in_array($register, ['medreg', 'psyreg'])) {
            $url = "https://www.healthreg-public.admin.ch/api/$register/public/person";
        } elseif ($register == 'betreg') {
            $url = "https://www.healthreg-public.admin.ch/api/$register/public/company";
        }

        $res = $this->session->post($url, ['json' => $req_content]);
        $soup = new Crawler($res->getBody()->getContents());

        $entry = null;

        var_dump($res);

        if ($res->getStatusCode() == 200) {
            $entry = json_decode($soup->filter('p')->text(), true);
        }

        if ($this->debug) {
            print($res->getStatusCode());
            // print_r($res->getHeaders());
            print_r($entry);
        }

        return [$entry, $res];
    }

    private function _getProxyList()
    {
        // Implement your logic to get proxies
        // You may use libraries like Goutte or others for web scraping

        return [];
    }

    private function _makeHeaders()
    {
        return [
            'Host' => 'www.healthreg-public.admin.ch',
            'User-Agent' => "Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0",
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Encoding' => 'gzip, deflate, br',
            'X-Requested-With' => 'XMLHttpRequest',
            'api-key' => $this->api_key,
            'Content-Type' => 'application/json',
            'Origin' => 'https://www.healthreg-public.admin.ch',
        ];
    }

    private function _getWorkingProxy()
    {
        // Implement your logic to get a working proxy
        // You may use libraries like Goutte or others for web scraping

        return [];
    }

    private function _getNewSession()
    {
        if (isset($this->session)) {
            $this->session->close();
        }

        $s = new Client(['headers' => $this->_makeHeaders()]);

        if ($this->use_proxy) {
            $proxy = $this->_getWorkingProxy();
            $s->getConfig('handler')->set('proxy', $proxy);
        }

        return $s;
    }

    public function changeSession()
    {
        $this->session = $this->_getNewSession();
    }

    public function waitApiUntilWorks($pers_id, $register, $res, $max_wait_times)
    {
        $wait_cnt = 0;

        while (!in_array($res->getStatusCode(), [200, 204])) {
            if ($wait_cnt >= $max_wait_times) {
                echo "Couldn't connect for a long time. Last response:\n\n" . $res->getStatusCode() . "\n\nSaving and exiting...";
                break;
            }

            $wait_mins = 5;
            $wait_until_dt = (new \DateTime())->add(new \DateInterval('PT' . $wait_mins . 'M'))->format('H:i:s');
            echo "Can't connect, waiting for $wait_mins minutes until $wait_until_dt before trying again. Status code: " . $res->getStatusCode() . PHP_EOL;
            sleep($wait_mins * 60);
            $wait_cnt++;

            list($entry, $res) = $this->getDataWithBAGPersonID($pers_id, $register);
        }

        return [$entry, $res];
    }
}

if (isset($argv[1]) && ($argv[1] == '-h' || $argv[1] == '--help')) {
    echo <<<EOL
    Scrape (download) medical data from the BAG health register (https://www.healthreg-public.admin.ch/).
    
    Usage:
    
        php BAG_API_Client.php <-r/--register medreg/psyreg/betreg/all> [OPTIONS]
    
        OPTIONS:
        
           -o/--offset <offset_integer> : Start downloading from a specific BAG internal ID, 
                                          only for the first register if using '-r all'.
           -u/--until <until_integer>   : Stop downloading at given BAG internal ID.
           
        Examples:

            - Simply download everything:
                php BAG_API_Client.php -r all
            
            - Resume a broken medreg download from ID 50000 (e.g. that got to 54320 
              and was saved until 49999):
                php BAG_API_Client.php -r medreg -o 50000
EOL;
    exit(0);
}

$offset_pid = 0;
$until_pid = 200000;
$registers = '';

if (count($argv) <= 1) {
    echo "Error: No arguments provided.\n";
    exit(1);
}

$argSet = array_chunk(array_slice($argv, 1), 2);
$data = ['person_ids' => []];
$saveAndExit = false;
$today = date('Ymd');

foreach ($argSet as $tup) {
    switch ($tup[0]) {
        case '-o':
        case '--offset':
            if (is_numeric($tup[1])) {
                $offset_pid = (int)$tup[1];
            }
            break;
        case '-u':
        case '--until':
            if (is_numeric($tup[1])) {
                $until_pid = (int)$tup[1];
            }
            break;
        case '-r':
        case '--register':
            $tup[1] = strtolower($tup[1]);
            if (in_array($tup[1], ['medreg', 'psyreg', 'betreg', 'all'])) {
                $registers = ($tup[1] == 'all') ? ['medreg', 'psyreg', 'betreg'] : [$tup[1]];
            } else {
                echo "Error: Arguments {$tup[0]} and {$tup[1]} not recognized.\n";
                echo "See usage with 'php BAG_API_Client.php -h'\n";
                exit(1);
            }
            break;
        default:
            echo "Error: Arguments {$tup[0]} and {$tup[1]} not recognized.\n";
            echo "See usage with 'php BAG_API_Client.php -h'\n";
            exit(1);
    }
}

if (empty($registers)) {
    echo "Error: No registers given.\n";
    echo "See usage with 'php BAG_API_Client.php -h'\n";
    exit(1);
}

echo (count($registers) > 1) ? "The program will scrape data from BAG registers: " . implode(', ', $registers) . ".\n" : "";
$client = new BAGAPIClient(false, 'AB929BB6-8FAC-4298-BC47-74509E45A10B');

foreach ($registers as $r) {
    echo "Starting scraping data from BAG register $r...\n";

    if ($r == 'betreg' && $until_pid == 200000) {
        $until_pid = 10000;
    }

    for ($pers_id = $offset_pid; $pers_id < $until_pid; $pers_id++) {
        echo $pers_id . "\n";
        list($entry, $res) = $client->getDataWithBAGPersonID($r, $pers_id);

        if (!in_array($res->getStatusCode(), [200, 204])) {
            echo "\nBAG response: {$res->getStatusCode()}\n";

            // Change session + proxies if used
            // $client->changeSession();
            // list($entry, $res) = $client->getDataWithBAGPersonID($r, $pers_id);

            // Or, if not using proxies, just wait 5m + (max_wait_times - 1) * 15m
            list($entry, $res) = $client->waitApiUntilWorks($pers_id, $r, $res, 10);
        }

        // If still no positive response, set saveAndExit to true
        if (!in_array($res->getStatusCode(), [200, 204])) {
            $saveAndExit = true;
        }

        if (!$saveAndExit) {
            $data['person_ids'][$pers_id] = $entry;
            // Small wait to delay getting blocked/timed out by the server
            usleep(100000);
        }

        // Save data to file if pers_id is 1 away from multi# 1st day of downloading, to keep naming consistentple of 10,000 or if exit_flag is true
        if (($pers_id + 1) % 10000 == 0 || $saveAndExit) {
            if ($saveAndExit) {
                $pers_id--;
            }

            if ($offset_pid != 0 && $pers_id <= floor($offset_pid / 10000) * 10000 + 9999) {
                $fname = "{$r}_{$today}_{$offset_pid}-{$pers_id}.json";
            } else {
                $fname = "{$r}_{$today}_" . floor($pers_id / 10000) * 10000 . "-{$pers_id}.json";
            }

            // Dump data into a JSON file
            if (!file_exists('data/')) {
                mkdir('data');
            }

            file_put_contents("data/{$fname}", json_encode($data));

            // Reset data structure
            $data = ['person_ids' => []];
        }

        if ($saveAndExit) {
            exit(1);
        }
    }

    // Reset offset to 0 (probably no need to offset next register)
    $offset_pid = 0;
}