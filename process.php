<?php
require_once(__DIR__.'/vendor/autoload.php');

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

$log = new Logger('debug');
$formatter = new LineFormatter('[%datetime%] %level_name%: %message%'.PHP_EOL, 'Y-m-d H:i:s', true);
$stream = new StreamHandler(__DIR__.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'process.log', Logger::DEBUG);
$stream->setFormatter($formatter);
$log->pushHandler($stream);

const RARBG_BASE_URL = 'https://rarbg.to/rssdd_magnet.php?categories=';
const RARBG_CATEGORIES = [
    4 => 'XXX',
    17 => 'Movies/x264',
    17 => 'TV Episodes',
    23 => 'Music/MP3',
    25 => 'Music/FLAC',
    27 => 'Games/PC ISO',
    28 => 'Games/PC RIP',
    33 => 'Software/PC ISO',
    41 => 'TV HD Episodes',
    42 => 'Movies/Full BD',
    44 => 'Movies/x264/1080',
    45 => 'Movies/x264/720',
    46 => 'Movies/BD Remux',
    47 => 'Movies/x264/3D',
    49 => 'TV UHD Episodes',
    50 => 'Movies/x264/4k',
    51 => 'Movies/x265/4k',
    52 => 'Movs/x265/4k/HDR',
    53 => 'Games/PS4',
    54 => 'Movies/x265/1080',
];
// Used to add readable proxy name to console output
const PROXY_NAMES = [
    'http://127.0.0.1:3213' => 'Astrill',
    'http://127.0.0.1:7890' => 'Clash'
];

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$categoryIds = explode(',', $_ENV['CATEGORIES']);

$proxyServers = [
    null // Direct connection
];
$extraProxyServers = explode(',', $_ENV['CURL_PROXIES']);

foreach ($extraProxyServers as $proxyServer) {
    if (trim($proxyServer)) {
        $proxyServers[] = trim($proxyServer);
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_DATABASE']);

// Get RSS feeds
foreach ($categoryIds as $categoryId) {
    echo '['.date('Y-m-d H:i').'] Getting items for '.RARBG_CATEGORIES[$categoryId].'...';
    $url = RARBG_BASE_URL.$categoryId;
    $savedItems = 0;

    // Get RSS feed with each proxy server
    foreach ($proxyServers as $proxyServer) {
        $proxyServerName = PROXY_NAMES[$proxyServer] ?? 'direct';
        echo '['.$proxyServerName.'] ';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, $_ENV['CURL_USER_AGENT']);
        if ($proxyServer) {
            curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
        }

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if (curl_errno($ch)) {
            echo 'cURL error, 0 items, ';

            continue;
        }

        $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);

        echo count($xml->channel->item).' items, ';

        foreach ($xml->channel->item as $xmlItem) {
            $dateTime = date('Y-m-d H:i:s', strtotime($xmlItem->pubDate));
            $stmt = $mysql->prepare('INSERT IGNORE INTO `items` (`title`, `category`, `guid`, `created_at`) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('siss', $xmlItem->title, $categoryId, $xmlItem->guid, $dateTime);

            try {
                $stmt->execute();
            }
            catch (\Exception $exception) {
                echo $exception->getMessage().PHP_EOL;

                continue;
            }

            if ($mysql->affected_rows == 1) {
                $savedItems++;
            }

            $stmt->close();
        }
    }

    $log->debug('['.RARBG_CATEGORIES[$categoryId].'] Saved '.$savedItems.' items');
    echo 'saved '.$savedItems.' items'.PHP_EOL;
}