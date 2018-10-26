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

sleep(2);

$slabs = $instance->getSlabs();
$keys  = $instance->getAllKeys();

echo "success count " . $success . "\n";
echo "not stored count " . $notStored . "\n";
echo "fail count " . $fail . "\n";

echo "slabs count " . count($slabs) . "\n";
echo "key count " . count($keys) . "\n";

assert(count($keys) == 200);
assert($success == 200);
assert($notStored == 0);
assert($fail == 0);

echo "SUCCESS";
