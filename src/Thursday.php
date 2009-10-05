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
    $page = "ThursdaySchedule.html";
    if (! is_readable ($page))
      display_error ("Unable to read $page");
    else
      readfile ($page);
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

  default:
    echo "Unknown action code: $action\n";
}

html_end();

/*
 * thursday_thing
 *
 * Display information about the Thursday Thing
 */

function thursday_thing()
{
  echo "<h2>Thursday Thing<font color=\"red\"> - New for 2009!</font></h2>\n";

  printf ("<p>The %s Thursday Thing is a one-day\n", CON_NAME);
  echo "conference about the writing, production, and play of live action\n";
  echo "roleplaying games.  There will be panels, discussions, and\n";
  echo "interactive workshops discussing LARP theory, costuming, writing\n";
  echo "techniques, play styles, and a variety of other topics.</p>\n";

  echo "<p>The Thursday Thing will run from Thursday evening to the opening\n";
  printf ("of %s on Friday evening, at the Chelmsford Radisson.</p>\n",
	  CON_NAME);

  echo "<p>If you'd like to propose a panel, discussion or workshop\n";
  printf ("please contact %s at %s.</p>\n",
	  NAME_THURSDAY,
	  mailto_or_obfuscated_email_address (EMAIL_THURSDAY));
  
  $sql = 'SELECT * FROM Thursday';
  $sql .= ' WHERE UserId=' . $_SESSION[SESSION_LOGIN_USER_ID];

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error ('Query for Thursday Thing record failed',
				$sql);
  if (0 != mysql_num_rows($result))
  {
    $row = mysql_fetch_object($result);
    if ('Paid' == $row->Status)
    {
      echo "<p>You are signed up for the Thursday Thing</p>\n";
      return;
    }
  }

  $cost = '5.00';
  if (DEVELOPMENT_VERSION)
    $cost= '0.05';

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
  printf ('alt=\"Click to pay for %s Thursday Thing\"></a>', CON_NAME);
  echo "Registration for the Thursday Thing costs \$$cost.  You can pay in\n";
  echo "advance using Paypal by clicking <a href=\"$url\">here</a>.\n";
  echo "Please be sure to click the \"Return to Merchant\" button on the\n";
  echo "PayPal site when your transaction is complete to return to the\n";
  echo CON_NAME . " website so we can register your payment for the\n";
  echo "Thursday Thing in the database.</p>\n";

  echo "<p>You can also register for the Thursday Thing Thursday night, at\n";
  echo "the door.</p>\n";
}

/*
 * thursday_report
 *
 * Show who's paid for the Thursday Thing
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
    return display_mysql_error ('Failed to get list of Thursday attendees',
				$sql);

  $count = mysql_num_rows($result);
  display_header ("$count Paid Thursday Thing Attendees");
  
  while ($row = mysql_fetch_object($result))
  {
    echo "$row->LastName, $row->FirstName<br>\n";
  }
  echo "<br>\n";
}

function select_from_all_users ($header, $href)
{
  // Get a list of all people signed up for Thursday

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

  dump_array ('$thursday_users', $thursday_users);

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
 * Thursday Thing status
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

  select_thursday_user ('Select User To Edit Thursday Thing Info',
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
      return display_mysql_error ("Thursday Query for UserId $UserId failed");

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
  display_header ("Thursday Thing Info for $name");

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
    printf ("Thursday Thing info last updated %s by %s\n",
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

?>