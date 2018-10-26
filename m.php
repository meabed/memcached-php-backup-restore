<?php
srand(1);

class memcachedTools
{
    public $filename = __DIR__ . '/memcacheData.txt';
    public $fileMode = FILE_APPEND;

    public $host = '127.0.0.1';
    public $port = '11211';

    public $memcached = null;

    public $list  = [];
    public $limit = 10000000000000;


    // internals
    private $slabs;
    private $memcachedSock;
    private $mcEnd = ["END\r\n", "ERROR\r\n", "STORED\r\n", "NOT_STORED\r\n", "EXISTS\r\n", "NOT_FOUND\r\n", "DELETED\r\n"];
    private $lastEnd;


    public function __construct($host = '127.0.0.1:11211')
    {
        $this->memcached = new Memcached();
        // $this->memcached->setOption(Memcached::OPT_TCP_KEEPALIVE, 200);
        // $this->memcached->setOption(Memcached::OPT_TCP_NODELAY, true);
        // $this->memcached->setOption(Memcached::OPT_NO_BLOCK, true);

        $hostList = explode(',', $host);
        foreach ($hostList as $host) {
            list($host, $port,) = explode(':', $host);
            if (!$port) {
                $port = $this->port;
            }
            $this->memcached->addServer($host, $port);
        }
    }

    // open tcp connection to server
    // so we can run raw queries like stats cachedump, stats slabs etc...
    // commands cheat-sheet below
    // @link https://lzone.de/cheat-sheet/memcached
    private function initMemcachedSock()
    {
        if (!is_resource($this->memcachedSock)) {
            $this->memcachedSock = fsockopen($this->host, $this->port);
            if (!$this->memcachedSock) {
                die('Could not connect to memcached');
            }
        }
    }

    // old memcache package had support for slab stats and cache dump to extract all keys
    // last release is 2013 http://pecl.php.net/package-stats.php?pid=294&rid=&cid=3
    // http://pecl.php.net/package-changelog.php?package=memcache
    //
    // So we switch to using memcached package
    // http://pecl.php.net/package-changelog.php?package=memcached
    // to send commands to memcached via TCP / like telnet, nc etc...
    private function mcQuery($command)
    {
        $this->initMemcachedSock();
        $response = '';
        fwrite($this->memcachedSock, $command . "\r\n");
        while (!feof($this->memcachedSock)) {
            $chunk    = fread($this->memcachedSock, 8192);
            $response .= $chunk;
            $check    = substr($response, -14);
            foreach ($this->mcEnd as $end) {
                if (substr($check, -strlen($end)) == $end) {
                    $response      = substr($response, 0, -strlen($end));
                    $this->lastEnd = $end;
                    break 2;
                }
            }
        }

        return $response;
    }

    /**
     * @return bool
     */
    function writeKeysToFile()
    {
        $filePath = __DIR__ . '/' . $this->filename;

        // if file name starts with / it means its absolute path
        if (substr($this->filename, 0, 1) === "/") {
            $filePath = $this->filename;
        }
        if (!count($this->list)) {
            echo "No data found in Memcached\n";

            return true;
        }
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
            file_put_contents($filePath, $data . PHP_EOL, $this->fileMode);
        }
        echo "SUCCESS: Memcached Data has been saved to file :" . $filePath . "\n";

        return true;
    }

    /**
     * @return bool
     */
    public function writeKeysToMemcached()
    {
        $filePath = __DIR__ . '/' . $this->filename;

        // if file name starts with / it means its absolute path
        if (substr($this->filename, 0, 1) === "/") {
            $filePath = $this->filename;
        }

        if (!is_file($filePath)) {
            echo "FAIL: Memcached Data could not be restored: " . $filePath . " Not Found\r\n";

            return false;
        }

        $data    = file_get_contents($filePath);
        $allData = explode("\n", $data);
        foreach ($allData as $key) {
            $keyData = json_decode($key, true);
            if (!isset($keyData['key'])) {
                continue;
            }
            $this->memcached->set($keyData['key'], base64_decode($keyData['val']), $keyData['age']);
        }
        echo "SUCCESS: Memcached Data has been restored from file: " . $filePath . "\r\n";

        return true;
    }

    /**
     * get all keys in memcached
     *
     * @return array
     */
    public function getAllKeys()
    {
        $allSlabs = $this->getSlabs();

        foreach ($allSlabs as $id => $slab) {
            $slabId = $id;

            if (!is_numeric($slabId)) {
                continue;
            }

            $cacheDump = $this->mcQuery('stats cachedump ' . (int)$slabId . ' ' . $this->limit);

            $re = '/ITEM\s(\w+)\s\[.*([0-9]{10})\ss\]/m';
            preg_match_all($re, $cacheDump, $entries, PREG_SET_ORDER, 0);
            foreach ($entries as $eData) {
                $key = $eData[1];

                $this->list[$key] = [
                    'key'    => $key,
                    'slabId' => $slabId,
                    'age'    => $eData[2],
                ];
            }
        }

        return $this->list;
    }

    /**
     * get all memcached slaps
     *
     * @return array
     */
    public function getSlabs()
    {
        if (null !== $this->slabs) {
            return $this->slabs;
        }

        // echo "Getting slabs stats...\n";
        $this->slabs    = [];
        $slabParamNames = [];
        $res            = $this->mcQuery("stats slabs");

        foreach (explode("\r\n", $res) as $line) {
            $line = substr($line, 5);
            $data = explode(':', $line);
            if (count($data) == 2) {
                $slabId                              = $data[0];
                $slabParam                           = explode(' ', $data[1], 2);
                $this->slabs[$slabId]['id']          = $slabId;
                $this->slabs[$slabId][$slabParam[0]] = $slabParam[1];
                $slabParamNames[$slabParam[0]]       = $slabParam[0];
            }
        }

        return $this->slabs;
    }
}

/**
 * Run the main cli function
 *
 * @param $argv
 */
function runCli($argv)
{
    $host             = '127.0.0.1';
    $port             = '11211';
    $action           = null; // defining this variable for readability only
    $allowedArguments = ['-h' => 'host', '-p' => 'port', '-op' => 'action', '-f' => 'file'];
    $argValues        = [];
    foreach ($allowedArguments as $key => $value) {
        $id = array_search($key, $argv);
        if ($id) {
            // one of the params host,port,action,file
            ${$value} = isset($argv[$id + 1]) ? $argv[$id + 1] : false;
        }
    }
    // remove empty or nulls
    $obj = new memcachedTools($host . ':' . $port);

// get the filename value (if present) and allocate to $obj->filename
    if (isset($file) && trim($file) != '') {
        $obj->filename = trim($file);
    }

    switch ($action) {
        case 'backup':
            $obj->getAllKeys();
            $obj->writeKeysToFile();
            break;
        case 'restore':
            $obj->writeKeysToMemcached();
            break;
        default:
            echo <<<EOF
Example Usage:
php m.php -h 127.0.0.1 -p 11211 -op backup
php m.php -h 127.0.0.1 -p 11211 -op restore
-h : Memcache Host address ( default is 127.0.0.1 )
-p : Memcache Port ( default is 11211 )
-op : Operation is required ( available options is: restore, backup )
-f : File path (default is __DIR__.'/memcacheData.txt') relative path, or absolute path

NB: The -h address can now contain multiple memcache servers in a 'pool' configuration
    these can be listed in an comma seperated list and each my optionally have a port
    number associated with it by seperating with a colon thus: 
        192.168.1.100:11211,192.168.1.101,192.168.1.100:11211
    In the above example the are two physical machines but 192.168.1.100 is running two
    instances of memcached. 
    The servers MUST be listed here in the same order that is used to write to the pool
    elsewhere in order for the keys to be correctly retrieved.

EOF;
            break;

    }
}

// check if the value is defined means its included or require by another file, so don't execute the cli function
if (!defined('INCLUDE_MODE')) {
    runCli($argv);
}
