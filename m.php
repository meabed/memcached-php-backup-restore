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
	// First test to see if $host is a comma seperated list
	if(mb_strpos($host,',')===FALSE) {
		// Just one server listed so just use that dummy
		// check if the string contains a colon to seperate the port number
		if(mb_strpos($host,':')===FALSE) {
			$host_live = trim($host);
			//set default port as none was defined
			$port_live = (isset($port) && !empty($port)) ? (int)$port : 11211;
		} else {
			$temp = explode(':', $host);
			$host_live = trim($temp[0]);
			$port = (isset($temp[1]) && !empty($temp[1])) ? trim($temp[1]) : '';
			// set default port value if it was empty in the defintion
			$port_live = !empty($port) ? (int)$port : 11211;
		}
		$this->memcached->addServer($host_live, $port_live);
	} else {
		// Multiple servers assumed in MEMCACHE_HOST because a comma was found
		$srvs = explode(',', $host);
		foreach($srvs as $key=>$svr) {
			// check if the string contains a colon to seperate the port number
			if(mb_strpos($svr,':')===FALSE) {
				$host_live = trim($svr);
				//set default port as none was defined
				$port_live = 11211;
			} else {
				$temp = explode(':', $svr);
				$host_live = trim($temp[0]);
				$port = (isset($temp[1]) && !empty($temp[1])) ? trim($temp[1]) : '';
				// set default port value if it was empty in the defintion
				$port_live = !empty($port) ? (int)$port : 11211;
			}
			$this->memcached->addServer($host_live, $port_live);
		}
	}
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
$allowedArgs = array('-h' => 'host', '-p' => 'port', '-op' => 'action', '-f' => 'file');
foreach ($allowedArgs as $key => $val) {
    $id = array_search($key, $argv);
    if ($id) {
        ${$val} = isset($argv[$id + 1]) ? $argv[$id + 1] : false;
    }

}
$obj = new memcachedTools($host, $port);

// get the filename value (if pressent) and allocate to $obj->filename
if(isset($filename) && trim($filename)!='') {
    $obj->filename = trim($filename);
}

switch ($action) {
    case 'backup':
        $obj->getAllKeys();
        $obj->writeKeysToFile();
        echo "Memcached Data has been saved to file :" . $obj->filename;
        break;
    case 'restore':
        $retval = $obj->writeKeysToMemcached();
        if(!$retval) {
            echo "Memcached Data could not be restored: " . $obj->filename . " Not Found\r\n";
        } else {
            echo "Memcached Data has been restore from file: " . $obj->filename . "\r\n";
        }
        break;
    default:
        echo <<<EOF
Example Usage : php m.php -h 127.0.0.1 -p 112112 -op restore
-h : Memcache Host address ( default is 127.0.0.1 )
-p : Memcache Port ( default is 11211 )
-op : Operation is required !! ( available options is : restore , backup )
-f : File name (default is memcacheData.txt)

NB: The -h address can now contain multiple memcache servers in a 'pool' configuration
    these can be listed in an comma seperated list and each my optionally have a port
    number associated with it by seperating with a colon thus: 
        192.168.1.100:11211,192.168.1.101,192.168.1.100:11212
    In the above example the are two physical machines but 192.168.1.100 is running two
    instances of memcached. 
    The servers MUST be listed here in the same order that is used to write to the pool
    elsewhere in order for the keys to be correctly retrieved.

EOF;
        break;

}
exit;

