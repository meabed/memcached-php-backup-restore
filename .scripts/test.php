<?php
srand(1);

define('INCLUDE_MODE', 1);

require_once __DIR__ . '/../m.php';

$instance = new memcachedTools();
// this number of seconds may not exceed 60*60*24*30 (number of seconds in 30 days);
$oneDayTime = time() + 300;
$keyPrefix  = 'tk_';

$success   = 0;
$notStored = 0;
$fail      = 0;

$originalBinContent = file_get_contents(__DIR__ . '/bin_file.png');
$instance->memcached->set('image_1', $originalBinContent, $oneDayTime);
$img = $instance->memcached->get('image_1');

for ($i = 0; $i < 200; $i++) {
    $k = md5($keyPrefix . $i . rand());
    $r = $instance->memcached->set($k, 'test_' . rand(), $oneDayTime);

    if ($r === true) {
        $success++;
    } elseif ($r === Memcached::RES_NOTSTORED) {
        $notStored++;
    } else {
        $fail++;
    }
}

sleep(6);

$slabs = $instance->getSlabs();
$keys  = $instance->getAllKeys();

echo "success count " . $success . "\n";
echo "not stored count " . $notStored . "\n";
echo "fail count " . $fail . "\n";

echo "slabs count " . count($slabs) . "\n";
echo "key count " . count($keys) . "\n";

assert(count($keys) == 201);
assert($success == 200);
assert($notStored == 0);
assert($fail == 0);

echo "SUCCESS Memcached functions\n";

echo "SUCCESS Binary image files\n";
assert(strlen($img) > 10);

$backupFilePath     = __DIR__ . '/memcachedTestDataFile' . date('U') . '.txt';
$instance->filename = $backupFilePath;
$instance->writeKeysToFile();

echo "backup file exist :" . is_file($backupFilePath) . "\n";

$totalLines = intval(exec("wc -l '$backupFilePath'"));
echo "line count in backup file :" . $totalLines . "\n";
assert(is_file($backupFilePath) == true);
assert($totalLines == 201);

echo "SUCCESS Memcached backup file\n";

$data  = file_get_contents($backupFilePath);
$lines = explode("\n", $data);
$json  = [];
foreach ($lines as $line) {
    if (substr($line, 0, 16) == '{"key":"image_1"') {
        $jsonArr = json_decode($line, true);
    }
}

$decodedContent = base64_decode($jsonArr['val']);

file_put_contents(__DIR__ . '/bin_file_memcache_data.png', $decodedContent);
echo "SUCCESS Memcached image restored from backup file\n";

assert($originalBinContent == $decodedContent);

echo "SUCCESS\n";
