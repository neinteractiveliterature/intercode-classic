<?
define (LIST_BY_GAME, 1);
define (LIST_BY_RUNID, 2);
define (LIST_BY_TIME, 3);

include ("intercon_db.inc");

// Connect to the database -- Really should require staff privilege

if (! intercon_db_connect ())
{
  display_mysql_error ('Failed to establish connection to the database');
  exit ();
}

// Standard header stuff

html_begin ();

// Everything in this file requires privileges to run

if (! user_has_priv (PRIV_SCHEDULING))
{
  display_access_error ();
  html_end ();
  exit ();
}

if (array_key_exists ('action', $_REQUEST))
  $action = $_REQUEST['action'];
else
  $action = LIST_GAMES;

switch ($action)
{
 case LIST_GAMES:
   list_games (LIST_BY_GAME);
   break;

 case LIST_GAMES_BY_TIME:
   list_games (LIST_BY_TIME);
   break;

 case ADD_RUN:
   add_run_form (false);
   break;

 case PROCESS_ADD_RUN:
   if (process_add_run ())
     list_games ();
   else
     add_run_form (false);
   break;

 case PROCESS_EDIT_RUN:
   if (process_add_run ())
     list_games ();
   else
     add_run_form (true);
   break;

 case EDIT_RUN:
   add_run_form (true);
   break;

 case LIST_CONVERT_ROOMS:
   convert_rooms();
   list_games (LIST_BY_GAME);
   break;

 case LIST_ADD_OPS:
   add_ops();
   break;
 
 case LIST_ADD_CONSUITE:
   add_consuite();
   break;

 default:
   echo "Unknown action code: $action\n";
}

html_end();

/*
 * list_games
 *
 * List the games by the list type
 */

function list_games ($type = 0)
{
  if (0 == $type)
    $type = $_SESSION['ListType'];
  else
  {
    $_SESSION['ListType'] = $type;
  }

  switch ($type)
  {
    case LIST_BY_GAME:
      return list_games_alphabetically ();
      break;

    case LIST_BY_RUNID:
      return list_games_by ($type);
      break;

    case LIST_BY_TIME:
      return list_games_by ($type);
      break;

    default:
      return display_error ("Invalid ListType: $type");
  }

  return false;
}

/*
 * list_games_alphabetically
 *
 * List the games in the database alphabetically by game title
 */

function list_games_alphabetically ()
{
  $sql = 'SELECT EventId, Title, Hours FROM Events';
  $sql .= ' WHERE SpecialEvent=0';
  $sql .= ' ORDER BY Title';

  $game_result = mysql_query ($sql);
  if (! $game_result)
    return display_error ('Cannot query game list: ' . mysql_error());

  if (0 == mysql_num_rows ($game_result))
    return display_error ('No games in database');

  echo "<B>\n";
  echo "Click on a game title to add a run.<BR>\n";
  echo "Click on a start time to edit or delete a run.<P>\n";
  echo "</B>\n";
  printf ("<A HREF=ListGames.php?action=%d>Order Chronologically</A><P>\n",
	  LIST_GAMES_BY_TIME);

  echo "<TABLE BORDER=1>\n";
  echo "  <TR>\n";
  echo "    <TH>Game Title</TH>\n";
  echo "    <TH>Hours</TH>\n";
  echo "    <TH>Day</TH>\n";
  echo "    <TH>Start Time</TH>\n";
  echo "    <TH>Run Suffix</TH>\n";
  echo "    <TH>Schedule Note</TH>\n";
  echo "    <TH>Room(s)</TH>\n";
  echo "    <TH>Track</TH>\n";
  echo "    <TH>Tracks Spanned</TH>\n";
  echo "  </TR>\n";

  while ($game_row = mysql_fetch_object ($game_result))
  {
    $sql = 'SELECT RunId, Track, Day, Span, TitleSuffix, ScheduleNote,';
    $sql .= ' StartHour, Rooms, Venue';
    $sql .= ' FROM Runs';
    $sql .= ' WHERE EventId=' . $game_row->EventId;
    $sql .= ' ORDER BY Day, StartHour';

    $runs_result = mysql_query ($sql);
    if (! $runs_result)
      return display_error ("Cannot query runs for Event $game_row->EventId: " . mysql_error());

    $rowspan = mysql_num_rows ($runs_result);
    if (0 == $rowspan)
      $rowspan = 1;

    echo "  <tr valign=\"top\">\n";
    printf ("    <td rowspan=\"%d\"><a href=\"ListGames.php?action=%d&EventId=%d\">%s</a></td>\n",
	    $rowspan,
	    ADD_RUN,
	    $game_row->EventId,
	    $game_row->Title);

    printf ("    <td rowspan=\"%d\" align=\"center\">%d</td>\n",
	    $rowspan,
	    $game_row->Hours);

    //    echo "<!-- NumRows: " . mysql_num_rows ($runs_result) . "-->\n";

    if (0 == mysql_num_rows ($runs_result))
    {
      echo "    <TD>&nbsp;</TD><TD>&nbsp;</TD><TD>&nbsp;</TD><TD>&nbsp;</TD><TD>&nbsp;</TD><TD>&nbsp;</TD><TD>&nbsp;</TD>\n";
      echo "  </TR>\n";
    }
    else
    {
      $run_row = mysql_fetch_object ($runs_result);

      echo "    <TD ALIGN=CENTER>$run_row->Day</TD>\n";

      $start_time = start_hour_to_24_hour ($run_row->StartHour);

      printf ("    <TD ALIGN=CENTER><A HREF=ListGames.php?action=%d&RunId=%d>%s</A></TD>\n",
	      EDIT_RUN,
	      $run_row->RunId,
	      $start_time);

      $suffix = $run_row->TitleSuffix;
      if ('' == $suffix)
	$suffix = '&nbsp;';
      echo "    <TD>$suffix</TD>\n";

      $note = $run_row->ScheduleNote;
      if ('' == $note)
	$note = '&nbsp;';
      echo "    <TD>$note</TD>\n";

      //      $venue = $run_row->Venue;
      //      if ('' == $venue)
      //	$venue = '&nbsp;';
      //      echo "    <TD>$venue</TD>\n";
      $rooms = pretty_rooms($run_row->Rooms);
      $bgcolor = '';
      if (('&nbsp;' == $rooms) && ('' != $run_row->Venue))
	$bgcolor = ' bgcolor="#FFCCCC"';
      echo "    <td $bgcolor>$rooms</td>\n";

      echo "    <TD ALIGN=CENTER>$run_row->Track</TD>\n";
      echo "    <TD ALIGN=CENTER>$run_row->Span</TD>\n";
      echo "  </TR>\n";

      while ($run_row = mysql_fetch_object ($runs_result))
      {
	echo "  <TR>\n";
        echo "    <TD ALIGN=CENTER>$run_row->Day</TD>\n";

	$start_time = start_hour_to_24_hour ($run_row->StartHour);

	printf ("    <TD ALIGN=CENTER><A HREF=ListGames.php?action=%d&RunId=%d>%s</A></TD>\n",
		EDIT_RUN,
		$run_row->RunId,
		$start_time);

        $suffix = $run_row->TitleSuffix;
        if ('' == $suffix)
	  $suffix = '&nbsp;';
        echo "    <TD>$suffix</TD>\n";

	$note = $run_row->ScheduleNote;
	if ('' == $note)
	  $note = '&nbsp;';
	echo "    <TD>$note</TD>\n";

	//	$venue = $run_row->Venue;
	//	if ('' == $venue)
	//	  $venue = '&nbsp;';
	//	echo "    <TD>$venue</TD>\n";

	$rooms = pretty_rooms($run_row->Rooms);
	echo "    <td>$rooms</td>\n";

        echo "    <TD ALIGN=CENTER>$run_row->Track</TD>\n";
	echo "    <TD ALIGN=CENTER>$run_row->Span</TD>\n";
        echo "  </TR>\n";
      }
    }
  }
  echo "</TABLE>\n";
}

/*
 * list_games_by
 *
 * List the games in the database ordered by RunId
 */

function list_games_by ($type)
{
  $sql = 'SELECT Runs.RunId, Runs.Track, Runs.TitleSuffix, Runs.Span,';
  $sql .= ' Runs.StartHour, Runs.Day, Runs.EventId, Runs.ScheduleNote,';
  $sql .= ' Events.Hours, Events.Title, Runs.Rooms';
  $sql .= ' FROM Events, Runs';
  $sql .= ' WHERE Events.EventId=Runs.EventId AND Events.SpecialEvent=0';

  switch ($type)
  {
    case LIST_BY_TIME:
      $sql .= ' ORDER BY Runs.Day, Runs.StartHour, Runs.Track';
      break;

    default:
      return display_error ("Invalid ListType: $type");
  }      

  $result = mysql_query ($sql);
  if (! $result)
    return display_error ('Cannot query game list: ' . mysql_error());

  if (0 == mysql_num_rows ($result))
    return display_error ('No games in database');

  echo "<B>\n";
  echo "Click on a game title to add a run.<BR>\n";
  echo "Click on a start time to edit or delete a run.<P>\n";
  echo "</B>\n";
  printf ("<A HREF=ListGames.php?action=%d>Order Alphabetically</A><P>\n",
	  LIST_GAMES);

  echo "<TABLE BORDER=1>\n";
  echo "  <TR>\n";
  echo "    <TH>Day</TH>\n";
  echo "    <TH>Start Time</TH>\n";
  echo "    <TH>Track</TH>\n";
  echo "    <TH>Game Title</TH>\n";
  echo "    <TH>Run Suffix</TH>\n";
  echo "    <TH>Schedule Note</TH>\n";
  echo "    <TH>Room(s)</TH>\n";
  echo "    <TH>Hours</TH>\n";
  echo "    <TH>Tracks Spanned</TH>\n";
  echo "  </TR>\n";

  while ($row = mysql_fetch_object ($result))
  {
    if (LIST_BY_TIME == $type)
    {
      if ((! empty ($day)) && ($day != $row->Day))
      {
	echo "  <TR>\n";
	echo "  <TD COLSPAN=9>&nbsp;</TD>\n";
	echo "  </TR>\n";
	echo "  <TR>\n";
	echo "    <TH>Day</TH>\n";
	echo "    <TH>Start Time</TH>\n";
	echo "    <TH>Track</TH>\n";
	echo "    <TH>Game Title</TH>\n";
	echo "    <TH>Run Suffix</TH>\n";
	echo "    <TH>Schedule Note</TH>\n";
	echo "    <TH>Room(s)</TH>\n";
	echo "    <TH>Hours</TH>\n";
	echo "    <TH>Tracks Spanned</TH>\n";
	echo "  </TR>\n";
      }
      $day = $row->Day;
    }

    $start_time = start_hour_to_24_hour ($row->StartHour);

    echo "  <TR VALIGN=TOP>\n";
    echo "    <TD ALIGN=CENTER>$row->Day</TD>\n";
    printf ("    <TD ALIGN=CENTER><A HREF=ListGames.php?action=%d&RunId=%d>%s</A></TD>\n",
	    EDIT_RUN,
	    $row->RunId,
	    $start_time);
    echo "    <TD ALIGN=CENTER>$row->Track</TD>\n";
    printf ("    <TD VALIGN=TOP><A HREF=ListGames.php?action=%d&EventId=%d>%s</A></TD>\n",
	    ADD_RUN,
	    $row->EventId,
	    $row->Title);

    $suffix = $row->TitleSuffix;
    if ('' == $suffix)
      $suffix = '&nbsp;';
    echo "    <TD>$suffix</TD>\n";

    $note = $row->ScheduleNote;
    if ('' == $note)
      $note = '&nbsp;';
    echo "    <TD>$note</TD>\n";

    //    $venue = $row->Venue;
    //    if ('' == $venue)
    //      $venue = '&nbsp;';
    //    echo "    <TD>$venue</TD>\n";

    $rooms = pretty_rooms($row->Rooms);
    echo "    <td>$rooms</td>\n";

    echo "    <TD VALIGN=TOP ALIGN=CENTER>$row->Hours</TD>\n";
    echo "    <TD VALIGN=TOP ALIGN=CENTER>$row->Span</TD>\n";
    echo "  </TR>\n";
  }
  echo "</TABLE>\n";
}

/*
 * add_run_form
 *
 * Display the form to add or edit a run
 */

function add_run_form ($update)
{
  // If we're updating the run, fill the $_POST array

  if (! $update)
  {
    $action = "Add";

    $EventId = $_REQUEST['EventId'];
    $_POST['Span'] = 1;
  }
  else
  {
    $action = "Edit";

    $RunId = $_REQUEST['RunId'];

    $sql = 'SELECT EventId, Track, Span, Day, TitleSuffix, ScheduleNote,';
    $sql .= ' StartHour, TitleSuffix, Rooms, Venue';
    $sql .= " FROM Runs WHERE RunId=$RunId";

    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ("Cannot query run data for RunId $RunId",
				  $sql);

    if (0 == mysql_num_rows ($result))
      return display_error ("Cannot find RunId $RunId in the database!");

    if (1 != mysql_num_rows ($result))
      return display_error ("RunId $RunId matched more than 1 row!");

    $row = mysql_fetch_array ($result, MYSQL_ASSOC);

    //    dump_array ('row', $row);

    foreach ($row as $k => $v)
      $_POST[$k] = $v;

    $EventId = $row['EventId'];
    $_POST['Rooms'] = explode(',', $row['Rooms']);
  }

  dump_array('_POST', $_POST);
  if (array_key_exists('Rooms', $_POST))
    if (is_array($_POST['Rooms']))
      dump_array('Rooms', $_POST['Rooms']);

  // Start by fetching the title

  $sql = "SELECT Title FROM Events WHERE EventId=$EventId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_error ("Cannot query game title for EventId $EventId: " . mysql_error ());

  if (0 == mysql_num_rows ($result))
    return display_error ("Cannot find EventId $EventId in the database!");

  if (1 != mysql_num_rows ($result))
    return display_error ("EventId $EventId matched more than 1 row!");

  $row = mysql_fetch_object ($result);
  $Title = $row->Title;

  // Display the form for the user

  echo "<H2>$action a run for <I>$row->Title</I></H2>";
  echo "<FORM METHOD=POST ACTION=ListGames.php>\n";
  form_add_sequence ();
  if ($update)
  {
    echo '<INPUT TYPE=HIDDEN NAME=action VALUE=' . PROCESS_EDIT_RUN . ">\n";
    echo "<INPUT TYPE=HIDDEN NAME=RunId Value=$RunId>\n";
    echo "<INPUT TYPE=HIDDEN NAME=Update VALUE=1>\n";
  }
  else
  {
    echo '<INPUT TYPE=HIDDEN NAME=action VALUE=' . PROCESS_ADD_RUN . ">\n";
    echo "<INPUT TYPE=HIDDEN NAME=EventId Value=$EventId>\n";
    echo "<INPUT TYPE=HIDDEN NAME=Update VALUE=0>\n";
  }
  echo "<TABLE BORDER=0>\n";

  form_text (3, 'Track');
  form_text (1, 'Tracks Spanned', 'Span');
  form_day ('Day');
  form_start_hour ('Start Hour', 'StartHour');
  form_text (32, 'Title Suffix', 'TitleSuffix');
  form_text (32, 'Schedule Note', 'ScheduleNote');

  echo "<tr>\n";
  echo "<td align=\"right\">Venue:</td>\n";
  printf("<td>%s</td>\n", $_POST['Venue']);
  echo "</tr>\n";

  //  form_text (64, 'Room(s)', 'Venue');

  form_con_rooms('Room(s)', 'Rooms');

  if ($update)
    form_submit2 ('Update Run', 'Delete Run', 'DeleteRun');
  else
    form_submit ('Add Run');

  echo "</TABLE>\n";
  echo "</FORM>\n";

  // If this is an update, warn the user about deletions if there are any
  // players signed up

  if ($update)
  {
    $sql = 'SELECT COUNT(*) AS Count';
    $sql .= ' FROM Signup';
    $sql .= " WHERE State<>'Withdrawn' AND RunId=$RunId";

    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ('Query for count of signed up players failed',
				  $sql);

    $row = mysql_fetch_object ($result);
    if (! $row)
      return display_error ('Failed to fetch count of signed up players');

    if (0 != $row->Count)
    {
      echo "<P><B>Warning:</B> There are $row->Count players signed up for\n";
      echo "this run of <I>$Title</I>.  If you delete this run, you should\n";
      echo "send them mail before deleting the run.  The site will not\n";
      echo "automatically send them cancellation notices.  You can get a\n";
      echo "list of EMail addresses for the signed up players\n";
      printf ("<A HREF=Schedule.php?action=%d&RunId=%d&EventId=%d&" .
	      "FirstTime=1 TARGET=_blank>here</A><P>\n",
	      SCHEDULE_SHOW_SIGNUPS,
	      $RunId,
	      $EventId);
    }
  }
  display_valid_start_times ();
}

function process_add_run ()
{
  // Check for a sequence error

  if (out_of_sequence ())
    return display_sequence_error (true);

  // Is this an update or an add?

  $Update = $_POST['Update'];

  if (! $Update)
  {
    $verb = 'INSERT';
    $action_failed = 'Insert into';

    $EventId = $_POST['EventId'];
  }
  else
  {
    $verb = 'UPDATE';
    $action_failed = 'Update of';

    $RunId = $_POST['RunId'];

    $sql = "SELECT EventId FROM Runs WHERE RunId=$RunId";
    $result = mysql_query ($sql);
    if (! $result)
      return display_error ("Cannot query run data for RunId $RunId: " . mysql_error ());

    if (0 == mysql_num_rows ($result))
      return display_error ("Cannot find RunId $RunId in the database!");

    if (1 != mysql_num_rows ($result))
      return display_error ("RunId $RunId matched more than 1 row!");

    $row = mysql_fetch_object ($result);

    $EventId = $row->EventId;
  }

  // Start by fetching the title

  $sql = "SELECT Title FROM Events WHERE EventId=$EventId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_error ("Cannot query game title for EventId $EventId: " . mysql_error ());

  if (0 == mysql_num_rows ($result))
    return display_error ("Cannot find EventId $EventId in the database!");

  if (1 != mysql_num_rows ($result))
    return display_error ("EventId $EventId matched more than 1 row!");

  $row = mysql_fetch_object ($result);
  $Title = $row->Title;

  // If DeleteRun is one of the Post parameters, this must be an update request
  // and the user has asked us to delete a run.

  // Note that we should check if any users have signed up for this game, and
  // ask the user if he's really sure.  If he is, then delete the entries from
  // the Signups list and possibly send the users a note.

  if (isset ($_POST['DeleteRun']))
  {
    // Remove any players from the confirmed or wait lists for this game

    $sql = 'UPDATE Signup SET PrevState=State,';
    $sql .= ' State="Withdrawn"';
    $sql .= " WHERE RunId=$RunId";

    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ('Attempt to withdraw players from run failed',
				  $sql);

    $withdrawn_players = mysql_affected_rows ();

    // Delete the run

    $sql = "DELETE FROM Runs WHERE RunId=$RunId";

    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ("Failed to delete run $RunId", $sql);

    echo "Deleted run for <I>$Title</I>.\n";
    if ($withdrawn_players > 0)
      echo "  $withdrawn_players players were withdrawn from the run.\n";
    echo "<P>\n";

    return true;
  }

  if (! validate_int ('Track', 1, MAX_TRACKS))
    return false;

  if (! validate_int ('Span', 1, MAX_TRACKS))
    return false;

  if (! validate_day_time ('StartHour', 'Day'))
    return false;

  $Rooms = '';
  if (array_key_exists('Rooms', $_POST))
    $Rooms = implode(',', $_POST['Rooms']);

  $sql = "$verb Runs SET EventId=$EventId";
  $sql .= build_sql_string ('Track');
  $sql .= build_sql_string ('Span');
  $sql .= build_sql_string ('Day');
  $sql .= build_sql_string ('StartHour');
  $sql .= build_sql_string ('TitleSuffix');
  $sql .= build_sql_string ('ScheduleNote');
  $sql .= build_sql_string ('Rooms', $Rooms);
  $sql .= build_sql_string ('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID]);

  if ($Update)
    $sql .= " WHERE RunId=$RunId";

  //  echo "Command: $sql\n";

  $result = mysql_query ($sql);
  if (! $result)
     return display_mysql_error ("$action_failed Runs table failed", $sql);

  if ($Update)
    echo "Updated run $RunId for <I>$Title</I>\n<P>\n";
  else
  {
    $RunId = mysql_insert_id ();
    echo "Inserted run $RunId for <I>$Title</I>\n<P>\n";
  }

  return $RunId;
}


function add_ops_for_day ($OpsEventId, $Day)
{
  // Get the range of hours that Ops is scheduled for

  $sql = 'SELECT StartHour FROM Runs';
  $sql .= " WHERE EventId=$OpsEventId AND Day='$Day'";
  $sql .= ' ORDER BY StartHour';

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Failed to get list of Ops runs for Friday: ',
				$sql);

  $min_ops_hour = 100;
  $max_ops_hour = 0;

  if ($row = mysql_fetch_object ($result))
    $min_ops_hour = $max_ops_hour = $row->StartHour;

  while ($row = mysql_fetch_object ($result))
    $max_ops_hour = $row->StartHour;
  
  // Get the range of hours that events that are *not* Ops are scheduled for
  // Don't count ConSuite runs either...

  $sql = 'SELECT Runs.StartHour, Events.Hours FROM Runs, Events';
  $sql .= ' WHERE Events.EventId=Runs.EventId';
  $sql .= "   AND Runs.EventId<>$OpsEventId";
  $sql .= "   AND Runs.Day='$Day'";
  $sql .= '   AND Events.IsConSuite="N"';
  $sql .= ' ORDER BY Runs.StartHour';
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Cannot query runs of Ops! ', $sql);

  if (0 == mysql_num_rows ($result))
  {
    echo "No events scheduled for $Day<p>\n";
    return true;
  }
  
  if ($row = mysql_fetch_object ($result))
  {
    $min_event_hour = $row->StartHour;
    $max_event_hour = $row->StartHour + $row->Hours;
  }

  while ($row = mysql_fetch_object ($result))
  {
    if ($max_event_hour < ($row->StartHour + $row->Hours))
      $max_event_hour = $row->StartHour + $row->Hours;
  }

  // OK, we assume that Ops is always scheduled for a contiguous run of
  // hours, covering all scheduled events.  Add any Ops runs to cover any
  // events not already covered.  Note that this will  NOT handle the case
  // where someone deletes a scheduled event that...  Don't do that.

  $count = 0;

  for ($hour = $min_event_hour; $hour < $max_event_hour; $hour++)
  {
    if (($hour < $min_ops_hour) || ($hour > $max_ops_hour))
    {
      $sql = "INSERT Runs SET EventId=$OpsEventId,";
      $sql .= 'Track=' . MAX_TRACKS . ',';
      $sql .= 'Span=1,';
      $sql .= "Day='$Day',";
      $sql .= "StartHour='$hour',";
      $sql .= 'TitleSuffix="",';
      $sql .= 'ScheduleNote="",';
      $sql .= 'Rooms="",';
      $sql .= 'UpdatedById="' . $_SESSION[SESSION_LOGIN_USER_ID] . '"';

      //      echo "Command: $sql<p>\n";
      
      $result = mysql_query ($sql);
      if (! $result)
	display_mysql_error ('Failed to insert Ops run: ', $sql);
      else
	$count++;
    }
  }

  echo "Added $count runs of Ops for $Day<p>\n";
}

function add_ops()
{
  // Display what we're proposing to do for the user

  echo "<h2>Add Runs for <i>Ops</i></h2>";

  // Check that we've got one (and only one) event marked as Ops!

  $sql = 'SELECT EventId, Title, Hours FROM Events';
  $sql .= ' WHERE IsOps="Y"';

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Cannot query for Ops! ', $sql);

  if (mysql_num_rows ($result) > 1)
  {
    display_error ("There are multiple ops entries!<br>\n<ul>\n");
    while ($row = mysql_fetch_object ($result))
    {
      echo "  <le>$row->Title\n";
    }
    echo "</ul>\n";
    return false;
  }

  if (0 == mysql_num_rows ($result))
    return display_error ('There are no events marked as Ops!');

  $row = mysql_fetch_object ($result);

  if (1 != $row->Hours)
    return display_error ("The scheduling size for Ops is $row->Hours " .
			  'instead of 1 as is expected');

  add_ops_for_day ($row->EventId, 'Fri');
  add_ops_for_day ($row->EventId, 'Sat');
  add_ops_for_day ($row->EventId, 'Sun');
}

/*
 * add_consuite_for_day
 *
 * Schedule ConSuite for the specified day
 */

function add_consuite_for_day ($ConSuiteEventId, $Day, $min_hour, $max_hour)
{
  $count = 0;

  // Add ConSuite runs.  Yeah, this could be done more efficiently.
  // Tough.  It's going to be run *very* infrequently

  // We assume that ConSuite is always scheduled for a contiguous run of
  // hours.  Add any ConSuite runs to cover any missing hours.

  for ($hour = $min_hour; $hour < $max_hour; $hour++)
  {
    // See if ConSuite is scheduled for this hour

    $sql = 'SELECT RunId FROM Runs';
    $sql .= " WHERE EventId=$ConSuiteEventId";
    $sql .= "   AND Day='$Day'";
    $sql .= "   AND StartHour=$hour";

    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ("Failed to get ConSuite run for $Day $hour: ",
				  $sql);
    if (0 != mysql_num_rows($result))
      continue;

    $sql = "INSERT Runs SET EventId=$ConSuiteEventId,";
    $sql .= sprintf ('Track=%d,', MAX_TRACKS-1);
    $sql .= 'Span=1,';
    $sql .= "Day='$Day',";
    $sql .= "StartHour='$hour',";
    $sql .= 'TitleSuffix="",';
    $sql .= 'ScheduleNote="",';
    $sql .= 'Rooms="",';
    $sql .= 'UpdatedById="' . $_SESSION[SESSION_LOGIN_USER_ID] . '"';

    //      echo "Command: $sql<p>\n";
      
    $result = mysql_query ($sql);
    if (! $result)
      display_mysql_error ('Failed to insert ConSuite run: ', $sql);
    else
      $count++;

    //    echo "Added ConSuite for $Day, $hour:00<br>\n";
  }

  echo "Added $count runs of ConSuite for $Day<p>\n";
}

function add_consuite()
{
  // Display what we're proposing to do for the user

  echo "<h2>Add Runs for <i>ConSuite</i></h2>";

  // Check that we've got one (and only one) event marked as Ops!

  $sql = 'SELECT EventId, Title, Hours FROM Events';
  $sql .= ' WHERE IsConSuite="Y"';

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Cannot query for ConSuite! ', $sql);

  if (mysql_num_rows ($result) > 1)
  {
    display_error ("There are multiple ConSuite entries!<br>\n<ul>\n");
    while ($row = mysql_fetch_object ($result))
    {
      echo "  <le>$row->Title\n";
    }
    echo "</ul>\n";
    return false;
  }

  $EventId = 0;

  if (0 == mysql_num_rows ($result))
  {
    // Fetch the user ID for the ConSuite mistress

    $names = explode (" ", NAME_CON_SUITE);
    $last_name = array_pop ($names);
    $first_name = implode ($names);

    $sql = 'SELECT UserId FROM Users';
    $sql .= " WHERE LastName='$last_name'";
    $sql .= "   AND FirstName='$first_name'";
    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ('Attempt to find ConSuite Mistress ' .
				  "$first_name $last_name failed:");
    $row = mysql_fetch_object ($result);
    $UserId = $row->UserId;

    $blurb = 'Help serve Intercon breakfast, lunch and dinner';

    $sql = 'INSERT Events SET ';
    $sql .= build_sql_string ('Title', 'ConSuite', false);
    $sql .= build_sql_string ('Author', NAME_CON_SUITE);
    $sql .= build_sql_string ('IsConSuite', 'Y');
    $sql .= build_sql_string ('MaxPlayersNeutral', '2');
    $sql .= build_sql_string ('Hours', '1');
    $sql .= build_sql_string ('Description', $blurb);
    $sql .= build_sql_string ('ShortBlurb', $blurb);
    $sql .= build_sql_string ('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID]);
    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ('Attempt to create ConSuite event failed!',
				  $sql);

    echo "Created ConSuite event.  Description: \"$blurb\"<p>";

    $EventId = mysql_insert_id();

    $sql = 'INSERT INTO GMs SET ';
    $sql .= build_sql_string ('UserId', $UserId, false);
    $sql .= build_sql_string ('EventId', $EventId);
    $sql .= build_sql_string ('Submitter', 'Y');
    $sql .= build_sql_string ('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID]);
    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ('Attempt to set ConSuite GM failed!', $sql);

    echo "Added $first_name $last_name as \"GM\" for ConSuite<p>\n";
  }
  else
  {
    $row = mysql_fetch_object ($result);

    if (1 != $row->Hours)
      return display_error ("The scheduling size for ConSuite is $row->Hours " .
			    'instead of 1 as is expected');
    $EventId = $row->EventId;
  }

  add_consuite_for_day ($EventId, 'Fri', FRI_MIN, 24);  // noon - midnight
  add_consuite_for_day ($EventId, 'Sat', 9, 25);        // 9AM - 1AM
  add_consuite_for_day ($EventId, 'Sun', 9, 15);        // 9AM - 3PM
}

/*
 * convert_rooms
 *
 * Convert the old "Venue" field to one or more entries in the Rooms set
 */

function convert_rooms()
{
  display_header('Convert Venue to Rooms');

  $sql = 'SELECT Events.Title, Runs.RunId, Runs.Venue, Runs.Rooms';
  $sql .= ' FROM Runs, Events';
  $sql .= ' WHERE Events.EventId=Runs.EventId';
  $sql .= '  AND Events.SpecialEvent=0';
  $sql .= ' ORDER BY Events.Title';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for runs failed', $sql);

  while ($row = mysql_fetch_object($result))
  {
    if ('' != $row->Rooms)
    {
      echo "Skipping - Already converted <i>$row->Title</i><br>\n";
      continue;
    }

    if ('' == $row->Venue)
    {
      echo "Skipping - empty venue <i>$row->Title</i><br>\n";
      continue;
    }

    if ('' != stristr($row->Venue, ','))
    {
      echo "Skipping - Complex venue <i>row->Title</i><br>\n";
      continue;
    }

    if ('' != stristr($row->Venue, ' and '))
    {
      echo "Skipping - Complex venue <i>row->Title</i><br>\n";
      continue;
    }

    if ('' != strstr($row->Venue, '&'))
    {
      echo "Skipping - Complex venue <i>row->Title</i><br>\n";
      continue;
    }

    $sql = "UPDATE Runs SET Rooms='$row->Venue' WHERE RunId=$row->RunId";
    $update_result = mysql_query($sql);
    if (! $update_result)
      display_mysql_error("Update failed for <i>$row->Title</i>", $sql);
    else
      echo "Updated <i>$row->Title</i> - $sql<br>\n";
  }
}

?>