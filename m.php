<?php

class memcachedTools
{
    public $filename = 'memcacheData.txt';
    public $fileMode = FILE_APPEND;

    public $host = '127.0.0.1';
    public $port = '11211';

    public $memcached = null;

    public $list = array();
    public $limit = 10000000000000;

    public function __construct($host = '127.0.0.1', $port = '11211')
    {
        $this->memcached = new Memcache();
        $this->memcached->connect($host, $port);
    }

    function writeKeysToFile()
    {
        foreach ($this->list as $row) {
            $value = $this->memcached->get($row['key']);
            $time = time();
            $data = serialize(
                array(
                    'key' => $row['key'],
                    'age' => ($row['age'] - $time),
                    'val' => base64_encode($value)
                )
            );
            file_put_contents($this->filename, $data . PHP_EOL, $this->fileMode);
        }
    }

    public function writeKeysToMemcached()
    {
        $data = file_get_contents($this->filename);
        $allData = explode("\n", $data);
        foreach ($allData as $key) {
            $keyData = unserialize($key);
            if (!isset($keyData['key'])) {
                continue;
            }
            $this->memcached->set($keyData['key'], base64_decode($keyData['val']), 0, $keyData['age']);
        }
    }

    public function getAllKeys()
    {
        $allSlabs = $this->memcached->getExtendedStats('slabs');
        $items = $this->memcached->getExtendedStats('items');

        foreach ($allSlabs as $server => $slabs) {
            foreach ($slabs as $slabId => $slabMeta) {
                if (!is_numeric($slabId)) {
                    continue;
                }

                $cdump = $this->memcached->getExtendedStats('cachedump', (int)$slabId, $this->limit);

                foreach ($cdump as $server => $entries) {
                    if (!$entries) {
                        continue;
                    }

                    foreach ($entries as $eName => $eData) {
                        $this->list[$eName] = array(
                            'key'    => $eName,
                            'slabId' => $slabId,
                            'size'   => $eData[0],
                            'age'    => $eData[1]
                        );
                    }
                }
            }
        }
        ksort($this->list);
    }
}

$host = '127.0.0.1';
$port = '11211';
$allowedArgs = array('-h' => 'host', '-p' => 'port', '-op' => 'action');
foreach ($allowedArgs as $key => $val) {
    $id = array_search($key, $argv);
    if ($id) {
        ${$val} = isset($argv[$id + 1]) ? $argv[$id + 1] : false;
    }

}
$obj = new memcachedTools($host, $port);

switch ($action) {
    case 'backup':
        $obj->getAllKeys();
        $obj->writeKeysToFile();
        echo "Memcached Data has been saved to file :" . $obj->filename;
        break;
    case 'restore':
        $obj->writeKeysToMemcached();
        echo "Memcached Data has been restore from file: " . $obj->filename;

        break;
    default:
        echo <<<EOF
Example Usage : php m.php -h 127.0.0.1 -p 112112 -op restore
-h : Memcache Host address ( default is 127.0.0.1 )
-p : Memcache Port ( default is 11211 )
-p : Operation is required !! ( available options is : restore , backup )
EOF;
        break;

}
exit;

