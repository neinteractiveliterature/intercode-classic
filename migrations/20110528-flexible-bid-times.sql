CREATE TABLE `BidTimes` (
  `BidTimeId` int(10) unsigned NOT NULL auto_increment,
  `BidId` int(10) unsigned NOT NULL,
  `Day` enum('Monday','Tuesday','Wednesday','Thursday','Friday', 'Saturday', 'Sunday') NOT NULL,
  `Slot` enum('Morning', 'Lunch', 'Afternoon', 'Dinner', 'Evening', 'After Midnight' ) NOT NULL,
  `Pref` char(1) NOT NULL default '',
  PRIMARY KEY  (`BidTimeId`),
  KEY (`BidId`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `BidTimes`
(`BidId`, `Day`, `Slot`, `Pref`)
SELECT BidId, 'Friday', 'Afternoon', FriPM
FROM `Bids`
WHERE FriPM != '';

INSERT INTO `BidTimes`
(`BidId`, `Day`, `Slot`, `Pref`)
SELECT BidId, 'Friday', 'Evening', FriEve
FROM `Bids`
WHERE FriEve != '';

INSERT INTO `BidTimes`
(`BidId`, `Day`, `Slot`, `Pref`)
SELECT BidId, 'Friday', 'After Midnight', FriLate
FROM `Bids`
WHERE FriLate != '';

INSERT INTO `BidTimes`
(`BidId`, `Day`, `Slot`, `Pref`)
SELECT BidId, 'Saturday', 'Morning', SatAM
FROM `Bids`
WHERE SatAM != '';

INSERT INTO `BidTimes`
(`BidId`, `Day`, `Slot`, `Pref`)
SELECT BidId, 'Saturday', 'Afternoon', SatPM
FROM `Bids`
WHERE SatPM != '';

INSERT INTO `BidTimes`
(`BidId`, `Day`, `Slot`, `Pref`)
SELECT BidId, 'Saturday', 'Evening', SatEve
FROM `Bids`
WHERE SatEve != '';

INSERT INTO `BidTimes`
(`BidId`, `Day`, `Slot`, `Pref`)
SELECT BidId, 'Saturday', 'After Midnight', SatLate
FROM `Bids`
WHERE SatLate != '';

INSERT INTO `BidTimes`
(`BidId`, `Day`, `Slot`, `Pref`)
SELECT BidId, 'Sunday', 'Morning', SunAM
FROM `Bids`
WHERE SunAM != '';

ALTER TABLE `Bids`
    DROP COLUMN `FriPM`,
    DROP COLUMN `FriEve`,
    DROP COLUMN `FriLate`,
    DROP COLUMN `SatAM`,
    DROP COLUMN `SatPM`,
    DROP COLUMN `SatEve`,
    DROP COLUMN `SatLate`,
    DROP COLUMN `SunAM`;