<?php
require_once(__DIR__.'/vendor/autoload.php');

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

$log = new Logger('debug');
$formatter = new LineFormatter('[%datetime%] %level_name%: %message%'.PHP_EOL, 'Y-m-d H:i:s', true);
$stream = new StreamHandler(__DIR__.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'trackers.log', Logger::DEBUG);
$stream->setFormatter($formatter);
$log->pushHandler($stream);

const TRACKER_URL = 'https://raw.githubusercontent.com/ngosang/trackerslist/master/trackers_all_http.txt';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$trackerFile = fopen($_ENV['TRACKER_FILE'],'wb');
if (!$trackerFile) {
    $log->error('Can\'t open tracker file '.$_ENV['TRACKER_FILE']);

    exit;
}

$ch = curl_init();

curl_setopt($ch, CURLOPT_FILE, $trackerFile);
curl_setopt($ch, CURLOPT_URL, TRACKER_URL);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
if ($_ENV['TRACKER_PROXY']) {
    curl_setopt($ch, CURLOPT_PROXY, $_ENV['TRACKER_PROXY']);
}

curl_exec($ch);

if (curl_errno($ch)) {
    $log->error('Failed to download trackers: '.curl_errno($ch).', '.curl_error($ch));
}
else {
    $log->debug('Updated trackers');
}

curl_close($ch);
fclose($trackerFile);