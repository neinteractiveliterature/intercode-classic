<?

include ("intercon_db.inc");

// Connect to the database

if (! intercon_db_connect ())
{
  display_mysql_error ('Failed to establish connection to the database');
  exit ();
}

// Standard header stuff

html_begin ();

if (array_key_exists ('action', $_REQUEST))
  $action = $_REQUEST['action'];
else
  $action = THURSDAY_THING;

switch ($action)
{
  case THURSDAY_THING:
    thursday_thing();
    /*
    $page = "ThursdaySchedule.html";
    if (! is_readable ($page))
      display_error ("Unable to read $page");
    else
      readfile ($page);
    */
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
     thursday_thing();
   break;

  case PRECON_MANAGE_EVENTS:
    display_event_summary();
    break;

  case PRECON_SHOW_STATUS_FORM:
    show_status_form();
    break;

  default:
    echo "Unknown action code: $action\n";
}

html_end();

/*
 * thursday_thing
 *
 * Display information about the Pre-Convention Events
 */

function thursday_thing()
{
  printf ("<h2>%s Pre-Convention Events</h2>\n", CON_NAME);

  $cost = '5.00';
  if (DEVELOPMENT_VERSION)
    $cost= '0.05';

  printf ("<p>The %s Pre-Convention will be run at the Chelmsford Radisson\n",
	  CON_NAME);
  printf ("from Thursday evening until the start of %s Friday evening.</p>\n",
	  CON_NAME);
  echo "<p>The Pre-Convention\n";
  echo "is a conference about the writing, production, and play of live\n";
  echo "action roleplaying games.  There will be panels, discussions, and\n";
  echo "interactive workshops discussing LARP theory, costuming, writing\n";
  echo "techniques, play styles, and a variety of other topics.</p>\n";

  $url = '';
  if (isset ($_SESSION[SESSION_LOGIN_USER_ID]))
  {
    if (1)
    {
      echo "<p>If you'd like to propose a panel, discussion or workshop\n";
      printf ("please contact %s at %s.</p>\n",
	      NAME_THURSDAY,
	      mailto_or_obfuscated_email_address (EMAIL_THURSDAY));
    }
    else
    {
      echo "<p>Want to propose a panel, discussion or workshop?  We'd love to\n";
      printf ("<a href=\"Thursday.php?action=%d\">hear</a>\n",
	      PRECON_SHOW_EVENT_FORM);
      echo "about it!\n";
    }

    $sql = 'SELECT * FROM Thursday';
    $sql .= ' WHERE UserId=' . $_SESSION[SESSION_LOGIN_USER_ID];

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error ('Query for PreCon record failed',
				  $sql);
    if (0 != mysql_num_rows($result))
    {
      $row = mysql_fetch_object($result);
      if ('Paid' == $row->Status)
      {
	printf ("<p>You are signed up for the %s Pre-Convention.</p>\n",
		CON_NAME);
	return;
      }
    }

    // Build the URL for the PayPal links.  If the user cancels, just return
    // to index.php which will default to his homepage

    $path_parts = pathinfo($_SERVER['PHP_SELF']);
    $dirname = '';
    if ("/" != $path_parts['dirname'])
      $dirname = $path_parts['dirname'];

    $return_url = sprintf ('http://%s%s/index.php',
			   $_SERVER['SERVER_NAME'],
			   $dirname);
    //  echo "dirname: $dirname<br>\n";
    //  echo "return_url: $return_url<br>\n";
    $cancel_url = $return_url;

    $url = 'https://www.paypal.com/cgi-bin/webscr?';
    $url .= build_url_string ('cmd', '_xclick');
    $url .= build_url_string ('business', 'InteractiveLit@yahoo.com');
    $url .= build_url_string ('item_name', PAYPAL_ITEM_THURSDAY);
    $url .= build_url_string ('no_note', '0');
    $url .= build_url_string ('cn', 'Any notes about your payment?');
    $url .= build_url_string ('no_shipping', '1');
    $url .= build_url_string ('custom', $_SESSION[SESSION_LOGIN_USER_ID]);
    $url .= build_url_string ('currency_code', 'USD');
    $url .= build_url_string ('amount', $cost);
    $url .= build_url_string ('rm', '2');
    $url .= build_url_string ('cancel_return', $cancel_url);
    $url .= build_url_string ('return', $return_url, FALSE);

    //  echo "Return URL: $return_url<br>\n";
    //  echo "Encoded URL: $url<p>\n";
    //  printf ("%d characters<p>\n", strlen ($url));

    echo "<p><a href=\"$url\"><img\n";
    echo "src=\"http://images.paypal.com/images/x-click-but3.gif\"\n";
    echo "border=\"0\" align=\"right\"\n";
    printf ('alt=\"Click to pay for the %s Pre-Convention.\"></a>', CON_NAME);
  }

  printf ("Registration for the %s Pre-Convention costs \$$cost.\n",
	  CON_NAME);

  if ('' == $url)
    echo "You must be logged in to pay for the Pre-Convention using PayPal.\n";
  else
  {
    echo "You can pay in\n";
    echo "advance using Paypal by clicking <a href=\"$url\">here</a>.\n";
    echo "Please be sure to click the \"Return to Merchant\" button on the\n";
    echo "PayPal site when your transaction is complete to return to the\n";
    echo CON_NAME . " website so we can register your payment for the\n";
    echo "Pre-Convention in the database.</p>\n";
  }
  echo "<p>You will also be able to register for the Pre-Convention Events\n";
  echo "Thursday night, at the door.</p>\n";
  echo "<p>Check back here for the list of Events that will be part of the\n";
  echo CON_NAME . " Pre-Convention!</p>\n";
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
    case '':  $dont_care = 'selected'; break;
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

  if (empty ($_REQUEST['PreConEventId']))
    $PreConEventId = 0;
  else
    $PreConEventId = intval (trim ($_REQUEST['PreConEventId']));

  // If this is a new bid, just display the header

  if (0 == $PreConEventId)
    display_header ('Bid a Precon Event for ' . CON_NAME);
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

    display_header ('Update Precon Event <i>' . $_POST['Title'] . '</i>');
  }

  echo "<form method=\"POST\" action=\"Thursday.php\">\n";
  form_add_sequence();
  form_hidden_value ('action', PRECON_PROCESS_EVENT_FORM);

  echo "<p><font color=red>*</font> indicates a required field\n";
  echo "<table border=\"0\">\n";
  form_text(64, 'Title', '', 128, true);
  form_text(1, 'Length', 'Hours', 0, true, '(Hours)');
  form_text(64, 'Special Requirements', 'SpecialRequests', true);

  $text = "Description for use on the " . CON_NAME . " website.  This\n";
  $text .= "information will also be used for advertising and some\n";
  $text .= "flyers.  The description should be a couple of paragraphs,\n";
  $text .= "but can be as long as you like.\n";
  $text .= "<P>The description will be displayed in the user's browser.\n";
  $text .= "You must use HTML tags for formatting.  A quick primer on\n";
  $text .= "a couple of useful HTML tags is available\n";
  $text .= "<A HREF=HtmlPrimer.html TARGET=_blank>here</A>.\n";
  form_textarea ($text, 'Description', 15, TRUE, TRUE);
  
  form_section ('Scheduling Information');

  echo "  <tr>\n";
  echo "    <td colspan=\"2\">\n";
  echo "      <p>The con can schedule your event into one (or more) of the\n";
  echo "      time slots available during the Pre Convention.  The con has\n";
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
  schedule_table_entry ('Thursday', THR_DATE, 21, 3);
  schedule_table_entry ('Thursday', THR_DATE, 22);
  schedule_table_entry ('Thursday', THR_DATE, 23);
  schedule_table_entry ('Friday',   FRI_DATE, 12, 6);
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

function process_precon_event_form()
{
  // If we're updating a bid, grab the bid ID

  $PreConEventId = 0;
  if (array_key_exists ('PreConEventId', $_REQUEST))
    $PreConEventId = intval (trim ($_REQUEST['PreConEventId']));

  // If this is a new bid, just display the header

  if (0 == $PreConEventId)
    $sql = 'INSERT PreConEvents SET ';
  else
    $sql = 'UPDATE PreConEvents SET ';

  $sql .= build_sql_string('Title', $_REQUEST['Title'], false);
  $sql .= build_sql_string('Hours');
  $sql .= build_sql_string('SpecialRequests');
  //  $sql .= build_sql_string('InviteOthers');
  $sql .= build_sql_string('Thursday21');
  $sql .= build_sql_string('Thursday22');
  $sql .= build_sql_string('Thursday23');
  $sql .= build_sql_string('Friday12');
  $sql .= build_sql_string('Friday13');
  $sql .= build_sql_string('Friday14');
  $sql .= build_sql_string('Friday15');
  $sql .= build_sql_string('Friday16');
  $sql .= build_sql_string('Friday17');
  $sql .= build_sql_string('Description');
  $sql .= ', UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];
  if (0 == $PreConEventId)
    $sql .= ', SubmitterUserId=' . $_SESSION[SESSION_LOGIN_USER_ID];
  else
    $sql .= " WHERE PreConEventId=$PreConEventId";

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error ('Submission failed');
  else
    return true;

}

function display_event_summary()
{
  display_header ('PreCon Events');

  $sql = 'SELECT PreConEventId, Title, Hours, Status FROM PreConEvents';
  $sql .= ' ORDER BY Status, Title';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for PreCon Events failed', $sql);

  if (0 == mysql_num_rows($result))
  {
    echo "<p>There are no Pre Convention events yet.</p>";
    return true;
  }

  echo "<table border=\"1\">\n";
  echo "  <tr valign=\"bottom\">\n";
  echo "    <th rowspan=\"2\">Status</th>\n";
  echo "    <th rowspan=\"2\">Title</th>\n";
  echo "    <th colspan=\"3\">Runs</th>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <th>Track</th>\n";
  echo "    <th>Day</th>\n";
  echo "    <th>Start&nbsp;Time</th>\n";
  echo "  </tr>\n";

  while ($row = mysql_fetch_object($result))
  {
    $sql = 'SELECT Track, Day, StartHour FROM PreConRuns';
    $sql .= " WHERE PreConEventId=$row->PreConEventId";
    $sql .= ' ORDER BY Day, StartHour';

    $run_result = mysql_query($sql);
    if (! $run_result)
      return display_mysql_error ('Query for PreCon Event Runs failed', $sql);

    $rowspan = mysql_num_rows($run_result);
    if (0 == $rowspan)
      $rowspan = 1;

    echo "  <tr>\n";
    printf ('    <td rowspan="%d"><a href="Thursday.php?action=%d&Event=%d">' .
	    "%s</a></td>\n",
	    $rowspan,
	    PRECON_SHOW_STATUS_FORM,
	    $row->PreConEventId,
	    $row->Status);
    echo "    <td rowspan=\"$rowspan\">$row->Title</td>\n";

    if (0 == mysql_num_rows($run_result))
      echo "    <td colspan=\"3\">&nbsp;</td>\n";
    else
    {
      while ($run_row = mysql_fetch_object($run_result))
      {
	echo "    <td>$row_run->Track</td>\n";
	echo "    <td>$row_run->Day</td>\n";
	echo "    <td>$row_run->StartTime</td>\n";
      }
    }
    echo "  </tr>\n";
  }

  echo "</table>\n";
}

function show_status_form()
{
  // Fetch the PreConEventId

  $PreConEventId = intval (trim ($_REQUEST['Event']));
  if (0 == $PreConEventId)
    return display_error ('Invalid PreConEventId');

  $sql = 'SELECT Title, Hours, Status FROM PreConEvents';
  $sql .= " WHERE PreConEventId=$PreConEventId";

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error("Query for PreConEventId $PreConEventId failed",
			       $sql);

  if (0 == mysql_num_rows($result))
    return display_error ("Failed to find PreConEventId $PreConEventId");

  if (1 != mysql_num_rows($result))
    return display_error ("Found multiple rows for PreConEventId $PreConEventId");

  $row = mysql_fetch_object($result);

  $count = 0;
  if ('Accepted' == $row->Status)
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

  switch ($row->Status)
  {
    case 'Pending':  $pending =  'selected'; break;
    case 'Accepted': $accepted = 'selected'; break;
    case 'Rejected': $rejected = 'selected'; break;
    case 'Dropped':  $dropped =  'selected'; break;
  }

  display_header ("Change status of <i>$row->Title</i>\n");

  echo "<form method=\"POST\" action=\"Thursday.php\">\n";
  form_add_sequence();
  form_hidden_value('action', PRECON_PROCESS_STATUS_CHANGE);
  echo "<table>\n";

  echo "  <tr>\n";
  echo "    <td>Status:</td>\n";
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

  form_section ('Schedule Run (Only if Accepting)');
  
  echo "</table>\n";
  echo "</form>\n";
  
}

?>