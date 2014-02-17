<?
include ("intercon_db.inc");

// If the user's not logged in, send him to the entrypoint

if (! array_key_exists (SESSION_LOGIN_USER_ID, $_SESSION))
{
  header ('Location: index.php');
  exit ();
}

// Connect to the database

if (! intercon_db_connect ())
{
  display_mysql_error ('Failed to connect to ' . DB_NAME);
  exit ();
}

echo "<!-- Begin Standard Prefix -->\n";
echo "<html>\n";
echo "<head>\n";
printf ("<title>%s</title>\n", CON_NAME);
echo "<meta http-equiv=\"charset\" content=\"iso-8859-1\">\n";
// Verification for Google apps for intercon-m.org
echo "<meta name=\"google-site-verification\" content=\"9yWQZ9402aK1LXPwNS5VqaZFV0-O7IGI_ZTMi1Fws3M\" />\n";
//  echo "<link href='screen.css.php' rel='stylesheet' type='text/css' media='screen'>\n";
//  echo "<link href='print.css' rel='stylesheet' type='text/css' media='print'>\n";
echo "<style>\n";
//  echo "  body {font-family: Arial, Helvetica, sans-serif}\n";
//  echo "  li, td {font-family: Arial, Helvetica, sans-serif}\n";
echo "  hr { color: #9a8069; width=300 text-align=left}\n";
echo "  .reserve { background-color: #006600; color: #ffffff }\n";
echo "  .reserveBody { background-color: #FFFFFF; color: #000000 }\n";
echo "  .reserveReverse { color: #006600; background-color: #ffffff }\n";
echo "  .shirt { background-color: #cccccc; color: #503984 }\n";
echo "  .shirtBody { background-color: #FFFFFF; color: #000000 }\n";
echo "  .shirtReverse { color: #503984; background-color: #cccccc }\n";
echo "</style>\n";
echo "</head>\n";
echo "<body>\n";
report_suites();
echo"</body>\n</html>\n";

function get_gms($event_id)
{
  echo "<!-- event_id: $event_id -->\n";

  $sql =  'SELECT Users.FirstName, Users.LastName';
  $sql .= '  FROM GMs, Users';
  $sql .= " WHERE GMs.EventId=$event_id";
  $sql .= '   AND Users.UserId=GMs.UserId';
  $sql .= ' ORDER BY Users.LastName, Users.FirstName';

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Query for GMs failed', $sql);

  $gms = '';

  while ($row = mysql_fetch_object($result))
  {
    if ('' != $gms)
      $gms .= ",<br />\n";
    $gms .= "$row->FirstName $row->LastName";
  }

  if ('' == $gms)
    $gms = '&nbsp;';

  return $gms;
}

function print_game_info(&$title, $hours, $room_count, $event_id, $first_room)
{
  echo "<!-- title: '$title', first_room: $first_room -->\n";
  echo "    <td rowspan='$hours' colspan='$room_count'><i><b>$title</b></i>\n";
  echo "    <br /><br />\n";
  $gms = get_gms($event_id);
  echo "    $gms\n";
  echo "    </td>\n";

  $title = '';
}

function print_run_info($run_id, $hours)
{
  $sql  = 'SELECT Runs.TitleSuffix, Runs.EventId, Events.Title';
  $sql .= '  FROM Events,Runs';
  $sql .= " WHERE Runs.RunId=$run_id";
  $sql .= '   AND Events.EventId=Runs.EventId';

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Query for RunId $run_id failed", $sql);

  if (1 != mysql_num_rows($result))
    return display_error("Number of rows for RunId $run_id is not 1");

  $row = mysql_fetch_object($result);

  $room_count = 1;
  echo "    <td rowspan='$hours' colspan='$room_count'><i><b>$row->Title</b></i><br />\n";
  if ('' != $row->TitleSuffix)
  {
    echo "$row->TitleSuffix<br />\n";
  }
  echo "    <br />\n";
  $gms = get_gms($row->EventId);
  echo "    $gms\n";
  echo "    </td>\n";
}

function day_min_and_max($day, &$day_min, &$day_max)
{
  $sql  = 'SELECT Runs.StartHour, Events.Hours';
  $sql .= '  FROM Runs, Events, RunsRooms, Rooms';
  $sql .= ' WHERE Rooms.RoomName LIKE "Suite%"';
  $sql .= '   AND RunsRooms.RoomId=Rooms.RoomId';
  $sql .= '   AND Runs.RunId=RunsRooms.RunId';
  $sql .= '   AND Events.EventId=Runs.EventId';
  $sql .= "   AND Runs.Day= '$day'";
  $sql .= ' ORDER BY CAST(Runs.StartHour AS SIGNED)';

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Query for times failed', $sql);

  $day_min = -1;
  $day_max = -1;

  while ($row = mysql_fetch_object($result))
  {
    $m = $row->StartHour + $row->Hours;

    if (-1 == $day_min)
    {
      $day_min = $row->StartHour;
      $day_max = $m;
    }
    else
    {
      if ($m > $day_max)
	$day_max = $m;
    }
  }

  return true;
}

function suite_report_for($day, $rooms)
{
  // Get day min and max
  if (! day_min_and_max($day, $day_min, $day_max))
    return;

  //  echo "<p>day_min; $day_min</p>\n";
  //  echo "<p>day_max; $day_max</p>\n";

  // Build a 2d array indexed by room and hour
  $a = array();
  foreach ($rooms as $r)
  {
    $hours = array();
    for ($h = $day_min; $h < $day_max; $h++)
    {
      $hours[$h] = 0;
    }
    $a[$r] = $hours;
  }

  // Fill the array with RunIds for hours the rooms are in use
  $sql  = 'SELECT Runs.RunId, Runs.StartHour, Events.Hours,';
  $sql .= '       Rooms.RoomName';
  $sql .= '  FROM Events,Runs,RunsRooms,Rooms';
  $sql .= ' WHERE Rooms.RoomName LIKE "Suite%"';
  $sql .= '   AND RunsRooms.RoomId=Rooms.RoomId';
  $sql .= '   AND Runs.RunId=RunsRooms.RunId';
  $sql .= '   AND Events.EventId=Runs.EventId';
  $sql .= "   AND Day='$day'";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Query for RunIds failed', $sql);

  while ($row = mysql_fetch_object($result))
  {
    for ($h = $row->StartHour; $h < $row->StartHour + $row->Hours; $h++)
      $a[$row->RoomName][$h] = $row->RunId;
  }

  // Display the table of suites
  echo "<table border='1'>\n";
  echo "  <tr valign='top'>\n";
  echo "    <td>&nbsp;</td>\n";
  foreach ($rooms as $r)
  {
    echo "    <td>$r</td>\n";
  }
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <td>Room&nbsp;#</td>\n";
  foreach ($rooms as $r)
  {
    echo "    <td>&nbsp;</td>\n";
  }
  echo "  </tr>\n";

  for ($h = $day_min; $h < $day_max; $h++)
  {
    echo "  <tr>\n";
    echo "    <td>$h:00</td>\n";
    for ($ri = 0; $ri < count($rooms); $ri++)
    {
      $r = $rooms[$ri];
      $run_id = $a[$r][$h];
 
      echo "<!-- a[$r][$h] - run_id: $run_id -->\n";
      // Skip -1 - it signals the timeslot is already displayed
      if (-1 == $run_id)
	echo "<!-- Skip - already filled -->\n";
      else
      {
	// 0 signals that the timeslot is unused
	if (0 == $run_id)
	{
	  echo "    <td>Unused</td>\n";
	}
	else
	{
	  $hours = 1;
	  while ($h + $hours - 1 < $day_max)
	  {
	    if ($a[$r][$h + $hours - 1] == $run_id)
	    {
	      echo "<!-- Zap a[$r][$h + $hours - 1] -->\n";
	      $a[$r][$h + $hours - 1] = -1;
	      $hours++;
	    }
	    else
	      break;
	  }
	  print_run_info($run_id, $hours-1);
	}
      }
    }
    echo "  </tr>\n";
  }

  echo "</table>\n";
}


function report_suites()
{
  $sql = 'SELECT RoomName FROM Rooms';
  $sql .=' WHERE RoomName LIKE "Suite%" ORDER BY RoomName';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for suite names failed', $sql);

  $rooms = array();
  while ($row = mysql_fetch_object($result))
  {
    $rooms[] = $row->RoomName;
  }

  echo "<h1>Suite Report for Friday, 1-Mar-2013</h1>\n";
  suite_report_for('Fri', $rooms);
  echo "<h1 style=\"page-break-before: always\">Suite Report for Saturday, 2-Mar-2013</h1>\n";
  suite_report_for('Sat', $rooms);
  echo "<h1 style=\"page-break-before: always\">Suite Report for Sunday, 3-Mar-2013</h1>\n";
  suite_report_for('Sun', $rooms);
}

 ?>
