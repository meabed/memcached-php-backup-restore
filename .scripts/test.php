<?php

class memcachedTools
{
    public $filename = 'memcacheData.txt';
    public $fileMode = FILE_APPEND;

    public $host = '127.0.0.1';
    public $port = '11211';

    public $memcached = null;

    public $list  = [];
    public $limit = 10000000000000;

    public function __construct($host = '127.0.0.1', $port = '11211')
    {
        $this->memcached = new Memcached();
        // First test to see if $host is a comma separated list
        if (mb_strpos($host, ',') === false) {
            // Just one server listed so just use that dummy
            // check if the string contains a colon to separate the port number
            if (mb_strpos($host, ':') === false) {
                $hostLive = trim($host);
                //set default port as none was defined
                $portLive = (isset($port) && !empty($port)) ? (int)$port : 11211;
            } else {
                $temp     = explode(':', $host);
                $hostLive = trim($temp[0]);
                $port     = (isset($temp[1]) && !empty($temp[1])) ? trim($temp[1]) : '';
                // set default port value if it was empty in the definition
                $portLive = !empty($port) ? (int)$port : 11211;
            }
            $this->memcached->addServer($hostLive, $portLive);
        } else {
            // Multiple servers assumed in MEMCACHE_HOST because a comma was found
            $servers = explode(',', $host);
            foreach ($servers as $key => $server) {
                // check if the string contains a colon to separate the port number
                if (mb_strpos($server, ':') === false) {
                    $hostLive = trim($server);
                    //set default port as none was defined
                    $portLive = 11211;
                } else {
                    $temp     = explode(':', $server);
                    $hostLive = trim($temp[0]);
                    $port     = (isset($temp[1]) && !empty($temp[1])) ? trim($temp[1]) : '';
                    // set default port value if it was empty in the defintion
                    $portLive = !empty($port) ? (int)$port : 11211;
                }
                $this->memcached->addServer($hostLive, $portLive);
            }
        }
    }

    function writeKeysToFile()
    {
        foreach ($this->list as $row) {
            $value = $this->memcached->get($row['key']);
            $time  = time();
            $data  = json_encode(
                [
                    'key' => $row['key'],
                    'age' => ($row['age'] - $time),
                    'val' => base64_encode($value),
                ]
            );
            file_put_contents($this->filename, $data . PHP_EOL, $this->fileMode);
        }
    }

    public function writeKeysToMemcached()
    {
        if (!is_file($this->filename)) {
            return false;
        }
        $data    = file_get_contents($this->filename);
        $allData = explode("\n", $data);
        foreach ($allData as $key) {
            $keyData = json_decode($key);
            if (!isset($keyData['key'])) {
                continue;
            }
            $this->memcached->set($keyData['key'], base64_decode($keyData['val']), 0, $keyData['age']);
        }

        return true;
    }

    public function getAllKeys()
    {
        $allSlabs = $this->memcached->getExtendedStats('slabs');

        foreach ($allSlabs as $server => $slabs) {
            foreach ($slabs as $slabId => $slabMeta) {
                if (!is_numeric($slabId)) {
                    continue;
                }

                $cacheDump = $this->memcached->getExtendedStats('cachedump', (int)$slabId, $this->limit);

                foreach ($cacheDump as $server => $entries) {
                    if (!$entries) {
                        continue;
                    }

                    foreach ($entries as $eName => $eData) {
                        $this->list[$eName] = [
                            'key'    => $eName,
                            'slabId' => $slabId,
                            'size'   => $eData[0],
                            'age'    => $eData[1],
                        ];
                    }
                }
            }
        }
        ksort($this->list);
    }
}

$instance   = new memcachedTools();
$oneDayTime = 60 * 60 * 24 * 1;
$instance->memcached->add('test_key', 'test_value', $oneDayTime);

var_dump($instance->memcached->get('test_key'));