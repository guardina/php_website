<?php

require 'vendor/autoload.php';

class RefdataClient
{
    private $request_body;
    private $request_headers;
    private $partner_data_url;
    private $fpath;
    private $file_date;

    public function __construct($type = 'ALL', $ptype = null, $term = null)
    {
        $this->request_body = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <DownloadPartnerInput xmlns="http://refdatabase.refdata.ch/">
            <TYPE xmlns="http://refdatabase.refdata.ch/Partner_in">$type</TYPE>
        </DownloadPartnerInput>
    </soap:Body>
</soap:Envelope>
XML;

        $this->request_headers = [
            'Host' => 'refdatabase.refdata.ch',
            'Content-Type' => 'text/xml; charset=utf-8',
            'Content-Length' => 'length',
            'SOAPAction' => '"http://refdatabase.refdata.ch/Download"'
        ];

        $this->partner_data_url = 'https://refdatabase.refdata.ch/Service/Partner.asmx';

        if (!file_exists('data')) {
            mkdir('data');
        }

        $this->file_date = $type != 'ALL' ? $type : date('Y-m-d');
        $this->fpath = 'data/refdata_partner_' . $this->file_date . '.xml';
    }

    public function downloadAll()
    {
        echo 'Downloading data from refdata.ch...' . PHP_EOL;
        $res = $this->sendRequest();
        echo $res->getStatusCode() . PHP_EOL;

        // Dump data into an XML file
        if ($res->getStatusCode() == 200) {
            file_put_contents($this->fpath, $res->getBody());
        } else {
            echo 'Download from refdata.ch failed. Status code: ' . $res->getStatusCode() . PHP_EOL;
            echo 'Response content:' . PHP_EOL;
            echo $res->getBody() . PHP_EOL; 
            exit(1);
        }
    }

    public function doesDataAlreadyExist()
    {
        return file_exists($this->fpath);
    }

    private function sendRequest()
    {
        $client = new \GuzzleHttp\Client();
        return $client->post($this->partner_data_url, [
            'headers' => $this->request_headers,
            'body' => $this->request_body
        ]);
    }

    public function mapXmlToList()
    {
        $xml = simplexml_load_file($this->fpath);
        $dataDicts = [];
        $rolesDicts = [];

        foreach ($xml->xpath('//Partner_out:ITEM') as $item) {
            $gln = (int)$item->Partner_out->GLN;

            $dataDict = [
                'gln' => $gln,
                'effective_dt' => (string)$item->attributes()->DT,
                'status_date' => (string)$item->Partner_out->STDATE,
                'ptype' => (string)$item->Partner_out->PTYPE,
                'status' => (string)$item->Partner_out->STATUS,
                'lang' => (string)$item->Partner_out->LANG,
                'descr1' => (string)$item->Partner_out->DESCR1,
                'descr2' => (string)$item->Partner_out->DESCR2,
            ];

            $dataDicts[] = $dataDict;

            foreach ($item->Partner_out->ROLE as $i => $role) {
                $rolesDict = [
                    'gln' => $gln,
                    'role_nr' => $i + 1,
                    'effective_dt' => $dataDict['effective_dt'],
                    'TYPE' => (string)$role->TYPE,
                    'STREET' => (string)$role->STREET,
                    'STRNO' => (string)$role->STRNO,
                    'POBOX' => (string)$role->POBOX,
                    'ZIP' => (string)$role->ZIP,
                    'CITY' => (string)$role->CITY,
                    'CTN' => (string)$role->CTN,
                    'CNTRY' => (string)$role->CNTRY,
                ];

                $rolesDicts[] = $rolesDict;
            }
        }

        return [$dataDicts, $rolesDicts];
    }

    // Add other methods as needed
}

// Example usage
if (isset($argv[1]) && isset($argv[2])) {
    $command = $argv[1];
    $downloadType = $argv[2];
    $client = new RefdataClient($downloadType);

    // Download data if not already present (with the current date)
    if (!$client->doesDataAlreadyExist()) {
        $client->downloadAll();
    } else {
        echo "Data with date {$client->file_date} already present in folder data/, skipping download." . PHP_EOL;
    }

    // Map XML to two lists of dicts
    list($partnersList, $rolesList) = $client->mapXmlToList();

    if ($command == 'update') {
        // Use ORM class (replace with your actual ORM implementation)
        // $mapper = new RefdataORM($dbConfig);
        // $mapper->deleteTables(true); // For good measure
        // $mapper->createOrReplaceTables(false, true);
        // $mapper->createOrReplaceTables(true, true);
        // $mapper->insertManyIntoTable('refdata_partner', $partnersList);
        // $mapper->insertManyIntoTable('refdata_partner_role', $rolesList);
        // $mapper->callProc('update_and_log_refdata');
        // $mapper->deleteTables(true);
    }
} else {
    echo "Usage: php refdata_client.php <command> <download type>" . PHP_EOL;
    exit(1);
}

?>
