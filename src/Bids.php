<?php
include ("intercon_db.inc");

// Connect to the database

if (! intercon_db_connect ())
{
  display_mysql_error ('Failed to establish connection to the database');
  exit ();
}

// Display boilerplate

html_begin ();

// Figure out what we''re supposed to do

if (array_key_exists ('action', $_REQUEST))
  $action=$_REQUEST['action'];

if (empty ($action))
  if (count($BID_TYPES) > 1)
    $action = BID_CHOOSE_GAME_TYPE;
  else
    $action = BID_GAME;

switch ($action)
{
  case BID_CHOOSE_GAME_TYPE:
    display_choose_form (TRUE);
    break;

  case BID_GAME:
    display_bid_form (TRUE);
    break;

  case BID_PROCESS_FORM:
    if (! process_bid_form ())
      display_bid_form (FALSE);
    else
      display_bid_etc ();
    break;

  case BID_REVIEW_BIDS:
    display_bids_for_review ();
    break;

  case BID_CHANGE_STATUS:
    change_bid_status ();
    break;

  case BID_PROCESS_STATUS_CHANGE:
    if (! process_status_change ())
      change_bid_status ();
    else
      display_bids_for_review ();
    break;

  case BID_SHOW_BID:
    show_bid ();
    break;

  case BID_FEEDBACK_SUMMARY:
    show_bid_feedback_summary();
    break;

  case BID_FEEDBACK_BY_GAME:
    update_feedback_by_game ();
    break;

  case BID_PROCESS_FEEDBACK_BY_GAME:
    if (! process_feedback_by_game ())
      update_feedback_by_game ();
    else
      show_bid_feedback_summary ();
    break;

  case BID_FEEDBACK_BY_ENTRY:
    show_bid_feedback_entry_form();
    break;

  case BID_FEEDBACK_PROCESS_ENTRY:
    if (! process_feedback_for_entry())
      show_bid_feedback_entry_form();
    else
      show_bid_feedback_summary();
    break;

  case BID_FEEDBACK_BY_CONCOM:
    show_bid_feedback_by_user_form();
    break;

  case BID_FEEDBACK_PROCESS_BY_CONCOM:
    if (! process_feedback_for_user())
      show_bid_feedback_by_user_form();
    else
      show_bid_feedback_summary();
    break;

  default:
    display_error ("Unknown action code: $action");
}

// Add the postamble

html_end ();

/*
 * form_players_entry
 *
 * Display an entry that lets the user select the min, max and preferred
 * numbers of characters
 */

function form_players_entry ($gender, $showword)
{
  $min = 'MinPlayers' . $gender;
  $max = 'MaxPlayers' . $gender;
  $pref = 'PrefPlayers' . $gender;

  if (array_key_exists ($min, $_POST))
    $min_value = $_POST[$min];
  else
    $min_value = '0';

  if (array_key_exists ($max, $_POST))
    $max_value = $_POST[$max];
  else
    $max_value = '0';

  if (array_key_exists ($pref, $_POST))
    $pref_value = $_POST[$pref];
  else
    $pref_value = '0';

  print ("  <tr>\n");
  if ($showword)
    print ("    <td align=\"right\">$gender Characters:</td>\n");
  else
    print ("    <td align=\"right\">Characters:</td>\n");
  print ("    <td align=\"left\">\n");
  printf ("      Min:<INPUT TYPE=TEXT NAME=%s SIZE=3 MAXLENGTH=3 VALUE=\"%s\">&nbsp;&nbsp;&nbsp;\n",
	  $min,
	  $min_value);
  printf ("      Preferred:<INPUT TYPE=TEXT NAME=%s SIZE=3 MAXLENGTH=3 VALUE=\"%s\">&nbsp;&nbsp;&nbsp;\n",
	 $pref,
	 $pref_value);
  printf ("      Max:<INPUT TYPE=TEXT NAME=%s SIZE=3 MAXLENGTH=3 VALUE=\"%s\">\n",
	  $max,
	  $max_value);
  print ("    </TD>\n");
  print ("  </tr>\n");
}

/*
 * form_combat
 *
 * Display the combat selections for the user and let him modify them.
 * If a value has already been chosen, set the selected value to it
 */

function form_combat ($key, $display)
{
  if (! isset ($_POST[$key]))
    $value = 'NonPhysical';
  else
  {
    $value = trim ($_POST[$key]);
    if (1 == get_magic_quotes_gpc())
      $value = stripslashes ($value);
  }

  $physical = '';
  $nonphysical = '';
  $nocombat = '';
  $other = '';

  switch ($value)
  {
    case 'Physical':    $physical    = 'selected'; break;
    case 'NonPhysical': $nonphysical = 'selected'; break;
    case 'NoCombat':    $nocombat    = 'selected'; break;
    case 'Other':       $other       = 'selected'; break;
  }

  echo "  <TR>\n";
  echo "    <TD COLSPAN=2>\n";
  echo "      $display:\n";
  echo "      <SELECT NAME=$key SIZE=1>\n";
  echo "        <option value=Physical $physical>Physical Methods (such as boffer weapons)</option>\n";
  echo "        <option value=NonPhysical $nonphysical>Non-Physical Methods (cards, dice, etc.)</option>\n";
  echo "        <option value=NoCombat $nocombat>There will be no combat</option>\n";
  echo "        <option value=Other $other>Other (describe in Other Details)</option>\n";
  echo "      </SELECT>\n";
  echo "    </TD>\n";
  echo "  </tr>   \n";
}

/*
 * form_bid_consensus
 *
 * Display the bid committee consensus selections for the user and let him
 * modify them.  If a value has already been chosen, set the selected value
 * to it
 */

function form_bid_consensus ($key, $display='')
{
  if ('' == $display)
    $display = $key . ':';

  if (! isset ($_POST[$key]))
    $value = 'Discuss';
  else
  {
    $value = trim ($_POST[$key]);
    if (1 == get_magic_quotes_gpc())
      $value = stripslashes ($value);
  }

  $early = '';
  $accept = '';
  $discuss = '';
  $reject = '';
  $drop = '';

  switch ($value)
  {
    case 'Accept':            $accept  = 'selected'; break;
    case 'Early Accepted':    $early   = 'selected'; break;
    case 'Discuss':           $discuss = 'selected'; break;
    case 'Reject':            $reject  = 'selected'; break;
    case 'Drop':              $drop    = 'selected'; break;
  }

  echo "  <TR>\n";
  echo "    <TD ALIGN=RIGHT>$display</TD>\n";
  echo "    <TD>\n";
  echo "      <SELECT NAME=$key SIZE=1>\n";
  echo "        <option value=Discuss $discuss>Discuss It</option>\n";
  echo "        <option value=Accept $accept>Accept It</option>\n";
  echo "        <option value=\"Early Accepted\" $early>Early Accepted It&nbsp;&nbsp;</option>\n";
  echo "        <option value=Reject $reject>Reject It</option>\n";
  echo "        <option value=Drop $drop>Drop It</option>\n";
  echo "      </SELECT>\n";
  echo "    </TD>\n";
  echo "  </tr>   \n";
}

/*
 * schedule_table_entry
 *
 * Display a drop-down list to allow the user to select whether he''s
 * willing to run his game in this time slot
 */

function schedule_table_entry ($key)
{
  //  echo "          <TD><INPUT TYPE=TEXT NAME=$key SIZE=1 MAXLENGTH=1 VALUE=\"$_POST[$key]\"></TD>\n";

  $mykey = str_replace ( ' ' , '_' , $key );

  if (! isset ($_POST[$mykey]))
    $value = '';
  else
  {
    $value = trim ($_POST[$mykey]);
    if (1 == get_magic_quotes_gpc())
      $value = stripslashes ($value);
  }

  $dont_care = '';
  $one = '';
  $two = '';
  $three = '';
  $no = '';

  switch ($value)
  {
    case '':  $dont_care = 'selected'; break;
    case '1': $one       = 'selected'; break;
    case '2': $two       = 'selected'; break;
    case '3': $three     = 'selected'; break;
    case 'X': $no        = 'selected'; break;
  }

  echo "          <TD>\n";
  echo "            <SELECT NAME=$mykey SIZE=1>\n";
  echo "              <option value=\"\" $dont_care>Don't Care&nbsp;</option>\n";
  echo "              <option value=1 $one>1st Choice&nbsp;</option>\n";
  echo "              <option value=2 $two>2nd Choice&nbsp;</option>\n";
  echo "              <option value=3 $three>3rd Choice&nbsp;</option>\n";
  echo "              <option value=X $no>Prefer Not&nbsp;</option>\n";
  echo "            </SELECT>\n";
  echo "          </TD>\n";
}

function validate_schedule_table_entry ($key, $display)
{
  $key = str_replace ( ' ' , '_' , $key );
  $value = trim ($_POST[$key]);

  switch ($value)
  {
    case '':
    case '1':      // 1, 2, 3 and X are all valid
    case '2':
    case '3':
    case 'X':
      return TRUE;

    case ' ':
      $_POST[$key] = '';
      return TRUE;

    case 'x':
      $_POST[$key] = 'X';
      return TRUE;
  }

  return display_error ("Invalid value \"$value\" for $display scheduling entry.  Valid values are 1, 2, 3 and X");
}

/*
 * show_text
 *
 * Display text in a two column form
 */

function show_text ($display, $value)
{
  echo "  <TR VALIGN=TOP>\n";
  echo "    <TD ALIGN=RIGHT NOWRAP><B>$display:</B></TD><TD ALIGN=LEFT>$value</TD>\n";
  echo "  </tr>\n";
}

function show_players ($array)
{
  $text = "<TABLE BORDER=1>\n";
  $text .= "  <TR ALIGN=CENTER>\n";
  $text .= "    <TD></TD>\n";
  $text .= "    <TD>Minimum</TD>\n";
  $text .= "    <TD>Preferred</TD>\n";
  $text .= "    <TD>Maximum</TD>\n";
  $text .= "  </tr>\n";
  $text .= "  <TR ALIGN=CENTER>\n";
  $text .= "    <TD ALIGN=RIGHT>Male</TD>\n";
  $text .= "    <TD>" . $array["MinPlayersMale"] . "</TD>\n";
  $text .= "    <TD>" . $array['PrefPlayersMale'] . "</TD>\n";
  $text .= "    <TD>" . $array['MaxPlayersMale'] . "</TD>\n";
  $text .= "  </tr>\n";

  $min = $array['MinPlayersMale'];
  $pref = $array['PrefPlayersMale'];
  $max = $array['MaxPlayersMale'];

  $text .= "  <TR ALIGN=CENTER>\n";
  $text .= "    <TD ALIGN=RIGHT>Female</TD>\n";
  $text .= "    <TD>" . $array['MinPlayersFemale'] . "</TD>\n";
  $text .= "    <TD>" . $array['PrefPlayersFemale'] . "</TD>\n";
  $text .= '    <TD>' . $array['MaxPlayersFemale'] . "</TD>\n";
  $text .= "  </tr>\n";

  $min += $array['MinPlayersFemale'];
  $pref += $array['PrefPlayersFemale'];
  $max += $array['MaxPlayersFemale'];

  $text .= "  <TR ALIGN=CENTER>\n";
  $text .= "    <TD ALIGN=RIGHT>Neutral</TD>\n";
  $text .= "    <TD>" . $array['MinPlayersNeutral'] . "</TD>\n";
  $text .= "    <TD>" . $array['PrefPlayersNeutral'] . "</TD>\n";
  $text .= "    <TD>" . $array['MaxPlayersNeutral'] . "</TD>\n";
  $text .= "  </tr>\n";

  $min += $array['MinPlayersNeutral'];
  $pref += $array['PrefPlayersNeutral'];
  $max += $array['MaxPlayersNeutral'];

  $text .= "  <TR ALIGN=CENTER>\n";
  $text .= "    <TD ALIGN=RIGHT>Total</TD>\n";
  $text .= "    <TD>$min</TD>\n";
  $text .= "    <TD>$pref</TD>\n";
  $text .= "    <TD>$max</TD>\n";
  $text .= "  </tr>\n";

  $text .= "</TABLE>";

  show_text ('Roles', $text);
}

function show_section ($text)
{
  echo "  <TR>\n";
  echo "    <TD COLSPAN=2><FONT SIZE=\"+1\"><HR><B>$text</B></FONT></TD>\n";
  echo "  </tr>\n";
}

function show_table_entry ($text)
{
  if ('' == $text)
     $text = '&nbsp;';

  echo "          <TD>$text</TD>\n";
}

/*
 * show_bid
 *
 * Display information about a bid in a read-only format
 */

function show_bid ()
{
  // Only bid committe members, the bid chair and the GM Liaison may access
  // this page

  if ((! user_has_priv (PRIV_BID_COM)) &&
      (! user_has_priv (PRIV_BID_CHAIR)) &&
      (! user_has_priv (PRIV_GM_LIAISON)))
    return display_access_error ();

  $BidId = intval (trim ($_REQUEST['BidId']));

  $sql = 'SELECT * FROM Bids WHERE BidId=' . $BidId;
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Query for BidId $BidId failed");

  if (0 == mysql_num_rows ($result))
    return display_error ("Failed to find bid $BidId");

  $bid_row = mysql_fetch_assoc ($result);

  // If the UserId is valid use that to override any user information

  $UserId = $bid_row['UserId'];
  if (0 != $UserId)
  {
    $sql = 'SELECT * FROM Users WHERE UserId=' . $UserId;
    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ("Query for UserId $UserId failed");

    if (0 == mysql_num_rows ($result))
      return display_error ("Failed to find user $UserId");

    $user_row = mysql_fetch_assoc ($result);
    foreach ($user_row as $key => $value)
      $bid_row[$key] = $value;
  }

  // If the EventId is valid, use that to override any game information

  $EventId = $bid_row['EventId'];
  if (0 != $EventId)
  {
    $sql = 'SELECT * FROM Events WHERE EventId=' . $EventId;
    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ("Query for EventId $EventId failed");

    if (0 == mysql_num_rows ($result))
      return display_error ("Failed to find event $EventId");

    $event_row = mysql_fetch_assoc ($result);
    foreach ($event_row as $key => $value)
      $bid_row[$key] = $value;
  }

  //Get the Bid Preferred Slot Info
  $sql = 'SELECT * FROM BidTimes WHERE BidId=' . $BidId;
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Query for BidId $BidId failed");

  $bid_pref_slots = array();
  while ($row = mysql_fetch_assoc($result)) {
    $bid_pref_slots[$row['Day'].$row['Slot']] = $row['Pref'];
	}

  echo "<TABLE BORDER=0 WIDTH=\"100%\">\n";
  echo "  <TR VALIGN=TOP>\n";
  echo "    <TD>\n";
  printf ("      <FONT SIZE=\"+2\"><B>%s</B></FONT>\n", $bid_row['Title']);
  echo "    </TD>\n";

  // Bid chair & GM Liaison can edit bids

  if (user_has_priv (PRIV_BID_CHAIR) || user_has_priv (PRIV_GM_LIAISON))
  {
    echo "    <TD>\n";
    printf ('      [<A HREF=Bids.php?action=%d&BidId=%d>Edit Bid</A>]',
	    BID_GAME,
	    $BidId);
    echo "    </TD>\n";
  }
  echo "  </tr>\n";
  echo "</TABLE>\n";

  echo "<TABLE BORDER=0>\n";

  show_section ('Submitter Information');
  show_text ('Submitter',
		     $bid_row['FirstName'].' '. $bid_row['LastName']);
  $text = $bid_row['Address1'];
  if ('' != $bid_row['Address2'])
    $text .= '<BR>' . $bid_row['Address2'];
  $text .= '<BR>' . $bid_row['City'] . ', ' . $bid_row['State'] . '  ' . $bid_row['Zipcode'];
  if ('' != $bid_row['Country'])
    $text .= '<BR>' . $bid_row['Country'];

  show_text ('Address', $text);
  show_text ('EMail', $bid_row['EMail']);
  show_text ('Daytime Phone', $bid_row['DayPhone']);
  show_text ('Evening Phone', $bid_row['EvePhone']);
  show_text ('Best Time To Call', $bid_row['BestTime']);
  show_text ('Preferred Contact', $bid_row['PreferredContact']);
  show_text ('Other LARPs', $bid_row['OtherGames']);

  show_section ('Game Information');

  show_text ('Author(s)', $bid_row['Author']);
  show_text ('GM(s)', $bid_row['GMs']);
  show_text ('Organization', $bid_row['Organization']);
  show_text ('Homepage', $bid_row['Homepage']);
  show_text ('Game EMail', $bid_row['GameEMail']);

  show_players ($bid_row);

  show_text ('Genre', $bid_row['Genre']);
  show_text ('Ongoing Campaign', $bid_row['OngoingCampaign']);
/*  show_text ('LARPA Small Game<br>Contest Entry',
	     $bid_row['IsSmallGameContestEntry']); */
  show_text ('Additional<br />Background Info', $bid_row['Premise']);
  show_text ('Run Before', $bid_row['RunBefore']);
  show_text ('Game System', $bid_row['GameSystem']);
  show_text ('Combat Resolution', $bid_row['CombatResolution']);
  show_text ('Space Requirements', $bid_row['SpaceRequirements']);

  show_section ('Game Restrictions');

  show_text ('Offensive', $bid_row['Offensive']);
  show_text ('Physical Restrictions', $bid_row['PhysicalRestrictions']);
  show_text ('Age Appropriate', $bid_row['AgeAppropriate']);

  show_section ('Scheduling Information');

  global $CON_DAYS;
  global $BID_SLOTS;
  global $BID_SLOT_ABBREV;

  echo "  <TR VALIGN=TOP>\n";
  echo "    <TD ALIGN=RIGHT><B>Preferred Slots:</B></TD>\n";
  echo "    <TD>\n";
  echo "      <TABLE BORDER=1>\n";
  echo "        <TR ALIGN=CENTER>\n";
  foreach ($CON_DAYS as $day)
  	echo "          <TD COLSPAN=".count($BID_SLOTS[$day]).">{$day}</TD>\n";
  echo "        </tr>\n";
  echo "        <TR ALIGN=CENTER>\n";
  foreach ($CON_DAYS as $day)
  	foreach ($BID_SLOTS[$day] as $slot)
  		echo "          <TD>".$BID_SLOT_ABBREV[$slot]."</TD>\n";
  echo "        </tr>\n";
  echo "        <TR ALIGN=CENTER>\n";
  foreach ($CON_DAYS as $day)
  	foreach ($BID_SLOTS[$day] as $slot)
  		if (isset($bid_pref_slots[$day.$slot]))
  			show_table_entry ($bid_pref_slots[$day.$slot]);
  		else
  			show_table_entry ('&nbsp;');
  echo "        </tr>\n";
  echo "      </TABLE>\n";
  echo "    </TD>\n";
  echo "  </tr>\n";

  show_text ('Hours', $bid_row['Hours']);
  show_text ('Multiple Runs', $bid_row['MultipleRuns']);
  show_text ('Can Play Concurrently', $bid_row['CanPlayConcurrently']);
  show_text ('Other Constraints', $bid_row['SchedulingConstraints']);
  show_text ('Setup/Teardown<br />Requirements', $bid_row['SetupTeardown']);

  show_section ('Game Descriptions');
  //  show_text ('Tweetable Blurb', $bid_row['ShortSentence']);
  show_text ('Short Blurb', $bid_row['ShortBlurb']);
  show_text ('Description', $bid_row['Description']);
  show_text ('Player Communications', $bid_row['PlayerCommunications']);

  echo "</TABLE>\n";
  echo "<P>\n";
}

/*
 * form_text_one_col
 *
 * Add a text input field to a 2 column form, but do it in a single column
 */

function form_text_one_col ($size, $display, $key='', $maxsize=0, $required=FALSE)
{
  // If not specified, fill in default values

  if ('' == $key)
    $key = $display;

  if (0 == $maxsize)
    $maxsize = $size;

  if ("" != $display)
    $display .= ":";

  // If this is a required field, make sure it has a leading '*'

  if ($required)
    $display = '<FONT COLOR=RED>*</FONT>&nbsp;' . $display;

  // If magic quotes are on, strip off the slashes

  if (! array_key_exists ($key, $_POST))
    $text = '';
  else
  {
    if (1 == get_magic_quotes_gpc())
      $text = stripslashes ($_POST[$key]);
    else
      $text = $_POST[$key];
  }

  // Spit out the HTML

  echo "  <TR>\n";
  echo "    <TD COLSPAN=2>\n";
  echo "      &nbsp;<BR>$display<BR>\n";
  printf ("    <INPUT TYPE=TEXT NAME=%s SIZE=%d MAXLENGTH=%d VALUE=\"%s\">\n",
	  $key,
	  $size,
	  $maxsize,
	  $text);
  echo "    </TD>\n";
  echo "  </tr>\n";
}

/**
 * display_choose_form
 */

function display_choose_form ()
{
  // Make sure that the user is logged in

  if (! isset ($_SESSION[SESSION_LOGIN_USER_ID]))
    return display_error ('You must login before submitting a game proposal');

  display_header ('Propose an event for ' . CON_NAME);

  echo ("<p>Before you can propose an event for ". CON_NAME );
  echo (", we need to know what type of event it is.</p>\n");

  echo "<form method=\"POST\" action=\"Bids.php\">\n";

  echo "<TABLE BORDER=0>\n";
  form_add_sequence ();

  form_hidden_value ('action', BID_GAME);

  //  form_game_type ('What is your event?', 'GameType');

  echo "<tr><td>&nbsp;</td></tr>\n";

  form_submit ('Continue');

  echo "</TABLE>\n";
  echo "</FORM>\n";
}

/*
 * display_bid_form
 *
 * Display the form the user has to fill out to bid a game
 */

function display_bid_form ($first_try)
{
  $EditGameInfo = 1;

  global $BID_TYPES;

  // Make sure that the user is logged in

  if (! isset ($_SESSION[SESSION_LOGIN_USER_ID]))
    return display_error ('You must login before submitting a game proposal');

  // If we're updating a bid, grab the bid ID

  if (empty ($_REQUEST['BidId']))
    $BidId = 0;
  else
    $BidId = intval (trim ($_REQUEST['BidId']));

  if (! array_key_exists ('GameType', $_POST))
      $gametype = $BID_TYPES[0];
  else
      $gametype = $_POST['GameType'];

  // Output the note about comps, so nobody can say that they didn't
  // see it


  if (BID_SHOW_COMPS)
  {
	  echo "<div style=\"border-style: solid; border-color: red; padding: 1ex; margin-bottom: 2ex\">\n";
	  echo "<b>Important Note:</b><br>\n";
	  echo "Each game that is accepted for " . CON_NAME . " gets <em>two</em>\n";
	  echo "(2) comped memberships to the convention. These free memberships\n";
	  echo "can be assigned by the GMs for that game to any attendee of \n";
	  echo CON_NAME . ".</p><p>" . CON_NAME . " runs on a very tight budget. We'd like\n";
	  echo " to comp every GM, but it is not financially possible for us to\n";
	  echo "comp more than two people for each game. If you need six GMs for\n";
	  echo "your game, at least four of them will have to buy memberships to\n";
	  echo "attend the convention.</p>\n";
	  echo "</div>\n";
  }


  // If this is a new bid, just display the header

  if (0 == $BidId)
  {
    if ($gametype == 'Other')
        display_header ("Propose an event for " . CON_NAME);
    else
        display_header ("Propose a {$gametype} for " . CON_NAME);
  }
  else
  {
    // If this is the first try, and we're updating an existing bid,
    // load the $_POST array from the database

    if ($first_try)
    {
      $sql = "SELECT * FROM Bids WHERE BidId=$BidId";
      $result = mysql_query ($sql);
      if (! $result)
		return display_mysql_error ("Query failed for BidId $BidId");

      if (0 == mysql_num_rows ($result))
		return display_error ("Failed to find BidId $BidId");

      if (1 != mysql_num_rows ($result))
		return display_error ("Found multiple entries for BidId $BidId");

      $row = mysql_fetch_array ($result, MYSQL_ASSOC);

      foreach ($row as $key => $value)
      {
        if (1 == get_magic_quotes_gpc())
          $_POST[$key] = mysql_real_escape_string ($value);
        else
          $_POST[$key] = $value;
      }

      //Also get the bid slot data.
      $sql = "SELECT * FROM BidTimes WHERE BidId=$BidId;";
      $result = mysql_query ($sql);
      if (! $result)
		return display_mysql_error ("BidTimes query failed for BidId $BidId");

	  while ($row = mysql_fetch_array ($result, MYSQL_ASSOC))
	  {
	  	 $key = $row['Day'].'_'.$row['Slot'];
  		 $key = str_replace ( ' ' , '_' , $key );
		 $_POST[$key] = mysql_real_escape_string ($row['Pref']);
	  }
	  mysql_free_result ($result);

      // If the user or game IDs are in the record, then have the user
      // modify them using the Edit User or Edit Game links

      $EventId = $row['EventId'];

      if (0 == $EventId)
	$EditGameInfo = 1;
      else
	$EditGameInfo = 0;
      //      $EditGameInfo = (0 == $EventId);
    }

    // Only the Bid Chair, GM Liaison or the bidder can update this bid

    $can_update =
      user_has_priv (PRIV_BID_CHAIR) ||
      user_has_priv (PRIV_GM_LIAISON) ||
      ($_SESSION[SESSION_LOGIN_USER_ID] == $_POST['UserId']);

    if (! $can_update)
      return display_access_error ();

    display_header ('Update bid for <I>' . $_POST['Title'] . '</I>');
  }

  echo "<form method=\"POST\" action=\"Bids.php\">\n";
  form_add_sequence ();
  form_hidden_value ('action', BID_PROCESS_FORM);
  form_hidden_value ('BidId', $BidId);
  form_hidden_value ('EditGameInfo', $EditGameInfo);

  echo "<p><font color=red>*</font> indicates a required field\n";
  echo "<TABLE BORDER=0>\n";

  //  $thingstring = strtolower($gametype);
  $thingstring = $gametype;
  if ($gametype == 'Other')
    $thingstring = 'event';

  if ($gametype == 'Other')
    form_section ('Event Information', TRUE, 'This will be shown on the Public description of the game.');
  else if ($gametype == 'Panel')
    form_section ('Panel Information', TRUE, 'This will be shown on the Public description of the game.');
  else
    form_section ('Game Information', TRUE, 'This will be shown on the Public description of the game.');

  form_hidden_value ('GameType', $gametype);

  if (! $EditGameInfo)
  {
    form_hidden_value ('Title', $_POST['Title']);
    echo "  <tr>\n";
    echo "    <td colspan=\"2\">\n";
    echo "The event has been accepted and is already in the Events table.\n";
    printf ("Click <a href=\"Schedule.php?action=%d&EventId=%d\" target=_blank>here</a>",
	    EDIT_GAME,
	    $EventId);
    echo " if you want to modify the event information.\n";
    echo "    </td>\n";
    echo "  </tr>\n";
  }
  else
  {
    form_text (64, 'Event Title', 'Title', 128, TRUE);
    if ($gametype == 'LARP')
        form_text (64, 'Author(s)', 'Author', 128, TRUE);
    else
        form_hidden_value ('Author', 'X');
    form_text (64, 'Organization');
    form_text (64, 'Event or organization homepage', 'Homepage', 128);
    form_text (64, 'EMail for event inquiries', 'GameEMail', 0, TRUE);
    form_text (2, 'Event Length', 'Hours', 0, TRUE,
	       '(Hours) - This is time that players will be participating in the LARP, from introduction to game wrap.');

    $text = "<b>Description</b> for use on the " . CON_NAME . " website.\n";
    $text .= "This information will displayed \n";
    $text .= "on the page users see for the game.  The description should be at least\n";
    $text .= "a couple of paragraphs, but can be as long as you like.</p>\n";
    $text .= "<p>The game description is used to promote your game and attract players who will enjoy it. Be reasonably clear on where and when the game is set, and what the game is about. Let your players know what can they expect to be doing during the LARP, and make them excited to play your game! (We can offer suggestions if you would like advice on this.)</p>\n";
    $text .= "<p><b>Per NEIL policy, game descriptions must include either a\n";
    $text .= "content warning or an explicit statement that no content warnings\n";
    $text .= "are applicable.</b> For more information,\n";
    $text .= "<a href=\"http://interactiveliterature.org/NEIL/communityPolicies.html#contentWarningsPolicy\" target=\"_blank\">see the NEIL policies page</a>.</p>";
    $text .= "<p>Please also include the preferred ages of players for your larp. Examples are \"Players must be 18 or older\", or \"players under 16 must check with the GMs before playing\", to \"children at least [age] years old are welcome in this game\".</p>\n";
    $text .= "<p>The description will be displayed in the user's browser.\n";
    $text .= "You must use HTML tags for formatting.  A quick primer on\n";
    $text .= "a couple of useful HTML tags is available\n";
    if (file_exists('HtmlPrimer.html'))
        $text .= "<A HREF=HtmlPrimer.html TARGET=_blank>here</A>.\n";
    else
        $text .= "<A HREF=".TEXT_DIR."/HtmlPrimer.html TARGET=_blank>here</A>.\n";
    form_textarea ($text, 'Description', 15, TRUE, TRUE);

    $text = "A <b>Short Blurb</b> (50 words or less) for the game to be\n";
    $text .= "used for the List of Events page and the convention\n";
    $text .= "program. Information in the Short Blurb must also be present in the (full) description!</p>\n";
    $text .= "<p>The short blurb will be displayed in the user's browser.\n";
    $text .= "You must use HTML tags for formatting.  A quick primer on\n";
    $text .= "a couple of useful HTML tags is available\n";
    if (file_exists('HtmlPrimer.html'))
        $text .= "<A HREF=HtmlPrimer.html TARGET=_blank>here</A>.\n";
    else
        $text .= "<A HREF=".TEXT_DIR."/HtmlPrimer.html TARGET=_blank>here</A>.\n";
    form_textarea ($text, 'ShortBlurb', 4, TRUE, TRUE);

    $text = "<b>Player Communications</b><br>\n";
    $text .= "How will you distribute game information to your players? Will you be using a casting form? Will character roles be cast and distributed before the convention or on site, or will characters be developed as part of the game?\n";
    form_textarea($text, 'PlayerCommunications', 4, TRUE, TRUE);

    if ($gametype != 'Panel')
    {
      form_section ('Character Counts', TRUE, 'This will be shown on the Public description of the game.');
      echo "  <tr>\n";
      echo "    <td colspan=2>\n";
      echo "Enter the minimum, preferred and maximum number of characters\n";
      echo "for your $thingstring. The character counts will be visible\n";
      echo "to users signing up to your $thingstring<br>\n";
      echo "<table>\n";
      echo "  <tr>\n";
      echo "    <td valign=\"top\"><i>Minimum&nbsp;-</i></td>\n";
      echo "    <td>The minimum number of characters required for\n";
      echo "your {$thingstring}.  If there are fewer than the minimum\n";
      echo "number of characters signed up, you should talk with the GM\n";
      echo "Liaison about lowering the minimum number of players or\n";
      echo "cancelling the {$thingstring}.</td>\n";
      echo "  </tr>\n";
      echo "  <tr>\n";
      echo "    <td valign=\"top\"><i>Preferred&nbsp;-</i></td>\n";
      echo "    <td>The number of characters that you'd prefer\n";
      echo "to have in your {$thingstring}.  If you're not sure, make this\n";
      echo "the same number as the Maximum.</td>\n";
      echo "  </tr>\n";
      echo "  <tr>\n";
      echo "    <td valign=\"top\"><i>Maximum&nbsp;-</i></td>\n";
      echo "    <td>The maximum number of players that your {$thingstring}\n";
      echo "can accomodate.</td>\n";
      echo "  </tr>\n";
      echo "</table>\n";
      if ($gametype != 'Board Game')
      {
	echo "Each of your characters can be male, female or neutral.  A\n";
	echo "<i>male</i> or <i>female</i> character is one which must\n";
	echo "be a specific gender.  A <i>neutral</i> character is one\n";
	echo "which can be cast as either male or female, depending on\n";
	echo "who signs up for the game.  The website will enforce your\n";
	echo "gender limits, so if only female roles are available, any\n";
	echo "players that signup who have specified that they prefer\n";
	echo "to play male characters will be put on the waitlist.  Once\n";
	echo "you've cast the game, you'll be able to &quot;freeze&quot;\n";
	echo "the gender balance of the game; essentially converting all\n";
	echo "of your neutral characters to male or female to match the\n";
	echo "preferred character gender of the players who are signed\n";
	echo "up.  This way, if a player drops out, the website will\n";
	echo "pick the first player on the waitlist with a matching\n";
	echo "preferred character gender so you don't have to\n";
	echo "frantically rewrite the character sheet to match the\n";
	echo "preferred character gender of the new player.<br>&nbsp;\n";
	echo "    </td>\n";
	echo "  </tr>\n";

	form_players_entry ('Male', true);
	form_players_entry ('Female', true);
	form_players_entry ('Neutral',true);
      }
      else
      {
	form_hidden_value ('MinPlayersMale', 0);
	form_hidden_value ('MaxPlayersMale', 0);
	form_hidden_value ('PrefPlayersMale', 0);
	form_hidden_value ('MinPlayersFemale', 0);
	form_hidden_value ('MaxPlayersFemale', 0);
	form_hidden_value ('PrefPlayersFemale', 0);
	form_players_entry ('Neutral',false);
      }
    }
    else
    {
      form_hidden_value ('MinPlayersMale', 0);
      form_hidden_value ('MaxPlayersMale', 0);
      form_hidden_value ('PrefPlayersMale', 0);
      form_hidden_value ('MinPlayersFemale', 0);
      form_hidden_value ('MaxPlayersFemale', 0);
      form_hidden_value ('PrefPlayersFemale', 0);
      form_hidden_value ('MinPlayersNeutral', 0);
      form_hidden_value ('MaxPlayersNeutral', 0);
      form_hidden_value ('PrefPlayersNeutral', 0);
    }
  }

  if ($gametype == 'Other')
    form_section ('Other Event Information', TRUE, 'This information will only be used by the Proposals Committee.');
  else if ($gametype == 'Panel')
    form_section ('Other Panel Information', TRUE, 'This information will only be used by the Proposals Committee.');
  else
    form_section ('Other Game Information', TRUE, 'This information will only be used by the Proposals Committee.');

  if ($gametype == 'LARP' || $gametype == 'Tabletop RPG')
  {
    form_text (64, 'Genre', '', 0, TRUE);
    form_yn ('Is this game part of an ongoing campaign?', 'N');
  }
  else
  {
    form_hidden_value ('Genre', 'X');
    form_hidden_value ('OngoingCampaign', 'N');
  }

  echo "  <tr>\n";
  echo "    <td colspan=2>\n";
  echo "&nbsp;<br>\n";
/*  echo "<a href=\"http://www.larpaweb.net/larpa-contest-mainmenu-35\" target=\"_blank\">The\n";
  echo "LARPA Small Game Contest</a> is a chance for you to win cash for\n";
  echo "your game!\n";
  echo "    </td>\n";
  echo "  </tr>\n";
  form_yn ('Is this game entered in the LARPA Small Game Contest?',
	   'IsSmallGameContestEntry');
*/
  if ($gametype == 'LARP' || $gametype == 'Tabletop RPG')
  {
    $text = CON_NAME ." is looking for games that are new and games that\n";
    $text .= "have run before, either at a convention, or elsewhere.\n";
    $text .= "If this game has run before, where was that?";
    form_text_one_col (80, $text, 'RunBefore', 128);
  }
  else
  {
    form_hidden_value ('RunBefore', '');
  }

  if ($gametype == 'LARP' || $gametype == 'Tabletop RPG')
  {
    form_text (64, 'Game System', 'GameSystem', 128);
  }
  else if ($gametype == 'Board Game')
  {
    form_text (64, 'Game Name', 'GameSystem', 128);
  }
  else
  {
    form_hidden_value ('GameSystem', '');
  }

  if ($gametype == 'LARP')
  {
    form_combat ('CombatResolution', 'How combat will be resolved');
  }
  else
  {
    form_hidden_value ('CombatResolution', 'NoCombat');
  }

  if ($gametype != 'Board Game')
  {
      form_textarea ('Please enter any additional background information here, or any other information you wish to tell the Bid Committee. This information will be shown only to the Bid Committee.', 'Premise', 5, TRUE, TRUE);
      form_textarea ("Are there any special setup or teardown requirements for this $thingstring? For example, do you need extra time to set up or tear down a complex set? (Requests for standard furniture will be handled separately closer to the convention. Intercon can not provide unusual setup materials for your game.)", 'SetupTeardown', 5);
  }
  else
  {
    form_hidden_value ('Premise', 'X');
    form_hidden_value ('SetupTeardown', 'X');
  }

  form_section ('GMs/Author Information', TRUE, 'This information will only be used by the Proposals Committee.');

  if ($gametype == 'LARP' || $gametype == 'Tabletop RPG')
  {
    $text = "<b>GMs for your game.</b>  Note that the GMs listed here are\n";
    $text .= "only for the purpose of evaluating your proposal. If your\n";
    $text .= "proposal is accepted, you'll be able to select GMs from the\n";
    $text .= "users registered for" . CON_NAME . ".\n";
    /*$text .= "Each accepted bid is allowed two comp'd attendees\n";
     $text .= "for the con.  You will be responsible for determing which of\n";
     $text .= "your GMs will be comp'd.\n";*/
    form_textarea ($text, 'GMs', 2);
  }
  else
  {
    $text = "<b>Event runners.</b>  Note that the people listed here are only for\n";
    $text .= "the purpose of evaluating your bid.  If your bid is accepted,\n";
    $text .= "you'll be able to select event runners from the users registered for\n";
    $text .= CON_NAME . ".\n";
      /*$text .= "Each accepted bid is allowed two comp'd attendees\n";
      $text .= "for the con.  You will be responsible for determing which of\n";
      $text .= "your runners will be comp'd.\n";*/
    form_textarea ($text, 'GMs', 2);
  }

  if ($gametype == 'LARP')
  {
    form_textarea ('What other LARPs have your written or run?  Where and when were they run? This information will only be shown to the Bid Committee.',
		   'OtherGames', 5);
  }
  else if ($gametype == 'Panel')
  {
    form_textarea ('What other panels have you organized?  What is your basis of expertise in this area?',
		   'OtherGames', 5);
  }
  else
  {
      form_hidden_value ('OtherGames', '');
  }

  form_section ('Restrictions', TRUE, 'This information will only be used by the Proposals Committee.');

  echo "  <TR>\n";
  echo "    <TD COLSPAN=2>\n";
  echo "     ".CON_NAME." appeals to a diverse group of players of all ages,\n";
  echo "     ethnicities, belief systems, sexual preferences, physical\n";
  echo "     capabilities, experience, etc.  Authors can write\n";
  echo "     interesting games that might not be suitable for all audiences.\n";
  echo "     In order for the con staff to balance these potentially\n";
  echo "     opposing requirements, please answer the following questions.\n";
  echo "     <p>\n";
  echo "     Note that answering yes to any or all of these questions\n";
  echo "     does not disqualify your proposal.  ";
  /*echo CON_NAME." has run several great\n";
  echo "     games that push these boundries and will continue to do so.\n";*/
  echo "    </TD>\n";
  echo "  </tr>\n";

  $text = "Are there any components of your {$thingstring} that\n";
  $text .= "might offend or upset some group of attendees? For\n";
  $text .= "example, adult themes, potentially offensive story arcs,\n";
  $text .= "etc.  If so, please explain and consider if this needs to\n";
  $text .= "be mentioned in the game descriptions.\n";
  form_textarea ($text, 'Offensive', 5);

  if ($gametype == 'LARP' || $gametype == 'Other')
  {
    $text = "Are there any physical restrictions imposed by your\n";
    $text .= "{$thingstring}?  For example, live boffer combat,\n";
    $text .= "confined sets, etc.  If so, please explain and consider\n";
    $text .= "if this needs to be mentioned in the game descriptions.";
    form_textarea ($text, 'PhysicalRestrictions', 5);
  }
  else
      form_hidden_value ('PhysicalRestrictions', '');

  $text = "Is your game appropriate for players under the age of 18?\n";
  $text .= "Please discuss any age restrictions here. If there are\n";
  $text .= "any components of your {$thingstring} that might be\n";
  $text .= "illegal for attendees under the age of 18 (props or\n";
  $text .= "items that are illegal for a minor to possess, alcohol,\n";
  $text .= "etc.) please explain. If your game has age restrictions,\n";
  $text .= "please be sure to mention this in the game descriptions.\n";
  form_textarea ($text, 'AgeAppropriate', 5);

 if (ALLOW_EVENT_FEES)
    form_yn ("&nbsp;<BR>Do you wish to charge a fee for your {$thingstring}?  If so, con will be in touch to discuss this.",
           'Fee');
 else
    form_hidden_value ('Fee', 'N');

  form_section ('Scheduling Information', TRUE, 'This information will only be used by the Proposals Committee.');

  echo "  <tr>\n";
  echo "    <td colspan=\"2\">\n";
  echo "      <p>The con can schedule your game into one (or more) of\n";
  echo "      the time slots available over the weekend.  The con has\n";
  echo "      to put together a balanced schedule so we can satisfy\n";
  echo "      the most players in the most time slots.  Your\n";
  echo "      flexibility in scheduling your game is vital.</p>\n";
  echo "      <p>Please pick your top three preferences for when\n";
  echo "      you'd like to run your game.</p>\n";
  echo "    </td>\n";
  echo "  </tr>\n";

  global $CON_DAYS;
  global $BID_SLOTS;

  echo "  <tr>\n";
  echo "    <td colspan=\"2\">\n";
  echo "      <table border=\"1\">\n";
  echo "        <tr valign=\"bottom\">\n";
  echo "          <th></th>\n";
  foreach ($CON_DAYS as $day)
    echo "          <th>{$day}</th>\n";
  echo "        </tr>\n";
  foreach ($BID_SLOTS['All'] as $main_slot)
  {
    echo "        <tr align=\"center\">\n";
    echo "          <th>{$main_slot}</th>\n";
    foreach ($CON_DAYS as $day)
    {
     if (in_array($main_slot,$BID_SLOTS[$day]))
	schedule_table_entry ("{$day}_{$main_slot}");
      else
	echo "          <td>&nbsp;</td>\n";
    }
    echo "        </tr>\n";
  }
  echo "      </TABLE>\n";
  echo "    </TD>\n";
  echo "  </tr>\n";

  if ($gametype == 'LARP' || $gametype == 'Tabletop RPG' || $gametype == 'Board Game')
  {
      form_yn ("&nbsp;<BR>Can players play in your {$thingstring} and another event at the same time?",
               'CanPlayConcurrently');
  }
  else
  {
      form_yn ("&nbsp;<BR>Can participants attend your {$thingstring} and another event at the same time?",
               'CanPlayConcurrently');
  }

  form_yn ("Are you willing to hold this {$thingstring} more than once at this convention?",
	   'MultipleRuns');

  $text = "If you are willing to hold the LARP more than once, please discuss your preferences here.<br>\n";
  $text .= "In addition, if there are scheduling constraints on your LARP (for example, if are you proposing another event), or there are times your LARP cannot be scheduled, please discuss them as well.\n";
  form_textarea ($text, 'SchedulingConstraints', 5);

  form_textarea ('Space Requirements', 'SpaceRequirements', 2);

  /* Tweetable Blurb
  $text = "A short sentence for the {$thingstring} to be used to sell the {$thingstring} to the\n";
  $text .= "general public.  Include information about the genre.  For\n";
  $text .= "example, &quot;Members of a Vampire Cabal battle for control of\n";
  $text .= "Chelmsford&quot; is better than &quot;The Prince of\n";
  $text .= "Chelmsford is dead.  Who will take his place?&quot;";
  form_textarea ($text, 'ShortSentence', 2, TRUE, TRUE);
  */
  if (0 == $BidId)
    $text = 'Submit Bid';
  else
    $text = 'Update Bid';
  form_submit ($text);

  echo "</TABLE>\n";
  echo "</FORM>\n";
}


/*
 * validate_players
 *
 * Validate the number of players passed in
 */

function validate_players ($gender)
{
  // Build the indicies into the $_POST array appropriate for the specified
  // gender

  $min  = 'MinPlayers'  . $gender;
  $pref = 'PrefPlayers' . $gender;
  $max  = 'MaxPlayers'  . $gender;

  // Validate the individual numbers

  if (! (validate_int ($min, 0, 100, "Min $gender Players") &&
	 validate_int ($max, 0, 100, "Max $gender Players") &&
	 validate_int ($pref, 0, 100, "Preferred $gender Players")))
    return false;

  // If the user didn't fill in the preferred number, default it to the
  // maximum

  if (0 == $_POST[$pref])
    $_POST[$pref] = $_POST[$max];

  if ((int)$_POST[$min] > (int)$_POST[$pref])
    return display_error ("Min $gender Players must be less than or equal to Preferred $gender Players");

  if ((int)$_POST[$pref] > (int)$_POST[$max])
    return display_error ("Preferred $gender Players must be less than or equal to Max $gender Players");

  return true;
}

/*
 * process_bid_form
 *
 * Validate the bid information and write it to the Bids table
 */

function process_bid_form ()
{
  if (out_of_sequence ())
    return display_sequence_error (false);

  //dump_array ('$_POST', $_POST);

  // Make sure that the user is logged in

  if (! isset ($_SESSION[SESSION_LOGIN_USER_ID]))
    return display_error ('You must login before submitting a game proposal');

  $BidId = intval (trim ($_REQUEST['BidId']));
  $EditGameInfo = intval (trim ($_REQUEST['EditGameInfo']));

  //echo "EditGameInfo: $EditGameInfo<br>\n";

  //echo "BidId: $BidId<br>\n";

  //echo "GameType: ".$_REQUEST['GameType']."<br>\n";

  // Always hopeful...

  $form_ok = TRUE;

  // Event Information

  if ($EditGameInfo)
  {
    //    $form_ok &= validate_string ('GameType');
    $form_ok &= validate_string ('Title');
    $form_ok &= validate_string ('Author');
    $form_ok &= validate_string ('GameEMail', 'EMail for game inquiries');

    if (! (validate_players ('Male') &&
	   validate_players ('Female') &&
	   validate_players ('Neutral')))
      $form_ok = FALSE;

    $form_ok &= validate_int ('Hours', 1, 12, 'Hours');
    $form_ok &= validate_string ('Description');
    $form_ok &= validate_string ('ShortBlurb', 'Short blurb');
    $form_ok &= validate_string ('PlayerCommunications', 'Player communications info');
  }

  // Game Details

  $form_ok &= validate_string ('Genre');
  $form_ok &= validate_string ('Premise');

  // Scheduling Information
  global $CON_DAYS;
  global $BID_SLOTS;
  foreach ($CON_DAYS as $day)
  	foreach ($BID_SLOTS[$day] as $slot)
  		$form_ok &= validate_schedule_table_entry ("{$day}_{$slot}", "{$day} {$slot}");

  // Advertising Information

  //  $form_ok &= validate_string ('ShortSentence', 'Short sentence');

  // If any errors were found, abort now

  if (! $form_ok)
    return FALSE;

  // Make sure that we don't already have a game with this title
  $Title = trim ($_POST['Title']);

  if (!$EditGameInfo)
  {
    if (!title_not_in_events_table ($Title))
      return false;
  }

  // Sanity checks

  if (0 == $BidId)
  {
    if (! $EditGameInfo)
      return display_error ("BidId = 0 when EditGameInfo = $EditGameInfo");
  }

  $new_bid = (0 == $BidId);

  // If this is a new bid, create an entry in the bid table

  if ($new_bid)
  {
    $sql = 'INSERT Bids SET Created=NULL';
    $sql .= build_sql_string ('UserId', $_SESSION[SESSION_LOGIN_USER_ID]);

    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ("Insert into Bids failed");

    $BidId = mysql_insert_id();
  }

  // Now the event information

  if ($EditGameInfo)
  {
    $sql = 'UPDATE Bids SET ';
    $sql .= build_sql_string ('Title', $Title, false);
    $sql .= build_sql_string ('Author');
    $sql .= build_sql_string ('Homepage');
    $sql .= build_sql_string ('GameEMail');
    $sql .= build_sql_string ('Organization');
    $sql .= build_sql_string ('GameType');

    $sql .= build_sql_string ('MinPlayersMale');
    $sql .= build_sql_string ('MaxPlayersMale');
    $sql .= build_sql_string ('PrefPlayersMale');

    $sql .= build_sql_string ('MinPlayersFemale');
    $sql .= build_sql_string ('MaxPlayersFemale');
    $sql .= build_sql_string ('PrefPlayersFemale');

    $sql .= build_sql_string ('MinPlayersNeutral');
    $sql .= build_sql_string ('MaxPlayersNeutral');
    $sql .= build_sql_string ('PrefPlayersNeutral');

    $sql .= build_sql_string ('Hours');
    $sql .= build_sql_string ('CanPlayConcurrently');
    $sql .= build_sql_string ('Description', '', true, true);
    $sql .= build_sql_string ('ShortBlurb', '', true, true);
    $sql .= build_sql_string ('PlayerCommunications', '', true, true);

    $sql .= " WHERE BidId=$BidId";

//    echo "Event Info: $sql<P>\n";

    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ("Insert into Bids failed");
  }

  // Game details

  $sql = 'UPDATE Bids SET ';
  $sql .= build_sql_string ('Genre', '', FALSE);
  $sql .= build_sql_string ('OngoingCampaign');
/*  $sql .= build_sql_string ('IsSmallGameContestEntry'); */
  $sql .= build_sql_string ('GMs', '', true, true);
  $sql .= build_sql_string ('Premise', '', true, true);
  $sql .= build_sql_string ('RunBefore');
  $sql .= build_sql_string ('Fee');
  $sql .= build_sql_string ('GameSystem');
  $sql .= build_sql_string ('CombatResolution');
  $sql .= build_sql_string ('OtherGMs');
  $sql .= build_sql_string ('OtherGames', '', true, true);

  $sql .= " WHERE BidId=$BidId";

  //echo "Game Details: $sql<P>\n";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Insert into Bids failed");

  // Restrictions & Scheduling Information

  $sql = 'UPDATE Bids SET ';

  $sql .= build_sql_string ('Offensive', '', FALSE, TRUE);
  $sql .= build_sql_string ('PhysicalRestrictions', '', TRUE, TRUE);
  //  $sql .= build_sql_string ('AgeRestrictions', '', TRUE, TRUE);
  $sql .= build_sql_string ('AgeAppropriate', '', TRUE, TRUE);
  $sql .= build_sql_string ('SchedulingConstraints', '', TRUE, TRUE);
  $sql .= build_sql_string ('SpaceRequirements', '', TRUE, TRUE);
  $sql .= build_sql_string ('SetupTeardown', '', true, true);
  $sql .= build_sql_string ('MultipleRuns');
  $sql .= " WHERE BidId=$BidId";

  //echo "Restrictions and Scheduling: $sql<P>\n";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Insert into Bids failed");

  $sql = "DELETE from BidTimes WHERE BidId=$BidId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Delete from BidTimes failed");

  global $CON_DAYS;
  global $BID_SLOTS;
  foreach ($CON_DAYS as $day)
  	foreach ($BID_SLOTS[$day] as $slot) {
	  $sql = "INSERT into BidTimes (BidId, Day, Slot, Pref) values (";
	  $sql .= "{$BidId}, ";
	  $sql .= "'{$day}', ";
	  $sql .= "'{$slot}', ";
	  $sql .= "'";
	  $sql .= $_POST[str_replace(' ','_',"${day}_{$slot}")];
	  $sql .= "');";
	  $result = mysql_query ($sql);
	  if (! $result)
		return display_mysql_error ("Add {$day} {$slot} to BidTimes failed");

  	}

  // Advertising Information

  $sql = 'UPDATE Bids SET ';

  $sql .= build_sql_string ('ShortSentence', '', FALSE);
  $sql .= " WHERE BidId=$BidId";

  //echo "Advertising info: $sql <P>\n";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Insert into Bids failed");

  // Where are we sending this information?

  if (1 == DEVELOPMENT_VERSION)
    $send_to = 'barry@tannenbaum.mv.com';
  else
    $send_to = EMAIL_BID_CHAIR;

  // See who's doing this

  $sql = 'SELECT FirstName, LastName, EMail FROM Users WHERE UserId=';
  $sql .= $_SESSION[SESSION_LOGIN_USER_ID];
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Cannot query user information');

  // Sanity check.  There should only be a single row

  if (0 == mysql_num_rows ($result))
    return display_error ('Failed to find user information');

  $row = mysql_fetch_object ($result);

  $name = trim ("$row->FirstName $row->LastName");
  $email = $row->EMail;

  $Title = stripslashes (trim ($_POST['Title']));

  if ($new_bid)
  {
    $subject = '[' . CON_NAME . " - Bid] New: $Title";

    $msg = "The bid has been submitted by $name";
  }
  else
  {
    $subject = '[' . CON_NAME . " - Bid] Update: $Title";

    $msg = "The bid has been updated by $name";
  }

  $msg .= ' and is waiting for your review at ';
  $msg .= sprintf ('http://interactiveliterature.org/%s/Bids.php' .
		   '?action=%d&BidId=%d',
		   CON_ID,
		   BID_SHOW_BID,
		   $BidId);
  $msg .= ' . You must be logged in to see this bid.';

  //echo "subject: $subject<br>\n";
  //echo "message: $msg<br>\n";

  if (! intercon_mail ($send_to,
		       $subject,
		       $msg,
		       $email))
    display_error ('Attempt to send mail failed');

  return TRUE;
}

function table_value ($value)
{
  if ('' == trim ($value))
    return '&nbsp;';

  return $value;
}

/*
 * display_bids_for_review
 *
 * Display the bids for review
 */

function display_bids_for_review ()
{
  // Only bid committe members, the bid chair and the GM Liaison may access
  // this page

  if ((! user_has_priv (PRIV_BID_COM)) &&
      (! user_has_priv (PRIV_BID_CHAIR)) &&
      (! user_has_priv (PRIV_GM_LIAISON)))
    return display_access_error ();

  $order = 'Status, Title';
  $desc = 'Status';

  if (array_key_exists ('order', $_REQUEST))
  {
    switch ($_REQUEST['order'])
    {
      case 'Game':
	$order = 'Title';
        $desc = 'Game Title';
        break;

      case 'LastUpdated':
	$order = 'LastUpdated DESC';
        $desc = 'Last Updated';
        break;

      case 'Created':
	$order = 'Bids.Created DESC';
        $desc = 'Created';
        break;

      case 'Submitter':
	$order = 'LastName, FirstName, Title';
        $desc = 'Submitter';
        break;
    }
  }

  $sql = 'SELECT Bids.BidId, Bids.Title, Bids.Hours, Bids.Status,';
  $sql .= ' Users.EMail, Users.FirstName, Users.LastName,';
  $sql .= ' Bids.Organization, Bids.EventId, Bids.UserId,';
  $sql .= ' DATE_FORMAT(Bids.LastUpdated, "%H:%i <NOBR>%d-%b-%y</NOBR>") AS LastUpdatedFMT,';
  $sql .= ' DATE_FORMAT(Bids.Created, "%H:%i <NOBR>%d-%b-%y</NOBR>") AS CreatedFMT,';
  $sql .= ' Bids.MinPlayersMale+Bids.MinPlayersFemale+Bids.MinPlayersNeutral AS Min,';
  $sql .= ' Bids.MaxPlayersMale+Bids.MaxPlayersFemale+Bids.MaxPlayersNeutral AS Max,';
  $sql .= ' Bids.PrefPlayersMale+Bids.PrefPlayersFemale+Bids.PrefPlayersNeutral AS Pref';
  $sql .= ' FROM Bids, Users';
  $sql .= ' WHERE Users.UserId=Bids.UserId';
  $sql .= " ORDER BY $order";

  //  echo "SQL: $sql<p>\n";

  $result = mysql_query ($sql);
  if (! $result)
    display_mysql_error ('Query failed for bids');

  if (0 == mysql_num_rows ($result))
    display_error ('There are no bids to review');

  display_header ('Games Bid for ' . CON_NAME . ' by ' . $desc);

  echo "Click on the game title to view the bid<br>\n";
  echo "Click on the submitter to send mail\n";
  if (user_has_priv (PRIV_BID_CHAIR))
    echo "<br>Click on the status to change the status\n";
  echo "<p>\n";

  global $CON_DAYS;
  global $BID_SLOTS;
  global $BID_SLOT_ABBREV;

  $numslots = 0;
  foreach ($CON_DAYS as $day)
	$numslots += count($BID_SLOTS[$day]);

  echo "<table border=\"1\">\n";
  echo "  <tr valign=\"bottom\">\n";
  printf ("    <th rowspan=\"3\" align=\"left\">" .
	  "<a href=\"Bids.php?action=%d&order=Game\">Game</th>\n",
	  BID_REVIEW_BIDS);
  printf ("    <th rowspan=\"3\" align=\"left\">" .
	  "<a href=\"Bids.php?action=%d&order=Submitter\">Submitter</th>\n",
	  BID_REVIEW_BIDS);
  echo "    <TH COLSPAN=3>Size</TH>\n";
  echo "    <TH ROWSPAN=3>Hours</TH>\n";
  echo "    <TH COLSPAN={$numslots}>Preferred Slots</TH>\n";
  printf ("    <th rowspan=\"3\" align=\"left\">" .
	  "<a href=\"Bids.php?action=%d&order=Status\">Status</th>\n",
	  BID_REVIEW_BIDS);
  printf ("    <th rowspan=\"3\" align=\"left\">" .
	  "<a href=\"Bids.php?action=%d&order=LastUpdated\">LastUpdated</th>\n",
	  BID_REVIEW_BIDS);
  printf ("    <th rowspan=\"3\" align=\"left\">" .
	  "<a href=\"Bids.php?action=%d&order=Created\">Created</th>\n",
	  BID_REVIEW_BIDS);
  echo "  </tr>\n";

  echo "  <TR VALIGN=BOTTOM>\n";
  echo "    <TH ROWSPAN=2>Min</TH>\n";
  echo "    <TH ROWSPAN=2>Pref</TH>\n";
  echo "    <TH ROWSPAN=2>Max</TH>\n";

  foreach ($CON_DAYS as $day)
	echo "    <TH COLSPAN='".count($BID_SLOTS[$day])."'>".substr($day,0,3)."</TH>\n";
  echo "  </tr>\n";

  echo "  <TR VALIGN=BOTTOM>\n";
  foreach ($CON_DAYS as $day)
  	foreach ($BID_SLOTS[$day] as $slot)
  		echo "          <TH>".$BID_SLOT_ABBREV[$slot]."</TH>\n";
  echo "  </TR>\n";

  while ($row = mysql_fetch_object ($result))
  {
    // Determine the background color for this row

    switch ($row->Status)
    {
      case 'Pending':        $bgcolor = '#FFFFCC'; break;
      case 'Under Review':   $bgcolor = '#DDDDFF'; break;
      case 'Accepted':       $bgcolor = '#CCFFCC'; break;
      case 'Rejected':       $bgcolor = '#FFCCCC'; break;
      case 'Dropped':        $bgcolor = '#FFCC99'; break;
      default:               $bgcolor = '#FFFFFF'; break;
    }

    // If we've got a UserId, fetch the name for the Users record and
    // override any information from the Bid record

    if (0 != $row->UserId)
    {
      $sql = "SELECT FirstName, LastName, EMail";
      $sql .= " FROM Users WHERE UserId=$row->UserId";
      $user_result = mysql_query ($sql);
      if (! $user_result)
	echo "<!-- Query failed for user $row->UserId -->\n";
      else
      {
	if (1 != mysql_num_rows ($user_result))
	  echo "<!-- Unexpected number of rows for user $row->UserId -->\n";
	else
	{
	  $user_row = mysql_fetch_object ($user_result);
	  $row->FirstName = $user_row->FirstName;
	  $row->LastName = $user_row->LastName;
	  $row->EMail = $user_row->EMail;
	}

	mysql_free_result ($user_result);
      }
    }

    // If we've got an EventId, fetch the name for the Users record and
    // override any information from the Bid record

    if (0 != $row->EventId)
    {
      $sql = "SELECT ";
      $sql .= ' MinPlayersMale+MinPlayersFemale+MinPlayersNeutral AS Min,';
      $sql .= ' MaxPlayersMale+MaxPlayersFemale+MaxPlayersNeutral AS Max,';
      $sql .= ' PrefPlayersMale+PrefPlayersFemale+PrefPlayersNeutral AS Pref';
      $sql .= " FROM Events WHERE EventId=$row->EventId";

      $event_result = mysql_query ($sql);
      if (! $event_result)
	echo "<!-- Query failed for event $row->EventId -->\n";
      else
      {
	if (1 != mysql_num_rows ($event_result))
	  echo "<!-- Unexpected number of rows for event $row->EventId -->\n";
	else
	{
	  $event_row = mysql_fetch_object ($event_result);
	  $row->Min = $event_row->Min;
	  $row->Max = $event_row->Max;
	  $row->Pref = $event_row->Pref;
	}

	mysql_free_result ($event_result);
      }
    }

	$sql = "SELECT * FROM BidTimes WHERE BidId=".$row->BidId.";";
	$btresult = mysql_query ($sql);
	if (! $btresult)
		return display_mysql_error ("BidTimes query failed for BidId ".$row->BidId);

	$bidtimes = array();
	while ($btrow = mysql_fetch_array ($btresult, MYSQL_ASSOC))
	{
		$key = $btrow['Day'].'_'.$btrow['Slot'];
		$bidtimes[$key] = mysql_real_escape_string ($btrow['Pref']);
	}
	mysql_free_result ($btresult);

    $name = $row->FirstName;
    if ('' != $name)
      $name .= ' ';
    $name .= $row->LastName;

    echo "  <TR ALIGN=CENTER BGCOLOR=\"$bgcolor\">\n";

    // If the status is "Pending" then folks with BidCom priv can know that
    // it's there, but they can't see the game.  The Bid Chair or the GM
    // Liaison can see bid.

    $game_link = true;
    $priv = user_has_priv (PRIV_BID_CHAIR) || user_has_priv (PRIV_GM_LIAISON);

    if (('Pending' == $row->Status) && (! $priv))
      $game_link = false;

    if ($game_link)
      $title = sprintf ("<A HREF=Bids.php?action=%d&BidId=%d>$row->Title</A>",
	      BID_SHOW_BID,
	      $row->BidId);
    else
      $title = $row->Title;
    echo "    <TD ALIGN=LEFT>$title</TD>\n";

    echo "    <TD ALIGN=LEFT><A HREF=mailto:$row->EMail>$name</A></TD>\n";
    printf ("    <TD><A NAME=BidId%d>$row->Min</A></TD>\n", $row->BidId);
    echo "    <TD>$row->Pref</TD>\n";
    echo "    <TD>$row->Max</TD>\n";
    echo "    <TD>$row->Hours</TD>\n";

    global $CON_DAYS;
    global $BID_SLOTS;

    echo "<!-- \n";
    echo "CON_DAYS:\n";
    print_r($CON_DAYS);
    echo "BID_SLOTS:\n";
    print_r($BID_SLOTS);
    echo "bidtimes:\n";
    print_r($bidtimes);
    echo "-->\n";

    foreach ($CON_DAYS as $day)
    {
      foreach ($BID_SLOTS[$day] as $slot)
      {
	$key = $day.'_'.$slot;
	echo "    <TD>" . table_value ($bidtimes[$key]) . "</TD>\n";
      }
    }

    if (user_has_priv (PRIV_BID_CHAIR))
      printf ("    <TD><A HREF=Bids.php?action=%d&BidId=%d>$row->Status</A></TD>\n",
	      BID_CHANGE_STATUS,
	      $row->BidId);
    else
      echo "    <TD>$row->Status</TD>\n";

    echo "    <TD>$row->LastUpdatedFMT</TD>\n";
    echo "    <TD>$row->CreatedFMT</TD>\n";
    echo "  </tr>\n";
  }

  echo "</TABLE>";

  echo "<P>\n";

  echo "<TABLE>\n";
  echo "  <TR VALIGN=TOP>\n";
  echo "    <TD BGCOLOR=#FFFFCC>Pending</TD>\n";
  echo "    <TD>\n";
  echo "      A newly submitted bid.  The Bid Coordinator is working\n";
  echo "      with the submitter to make sure that it is complete\n";
  echo "    </TD>\n";
  echo "  </tr>\n";
  echo "  <TR VALIGN=TOP>\n";
  echo "    <TD BGCOLOR=#DDDDFF>Under Review</TD>\n";
  echo "    <TD>\n";
  echo "      A bid that is available for review by the Bid Committee\n";
  echo "    </TD>\n";
  echo "  </tr>\n";
  echo "  <TR VALIGN=TOP>\n";
  echo "    <TD BGCOLOR=#CCFFCC>Accepted</TD>\n";
  echo "    <TD>\n";
  echo "      A bid that has been accepted for ".(USE_CON_SHORT_NAME ? CON_SHORT_NAME : CON_NAME)."\n";
  echo "    </TD>\n";
  echo "  </tr>\n";
  echo "  <TR VALIGN=TOP>\n";
  echo "    <TD BGCOLOR=#FFCCCC>Rejected</TD>\n";
  echo "    <TD>\n";
  echo "      A bid that has been rejected for ".(USE_CON_SHORT_NAME ? CON_SHORT_NAME : CON_NAME)."\n";
  echo "    </TD>\n";
  echo "  </tr>\n";
  echo "  <TR VALIGN=TOP>\n";
  echo "    <TD BGCOLOR=#FFCC99>Dropped</TD>\n";
  echo "    <TD>\n";
  echo "      A bid that was previously accepted and has been dropped\n";
  echo "      from the schedule\n";
  echo "    </TD>\n";
  echo "  </tr>\n";
  echo "</TABLE>\n";

  echo "<P>\n";
}

/*
 * change_bid_status
 *
 * Allow a user to change the status of a bid
 */

function change_bid_status ()
{
  // Only the bid chair has privilege to access this page

  if (! user_has_priv (PRIV_BID_COM))
    return display_access_error ();

  // Extract the BidId

  $BidId = intval (trim ($_REQUEST['BidId']));
  if (0 == $BidId)
    return display_error ("BidId not specified!");

  // Fetch information to display about the bid

  $sql = "SELECT Title, Status From Bids WHERE BidId=$BidId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Query failed for BidId $BidId");

  if (0 == mysql_num_rows ($result))
    return display_error ("Failed to find BidId $BidId");

  if (1 != mysql_num_rows ($result))
    return display_error ("Found multiple entries for BidId $BidId");

  $row = mysql_fetch_object ($result);

  display_header ("Change status for <I>$row->Title</I>");

  echo "<form method=\"POST\" action=\"Bids.php\">\n";
  form_add_sequence ();
  printf ("<INPUT TYPE=HIDDEN NAME=action VALUE=%d>\n",
	  BID_PROCESS_STATUS_CHANGE);
  printf ("<INPUT TYPE=HIDDEN NAME=BidId VALUE=%d>\n", $BidId);

  echo "<P>Bid Status: \n";
  echo "<SELECT Name=Status SIZE=1>\n";

  switch ($row->Status)
  {
    case 'Pending':
      echo "  <option value=Pending selected>Pending</option>\n";
      echo "  <option value=\"Under Review\">Under Review</option>\n";
      echo "  <option value=Rejected>Rejected</option>\n";
      break;

    case 'Under Review':
      echo "  <option value=\"Under Review\" selected>Under Review</option>\n";
      echo "  <option value=Accepted>Accepted</option>\n";
      echo "  <option value=Rejected>Rejected</option>\n";
      echo "  <option value=Dropped>Dropped</option>\n";
      break;

    case 'Accepted':
      echo "  <option value=Accepted selected>Accepted</option>\n";
      echo "  <option value=Dropped>Dropped</option>\n";
      break;

    case 'Rejected':
      echo "  <option value=\"Under Review\">Under Review</option>\n";
      echo "  <option value=Accepted>Accepted</option>\n";
      echo "  <option value=Rejected selected>Rejected</option>\n";
      break;

    case 'Dropped':
      echo "  <option value=\"Under Review\">Under Review</option>\n";
      echo "  <option value=Rejected>Rejected</option>\n";
      echo "  <option value=Dropped selected>Dropped</option>\n";
      break;

    default:
      echo "</SELECT>\n";
      echo "</FORM>\n";
      return display_error ("Invalid Status: $row->Status");
  }

  echo "</SELECT>\n";

  echo "<P>\n";
  echo "<INPUT TYPE=SUBMIT VALUE=\"Update Status\">\n";
  echo "</FORM>\n";
}

/*
 * process_status_change
 *
 * Change the status of a bid
 */

function process_status_change ()
{
  // Only the bid chair has privilege to access this page

  if (! user_has_priv (PRIV_BID_COM))
    return display_access_error ();

  // Check for a sequence error

  if (out_of_sequence ())
    return display_sequence_error (false);

  // Extract the BidId

  $BidId = intval (trim ($_REQUEST['BidId']));
  if (0 == $BidId)
    return display_error ("BidId not specified!");

  $Status = trim ($_POST['Status']);
  if (1 == get_magic_quotes_gpc())
    $Status = stripslashes ($Status);

  // Fetch the status to see if this is really a change

  $sql = "SELECT Title, Status, EventId From Bids WHERE BidId=$BidId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Query failed for BidId $BidId");

  if (0 == mysql_num_rows ($result))
    return display_error ("Failed to find BidId $BidId");

  if (1 != mysql_num_rows ($result))
    return display_error ("Found multiple entries for BidId $BidId");

  $row = mysql_fetch_object ($result);

  if ($row->Status == $Status)
    display_error ("Status unchanged for $row->Title");

  // Update the bid status

  $sql = "UPDATE Bids SET Status='$Status' WHERE BidId=$BidId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Failed to update status for BidId $BidId");

  // Handle dropped bids

  if ('Dropped' == $Status)
    return drop_bid ($BidId, $row->EventId);

  // Bids that have moved to Under Review need to have a discussion entry
  // added

  if ('Under Review' == $Status)
    return create_feedback_forum ($BidId);

  // If the status isn't accepted, we're done

  if ('Accepted' != $Status)
    return TRUE;

  // Fetch the bid information and stuff it into the $_POST array so we
  // so we can write it into the User and Event tables

  $sql = "SELECT * FROM Bids WHERE BidId=$BidId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Query failed for BidId $BidId");

  if (0 == mysql_num_rows ($result))
    return display_error ("Failed to find BidId $BidId");

  if (1 != mysql_num_rows ($result))
    return display_error ("Found multiple entries for BidId $BidId");

  $row = mysql_fetch_array ($result, MYSQL_ASSOC);

  foreach ($row as $key => $value)
  {
    if (1 == get_magic_quotes_gpc())
      $_POST[$key] = mysql_real_escape_string ($value);
    else
      $_POST[$key] = $value;
  }

//  dump_array ("_POST", $_POST);

  // If the EventId is 0, create an event

  if (0 != $row['EventId'])
    $EventId = intval ($row['EventId']);
  else
  {
    $EventId = add_event ($row);
    if (! is_int ($EventId))
      return FALSE;

    // Update the bid with the event ID

    $sql = "UPDATE Bids SET EventId='$EventId' WHERE BidId=$BidId";
    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ("Failed to update EventId for BidId $BidId");
  }

  // If the submitter is unpaid, comp him or her now

  $UserId = intval ($_POST['UserId']);

  // Let the submitter deal with who's comped
/*
  $sql = "SELECT CanSignup FROM Users WHERE UserId=$UserId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Query for user payment status failed for UserId $UserId");
  $row = mysql_fetch_object ($result);
  if (! $row)
    return display_error ("Failed to find user for UserId $UserId");

  if (is_unpaid ($row->CanSignup))
    comp_user ($UserId, $EventId);
*/
  // Add the lead GM as a GM for the game

  $sql = "INSERT INTO GMs SET EventId=$EventId, UserId=$UserId,";
  $sql .= '  Submitter="Y", ReceiveConEMail="Y",';
  $sql .= '  UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("GM insertion failed");

  return TRUE;
}

/*
 * show_bid_feedback_summary
 *
 */

function show_bid_feedback_summary()
{
  // Only bid committe members, the bid chair and the GM Liaison may access
  // this page

  if ((! user_has_priv (PRIV_BID_COM)) &&
      (! user_has_priv (PRIV_BID_CHAIR)) &&
      (! user_has_priv (PRIV_GM_LIAISON)))
    return display_access_error ();

  display_header ('Bid Committee Feedback');
  echo "<p>Click on a game title to update all entries for that bid<br>\n";
  echo "Click on a ConCom member to update all entries under discussion\n";
  echo "for the ConCom member<br>\n";
  echo "Click on a vote to update just the entry for that bid/ConCom member</p>\n";

  // Get the names of all Bid Committee members

  $sql = "SELECT UserId, FirstName, LastName FROM Users";
  $sql .= "  WHERE FIND_IN_SET('BidCom', Priv)";
  $sql .= "  ORDER BY LastName, FirstName";

  $result = mysql_query ($sql);
  if (! $result)
    display_mysql_error ('Query failed for Bid Committee Members');

  if (0 == mysql_num_rows ($result))
    display_error ('There are no Bid Committee Members to display');

  $committee = array ();
  $committee_users = array();
  $committee_headers = array();

  while ($row = mysql_fetch_object ($result))
  {
    $name = trim ("$row->FirstName $row->LastName");
    $committee[$name] = '';
    $committee_users[$name] = $row->UserId;
    $committee_headers[$name] = trim ("$row->FirstName<br>$row->LastName");
  }

  //  dump_array ('$committee', $committee);
  //  dump_array ('$committee_users', $committee_users);

  $sql = 'SELECT Bids.Title, Bids.Status, BidStatus.BidStatusId,';
  $sql .= ' BidStatus.Consensus, BidStatus.Issues,';
  $sql .= ' DATE_FORMAT(BidStatus.LastUpdated, "<nobr>%d-%b</nobr> %H:%i") AS LastUpdated';
  $sql .= '  FROM BidStatus, Bids';
  $sql .= '  WHERE Bids.BidId=BidStatus.BidId';
  $sql .= '  ORDER BY BidStatus.Consensus, Bids.Title';

  $result = mysql_query ($sql);
  if (! $result)
    display_mysql_error ('Query failed for bids');

  if (0 == mysql_num_rows ($result))
    display_error ('There are no bids with feedback to display');

  $prefix = '';
  $suffix = '';
  if (user_has_priv (PRIV_BID_CHAIR))
    $suffix = '</a>';

  echo "<table border=\"1\">\n";
  echo "  <tr valign=\"bottom\">\n";
  echo "    <th align=\"left\">Game</th>\n";
  echo "    <th>Status / Updated</th>\n";
  foreach ($committee as $key => $value)
  {
    if (user_has_priv (PRIV_BID_CHAIR))
    {
      $prefix = sprintf ('<a href="Bids.php?action=%d&UserId=%d">',
			 BID_FEEDBACK_BY_CONCOM,
			 $committee_users[$key]);

    }
    printf ("    <th>%s%s%s</th>\n",
	    $prefix, $committee_headers[$key], $suffix);
  }
  echo "    <th nowrap>Issue Summary</th>\n";
  echo "  </tr>\n";

  while ($row = mysql_fetch_object ($result))
  {
    // Initialize all committee members to Undecided

    $prefix = '';
    $suffix = '';
    if (user_has_priv (PRIV_BID_CHAIR))
      $suffix = '</a>';

    foreach ($committee as $key => $value)
    {
      if (user_has_priv (PRIV_BID_CHAIR))
      {
	$prefix = sprintf ('<a href="Bids.php?action=%dBid&BidStatusId=%d&UserId=%d">',
			   BID_FEEDBACK_BY_ENTRY,
			   $row->BidStatusId,
			   $committee_users[$key]);
      }
      $committee[$key] = "${prefix}Undecided${suffix}";
    }

    // Fetch committee votes from the database

    $sql = 'SELECT Users.FirstName, Users.LastName,';
    $sql .= ' BidFeedback.Vote, BidFeedback.Issues, BidFeedback.FeedbackId,';
    $sql .= ' BidFeedback.UserId';
    $sql .= ' FROM Users, BidFeedback';
    $sql .= " WHERE BidFeedback.BidStatusId=$row->BidStatusId";
    $sql .= '   AND Users.UserId=BidFeedback.UserId';
    $sql .= ' ORDER BY Users.FirstName';

    $committee_result = mysql_query ($sql);
    if (! $committee_result)
      return display_mysql_error ("Query for bid committee info failed");

    $prefix = '';
    $suffix = '';
    if (user_has_priv (PRIV_BID_CHAIR))
      $suffix = '</a>';
    while ($committee_row = mysql_fetch_object ($committee_result))
    {

      $name = trim ("$committee_row->FirstName $committee_row->LastName");
      if (user_has_priv (PRIV_BID_CHAIR))
	$prefix = sprintf ('<a href="Bids.php?action=%d&FeedbackId=%d&UserId=%d">',
			   BID_FEEDBACK_BY_ENTRY,
			   $committee_row->FeedbackId,
			   $committee_row->UserId);
      $committee[$name] = "$prefix<nobr><b>$committee_row->Vote</b></nobr>$suffix";
      if ('' != $committee_row->Issues)
	$committee[$name] .= '<br>'.$committee_row->Issues;
    }

    // If this is the bid chairman the feedback information can be edited

    $title = $row->Title;
    if (user_has_priv (PRIV_BID_CHAIR))
      $title = sprintf ('<a href="Bids.php?action=%d&BidStatusId=%d">%s</a>',
			BID_FEEDBACK_BY_GAME,
			$row->BidStatusId,
			$title);

    // Make HTML happy about an empty cell

    $issues = $row->Issues;
    if ('' == $issues)
      $issues = '&nbsp;';

    // Determine the background color for this row

    switch ($row->Consensus)
    {
      case 'Discuss':         $bgcolor = '#DDDDFF'; break;
      case 'Accept':          $bgcolor = '#CCFFCC'; break;
      case 'Early Accepted':  $bgcolor = '#99CC99'; break;
      case 'Reject':          $bgcolor = '#FFCCCC'; break;
      case 'Drop':            $bgcolor = '#FFCC99'; break;
      default:                $bgcolor = '#FFFFFF'; break;
    }

    $Consensus = sprintf ("<a name=\"BidStatusId%d\">$row->Consensus</a>",
			  $row->BidStatusId);

    echo "  <tr valign=\"top\" bgcolor=\"$bgcolor\">\n";
    echo "    <td>$title</td>\n";
    echo "    <td><b>$Consensus It</b><br>$row->LastUpdated</td>\n";

    foreach ($committee as $key => $value)
      echo "    <td>$value</td>\n";

    echo "    <td>$issues</td>\n";
    echo "  </tr>\n";

    //    dump_array ('committee', $committee);
  }
  echo "</table>\n<P>\n";

  // Display the key for the feedback table

  echo "<p>\n";

  echo "<table>\n";
  echo "  <tr valign=\"top\">\n";
  echo "    <td bgcolor=\"#DDDDFF\">Discuss It</td>\n";
  echo "    <td>\n";
  echo "      A bid that is available for review by the Bid Committee\n";
  echo "    </td>\n";
  echo "  </tr>\n";
  echo "  <tr valign=\"top\">\n";
  echo "    <td bgcolor=\"#CCFFCC\">Accept It</td>\n";
  echo "    <td>\n";
  echo "      A bid that has been accepted for Intercon\n";
  echo "    </td>\n";
  echo "  </tr>\n";
  echo "  <tr valign=\"top\">\n";
  echo "    <td bgcolor=\"#99CC99\">Early Accepted It</td>\n";
  echo "    <td>\n";
  echo "      A bid that was Early Accepted for Intercon\n";
  echo "    </td>\n";
  echo "  </tr>\n";
  echo "  <tr valign=\"top\">\n";
  echo "    <td bgcolor=\"#FFCCCC\">Reject It</td>\n";
  echo "    <td>\n";
  echo "      A bid that has been rejected for Intercon\n";
  echo "    </td>\n";
  echo "  </tr>\n";
  echo "  <tr valign=\"top\">\n";
  echo "    <td bgcolor=\"#FFCC99\">Drop It</td>\n";
  echo "    <td>\n";
  echo "      A bid that was previously accepted and has been dropped\n";
  echo "      from the schedule\n";
  echo "    </td>\n";
  echo "  </tr>\n";
  echo "</table>\n";

  echo "<p>\n";
}


/*
 * create_feedback_forum
 *
 * Create a BidStatus for a bid that's now Under Review
 */

function  create_feedback_forum ($BidId)
{
  // Check whether a forum for this bid already exists

  $sql = "SELECT BidStatusId FROM BidStatus WHERE BidId=$BidId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Check for existing BidStatus entry failed for Bid Id $BidId");

  // If a forum already exists, use it

  if (0 != mysql_num_rows ($result))
  {
    display_error ("Using existing forum for bid ID $BidId");
    return true;
  }

  $sql = "INSERT INTO BidStatus SET BidId=$BidId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Failed to create forum for bid ID $BidId");

  return true;
}

/*
 * add_event
 *
 * Add an event from the bid information
 */

function add_event ($bid_row)
{
  // Verify that the title isn't in the database yet

  $Title = $bid_row['Title'];
  if ('' == $Title)
    return display_error ('A blank Title is invalid');

  // Check that the title isn't already in the Events table

  if (! title_not_in_events_table ($Title))
    return false;

  $sql = 'INSERT Events SET ';
  $sql .= build_sql_string ('Title', $Title, false);
  $sql .= build_sql_string ('Author');
  $sql .= build_sql_string ('GameEMail');
  $sql .= build_sql_string ('Organization');
  $sql .= build_sql_string ('Homepage');

  $sql .= build_sql_string ('MinPlayersMale');
  $sql .= build_sql_string ('MaxPlayersMale');
  $sql .= build_sql_string ('PrefPlayersMale');

  $sql .= build_sql_string ('MinPlayersFemale');
  $sql .= build_sql_string ('MaxPlayersFemale');
  $sql .= build_sql_string ('PrefPlayersFemale');

  $sql .= build_sql_string ('MinPlayersNeutral');
  $sql .= build_sql_string ('MaxPlayersNeutral');
  $sql .= build_sql_string ('PrefPlayersNeutral');

  $sql .= build_sql_string ('Hours');

  $sql .= build_sql_string ('Description');
  $sql .= build_sql_string ('ShortBlurb');
  $sql .= build_sql_string ('PlayerCommunications');

/*  $sql .= build_sql_string ('IsSmallGameContestEntry'); */

  $sql .= build_sql_string ('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID]);

  //  echo "$sql<P>\n";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Insert into Events failed");

  return mysql_insert_id();
}

/*
 * password_from_title
 *
 * Build a password from a game title
 */

function password_from_title ($title)
{
  // Start by making sure the title has title case

  $title = ucwords ($title);

  // Remove any quotes

  $title = str_replace ("'", '', $title);
  $title = str_replace ("\"", '', $title);

  // Create a password from the games' title

  $words = explode (" ", $title);
  $password = '';

  foreach ($words as $w)
  {
    $password .= $w;
    if (strlen ($password) > 8)
      return $password;
  }

  $password .= 'ChangeMe';
  return $password;
}

/*
 * drop_bid
 *
 * Change the status of a bid from Accepted to Dropped
 */

function drop_bid ($BidId, $EventId)
{
  // If the EventId is 0, we don't have to do anything more

  if (0 == $EventId)
    return true;

  // Fetch the Event information and use it to update the bid

  $sql = "SELECT * From Events WHERE EventId=$EventId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Query failed for EventId $EventId");

  if (0 == mysql_num_rows ($result))
    return display_error ("Failed to find EventId $EventId");

  if (1 != mysql_num_rows ($result))
    return display_error ("Found multiple entries for EventId $EventId");

  $row = mysql_fetch_array ($result, MYSQL_ASSOC);

  // Copy the information into the $_POST array so that build_sql_string
  // will find it

  foreach ($row as $key => $value)
  {
    if (1 == get_magic_quotes_gpc())
      $_POST[$key] = mysql_real_escape_string ($value);
    else
      $_POST[$key] = $value;
  }

  // Build the string to update the game information in the bid

  $sql = 'UPDATE Bids SET ';

  $sql .= build_sql_string ('Title', $Title, false);
  $sql .= build_sql_string ('Author');
  $sql .= build_sql_string ('Homepage');
  $sql .= build_sql_string ('GameEMail');
  $sql .= build_sql_string ('Organization');

  $sql .= build_sql_string ('MinPlayersMale');
  $sql .= build_sql_string ('MaxPlayersMale');
  $sql .= build_sql_string ('PrefPlayersMale');

  $sql .= build_sql_string ('MinPlayersFemale');
  $sql .= build_sql_string ('MaxPlayersFemale');
  $sql .= build_sql_string ('PrefPlayersFemale');

  $sql .= build_sql_string ('MinPlayersNeutral');
  $sql .= build_sql_string ('MaxPlayersNeutral');
  $sql .= build_sql_string ('PrefPlayersNeutral');

  $sql .= build_sql_string ('Hours');
  $sql .= build_sql_string ('CanPlayConcurrently');
  $sql .= build_sql_string ('Description');
//  printf ("    <td><A NAME=BidStatus%d>$title</A></td>\n", $row->BidStatusId);

  $sql .= build_sql_string ('ShortBlurb');
  $sql .= build_sql_string ('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID]);

  $sql .= ', EventId=0';

  $sql .= " WHERE BidId=$BidId";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Update of Bids failed");

  // Now remove the entry from the Events table

  $sql = "DELETE FROM Events WHERE EventId=$EventId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Deletion from Events failed for $EventId");

  // And remove any GMs for that game

  $sql = "DELETE FROM GMs WHERE EventId=$EventId";
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Deletion from GMs failed for Event $EventId");

  return TRUE;
}

function display_bid_etc ()
{
  echo "<FONT SIZE=\"+2\">Thank you for bidding your event for ";
  echo CON_NAME . "!</FONT>\n";
  echo "<P>\n";
  echo "The Bid Coordinator has been notified of your bid.\n";
  echo "<P>\n";

  $page = 'bidFollowup.html';

  if (! is_readable ($page))
  {
    if (! is_readable (TEXT_DIR."/$page"))
    {
      display_error ("Unable to read $page");
    }
    else
      include (TEXT_DIR."/$page");
  }
  else
    include ($page);
}

function form_vote ($key)
{
  $sy = '';
  $y  = '';
  $wy = '';
  $nc = '';
  $wn = '';
  $n  = '';
  $sn = '';
  $u = '';
  $a = '';

  switch ($_POST[$key])
  {
    case 'Strong Yes':  $sy = 'selected'; break;
    case 'Yes':         $y =  'selected'; break;
    case 'Weak Yes':    $wy = 'selected'; break;
    case 'No Comment':  $nc  = 'selected'; break;
    case 'Weak No':     $wn = 'selected'; break;
    case 'No':          $n =  'selected'; break;
    case 'Strong No':   $sn = 'selected'; break;
    case 'Undecided':   $u  = 'selected'; break;
    case 'Author':      $a  = 'selected'; break;
  }

  echo "    <td valign=\"top\">\n";
  echo "      <select name=\"$key\" size=\"1\">\n";
  echo "        <option value=\"Strong Yes\" $sy>Strong Yes&nbsp;&nbsp;</option>\n";
  echo "        <option value=\"Yes\" $y>Yes&nbsp;&nbsp;</option>\n";
  echo "        <option value=\"Weak Yes\" $wy>Weak Yes&nbsp;&nbsp;</option>\n";
  echo "        <option value=\"No Comment\" $nc>No Comment&nbsp;&nbsp;</option>\n";
  echo "        <option value=\"Weak No\" $wn>Weak No</option>\n";
  echo "        <option value=\"No\" $n>No</option>\n";
  echo "        <option value=\"Strong No\" $sn>Strong No</option>\n";
  echo "        <option value=\"Undecided\" $u>Undecided&nbsp;&nbsp;</option>\n";
  echo "        <option value=\"Author\" $a>Author&nbsp;&nbsp;</option>\n";
  echo "      </select>\n";
  echo "    </td>\n";
}

function form_issues($key)
{
  if (1 == get_magic_quotes_gpc())
    $text = stripslashes ($_POST[$key]);
  else
    $text = $_POST[$key];

  printf ('    <td><textarea name="%s" cols="64" rows="5">' .
	  "%s</textarea></td>\n",
	  $key,
	  $text);
}

/*
 * update_feedback_by_game
 *
 * Allow the Bid Committee Chairman to update the bid feedback displayed
 * for bid committee members
 */

function update_feedback_by_game ()
{
  // Only the bid chair may access this page

  if (! user_has_priv (PRIV_BID_CHAIR))
    return display_access_error ();

  $BidStatusId = intval ($_REQUEST['BidStatusId']);

  // Get the information about the bid

  $sql = 'SELECT Bids.Title, Bids.Status,';
  $sql .= ' BidStatus.Consensus, BidStatus.Issues,';
  $sql .= ' DATE_FORMAT(BidStatus.LastUpdated, "%d-%b %H:%i") AS LastUpdated';
  $sql .= '  FROM BidStatus, Bids';
  $sql .= "  WHERE BidStatus.BidStatusId=$BidStatusId";
  $sql .= '    AND Bids.BidId=BidStatus.BidId';

  $result = mysql_query ($sql);
  if (! $result)
    display_mysql_error ('Query failed for bid status information');

  if (0 == mysql_num_rows ($result))
    display_error ('There are no bids with feedback to display');

  $row = mysql_fetch_object ($result);

  display_header ("Bid Committee Feedback for <I>$row->Title</I>");
  echo "Last updated $row->LastUpdated<p>\n";

  $Consensus = $row->Consensus;
  $Issues = $row->Issues;

  // Get the names of all Bid Committee members

  $sql = "SELECT UserId, FirstName, LastName FROM Users";
  $sql .= "  WHERE FIND_IN_SET('BidCom', Priv)";
  $sql .= "  ORDER BY FirstName";

  $result = mysql_query ($sql);
  if (! $result)
    display_mysql_error ('Query failed for Bid Committee Memebers');

  if (0 == mysql_num_rows ($result))
    display_error ('There are no Bid Committee Members to display');

  $committee = array ();
  $user_id = array ();
  $issues = array ();
  $feedback_ids = array ();

  while ($row = mysql_fetch_object ($result))
  {
    $name = trim ("$row->FirstName $row->LastName");
    $committee[$name] = 'Undecided';
    $user_id[$name] = $row->UserId;
    $issues[$name] = '';
    $feedback_ids[$name] = 0;
  }

  // If this is the first time in, fill in the $_POST array

  if (! array_key_exists ('Consensus', $_POST))
  {
    $_POST['Consensus'] = $Consensus;
    $_POST['Issues'] = $Issues;

    // Fetch committee votes from the database

    $sql = 'SELECT Users.UserId, Users.FirstName, Users.LastName,';
    $sql .= ' BidFeedback.Vote, BidFeedback.Issues, BidFeedback.FeedbackId';
    $sql .= ' FROM Users, BidFeedback';
    $sql .= " WHERE BidFeedback.BidStatusId=$BidStatusId";
    $sql .= '   AND Users.UserId=BidFeedback.UserId';
    $sql .= ' ORDER BY Users.FirstName';

    echo "<!-- $sql -->\n";

    $committee_result = mysql_query ($sql);
    if (! $committee_result)
      return display_mysql_error ("Query for bid committee info failed");

    while ($committee_row = mysql_fetch_object ($committee_result))
    {
      $name = trim ("$committee_row->FirstName $committee_row->LastName");
      $committee[$name] = $committee_row->Vote;
      $user_id[$name] = $committee_row->UserId;
      $issues[$name] = $committee_row->Issues;
      $feedback_ids[$name] = $committee_row->FeedbackId;
    }

    //dump_array("committee", $committee);
    //dump_array("user_id", $user_id);
    //dump_array("issues", $issues);
    //dump_array("feedback_ids", $feedback_ids);

    $i = 0;
    foreach ($committee as $k => $v)
    {
      $i++;
      $_POST["vote_$i"] = $v;
      $_POST["issues_$i"] = $issues[$k];
      $_POST["uid_$i"] = $user_id[$k];

      if (array_key_exists ($k, $feedback_ids))
	$_POST["id_$i"] = $feedback_ids[$k];
      else
	$_POST["id_$i"] = 0;
    }
  }

  printf ("<form method=\"POST\" action=\"Bids.php#BidStatusId%d\">\n",
	  $BidStatusId);
  form_add_sequence ();
  form_hidden_value ('action', BID_PROCESS_FEEDBACK_BY_GAME);
  form_hidden_value ('BidStatusId', $BidStatusId);
  echo "<table>\n";

  $i = 0;

  foreach ($committee as $k => $v)
  {
    $i++;
    $u = $user_id[$k];
    form_hidden_value ("id_$i", $_POST["id_$i"]);
    form_hidden_value ("uid_$i", $_POST["uid_$i"]);

    echo "  <tr>\n";
    echo "    <td valign=\"top\" align=\"right\">$k:&nbsp;&nbsp;</td>\n";

    form_vote ("vote_$i");
    form_issues ("issues_$i");
    echo "  </tr>\n";
  }

  echo "  <tr><td>&nbsp;</td></tr>\n";

  form_bid_consensus ('Consensus');

  // If magic quotes are on, strip off the slashes

  $key = 'Issues';
  if (1 == get_magic_quotes_gpc())
    $text = stripslashes ($_POST[$key]);
  else
    $text = $_POST[$key];

  echo "  <tr>\n";
  echo "    <td align=\"right\">Issue Summary:</td>\n";
  echo "    <td colspan=\"2\">\n";
  echo "    <TEXTAREA NAME=$key COLS=80 ROWS=5>$text</TEXTAREA>\n";
  echo "    </td>\n";
  echo"  </tr>\n";


  echo "  <tr>\n";
  echo "    <td colspan=\"3\" align=\"center\">\n";
  echo "      <INPUT TYPE=SUBMIT VALUE=\"Update Feedback\">\n";
  echo "    </td>\n";
  echo "  </tr>\n";

  echo "</table>\n";
  echo "</form>\n";

}

function process_feedback_by_game ()
{
  // Only the bid chair may access this page

  if (! user_has_priv (PRIV_BID_CHAIR))
    return display_access_error ();

  // Check for a sequence error

  if (out_of_sequence ())
    return display_sequence_error (true);

  $BidStatusId = intval ($_POST['BidStatusId']);

  // Start by updating the BidStatus table, since it's easier

  $sql = 'UPDATE BidStatus SET ';
  $sql .= build_sql_string ('Consensus', '', FALSE);
  $sql .= build_sql_string ('Issues');
  $sql .= ', LastUpdated=NULL';
  $sql .= " WHERE BidStatusId=$BidStatusId";

  //  echo "BidStatus: $sql<p>\n";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ("Update of BidStatus for Id $BidStatusId failed");

  $i = 1;
  while (isset ($_POST["id_$i"]))
  {
    $id = intval ($_POST["id_$i"]);
    if (0 == $id)
      $sql = 'INSERT INTO BidFeedback SET ';
    else
      $sql = 'UPDATE BidFeedback SET ';

    $issues = $_POST["issues_$i"];
    if ('' == $issues)
      $issues = ' ';

    $sql .= build_sql_string ('Vote', $_POST["vote_$i"], FALSE);
    $sql .= build_sql_string ('Issues', $issues);

    if (0 == $id)
    {
      $uid = $_POST["uid_$i"];
      $sql .= build_sql_string ('BidStatusId');
      $sql .= build_sql_string ('UserId', $uid);;
    }
    else
    {
      $uid = 0;
      $sql .= " WHERE FeedbackId=$id";
    }

    // echo "$sql<p>\n";

    $result = mysql_query ($sql);
    if (! $result)
      display_mysql_error ("BidFeedback update for id $id, Uid $uid failed");

    $i++;
  }

  return true;
}

function show_bid_feedback_entry_form()
{
  // Only the bid chair may access this page

  if ((! user_has_priv (PRIV_BID_COM)) &&
      (! user_has_priv (PRIV_BID_CHAIR)) &&
      (! user_has_priv (PRIV_GM_LIAISON)))
    return display_access_error ();

  if (! array_key_exists ('UserId', $_REQUEST))
    return display_error ('Failed to find UserId in $_REQUEST array');

  $UserId = intval($_REQUEST['UserId']);
  if ($UserId < 1)
    return display_error ("Invalid value for $$UserId: $UserId");

  $sql = "SELECT FirstName, LastName FROM Users WHERE UserId=$UserId";
  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error ('Query for User Name failed', $sql);

  $row = mysql_fetch_object($result);
  if (! $row)
    return display_error ("Failed to find user for $$UserId: $UserId");

  $name = trim ("$row->FirstName $row->LastName");

  $BidStatusId = 0;
  $FeedbackId = 0;

  if (array_key_exists ('FeedbackId', $_REQUEST))
    $FeedbackId = intval ($_REQUEST['FeedbackId']);

  $Vote = 'Undecided';
  $Title = 'Unknown';
  $Issues = '';

  if (0 != $FeedbackId)
  {
    $sql = 'SELECT BidFeedback.Vote, BidFeedback.Issues,';
    $sql .= ' BidFeedback.BidStatusId, Bids.Title';
    $sql .= ' FROM Bids, BidStatus, BidFeedback';
    $sql .= " WHERE BidFeedback.FeedbackId=$FeedbackId";
    $sql .= '   AND BidStatus.BidStatusId=BidFeedback.BidStatusId';
    $sql .= '   AND Bids.BidId=BidStatus.BidId';

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('Query failed for bid info', $sql);

    if (0 == mysql_num_rows($result))
      return display_error("Failed to find bid info for FeedbackId: $FeedbackId");

    $row = mysql_fetch_object($result);
    $Vote = $row->Vote;
    $Issues = $row->Issues;
    $Title = $row->Title;
    $BidStatusId = $row->BidStatusId;
  }
  else
  {
    // If we don't have a FeedbackId, we'd better have a BidStatusId

    if (! array_key_exists ('BidStatusId', $_REQUEST))
      return display_error ('Failed to find BidStatusIdId in $_REQUEST array');

    $BidStatusId = intval($_REQUEST['BidStatusId']);
    if (0 == $BidStatusId)
      return display_error ("Invalid BidStatusId: $BidStatusId");

    $sql = 'SELECT Bids.Title';
    $sql .= ' FROM Bids, BidStatus';
    $sql .= " WHERE BidStatus.BidStatusId=$BidStatusId";
    $sql .= '   AND Bids.BidId=BidStatus.BidId';

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('Query failed for bid info', $sql);

    if (0 == mysql_num_rows($result))
      return display_error("Failed to find bid info for FeedbackId: $FeedbackId");

    $row = mysql_fetch_object($result);
    $Title = $row->Title;
  }

  // If this is the first time in, fill in the $_POST array

  if (! array_key_exists ('Vote', $_POST))
  {
    $_POST['Vote'] = $Vote;
    $_POST['Issues'] = $Issues;
  }

  display_header ("Bid Committee Feedback for $name on <i>$Title</i>");

  echo "<form method=\"POST\" action=\"Bids.php\">\n";
  form_add_sequence ();
  form_hidden_value ('action', BID_FEEDBACK_PROCESS_ENTRY);
  form_hidden_value ('FeedbackId', $FeedbackId);
  form_hidden_value ('BidStatusId', $BidStatusId);
  form_hidden_value ('UserId', $UserId);

  echo "<table>\n";
  echo "  <tr>\n";
  echo "    <th valign=\"top\" align=\"right\">Vote:&nbsp;&nbsp;</th>\n";
  form_vote('Vote');
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <th valign=\"top\" align=\"right\">Issues:&nbsp;&nbsp;</th>\n";
  form_issues('Issues');
  echo "  </tr>\n";
  form_submit ('Update Feedback');
  echo "</table>\n";
}

function process_feedback_for_entry()
{
  // Only the bid chair may access this page

  if (! user_has_priv (PRIV_BID_CHAIR))
    return display_access_error ();

  // Check for a sequence error

  if (out_of_sequence ())
    return display_sequence_error (true);

  $BidStatusId = intval ($_POST['BidStatusId']);
  $FeedbackId = intval ($_POST['FeedbackId']);
  $UserId = intval ($_POST['UserId']);

  if (0 == $FeedbackId)
    $sql = 'INSERT INTO BidFeedback SET ';
  else
    $sql = 'UPDATE BidFeedback SET ';

  $sql .= build_sql_string ('Vote', $_POST['Vote'], false);
  $sql .= build_sql_string ('Issues');

  if (0 == $FeedbackId)
  {
    $sql .= build_sql_string ('UserId');
    $sql .= build_sql_string ('BidStatusId');
  }
  else
    $sql .= " WHERE FeedbackId=$FeedbackId";

  //  echo "$sql<p>\n";

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error ('BidFeedback entry update failed', $sql);
  else
    return true;
}

function show_bid_feedback_by_user_form()
{
  // Only the bid chair may access this page

  if (! user_has_priv (PRIV_BID_CHAIR))
    return display_access_error ();

  // Make sure we've got a UserId

  if (! array_key_exists ('UserId', $_REQUEST))
    return display_error ('Failed to find UserId in $_REQUEST array');

  $UserId = intval($_REQUEST['UserId']);
  if ($UserId < 1)
    return display_error ("Invalid value for $$UserId: $UserId");

  // Get the ConCom member's name

  $sql = "SELECT FirstName, LastName FROM Users WHERE UserId=$UserId";
  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error ('Query for User Name failed', $sql);

  $row = mysql_fetch_object($result);
  if (! $row)
    return display_error ("Failed to find user for $$UserId: $UserId");

  $name = trim ("$row->FirstName $row->LastName");

  display_header ("Bid Committee Feedback for $name");

  //  dump_array ('$_REQUEST', $_REQUEST);
  //  dump_array ('$_POST before being filled', $_POST);

  // Populate the $_POST array, if necessary

  if (! array_key_exists ('BidCount', $_POST))
  {
    // Gather the list of bids under discussion

    $sql = 'SELECT Bids.Title, BidStatus.BidStatusId';
    $sql .= ' FROM BidStatus,Bids';
    $sql .= ' WHERE Consensus="Discuss"';
    $sql .= '   AND Bids.BidId=BidStatus.BidId';
    $sql .= ' ORDER BY Bids.Title';

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error ('Query failed for bids under discussion',
				  $sql);

    $_POST['BidCount'] = mysql_num_rows($result);
    if (0 == $_POST['BidCount'])
      return display_error ('There are no bids under discussion');

    $bids = array();
    $b = 1;

    while ($row = mysql_fetch_object($result))
    {
      $_POST["BidStatusId_$b"] = $row->BidStatusId;
      $_POST["Title_$b"] = $row->Title;
      $bids[$row->BidStatusId] = $b;
      $b++;
    }

    // Now gather any existing Feedback

    $sql = 'Select BidFeedback.Vote, BidFeedback.Issues,';
    $sql .= 'BidFeedback.FeedbackId, BidFeedback.BidStatusId';
    $sql .= ' FROM BidFeedback, BidStatus';
    $sql .= " WHERE BidFeedback.UserId=$UserId";
    $sql .= '   AND BidStatus.BidStatusId=BidFeedback.BidStatusId';
    $sql .= '   AND BidStatus.Consensus="Discuss"';

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error ('Failed to fetch feedback', $sql);

    while ($row = mysql_fetch_object($result))
    {
      $b = $bids[$row->BidStatusId];
      $_POST["Vote_$b"] = $row->Vote;
      $_POST["Issues_$b"] = $row->Issues;
      $_POST["FeedbackId_$b"] = $row->FeedbackId;
      $_POST["BidStatusId_$b"] = $row->BidStatusId;
      $bids[$row->BidStatusId] = 0;
    }

    // Now deal with any new entries

    foreach ($bids as $key => $b)
    {
      if (0 == $b)
	continue;

      $_POST["Vote_$b"] = 'Undecided';
      $_POST["Issues_$b"] = '';
      $_POST["FeedbackId_$b"] = 0;
      $_POST["BidStatusId_$b"] = $key;
    }

    //    dump_array ('$_POST after being filled', $_POST);

  }

  $BidCount = intval($_POST['BidCount']);

  echo "<form method=\"POST\" action=\"Bids.php\">\n";
  form_add_sequence ();
  form_hidden_value ('action', BID_FEEDBACK_PROCESS_BY_CONCOM);
  form_hidden_value ('UserId', $UserId);
  form_hidden_value ('BidCount', $BidCount);
  echo "<table>\n";
  echo "  <tr>\n";
  echo "    <th>Game</th>\n";
  echo "    <th>Vote</th>\n";
  echo "    <th>Issue(s)</th>\n";
  echo "  </tr>\n";

  for ($b = 1; $b <= $BidCount; $b++)
  {
    echo "  <tr>\n";
    printf ("    <td>%s</td>\n", $_POST["Title_$b"]);
    form_vote ("Vote_$b");
    form_issues ("Issues_$b");
    form_hidden_value ("Title_$b", $_POST["Title_$b"]);
    form_hidden_value ("FeedbackId_$b", $_POST["FeedbackId_$b"]);
    form_hidden_value ("BidStatusId_$b", $_POST["BidStatusId_$b"]);
    echo "  </tr>\n";
  }

  form_submit ('Submit', 3);

  echo "</table>\n";
}

function process_feedback_for_user()
{
  dump_array ('$_POST', $_POST);

  // Only the bid chair may access this page

  if (! user_has_priv (PRIV_BID_CHAIR))
    return display_access_error ();

  // Make sure we've got a UserId

  if (! array_key_exists ('UserId', $_REQUEST))
    return display_error ('Failed to find UserId in $_REQUEST array');

  $UserId = intval($_REQUEST['UserId']);
  if ($UserId < 1)
    return display_error ("Invalid value for $$UserId: $UserId");

  // Make sure we've got a BidCount

  if (! array_key_exists ('BidCount', $_REQUEST))
    return display_error ('Failed to find BidCount in $_REQUEST array');

  $BidCount = intval($_REQUEST['BidCount']);
  if ($BidCount < 1)
    return display_error ("Invalid value for $$BidCount: $BidCount");

  for ($b = 1; $b <= $BidCount; $b++)
  {
    $FeedbackId = intval($_POST["FeedbackId_$b"]);
    if (0 == $FeedbackId)
      $sql = 'INSERT INTO BidFeedback SET ';
    else
      $sql = 'UPDATE BidFeedback SET ';

    $issues = $_POST["Issues_$b"];
    if ('' == $issues)
      $issues = ' ';

    $sql .= build_sql_string ('Vote', $_POST["Vote_$b"], false);
    $sql .= build_sql_string ('Issues', $issues);

    if (0 == $FeedbackId)
    {
      $sql .= build_sql_string ('UserId', $UserId);
      $sql .= build_sql_string ('BidStatusId', $_POST["BidStatusId_$b"]);
    }
    else
      $sql .= " WHERE FeedbackId=$FeedbackId";

    //    echo "$sql<br>\n";
    $result = mysql_query($sql);
    if (! $result)
    {
      if (0 == $FeedbackId)
	return display_mysql_error ('Insert into BidFeedbackId failed', $sql);
      else
	return display_mysql_error ('Update of BidFeedback failed', $sql);
    }
  }

  return true;
}

?>
