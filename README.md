# RarBG RSS cache
For people like me, that are on a unreliable connection.

Stores RSS items in database and serves RSS feed for torrent client. Overwrites trackers with trackerlist from [ngosang/trackerslist](https://github.com/ngosang/trackerslist)

## Install
```shell
composer install
```

## Usage
* Copy `.env.example` to `.env` and edit values
* Import database scheme from `docs/database.sql`

### Category IDs
`CATEGORIES` in `.env` is used to declare which categories to get. Comma seperated list of category IDs, eg:
```shell
CATEGORIES=4,41,49,54
```

All categories:
```php
[
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
]
```

### Proxies
It's possible to use multiple proxies to try to update the feeds. RarBG doesn't seem to like datacenter IPs for category 4.

It will always try a direct connection first.

Proxies can be set in `.env` as a comma seperated, eg:
```shell
CURL_PROXIES='http://127.0.0.1:3213,http://127.0.0.1:7890'
```

### Files
`process.php` gets the items from the RSS feeds.

`trackers.php` gets ngosang/trackerslist trackerlist from GitHub, which is then used when serving the RSS feed.

`index.php` serves RSS feed. Specific categories can be selected with the `categories` querystring. eg: `index.php?categories=4,41`

### Cronjobs
Run `process.php` every 5 minutes (`*/5 * * * *`)

Run `trackers.php` once an hour (`0 * * * *`)

### Logging
Logs written to `logs` directory