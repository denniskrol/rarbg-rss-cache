<?php
require_once(__DIR__.'/vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($_GET['categories'])) {
    return;
}

$mysql = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_DATABASE']);

$xml = new SimpleXMLElement('<rss xmlns:atom="http://www.w3.org/2005/Atom" version="2.0"/>');
$channel = $xml->addChild('channel');
$channel->addChild('title', 'RARBG');
$channel->addChild('lastBuildDate', date('c'));

$categories = explode(',', $_GET['categories']);
$placeholders = implode(',', array_fill(0, count($categories), '?'));
$bindStr = str_repeat('i', count($categories));

$stmt = $mysql->prepare('SELECT `title`, `guid`, `created_at` FROM `items` WHERE `category` IN ('.$placeholders.') ORDER BY `created_at` DESC Limit 100');
$stmt->bind_param($bindStr, ...$categories);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $item = $channel->addChild('item');
    $item->addChild('title', $row['title']);
    $item->addChild('link', createLink($row));
    $item->addChild('guid', $row['guid']);
    $item->addChild('pubDate', date('r', strtotime($row['created_at'])));
}
$stmt->close();

Header('Content-type: text/xml');
print($xml->asXML());


function createLink($item) {
    $trackers = getTrackers();

    $link = 'magnet:?xt=urn:btih:';
    $link .= $item['guid'];
    $link .= '&dn='.urlencode($item['title']);
    foreach ($trackers as $tracker) {
        $link .= '&tr='.urlencode($tracker);
    }

    return htmlspecialchars($link);
}

function getTrackers() {
    $trackers = [];
    $trackersFile = file_get_contents($_ENV['TRACKER_FILE']);

    $lines = preg_split('/\r\n|\r|\n/', $trackersFile);
    foreach ($lines as $line) {
        if ($line) {
            $trackers[] = trim($line);
        }
    }

    return $trackers;
}