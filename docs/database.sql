-- Dumping structure for table rarbg.items
CREATE TABLE IF NOT EXISTS `items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `category` int(10) unsigned NOT NULL,
    `guid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `guid` (`guid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
