<?php

include ("intercon_db.inc");
include ("intercon_schedule.inc");
include ("pcsg.inc");

// Connect to the database

if (! intercon_db_connect ())
{
  display_mysql_error ('Failed to establish connection to the database');
  exit ();
}

// Standard header stuff

html_begin ();

$action = request_int('action', THURSDAY_THING);

switch ($action)
{
  case THURSDAY_THING:
    thursday_thing();
    break;

  case THURSDAY_REPORT:
    thursday_report();
    break;

  case THURSDAY_SELECT_USER:
    thursday_select_user();
    break;

  case THURSDAY_EDIT_USER:
    thursday_user_form();
    break;

  case THURSDAY_PROCESS_USER:
    if (! thursday_process_user_form())
      thursday_user_form();
    else
      thursday_select_user();
    break;

  case PRECON_SHOW_EVENT_FORM:
    display_precon_event_form();
    break;

 case PRECON_PROCESS_EVENT_FORM:
   if (! process_precon_event_form())
     display_precon_event_form();
   else
     if (user_has_priv(PRIV_PRECON_BID_CHAIR))
       display_event_summary();
     else
       display_bid_thank_you();
   break;

  case PRECON_MANAGE_EVENTS:
    display_event_summary();
    break;

  case PRECON_PROCESS_STATUS_FORM:
    if (process_status_form())
      display_event_summary();
    else
      show_status_form();
    break;

  case PRECON_SHOW_STATUS_FORM:
    show_status_form();
    break;

  case PRECON_SHOW_EVENT:
    show_precon_event();
    break;

  case PRECON_SHOW_RUN_FORM:
    show_run_form();
    break;

  case PRECON_PROCESS_RUN_FORM:
    if (process_run_form())
      display_event_summary();
    else
      show_run_form();
    break;

  default:
    echo "Unknown action code: $action\n";
}

html_end();

/*
 * list_accepted_events
 *
 * List the accepted Pre-Convention events
 */

function list_accepted_events()
{
  $sql = 'SELECT events.PreConEventId, PreConRunId, Title, Rooms, Day, StartHour, Hours FROM PreConRuns runs';
  $sql .= ' INNER JOIN PreConEvents events ON events.PreConEventId = runs.PreConEventId';
  $sql .= ' WHERE "Accepted"=Status';
  $sql .= ' ORDER BY Day, StartHour, Hours';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for PreCon events failed', $sql);

  $thursday = new ScheduleBlock(20, 24);
  $friday = new ScheduleBlock(9, 18);
  $runs = array();

  while ($row = mysql_fetch_object($result))
  {
    $pcsgRun = new EventRun($row->StartHour, $row->Hours, $row->PreConRunId);
    if ($row->Day == "Thu") {
      $thursday->addEventRun($pcsgRun);
    } else {
      $friday->addEventRun($pcsgRun);
    }

    $runs[$row->PreConRunId] = $row;
    echo "<p>\n";
  }

  mysql_free_result($result);

  if (0 == sizeof($runs))
    return;

  $thursday->computeRunDimensions();
  $friday->computeRunDimensions();

  echo "<h3>Schedule of Events</h3>";

  echo "<h4>Thursday, " . THR_DATE . "</h4>\n";
  precon_schedule_day($thursday, $runs);

  echo "<h4>Friday, " . FRI_DATE . "</h4>\n";
  precon_schedule_day($friday, $runs);
}

function precon_schedule_day($block, $runs) {
  // calculate the minimum schedule width in pixels
  $time_width = 70;
  $events_width = $block->maxColumns * 90;
  $full_width = $events_width + $time_width;

  $events_width .= "px";
  $full_width .= "px";
  $time_width .= "px";

  $full_height = ($block->getHours() * 9) . "em";

  // main wrapper for the whole schedule
  echo "<div style=\"position: relative; border: 1px black solid; min-width: $full_width;\">";

  // left column: times
  echo "<div style=\"position: relative; width: $time_width; float: left;\">";
  echo "<div style=\"width: 100%; height: 30px;\">";
  write_centering_table("<b>Time</b>");
  echo "</div>";

  echo "<div style=\"position: relative; width: 100%; height: $full_height;\">";
  for ($hour = $block->startHour; $hour < $block->endHour; $hour++) {
	echo "<div style=\"position: absolute; ";
	echo "width: 100%; left: 0%; ";
	echo "top: " . ((($hour - $block->startHour) / $block->getHours()) * 100.0) . "%; ";
	echo "height: " . (100.0 / $block->getHours()) . "%;";
	echo "\">";

	write_24_hour($hour);

	echo "</div>";
  }
  echo "</div></div>";

  // main column: events and volunteer track
  echo "<div style=\"position: relative; margin-left: $time_width; ";
  // ie6 and 7 hacks to give this div hasLayout=true
  echo "_height: 0; min-height: 0;";
  echo "\">";
  echo "<div style=\"height: 30px;\">";
  write_centering_table("<b>Events</b>");
  echo "</div>";

  display_precon_runs_in_div($block, $runs,
							   "height: $full_height;",
							   $hour);

  echo "</div></div>";
}

function display_precon_runs_in_div($block, $runs, $css) {

  $runDimensions = $block->getRunDimensions();

  echo "<div style=\"$css\">";
  echo "<div style=\"position: relative; height: 100%; width: 100%;\">";

  foreach ($runDimensions as $dimensions) {
	$runId = $dimensions->run->id;
	$row = $runs[$runId];

    display_precon_event ($row, $dimensions);
  }

  echo "</div></div>";
}

function display_precon_event($row, $dimensions) {
  $text = sprintf ("<a href=\"Thursday.php?action=%d&PreConEventId=%d\">%s</a>\n"
                   ."<p>%s</p>",
	   PRECON_SHOW_EVENT,
	   $row->PreConEventId,
	   $row->Title,
       pretty_rooms($row->Rooms));

  echo "<div style=\"".$dimensions->getCSS()."\">";
  write_centering_table($text);
  echo "</div>\n";
}

/*
 * thursday_thing
 *
 * Display information about the Pre-Convention Events
 */

function thursday_thing()
{
  printf ("<h2>%s Panels and Other Events</h2>\n", CON_NAME);

  printf("<p>%s, as in previous years, will be starting with\n", CON_NAME);
  echo "a day of panels, discussions, interactive workshops and\n";
  echo "presentations about the writing, production, and play of\n";
  echo "live action roleplaying games, LARP theory, costuming,\n";
  echo "writing techniques, play styles, and a variety of other topics.\n";
  echo "These sessions will be run at the Crowne Plaza from Thursday\n";
  echo "evening until Friday evening around dinnertime.  There is no\n";
  echo "additional charge to attend this part of the convention. Your\n";
  echo "admission to " . CON_NAME ." includes all parts of the convention.</p>\n";

  echo "<style type=\"text/css\">\n";
  echo "#precon_top { border-spacing: 5px; }\n";
  echo "#precon_top td { vertical-align: top; padding: 0; width: 50%; text-align: left !important; font-size: 90%; }\n";
  echo "#precon_top td > * { padding: 5px; }\n";
  echo "#precon_top td .title { text-align: center; font-size: 100%; margin-bottom: 0; }\n";
//  echo "#precon_top td h3 { background-color: black; color: white; font-size: 100%; padding: 0; text-align: center; }\n";
  echo "</style>\n";

 echo "<table id=\"precon_top\"><tr>";
 echo "<td id=\"precon_bid\" class=\"menulike\">";
 echo "<p class=\"title\">Help Intercon Panels be awesome!</p>\n";

 echo "<p>Curious about what panels, discussions, and workshops might run at this upcoming Intercon? Have a larp technique you'd like to share with others? Have any recent discussions with your friends about larping you'd like to have with a wider audience? Come participate in the panels at Intercon as a moderator or panelist!
<a href=\"https://docs.google.com/forms/d/e/1FAIpQLSfyiXf7OeLEHXyBjJd6D3D5_IVrwOk9EbEVZcmn6A3ojNWWRg/viewform\">Click this link!</a></p>";

   echo "<p>It's never too late to get involved!  If you have any additional ideas or questions, email\n";
   echo mailto_or_obfuscated_email_address(EMAIL_THURSDAY);
   echo "!</p>";
   echo "</td>\n";
   echo "</tr></table>\n\n";


   /*
   echo "<p><strong>NOTE: The schedule below is tentative.  Events, times, and participants may still change before\n";
   echo "Precon.</strong></p>\n";


  echo "<p>Some of the panels and workshops at Precon O:</p>\n";
  echo "<ul>\n";
  echo "<li>Gender and Larp</li>\n";
  echo "<li>Writers are Total Cocks</li>\n";
  echo "<li>Building a strong and welcoming LARP community</li>\n";
  echo "<li>Kids at Intercon</li>\n";
  echo "<li>What Live Action Roleplaying Games do well</li>\n";
  echo "<li>Small Weekend Games Writing Workshop</li>\n";
  echo "<li>Ars Armani Workshop</li>\n";
  echo "<li>Introduction to Make-Up for LARPS</li>\n";
  echo "<li>Introduction to Dancing</li>\n";
  echo "<li>Acting Exercises for LARPers</li>\n";
  echo "<li>Using goals to distribute reality across game participants</li>\n";
  echo "<li>Vortex Mechanics: Viewing a game as interacting systems</li>\n";
  echo "<li>Turning a work of fiction into a LARP</li>\n";
  echo "<li>I don't like your game mechanics. So What?</li>\n";
  echo "<li>Writing Romance in Games</li>\n";
  echo "<li>Intellectual Property Issues in Game Design</li>\n";
  echo "<li>Using Querki for LARP Creation and Management</li>\n";
  echo "<li>Introduction to Accelerant for theater players</li>\n";
  echo "<li>Rails or Agency: A discussion of plot and players</li>\n";
  echo "<li>Props Workshop</li>\n";
  echo "<li>The Role of NPCs</li>\n";
  echo "<li>So you want to run a game: questions you should be asking yourself</li>\n";
  echo "<li>How to run a game wrap</li>\n";
  echo "<li>How to set expectations: blurbs and surveys</li>\n";
  echo "</ul>\n";
  echo "<p>If you have any questions about the Pre-Convention, we'd be happy to";
  echo " help.  Email the Pre-Convention coordinator, ".NAME_THURSDAY.", at ";
  echo mailto_or_obfuscated_email_address (EMAIL_THURSDAY);
  echo ".</p>\n\n";
  */
  list_accepted_events();
}

/*
 * thursday_report
 *
 * Show who's paid for the Pre-Convention
 */

function thursday_report()
{
  $sql = 'SELECT FirstName, LastName';
  $sql .= ' FROM Thursday, Users';
  $sql .= ' WHERE Thursday.UserId=Users.UserId';
  $sql .= '   AND Thursday.Status="Paid"';
  $sql .= ' ORDER BY LastName, FirstName';

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Failed to get list of PreCon attendees',
				$sql);

  $count = mysql_num_rows($result);
  display_header ("$count Paid PreCon Attendees");

  while ($row = mysql_fetch_object($result))
  {
    echo "$row->LastName, $row->FirstName<br>\n";
  }
  echo "<br>\n";
}

function select_from_all_users ($header, $href)
{
  // Get a list of all people signed up for the Pre-Convention

  $sql = 'SELECT UserId, Status, PaymentAmount, PaymentNote';
  $sql .= '  FROM Thursday';
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Failed to get list of users', $sql);

  $thursday_users = array();

  while ($row = mysql_fetch_object($result))
  {
    $thursday_users[$row->UserId] = sprintf ('%s/%d/%s',
					     $row->Status,
					     $row->PaymentAmount / 100,
					     $row->PaymentNote);
  }

  //  dump_array ('$thursday_users', $thursday_users);

  // Get a list of first characters

  $sql = 'SELECT DISTINCT UCASE(SUBSTRING(LastName,1,1)) AS Ch';
  $sql .= '  FROM Users';
  $sql .= "  WHERE LastName<>'Admin'";
  $sql .= ' AND CanSignup<>"Alumni"';
  $sql .= '  ORDER BY Ch';
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Failed to get list of characters');

  // Initialize the list of anchors to the alphabet, and then FALSE,
  // indicating that we haven't seen the character yet.  Then pull the
  // list of leading characters from the database and set them to TRUE,
  // indicating that we've got an anchor for that character

  $anchors = array ();
  for ($i = ord('A'); $i <= ord('Z'); $i++)
    $anchors[chr($i)] = FALSE;

  while ($row = mysql_fetch_object ($result))
    $anchors[$row->Ch] = TRUE;

  // Get a list of all users

  $sql = 'SELECT UserId, FirstName, LastName';
  $sql .= '  FROM Users';
  $sql .= ' WHERE CanSignup<>"Unpaid"';
  $sql .= '   AND CanSignup<>"Alumni"';
  $sql .= '  ORDER BY LastName, FirstName';
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Failed to get list of users', $sql);

  display_header ($header);

  // Display the list of anchors

  echo "<table width=\"100%\">\n";
  echo "  <tr>\n";

  foreach ($anchors as $key => $value)
  {
    if ($value)
      echo "    <td><a href=\"#$key\">$key</a></td>\n";
    else
      echo "    <td>$key</td>\n";
  }

  echo "  </tr>\n";
  echo "</table>\n";

  $ch = '';

  echo "<table border=\"0\" cellpadding=\"2\">\n";

  while ($row = mysql_fetch_object ($result))
  {
    // Skip the Admin account

    if ('Admin' == $row->LastName)
      continue;

    // Add spacer between names starting with different letters

    if ($ch != strtoupper($row->LastName{0}))
    {
      $ch = strtoupper ($row->LastName{0});
      echo "  <tr bgcolor=\"#CCCCFF\">\n";
      echo "    <td colspan=\"4\"><A name=\"$ch\">$ch</a></td>\n";
      echo "  </tr>\n";
    }

    // Display the user name for selection

    echo " <tr>\n";

    printf ("    <td><a href=\"%s&UserId=%d\">%s, %s</a></td>\n",
	    $href,
	    $row->UserId,
	    $row->LastName,
	    $row->FirstName);

    if (array_key_exists ($row->UserId, $thursday_users))
    {
      $a = explode ("/", $thursday_users[$row->UserId], 3);
      if ('Paid' == $a[0])
	printf ("    <td>&nbsp;%s $%d&nbsp;</td>\n", $a[0], $a[1]);
      else
	printf ("    <td>&nbsp;%s&nbsp;&nbsp;</td>\n", $a[0]);
      printf ("    <td>%s</td>\n", $a[2]);

    }

    echo "  </tr>\n";
  }

  echo "</table>\n";
}

/*
 * select_thursday_user
 *
 * General function to display the list of users in the database and allow
 * the current user to select one
 */

function select_thursday_user ($header,
			       $href,
			       $all_users)
{
  if ($all_users)
    return select_from_all_users ($header, $href);

  // Get a list of first characters

  $sql = 'SELECT DISTINCT UCASE(SUBSTRING(Users.LastName,1,1)) AS Ch';
  $sql .= '  FROM Thursday, Users';
  $sql .= '  WHERE Users.UserId=Thursday.UserId';
  $sql .= '  ORDER BY Ch';
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Failed to get list of characters');

  // Initialize the list of anchors to the alphabet, and then FALSE,
  // indicating that we haven't seen the character yet.  Then pull the
  // list of leading characters from the database and set them to TRUE,
  // indicating that we've got an anchor for that character

  $anchors = array ();
  for ($i = ord('A'); $i <= ord('Z'); $i++)
    $anchors[chr($i)] = FALSE;

  while ($row = mysql_fetch_object ($result))
    $anchors[$row->Ch] = TRUE;

  // Get a list of all people signed up for Thursday

  $sql = 'SELECT Thursday.UserId, Users.FirstName, Users.LastName,';
  $sql .= ' Thursday.Status, Thursday.PaymentAmount, Thursday.PaymentNote';
  $sql .= '  FROM Thursday, Users';
  $sql .= '  WHERE Users.UserId=Thursday.UserId';
  $sql .= '  ORDER BY LastName, FirstName';
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Failed to get list of users', $sql);

  display_header ($header);

  // Display the list of anchors

  echo "<table width=\"100%\">\n";
  echo "  <tr>\n";

  foreach ($anchors as $key => $value)
  {
    if ($value)
      echo "    <td><a href=\"#$key\">$key</a></td>\n";
    else
      echo "    <td>$key</td>\n";
  }

  echo "  </tr>\n";
  echo "</table>\n";

  $ch = '';

  echo "<table border=\"0\" cellpadding=\"2\">\n";

  while ($row = mysql_fetch_object ($result))
  {
    // Skip the Admin account

    if ('Admin' == $row->LastName)
      continue;

    // Add spacer between names starting with different letters

    if ($ch != strtoupper($row->LastName{0}))
    {
      $ch = strtoupper ($row->LastName{0});
      echo "  <tr bgcolor=\"#CCCCFF\">\n";
      echo "    <td colspan=\"4\"><A name=\"$ch\">$ch</a></td>\n";
      echo "  </tr>\n";
    }

    // Display the user name for selection

    echo " <tr>\n";

    printf ("    <td><a href=\"%s&UserId=%d\">%s, %s</a></td>\n",
	    $href,
	    $row->UserId,
	    $row->LastName,
	    $row->FirstName);

    echo "    <td>&nbsp;$row->Status&nbsp;";
    if ('Paid' == $row->Status)
      printf (' $%d', $row->PaymentAmount / 100);
    echo "</td>\n";
    echo "    <td>$row->PaymentNote</td>\n";
    echo "  </tr>\n";
  }

  echo "</table>\n";
}

/*
 * thursday_select_user
 *
 * Display the list of users and let the user pick one to edit their
 * Pre-Convention status
 */

function thursday_select_user()
{
  // Make sure that only privileged users get here

  if (! user_has_priv (PRIV_REGISTRAR))
    return display_access_error ();

  $link = sprintf ('Thursday.php?action=%d&Seq=%d',
		   THURSDAY_EDIT_USER,
		   increment_sequence_number());

  $all_checked = '';
  if (array_key_exists ('AllUsers', $_REQUEST))
    $all_checked = 'CHECKED';

  echo "<form method=\"post\" action=\"Thursday.php\">\n";
  printf ("<input type=\"hidden\" name=\"action\" value=\"%d\">\n",
	  THURSDAY_SELECT_USER);
  echo "<input type=\"checkbox\" name=\"AllUsers\" $all_checked>";
  echo "&nbsp;All Users\n";
  echo "<input type=\"submit\" value=\"Update\">\n";
  echo "</form>\n";

  select_thursday_user ('Select User To Edit Pre-Convention Info',
			$link,
			'CHECKED' == $all_checked);
}

function status_radio($value)
{
  $checked = '';
  if ($value == $_POST['Status'])
    $checked = ' CHECKED';

  printf ("    <input type=\"radio\" name=\"Status\" value=\"%s\"%s>%s\n",
	  $value, $checked, $value);
}

function name_user($UserId)
{
  $sql = 'SELECT FirstName, LastName FROM Users';
  $sql .= " WHERE UserId=$UserId";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Name query for UserId $UserId failed");

  $row = mysql_fetch_object ($result);

  return trim ("$row->FirstName $row->LastName");
}

function thursday_user_form()
{
  // Make sure that only privileged users get here

  if (! user_has_priv (PRIV_REGISTRAR))
    return display_access_error ();

  // Fetch the UserId

  $UserId = intval (trim ($_REQUEST['UserId']));
  if (0 == $UserId)
    return display_error ('Invalid UserId');

  // Fetch the selected user's name

  $name = name_user ($UserId);

  // If necessary, fetch the user info

  if (! array_key_exists ('Status', $_POST))
  {
    $sql = 'SELECT Thursday.*';
    $sql .= ' FROM Thursday';
    $sql .= " WHERE Thursday.UserId=$UserId";

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error ("PreCon Query for UserId $UserId failed");

    if ($row = mysql_fetch_assoc ($result))
    {
      foreach ($row as $k => $v)
	$_POST[$k] = $v;

      // Convert payment amount to dollars

      $_POST['PaymentAmount'] = $_POST['PaymentAmount']/100;
    }
    else
    {
      $_POST['PaymentAmount'] = '';
      $_POST['Status'] = '';
      $_POST['PaymentNote'] = '';
      $_POST['UpdatedById'] = 0;
    }
  }

  //  dump_array ('$_POST', $_POST);

  if (0 == $_POST['UpdatedById'])
    $updater = 0;
  else
    $updater = name_user ($_POST['UpdatedById']);

  $seq = increment_sequence_number();
  display_header ("Pre-Convention Info for $name");

  echo "<p><form method=\"post\" action=\"Thursday.php\">\n";
  form_add_sequence ($seq);
  printf ("<input type=\"hidden\" name=\"action\" value=%d>\n",
	  THURSDAY_PROCESS_USER);
  printf ("<input type=\"hidden\" name=\"UserId\" value=%d>\n", $UserId);
  echo "<table border=\"0\">\n";

  echo "  <tr>\n";
  echo "    <td align=\"right\" valign=\"top\">Status:</td>\n";
  echo "    <td>\n";
  status_radio ('Unpaid');
  status_radio ('Paid');
  status_radio ('Cancelled');
  echo "    </td>\n";
  echo "  </tr>\n";

  form_text (2, 'Payment Amount $', 'PaymentAmount');
  form_text (64, 'Payment Note', 'PaymentNote');

  form_submit ('Update');

  echo "<!-- updater: $updater -->\n";

  if (is_string ($updater))
  {
    echo "  <tr>\n";
    echo "    <td colspan=\"2\">\n";
    printf ("PreCon info last updated %s by %s\n",
	    $_POST['LastUpdated'],
	    $updater);
    echo "    </td>\n";
    echo "  </tr>\n";
  }
  echo "</table>\n";
  echo "</form>\n";

  printf ("<p><a href=\"Thursday.php?action=%d\">Select another user</a></p>\n",
	  THURSDAY_SELECT_USER);
}

function thursday_process_user_form()
{
  // Make sure that only privileged users get here

  if (! user_has_priv (PRIV_REGISTRAR))
    return display_access_error ();

  // Fetch the UserId

  $UserId = intval (trim ($_REQUEST['UserId']));
  if (0 == $UserId)
    return display_error ('Invalid UserId');

  // Check for sequence errors

  if (out_of_sequence ())
    return display_sequence_error (false);

  // Determine if we're adding a new user to the Thursday table, or if they're
  // already there

  $insert_thursday = true;
  $sql = "SELECT Status FROM Thursday WHERE UserId=$UserId";
  $result = mysql_query ($sql);
  if ($result)
    if (0 != mysql_num_rows ($result))
      $insert_thursday = false;

  $PaymentAmount = intval ($_POST['PaymentAmount']) * 100;

  if ($insert_thursday)
    $sql = 'INSERT Thursday SET ';
  else
    $sql = 'UPDATE Thursday SET ';
  $sql .= build_sql_string ('Status', $_POST['Status'], false);
  $sql .= build_sql_string ('PaymentAmount', $PaymentAmount);
  $sql .= build_sql_string ('PaymentNote');
  $sql .= ', UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];
  $sql .= ', LastUpdated=NULL';
  if ($insert_thursday)
    $sql .= build_sql_string ('UserId', $UserId);
  else
    $sql .= " WHERE UserId=$UserId";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Thursday update failed');
  else
    return true;
}

/*
 * schedule_table_entry
 *
 * Display a drop-down list to allow the user to select whether he's
 * willing to run his game in this time slot
 */

function schedule_table_entry ($day, $date, $start_hour, $rowspan=0)
{
  $key = sprintf('%s%02d', $day, $start_hour);

  if (! isset ($_POST[$key]))
    $value = '';
  else
  {
    $value = trim ($_POST[$key]);
    if (1 == get_magic_quotes_gpc())
      $value = stripslashes ($value);
  }

  if (23 == $start_hour)
    $end_hour = 0;
  else
    $end_hour = $start_hour + 1;

  $period = sprintf('%02d:00 -- %02d:00', $start_hour, $end_hour);

  $dont_care = '';
  $one = '';
  $two = '';
  $three = '';
  $no = '';

  switch ($value)
  {
    case '-': $dont_care = 'selected'; break;
    case '1': $one       = 'selected'; break;
    case '2': $two       = 'selected'; break;
    case '3': $three     = 'selected'; break;
    case 'X': $no        = 'selected'; break;
  }

  echo "        <tr valign=\"top\">\n";

  if (0 != $rowspan)
    echo "          <th rowspan=\"$rowspan\">&nbsp;$day&nbsp;<br>&nbsp;$date&nbsp;</th>\n";

  echo "          <td>&nbsp;$period&nbsp;</td>\n";
  echo "          <td>\n";
  echo "            <select name=\"$key\">\n";
  echo "              <option value=\"-\" $dont_care>Don't Care&nbsp;</option>\n";
  echo "              <option value=\"1\" $one>1st Choice&nbsp;</option>\n";
  echo "              <option value=\"2\" $two>2nd Choice&nbsp;</option>\n";
  echo "              <option value=\"3\" $three>3rd Choice&nbsp;</option>\n";
  echo "              <option value=\"X\" $no>Prefer Not&nbsp;</option>\n";
  echo "            </select>\n";
  echo "          </td>\n";
  echo "        </tr>\n";
}

function display_precon_event_form()
{
  // Make sure that the user is logged in

  if (! isset ($_SESSION[SESSION_LOGIN_USER_ID]))
    return display_error ('You must login before submitting a bid');

  // If we're updating a bid, grab the bid ID

  $PreConEventId = request_int('PreConEventId');

  // If this is a new bid, just display the header

  if (0 == $PreConEventId)
    display_header ('Bid a Panel, Discussion, or Workshop for ' . CON_NAME);
  else
  {
    // Load the $_POST array from the database

    $sql = "SELECT * FROM PreConEvents WHERE PreConEventId=$PreConEventId";
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error ('Query failed for PreConEventId ' .
				  $PreConEventId);
    if (0 == mysql_num_rows($result))
      return display_error("Failed to find PreConEventId $PreConEventId");

    if (1 != mysql_num_rows($result))
      return display_error ('Found multiple entrys for PreConEventId ' .
			    $PreConEventId);

    $row = mysql_fetch_array($result, MYSQL_ASSOC);

    foreach ($row as $key => $value)
    {
      if (1 == get_magic_quotes_gpc())
	$_POST[$key] = mysql_real_escape_string($value);
      else
	$_POST[$key] = $value;
    }

    // Only the submitter or PreCon Bid Chair can update this bid

    $can_update =
      user_has_priv(PRIV_PRECON_BID_CHAIR) ||
      ($_SESSION[SESSION_LOGIN_USER_ID] == $_POST['SubmitterUserId']);

    if (! $can_update)
      return display_access_error();

    display_header ('Update Panel <i>' . $_POST['Title'] . '</i>');
  }

  echo "<form method=\"POST\" action=\"Thursday.php\">\n";
  form_add_sequence();
  form_hidden_value ('action', PRECON_PROCESS_EVENT_FORM);
  form_hidden_value('PreConEventId', $PreConEventId);

  echo "<p><font color=red>*</font> indicates a required field\n";
  echo "<table border=\"0\">\n";
  form_text(64, 'Title', '', 128, true);
  form_text(1, 'Length', 'Hours', 1, true, '(Hours)');
  form_text(64, 'Special Requirements', 'SpecialRequests', 128);

  $text = "Description for use on the " . CON_NAME . " website.  This\n";
  $text .= "information will also be used for advertising and some\n";
  $text .= "flyers.  The description should be a couple of paragraphs,\n";
  $text .= "but can be as long as you like.\n";
  $text .= "<P>The description will be displayed in the user's browser.\n";
  $text .= "You must use HTML tags for formatting.  A quick primer on\n";
  $text .= "a couple of useful HTML tags is available\n";
  $text .= "<A HREF=HtmlPrimer.html TARGET=_blank>here</A>.\n";
  form_textarea ($text, 'Description', 15, TRUE, TRUE);

  form_section ('Scheduling Requests');

  echo "  <tr>\n";
  echo "    <td colspan=\"2\">\n";
  echo "      <p>The con can schedule your event into one (or more) of the\n";
  echo "      time slots available during the Pre-Convention.  The con has\n";
  echo "      to put together a balanced schedule so we can satisfy the\n";
  echo "      most attendees in the most time slots.  Your flexibility in\n";
  echo "      scheduling your event is vital.</p>\n";
  echo "      <p>Please pick your top three preferences for when you'd like\n";
  echo "      to run your event.</p>\n";
  echo "    </td>\n";
  echo "  </tr>\n";

  echo "  <tr>\n";
  echo "    <td colspan=\"2\">\n";
  echo "      <table border=\"1\">\n";
  schedule_table_entry ('Thursday', THR_DATE, 20, 4);
  schedule_table_entry ('Thursday', THR_DATE, 21);
  schedule_table_entry ('Thursday', THR_DATE, 22);
  schedule_table_entry ('Thursday', THR_DATE, 23);
  schedule_table_entry ('Friday',   FRI_DATE, 9, 9);
  schedule_table_entry ('Friday',   FRI_DATE, 10);
  schedule_table_entry ('Friday',   FRI_DATE, 11);
  schedule_table_entry ('Friday',   FRI_DATE, 12);
  schedule_table_entry ('Friday',   FRI_DATE, 13);
  schedule_table_entry ('Friday',   FRI_DATE, 14);
  schedule_table_entry ('Friday',   FRI_DATE, 15);
  schedule_table_entry ('Friday',   FRI_DATE, 16);
  schedule_table_entry ('Friday',   FRI_DATE, 17);
  echo "      </table>\n";
  echo "    </td>\n";
  echo "  </tr>\n";

  if (0 == $PreConEventId)
    $text = 'Submit Event';
  else
    $text = 'Update Event';
  form_submit ($text);
  echo "</table>\n";
  echo "</form>\n";
}

function create_precon_event_mail($PreConEventId, &$subject, &$body)
{
  $subject = '';
  $body = '';

  $user = name_user($_SESSION[SESSION_LOGIN_USER_ID]);

  if (0 == $PreConEventId)
  {
    $subject = sprintf('[%s - Pre-Con Event Bid] New: %s',
		       CON_NAME,
		       $_POST['Title']);
    $body = "The bid has been submitted by $user";
    return;
  }


  $sql = "SELECT * FROM PreConEvents WHERE PreConEventId=$PreConEventId";
  $result = mysql_query($sql);
  if (! $result)
    return;

  $changes = '';
  $ary = mysql_fetch_assoc($result);
  foreach ($ary as $k => $v)
  {
    if (post_string($k) != $v)
    {
      if ('' == $changes)
	$changes = $k;
      else
	$changes .= ', ' . $k;
    }
  }

  if ('' == $changes)
    return;

  $subject = sprintf('[%s - Pre-Con Event Bid] Updated: %s',
		     CON_NAME,
		     $_POST['Title']);
  $body = "$user has changed the following fields: $changes.";
}

function process_precon_event_form()
{
  //  dump_array('$_REQUEST', $_REQUEST);

  // Check for a sequence error
  if (out_of_sequence())
    return display_sequence_error(false);

  // If we're updating a bid, grab the bid ID
  $PreConEventId = request_int('PreConEventId');

  // We must have a title, Length and Descriptions!

  if ('' == request_string('Title'))
    return display_error ('You must specify a title.');

  if (0 == request_int('Hours'))
    return display_error ('You must specify the number of hours');

  if ('' == request_string('Description'))
    return display_error('You must provide a description');

  // If this is an existing bid, build the list of changes

  create_precon_event_mail($PreConEventId, $subject, $body);

  // If this is a new bid, just display the header

  if (0 == $PreConEventId)
    $sql = 'INSERT PreConEvents SET ';
  else
    $sql = 'UPDATE PreConEvents SET ';

  $sql .= build_sql_string('Title', $_REQUEST['Title'], false);
  $sql .= build_sql_string('Hours');
  $sql .= build_sql_string('SpecialRequests');
  $sql .= build_sql_string('Thursday21');
  $sql .= build_sql_string('Thursday22');
  $sql .= build_sql_string('Thursday23');
  $sql .= build_sql_string('Friday09');
  $sql .= build_sql_string('Friday10');
  $sql .= build_sql_string('Friday11');
  $sql .= build_sql_string('Friday12');
  $sql .= build_sql_string('Friday13');
  $sql .= build_sql_string('Friday14');
  $sql .= build_sql_string('Friday15');
  $sql .= build_sql_string('Friday16');
  $sql .= build_sql_string('Friday17');
  $sql .= build_sql_string('Description', '', true, true);
  $sql .= ', UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];
  if (0 == $PreConEventId)
    $sql .= ', SubmitterUserId=' . $_SESSION[SESSION_LOGIN_USER_ID];
  else
    $sql .= " WHERE PreConEventId=$PreConEventId";

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error ('Submission failed');

  if (! user_has_priv(PRIV_PRECON_BID_CHAIR))
  {
    if (! intercon_mail (EMAIL_THURSDAY, $subject, $body))
      display_error ('Attempt to send changes to Pre-Con event chair failed!');
  }

  return true;
}

function display_bid_thank_you()
{
  display_header('Thank you for bidding a Pre-Convention Event!');
  echo "<p>The Pre-Convention Bid Chair has been notified and should\n";
  echo "review your submission and be in touch with you shortly.  You\n";
  printf ("can contact the Pre-Convention Bid Chair at %s .</p>\n",
	  mailto_or_obfuscated_email_address(EMAIL_THURSDAY));
}

function display_event_summary()
{
  // Make sure that only privileged users get here

  if (! user_has_priv (PRIV_PRECON_BID_CHAIR))
    return display_access_error ();

  display_header ('Pre-Convention Events');

  $sql = 'SELECT PreConEvents.PreConEventId, PreConEvents.Title,';
  $sql .= ' PreConEvents.Hours, PreConEvents.Status,';
  $sql .= ' Users.FirstName, Users.LastName';
  $sql .= ' FROM PreConEvents, Users';
  $sql .= ' WHERE Users.UserId=PreConEvents.SubmitterUserId';
  $sql .= ' ORDER BY Status, Title';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for PreCon Events failed', $sql);

  printf("<p><a href=\"Thursday.php?action=%d\">Add new event</a></p>",
	 PRECON_SHOW_EVENT_FORM);

  if (0 == mysql_num_rows($result))
  {
    echo "<p>There are no Pre Convention events yet.</p>\n";
    return true;
  }

  echo "<table border=\"1\">\n";
  echo "  <tr valign=\"bottom\">\n";
  echo "    <th rowspan=\"2\">Status</th>\n";
  echo "    <th rowspan=\"2\">Edit Event</th>\n";
  echo "    <th rowspan=\"2\">Submitter</th>\n";
  echo "    <th rowspan=\"2\">Title</th>\n";
  echo "    <th colspan=\"3\">Runs</th>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <th>&nbsp;Day&nbsp;</th>\n";
  echo "    <th>&nbsp;Start&nbsp;Time&nbsp;</th>\n";
  echo "    <th>&nbsp;Room(s)&nbsp;</th>\n";
  echo "  </tr>\n";

  while ($row = mysql_fetch_object($result))
  {
    $sql = 'SELECT Day, StartHour, PreConRunId, Rooms FROM PreConRuns';
    $sql .= " WHERE PreConEventId=$row->PreConEventId";
    $sql .= ' ORDER BY Day, StartHour';

    $run_result = mysql_query($sql);
    if (! $run_result)
      return display_mysql_error ('Query for PreCon Event Runs failed', $sql);

    $submitter = trim("$row->FirstName $row->LastName");

    $rowspan = mysql_num_rows($run_result);
    if (0 == $rowspan)
      $rowspan = 1;

    echo "  <tr align=\"center\" valign=\"top\">\n";
    printf ('    <td rowspan="%d">' .
	    '<a href="Thursday.php?action=%d&PreConEventId=%d">' .
	    "%s</a></td>\n",
	    $rowspan,
	    PRECON_SHOW_STATUS_FORM,
	    $row->PreConEventId,
	    $row->Status);
    printf ('    <td rowspan="%d">' .
	    '<a href="Thursday.php?action=%d&PreConEventId=%d">' .
	    "Edit</a></td>\n",
	    $rowspan,
	    PRECON_SHOW_EVENT_FORM,
	    $row->PreConEventId);
    echo "    <td rowspan=\"$rowspan\">$submitter</td>\n";
    if ('Accepted' == $row->Status)
      printf ('    <td rowspan="%d" align="left" valign="top">&nbsp;' .
	      '<a href="Thursday.php?action=%d&PreConEventId=%d">%s</a>' .
	      "&nbsp;</td>\n",
	      $rowspan,
	      PRECON_SHOW_RUN_FORM,
	      $row->PreConEventId,
	      $row->Title);
    else
      printf ('    <td rowspan="%d" align="left" valign="top">&nbsp;' .
	      "%s&nbsp;</td>\n",
	      $rowspan,
	      $row->Title);

    if (0 == mysql_num_rows($run_result))
      echo "    <td colspan=\"3\">&nbsp;</td>\n";
    else
    {
      while ($run_row = mysql_fetch_object($run_result))
      {
	printf ('    <td>' .
		'<a href="Thursday.php?action=%d&PreConEventId=%d' .
		'&PreConRunId=%d">%s</a>' .
		"</td>\n",
		PRECON_SHOW_RUN_FORM,
		$row->PreConEventId,
		$run_row->PreConRunId,
		$run_row->Day);
	printf ('    <td>' .
		'<a href="Thursday.php?action=%d&PreConEventId=%d' .
		'&PreConRunId=%d">%d:00</a>' .
		"</td>\n",
		PRECON_SHOW_RUN_FORM,
		$row->PreConEventId,
		$run_row->PreConRunId,
		$run_row->StartHour);

	$Rooms = pretty_rooms($run_row->Rooms);
	echo "    <td>&nbsp;$Rooms&nbsp;</td>\n";
      }
    }
    echo "  </tr>\n";
  }

  echo "</table>\n";
  echo "<p>\n";
  echo "Click on the <b>Status</b> to change (accept, reject, etc.)<br>\n";
  echo "Click on the event <b>Title</b> to add a run<br>\n";
  echo "Click on the <b>Day</b> or <b>Start Time</b> to edit or delete a run\n";
  echo "</p>\n";
}

function scheduling_preference_row($day, $hour, $ary, $rowspan=0)
{
  $row_value = $ary[sprintf("%s%02d", $day, $hour)];
  $text = "Don't Care";
  $bg = '';

  switch ($row_value)
  {
    case '1':
      $text = 'First Choice';
      $bg = 'bgcolor="#CCFFCC"';  // Light green
      break;
    case '2':
      $text = 'Second Choice';
      $bg = 'bgcolor="#FFFFCC"';  // Light yellow
      break;
    case '3':
      $text = 'Third Choice';
      $bg = 'bgcolor="#CCCCFF"';  // Light blue
      break;
    case 'X':
      $text = 'Prefer Not';
      $bg = 'bgcolor="#FFCCCC"';  // Light red
      break;
  }

  echo "        <tr>\n";
  if (0 != $rowspan)
    echo "          <th rowspan=\"$rowspan\" valign=\"top\">&nbsp;$day&nbsp;</th>\n";

  printf ("          <td>&nbsp;%d:00 -- %d:00&nbsp;</td>\n",
	  $hour, $hour+1);
  echo "          <td $bg>&nbsp;$text&nbsp;</td>\n";
  echo "        </tr>\n";
}

function scheduling_start_option($day, $hour, $selection)
{
  $selected = '';
  if ($selection == "$day$hour")
    $selected = 'selected';

  printf ("        <option value=\"%s%02d\" %s>%s %d:00</option>\n",
	  $day, $hour,
	  $selected,
	  $day, $hour);
}

function show_status_form()
{
  // Make sure that only privileged users get here

  if (! user_has_priv (PRIV_PRECON_BID_CHAIR))
    return display_access_error ();

  // Fetch the PreConEventId

  $PreConEventId = request_int('PreConEventId');
  if (0 == $PreConEventId)
    return display_error ('Invalid PreConEventId');

  $sql = 'SELECT * FROM PreConEvents';
  $sql .= " WHERE PreConEventId=$PreConEventId";

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error("Query for PreConEventId $PreConEventId failed",
			       $sql);

  if (0 == mysql_num_rows($result))
    return display_error ("Failed to find PreConEventId $PreConEventId");

  if (1 != mysql_num_rows($result))
    return display_error ("Found multiple rows for PreConEventId $PreConEventId");

  $ary = mysql_fetch_array($result);

  $count = 0;
  if ('Accepted' == $ary['Status'])
  {
    $sql = 'SELECT PreConRunId FROM PreConRuns';
    $sql .= " WHERE PreConEventId=$PreConEventId";

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error ("Query for PreConRuns failed", $sql);

    $count = mysql_num_rows($result);
  }

  $pending = '';
  $accepted = '';
  $rejected = '';
  $dropped = '';

  switch ($ary['Status'])
  {
    case 'Pending':  $pending =  'selected'; break;
    case 'Accepted': $accepted = 'selected'; break;
    case 'Rejected': $rejected = 'selected'; break;
    case 'Dropped':  $dropped =  'selected'; break;
  }

  $Title = $ary['Title'];
  display_header ("Change status of <i>$Title</i>\n");

  echo "<form method=\"POST\" action=\"Thursday.php\">\n";
  form_add_sequence();
  form_hidden_value('action', PRECON_PROCESS_STATUS_FORM);
  form_hidden_value('PreConEventId', $PreConEventId);

  echo "<table>\n";

  echo "  <tr>\n";
  echo "    <th>Status:</th>\n";
  echo "    <td>\n";
  echo "      <select name=\"Status\">\n";
  echo "        <option value=\"Pending\" $pending>Pending</option>\n";
  echo "        <option value=\"Accepted\" $accepted>Accepted</option>\n";
  echo "        <option value=\"Rejected\" $rejected>Rejected</option>\n";
  echo "        <option value=\"Dropped\" $dropped>Dropped</option>\n";
  echo "      </select>\n";
  echo "    </td>\n";
  echo "  <tr>\n";

  if ($count > 0)
  {
    echo "  <tr>\n";
    echo "    <td colspan=\"2\"><font color=\"red\">Warning: This event has runs scheduled.</font></td>\n";
    echo "  <tr>\n";
  }

  $none_selected = 'selected';
  form_section ('Schedule Run (Only if Accepting)');
  echo "  <tr>\n";
  echo "    <th>Start Time:</th>\n";
  echo "    <td>\n";
  echo "      <select name=\"StartTime\">\n";
  echo "        <option value=\"None\" $none_selected>None</option>\n";
  scheduling_start_option('Thursday', 18, '');
  scheduling_start_option('Thursday', 19, '');
  scheduling_start_option('Thursday', 20, '');
  scheduling_start_option('Thursday', 21, '');
  scheduling_start_option('Thursday', 22, '');
  scheduling_start_option('Thursday', 23, '');
  scheduling_start_option('Friday', 9, '');
  scheduling_start_option('Friday', 10, '');
  scheduling_start_option('Friday', 11, '');
  scheduling_start_option('Friday', 12, '');
  scheduling_start_option('Friday', 13, '');
  scheduling_start_option('Friday', 14, '');
  scheduling_start_option('Friday', 15, '');
  scheduling_start_option('Friday', 16, '');
  scheduling_start_option('Friday', 17, '');
  echo "      </select>\n";
  echo "    </td>\n";
  echo "  </tr>\n";

  form_con_rooms ('Room(s)', 'Rooms');

  form_submit('Submit');

  form_section ('Schedule Preferences');
  echo "  <tr>\n";
  echo "    <td colspan=\"2\">\n";
  echo "      <table border=\"1\">\n";

  scheduling_preference_row('Thursday', 21, $ary, 3);
  scheduling_preference_row('Thursday', 22, $ary);
  scheduling_preference_row('Thursday', 23, $ary);
  scheduling_preference_row('Friday', 9, $ary, 9);
  scheduling_preference_row('Friday', 10, $ary);
  scheduling_preference_row('Friday', 11, $ary);
  scheduling_preference_row('Friday', 12, $ary);
  scheduling_preference_row('Friday', 13, $ary);
  scheduling_preference_row('Friday', 14, $ary);
  scheduling_preference_row('Friday', 15, $ary);
  scheduling_preference_row('Friday', 16, $ary);
  scheduling_preference_row('Friday', 17, $ary);
  echo "      </table>\n";
  echo "    </td>\n";
  echo "  </tr>\n";

  echo "</table>\n";
  echo "</form>\n";
}

function show_precon_event()
{
  // Fetch the PreConEventId

  $PreConEventId = request_int('PreConEventId');
  if (0 == $PreConEventId)
    return display_error ('Invalid PreConEventId');


  $sql = 'SELECT PreConEvents.Title, PreConEvents.Hours,';
  $sql .= ' PreConEvents.Description, Users.FirstName, Users.LastName,';
  $sql .= ' PreConEvents.UpdatedById, PreConEvents.SubmitterUserId,';
  $sql .= ' DATE_FORMAT(PreConEvents.LastUpdated, "%d-%b-%Y %H:%i") AS Timestamp';
  $sql .= ' FROM PreConEvents, Users';
  $sql .= " WHERE PreConEvents.PreConEventId=$PreConEventId";
  $sql .= '   AND Users.UserId=PreConEvents.SubmitterUserId';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error("Query for PreConEventId $PreConEventId failed",
			       $sql);

  if (0 == mysql_num_rows($result))
    return display_error ("Failed to find PreConEventId $PreConEventId");

  if (1 != mysql_num_rows($result))
    return display_error ("Found multiple rows for PreConEventId $PreConEventId");

  $row = mysql_fetch_object($result);

  $runSql = 'SELECT Rooms, Day, StartHour';
  $runSql .= ' FROM PreConRuns';
  $runSql .= " WHERE PreConEventId = $PreConEventId";
  $runResult = mysql_query($runSql);
    if (! $runResult)
    return display_mysql_error("Query for PreConRuns for event ID $PreConEventId failed",
             $sql);

  $can_edit_event = user_has_priv(PRIV_PRECON_BID_CHAIR);
  if (array_key_exists(SESSION_LOGIN_USER_ID, $_SESSION))
    if ($row->SubmitterUserId == $_SESSION[SESSION_LOGIN_USER_ID])
      $can_edit_event = true;

  echo "<table width=\"100%\">\n";
  echo "  <tr>\n";
  echo "    <td><big><big><b><i>$row->Title</i></b></big></big></td>\n";
  if ($can_edit_event)
  {
    printf ('    <td align="right" nowrap>[<a href="Thursday.php?action=%d' .
	    '&PreConEventId=%d">Edit Event</a>]' .
	    "</td>\n",
	    PRECON_SHOW_EVENT_FORM,
	    $PreConEventId);
  }
  echo "  </tr>\n";
  while ($runRow = mysql_fetch_object($runResult)) {
    echo "  <tr>\n";
    echo "    <td>";
    echo pretty_rooms($runRow->Rooms) . " - ";
    echo $runRow->Day . ", " . $runRow->StartHour .":00 - " . ($runRow->StartHour + $row->Hours). ":00";
    echo "    </td>\n";
    echo "  </tr>\n";
  }
  echo "</table>\n";

  $name = trim("$row->FirstName $row->LastName");
//  echo "<p><b>Submitted by:</b> $name</p>";
  echo "<p>$row->Description</p>\n";

  if ($can_edit_event)
  {
    $updater = name_user($row->UpdatedById);
    echo "<p><b>Last updated</b>: $row->Timestamp by $updater</p>\n";
  }
}

function process_status_form()
{
  // Make sure that only privileged users get here
  if (! user_has_priv (PRIV_PRECON_BID_CHAIR))
    return display_access_error ();

  //  dump_array('$_POST', $_POST);

  // Fetch the PreConEventId
  $PreConEventId = request_int('PreConEventId');
  if (0 == $PreConEventId)
    return display_error ('Invalid PreConEventId');

  // Fetch the Status
  $Status = request_string('Status');
  switch ($Status)
  {
    case 'Pending':
    case 'Accepted':
    case 'Rejected':
    case 'Dropped':
      break;

    default:
      return display_error ('Invalid Status');
  }

  // The bid is being set to any status other than 'Accepted',
  // delete any runs
  if ('Accepted' != $Status)
  {
    $sql = "DELETE FROM PreConRuns WHERE PreConEventId=$PreConEventId";
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('Failed to delete any Pre-Con Runs for' .
				 "PreConEventId: $PreConEventId",
				 $sql);
  }

  // Update the bid status
  $sql = 'UPDATE PreConEvents';
  $sql .= " SET Status='$Status', ";
  $sql .= 'UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];
  $sql .= " WHERE PreConEventId=$PreConEventId";

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Status update failed', $sql);

  // We're done if this wasn't an attempt to accept the bid
  if ('Accepted' != $Status)
    return true;

  // Fetch the Start time
  $day = '';
  $hour = 0;
  $StartTime = request_string('StartTime');

  switch ($StartTime)
  {
    case 'None':
      return true;  // We're done!

    case 'Thursday20':
      $day = 'Thu';
      $hour = 20;
      break;

    case 'Thursday21':
      $day = 'Thu';
      $hour = 21;
      break;

    case 'Thursday22':
      $day = 'Thu';
      $hour = 22;
      break;

    case 'Thursday23':
      $day = 'Thu';
      $hour = 23;
      break;

    case 'Friday09':
      $day = 'Fri';
      $hour = 9;
      break;

    case 'Friday10':
      $day = 'Fri';
      $hour = 10;
      break;

    case 'Friday11':
      $day = 'Fri';
      $hour = 11;
      break;

    case 'Friday12':
      $day = 'Fri';
      $hour = 12;
      break;

    case 'Friday13':
      $day = 'Fri';
      $hour = 13;
      break;

    case 'Friday14':
      $day = 'Fri';
      $hour = 14;
      break;

    case 'Friday15':
      $day = 'Fri';
      $hour = 15;
      break;

    case 'Friday16':
      $day = 'Fri';
      $hour = 16;
      break;

    case 'Friday17':
      $day = 'Fri';
      $hour = 17;
      break;

    default:
      return display_error ('Invalid StartTime');
  }

  $Rooms = '';
  if (array_key_exists('Rooms', $_POST))
    $Rooms = implode(',', $_POST['Rooms']);

  $sql = 'INSERT PreConRuns SET ';
  $sql .= build_sql_string('PreConEventId', $PreConEventId, false);
  $sql .= build_sql_string('Day', $day);
  $sql .= build_sql_string('StartHour', $hour);
  $sql .= build_sql_string('Rooms', $Rooms);
  $sql .= ', UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Failed to create run', $sql);
  else
    return true;
}

function show_run_form()
{
  // Make sure that only privileged users get here

  if (! user_has_priv (PRIV_PRECON_BID_CHAIR))
    return display_access_error ();

  //  dump_array('$_REQUEST', $_REQUEST);

  // Fetch the PreConEventId

  $PreConEventId = request_int('PreConEventId');
  if (0 == $PreConEventId)
    return display_error ('Invalid PreConEventId');

  // Fetch the PreConRunId

  $PreConRunId = request_int('PreConRunId');

  if (0 == $PreConRunId)
  {
    if (! array_key_exists('Rooms', $_POST))
      $_POST['Rooms'] = '';
  }
  else
  {
    $sql = "SELECT * FROM PreConRuns WHERE PreConRunId=$PreConRunId";
    $result = mysql_query($sql);
    if (!$result)
      return display_mysql_error("Query for PreConRun $PreConRunId failed",
				 $sql);

    if ($row = mysql_fetch_assoc($result))
    {
      foreach($row as $k => $v)
	$_POST[$k] = $v;
      if ('Thu' == $_POST['Day'])
	$day = 'Thursday';
      else
	$day = 'Friday';
      $_POST['StartTime'] = $day . $_POST['StartHour'];
    }
    $_POST['Rooms'] = explode(',', $row['Rooms']);
  }

  $sql = "SELECT Title FROM PreConEvents WHERE PreConEventId=$PreConEventId";
  $result = mysql_query($sql);
  if (!$result)
    return display_mysql_error("Query for PreConEvent $PreConEventId failed",
			       $sql);

  $row = mysql_fetch_object($result);
  if (! $row)
    return display_error("PreConEvent row not found for $PreConEventId");

  if (0 == $PreConRunId)
  {
    $verb = 'Add';
    $ok = 'Add Run';
  }
  else
  {
    $verb = 'Edit';
    $ok = 'Update Run';
  }

  display_header ("$verb run for <i>$row->Title</i>");

  echo "<form method=\"POST\" action=\"Thursday.php\">\n";
  form_add_sequence();
  form_hidden_value('action', PRECON_PROCESS_RUN_FORM);
  form_hidden_value('PreConEventId', $PreConEventId);
  form_hidden_value('PreConRunId', $PreConRunId);

  echo "<table>\n";

  $start_time_selection = post_string('StartTime', 'None');

  if ('None' == $start_time_selection)
    $none_selected = 'selected';
  else
    $none_selected = '';

  echo "  <tr>\n";
  echo "    <th>Start Time:</th>\n";
  echo "    <td>\n";
  echo "      <select name=\"StartTime\">\n";
  echo "        <option value=\"None\" $none_selected>None</option>\n";
  scheduling_start_option('Thursday', 18, $start_time_selection);
  scheduling_start_option('Thursday', 19, $start_time_selection);
  scheduling_start_option('Thursday', 20, $start_time_selection);
  scheduling_start_option('Thursday', 21, $start_time_selection);
  scheduling_start_option('Thursday', 22, $start_time_selection);
  scheduling_start_option('Thursday', 23, $start_time_selection);
  scheduling_start_option('Friday', 9, $start_time_selection);
  scheduling_start_option('Friday', 10, $start_time_selection);
  scheduling_start_option('Friday', 11, $start_time_selection);
  scheduling_start_option('Friday', 12, $start_time_selection);
  scheduling_start_option('Friday', 13, $start_time_selection);
  scheduling_start_option('Friday', 14, $start_time_selection);
  scheduling_start_option('Friday', 15, $start_time_selection);
  scheduling_start_option('Friday', 16, $start_time_selection);
  scheduling_start_option('Friday', 17, $start_time_selection);
  echo "      </select>\n";
  echo "    </td>\n";
  echo "  </tr>\n";

  form_con_rooms('Rooms(s)', 'Rooms');

  form_submit2($ok, 'Delete Run', 'Delete');
  echo "</table>\n";
  echo "</form>\n";
}

function process_run_form()
{
  // Make sure that only privileged users get here

  if (! user_has_priv (PRIV_PRECON_BID_CHAIR))
    return display_access_error ();

  // Fetch the PreConEventId

  $PreConEventId = request_int('PreConEventId');
  if (0 == $PreConEventId)
    return display_error ('Invalid PreConEventId');

  // Fetch the PreConRunId (may be zero)
  $PreConRunId = request_int('PreConRunId');

  if (array_key_exists('Delete', $_POST))
  {
    if (0 == $PreConRunId)
      return display_error ('Cannot delete PreConRun 0');

    $sql = "DELETE FROM PreConRuns WHERE PreConRunId=$PreConRunId";
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('Failed to delete PreConRun entry', $sql);
    return true;
  }

  $StartTime = $_POST['StartTime'];
  if ('None' == $StartTime)
    return display_error ('You must specify a start time');

  $hour = intval(substr($StartTime, -2));
  if (0 == $hour)
    return display_error ("Invalid start time: $StartTime");

  $day = substr($StartTime, 0, 3);
  if (('Thu' != $day) && ('Fri' != $day))
    return display_error ("Invalid start time: $StartTime");

  $Rooms = '';
  if (array_key_exists('Rooms', $_POST))
    $Rooms = implode(',', $_POST['Rooms']);


  if (0 == $PreConRunId)
    $sql = 'INSERT PreConRuns SET ';
  else
    $sql = 'UPDATE PreConRuns SET ';

  $sql .= build_sql_string('PreConEventId', $PreConEventId, false);
  $sql .= build_sql_string('Day', $day);
  $sql .= build_sql_string('StartHour', $hour);
  $sql .= build_sql_string('Rooms', $Rooms);
  $sql .= ', UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];

  if (0 != $PreConRunId)
    $sql .= " WHERE PreConRunId=$PreConRunId";

  //  echo "SQL: $sql\n";

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('PreConRun update failed', $sql);
  else
    return true;
}

?>
