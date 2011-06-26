ALTER TABLE `Bids`
    ADD COLUMN `GameType` varchar(30) NOT NULL DEFAULT '',
    ADD COLUMN `Fee` enum('Y','N') DEFAULT NULL;

ALTER TABLE `Events`
    ADD COLUMN `GameType` varchar(30) NOT NULL DEFAULT '',
    ADD COLUMN `Fee` varchar(30) DEFAULT '';