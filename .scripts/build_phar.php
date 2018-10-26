<?php
// create with alias "project.phar"
$phar = new Phar('m.phar', 0, 'm.phar');
// add all files in the project
// $phar->buildFromDirectory(__DIR__ . '/../');
$phar->buildFromIterator(
    new ArrayIterator(
        [
            'm.php' => __DIR__ . '/../m.php',
        ]));

$phar->setStub($phar->createDefaultStub('m.php', 'www/index.php'));
