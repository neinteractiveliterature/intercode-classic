CREATE FUNCTION room_names(RunId INT(11))
  RETURNS TEXT
BEGIN
  DECLARE RoomNames TEXT;
  SELECT GROUP_CONCAT(RoomName ORDER BY RoomName SEPARATOR ',') INTO RoomNames
    FROM RunsRooms INNER JOIN Rooms ON RunsRooms.RoomId = Rooms.RoomId
    WHERE RunsRooms.RunId = RunId;
  RETURN RoomNames;
END
//
