<?php

use Clue\React\Ssdp\Client;

require __DIR__ . '/../vendor/autoload.php';

$client = new Client();

$client->search()->then(
    function () {
        echo 'Search completed' . PHP_EOL;
    },
    function($e) {
        echo 'There was an error searching for devices: ' . $e . PHP_EOL;
    },
    function ($progress) {
        echo 'Found a device: ' . PHP_EOL;
        var_dump($progress);
        echo PHP_EOL;
    }
);
