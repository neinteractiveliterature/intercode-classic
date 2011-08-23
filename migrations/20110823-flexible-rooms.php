<?

set_include_path("../src:/usr/lib/php");
include('intercon_db.inc');
intercon_db_connect();

function do_query($query) {
  printf("$query\n");

  $result = mysql_query($query);
  if (!$result) {
    die("FATAL: " . mysql_error() . "\n");
  }
  
  return $result;
}

// Swiped from MySQL manual comments
$coldesc = mysql_fetch_object(do_query("SHOW COLUMNS FROM `Runs` LIKE 'Rooms'"));
$set  = substr($coldesc->Type,5,strlen($coldesc->Type)-7); // Remove "set(" at start and ");" at end
$rooms = preg_split("/','/",$set); // Split into an array

do_query("
CREATE TABLE `Rooms` (
  `RoomId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `RoomName` char(40) DEFAULT '',
  PRIMARY KEY (`RoomId`)
);");

do_query("
CREATE TABLE `RunsRooms` (
  `RoomId` int(10) unsigned NOT NULL,
  `RunId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`RunId`, `RoomId`),
  KEY (`RoomId`)
);
");

foreach ($rooms as $room) {
  $sql_room = mysql_real_escape_string($room);
  do_query("INSERT INTO Rooms (RoomName) VALUES ('$sql_room');");
  $room_id = mysql_insert_id();
  do_query("INSERT INTO RunsRooms (RoomId, RunId) SELECT $room_id, RunId FROM Runs WHERE FIND_IN_SET('$sql_room', Rooms) > 0;");
}

do_query("ALTER TABLE Runs 
  DROP COLUMN Rooms, 
  MODIFY COLUMN `Day` char(32) NOT NULL DEFAULT '',
  MODIFY COLUMN `StartHour` char(10) NOT NULL DEFAULT '0'
");

?>