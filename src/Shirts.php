<?
include ("intercon_db.inc");
include_once "StoreShirts.inc";
include_once "StoreOrder.inc";

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

// Display boilerplate

html_begin ();

// Figure out what we're supposed to do

if (array_key_exists ('action', $_REQUEST))
  $action = $_REQUEST['action'];
else
  $action = SHOW_SHIRT_FORM;

switch ($action)
{
  case SHOW_STORE_ITEM_FORM:
    show_store_item_form();
    break;

  case EDIT_STORE_ITEM:
    show_edit_item_form();
    break;

  case PROCESS_ITEM_FORM:
    if (! process_item_form())
      show_edit_item_form();
    else
      show_store_item_form();
    break;

  case DELETE_STORE_ITEM:
    delete_store_item();
    show_store_item_form();
    break;

  case SHOW_SHIRT_FORM:
    show_shirt_form();
    break;

  case PROCESS_SHIRT_FORM:
    if (! process_shirt_form())
      show_shirt_form();
    break;

  case SHOW_SHIRT_SUMMARY:
    show_shirt_summary();
    break;

  case SHOW_SHIRT_REPORT:
    show_shirt_report();
    break;

  case SHOW_INDIV_SHIRT_FORM:
    show_indiv_shirt_form();
    break;

  default:
    display_error ("Unknown action code: $action");
}

// Standard postamble

html_end ();

function render_dropdown_list(&$list, $key)
{
  echo "      <select name=$key>\n";

  foreach($list as $k => $v)
  {
    echo "        <option value=\"$k\"";
    if ($_POST[$key] == $k)
      echo ' selected';
    echo ">$v</option>\n";
  }

  echo "      </select>\n";
}

function form_dropdown_list(&$list, $display, $key='')
{
  if ('' == $key)
    $key = $display;

  echo "  <tr>\n";
  echo "    <td align=\"right\">$display:</td>\n";
  echo "    <td>\n";

  render_dropdown_list($list, $key);

  echo "    </td>\n";
  echo "  </tr>\n";
}

/*
 * quantity_form_text
 *
 * Add a text input field
 */
function quantity_form_text($display, $key='')
{
  // If not specified, fill in default values
  if ($key == '')
    $key = $display;

  if ("" != $display)
    $display .= ":";

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

  printf ("<input type=\"text\" name=\"%s\" size=\"2\" maxlength=\"2\" value=\"%s\">",
	  $key,
	  htmlspecialchars ($text));
}

function show_store_item_form()
{
  // Make sure that only users with Staff priv view this page
  if (! user_has_priv (PRIV_STAFF))
    return display_access_error ();

  // Load the available shirts from the database
  $shirts = new StoreShirts();
  if (! $shirts->load_from_db())
    return false;

  // Display the sales form
  $shirts->render_management_form();
}

/*
 * form_gender
 *
 * Display gender as a dropdown list.
 */

function form_gender($display, $key='')
{
  if ('' == $key)
    $key = $display;

  $list = array ("Men's" => "Men's",
		 "Women's" => "Women's",
		 "Unisex" => "Unisex");

  if (! array_key_exists($key, $_POST))
    $_POST[$key] = "Unisex";

  form_dropdown_list($list, $display, $key);
}

function form_yes_no($display, $key='')
{
  if ('' == $key)
    $key = $display;

  $list = array('Y' => "Yes", "N" => "No");

  if (! array_key_exists($key, $_POST))
    $_POST[$key] = 'N';

  form_dropdown_list($list, $display, $key);
}

/*
 * show_edit_item_form
 *
 * Display the form that allows a user with Staff privs to modify
 * a shirt
 */
function show_edit_item_form()
{
  // Make sure that only users with Staff priv view this page
  if (! user_has_priv (PRIV_STAFF))
    return display_access_error ();

  //  dump_array ('REQUEST', $_REQUEST);

  $ItemId = 0;
  if (array_key_exists('ItemId', $_REQUEST))
    $ItemId = intval($_REQUEST['ItemId']);

  if (0 == $ItemId)
    return display_error('Invalid ItemId');

  // If ItemId is -1, we're adding a new item
  if (-1 == $ItemId)
  {
    StoreItem::load_POST_defaults();
  }
  else
  {
    // Load date from the database into the $_POST array
    $item = new StoreItem();
    $item->load_from_db($ItemId);
    $item->load_POST();
  }

  display_header("Edit item $ItemId: " . $item->name());
  echo "<form method=post action=Shirts.php>\n";
  form_add_sequence();
  form_hidden_value('action', PROCESS_ITEM_FORM);
  form_hidden_value('ItemId', $ItemId);
  echo "<table>\n";
  form_yes_no('For Sale', 'Available');
  form_gender('Gender');
  form_text(64, 'Style');
  form_text(64, 'Singular');
  form_text(64, 'Plural');
  form_text(32, 'Color');
  form_text(64, 'Sizes', '', 128);
  form_text(64, 'ThumbnailFilename', '', 128);
  form_text(64, 'ImageFilename', '', 128);
  form_text( 6, 'Price in Cents', 'PriceCents');
  if (-1 == $ItemId)
    form_submit("Add Item");
  else
    form_submit("Update Item");
  echo "</table>\n";
  echo "</form>\n";
}

/*
 * process_item_form
 *
 * Add or modify an item in the store
 */
function process_item_form()
{
  // Make sure that only users with Staff priv view this page
  if (! user_has_priv (PRIV_STAFF))
    return display_access_error ();

  // Make sure the user hasn't used the back key
  if (out_of_sequence ())
    return display_sequence_error (false);

  // Make sure the data is valid
  if (! StoreItem::validate_POST_data())
    return false;

  // Write the data in the $_POST array to the database
  return StoreItem::write_db_from_POST();
}

/*
 * delete_store_item
 *
 * Delete an item from the store
 */
function delete_store_item()
{
  // Make sure that only users with Staff priv view this page
  if (! user_has_priv (PRIV_STAFF))
    return display_access_error ();

  // Delete the item identifed by $_REQUEST['ItemId']
  StoreItem::delete_from_db();
}

function no_new_shirt_orders()
{
  $shirt_close = strftime ('%d-%b-%Y', parse_date (SHIRT_CLOSE));

  // See if there are any unpaid orders in the database for this user?
  $sql = 'SELECT * FROM StoreOrders';
  $sql .= ' WHERE UserId=' . $_SESSION[SESSION_LOGIN_USER_ID];
  $sql .= '   AND Status="Unpaid"';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for StoreOrders record failed', $sql);

  $row = mysql_fetch_object($result);
  if (! $row)
  {
    // If you don't have an order pending and it's past the deadline you
    // are SOL.  Try to buy one at the Con.
    echo "The order deadline for shirts was $shirt_close.  When you\n";
    echo "checking at registration at the con ask if there are any shirts\n";
    echo "in your size.<p>\n";
    return true;
  }

  $order = new StoreOrder();
  $order->load_from_row($row);
  $order->list_entries();
}

function show_shirt_form()
{
  $shirt_close = strftime ('%d-%b-%Y', parse_date (SHIRT_CLOSE));
  $email = mailto_or_obfuscated_email_address (EMAIL_OPS);

  // If it's past the shirt deadline, you can pay for existing orders
  // but we're not accepting new orders
  if (past_shirt_deadline())
    return no_new_shirt_orders();

  // Load the available shirts from the database
  $shirts = new StoreShirts();
  if (! $shirts->load_from_db())
    return false;

  // See if there are any unpaid orders in the database for this user?
  $sql = 'SELECT * FROM StoreOrders';
  $sql .= ' WHERE UserId=' . $_SESSION[SESSION_LOGIN_USER_ID];
  $sql .= '   AND Status="Unpaid"';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for StoreOrders record failed', $sql);

  $order = new StoreOrder();

  $row = mysql_fetch_object($result);
  if ($row)
  {
    $order->load_from_row($row);
    $order->populate_POST();
  }

  display_header ('Don\'t Lose Your Shirt!');

  echo "Only a small number of " . CON_NAME . " shirts will be available\n";
  echo "for sale at the convention.  The only way to guarantee that you get\n";
  echo "the shirt you want is to order and pay for it now.<p>\n";

  // Display what's for sale
  $num_shirts = $shirts->num_available_shirts();
  echo "<p>This year there are $num_shirts shirts available.\n";
  echo "All shirts are 100% cotton. Click on a shirt image to see a\n";
  echo "larger image with details of the " . CON_NAME . " logo.</p>\n";

  $order->has_unavailable_shirt();

  if ($order->has_unavailable_shirt())
    $order->render_conversion_form($shirts);
  else
    $shirts->render_sales_form($order);

  echo "<p>If you want a size that's not listed on the website,\n";
  echo 'please contact ' . NAME_OPS . " at $email.<p>\n";
  echo "We will be ordering fewer shirts this year, so if you want\n";
  echo "to be sure of getting one in your size, be sure to order it\n";
  echo "now. <b>The deadline for shirt orders is $shirt_close.</b>\n";
  echo "Why wait and risk losing your shirt?<p>\n";

  $cost = $order->cost();
  if ($cost > 0)
  {
    $OrderId = $order->order_id();
    $url = build_paypal_url($order->order_id(), $cost);

    //    echo "<p>Paypal URL (pay no attention to the browser parsing error\n";
    //    echo "around &amp;curren): $url</p>\n";

    echo "<p>To complete your purchase, click <a href=$url>here to pay\n";
    echo "\$$cost using PayPal</a>.  Please be sure to click the \"Return\n";
    echo "to Merchant\" button on the PayPal site to return to the\n";
    echo CON_NAME . " website to register your payment for the shirts.</p>\n";
    echo "<p>If you don't want to join PayPal, you can send a check or money\n";
    echo "order for \$$cost made out to\n";
    echo "&quot<b>New&nbsp;England&nbsp;Interactive&nbsp;Literature</b>&quot;.\n";
    echo "Send it to:<br>\n";
    echo "<table>\n";
    echo "  <tr>\n";
    echo "    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
    printf ("    <td><b>%s<br>c/o %s<br>%s</b></td>\n",
	    CON_NAME,
	    NAME_SEND_CHECKS,
	    ADDR_SEND_CHECKS);
    echo "  </tr>\n";
    echo "</table>\n";
  }
}

function cancel_shirt_order()
{
  $order = new StoreOrder();
  if (! $order->load_from_POST())
    return false;

  // Cancel the order.  This will update the database
  $order->cancel_order();

  // Return false so the (empty) order form will be displayed
  return display_error('Order ' . $order->order_id() . ' cancelled.');
}

function build_paypal_url($OrderId, $cost)
{
  // If this is a development installation, force the price down to a nickel.
  // I'm willing to spend 5 cents/test, but not full price
  if (DEVELOPMENT_VERSION)
    $cost = '0.05';

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
  $url .= build_url_string ('business', PAYPAL_ACCOUNT_EMAIL);
  $url .= build_url_string ('item_name', PAYPAL_ITEM_SHIRT);
  $url .= build_url_string ('no_note', '0');
  $url .= build_url_string ('cn', 'Any notes about your payment?');
  $url .= build_url_string ('no_shipping', '1');
  $url .= build_url_string ('custom', $OrderId);
  $url .= build_url_string ('amount', $cost);
  $url .= build_url_string ('currency_code', 'USD');
  $url .= build_url_string ('rm', '2');
  $url .= build_url_string ('cancel_return', $cancel_url);
  $url .= build_url_string ('return', $return_url, FALSE);

  return $url;
}

function process_shirt_form()
{
  // Make sure the user hasn't used the back key
  if (out_of_sequence ())
    return display_sequence_error (false);
  /*
  echo "<p>process_shirt_form</p>\n";
  echo "<pre>\n";
  print_r($_POST);
  echo "</pre>\n";
  */

  $BtnAction = '';
  if (array_key_exists('BtnAction', $_POST))
    $BtnAction = $_POST['BtnAction'];

  //  echo "<p>BtnAction: $BtnAction</p>\n";

  switch($BtnAction)
  {
    case 'Submit':
    case 'Update':
      break;      // Continue processing

    case 'Cancel':
      return cancel_shirt_order();

    default:
      return display_error("Unexpected BtnAction: \"$BtnAction\"");
  }

  // Load the order information from the $_POST array
  $order = new StoreOrder();
  if (! $order->load_from_POST())
    return false;

  // Add the order to the database
  $order->write_to_db();

  // Display it to the user
  $order->list_entries();

  $cost = $order->cost();

  $OrderId = $order->order_id();
  $url = build_paypal_url($order->order_id(), $cost);

  echo "<p>Paypal URL (pay no attention to the browser parsing error\n";
  echo "around &amp;curren): $url</p>\n";

  echo "<p><b>Note:</b> Your order is not complete until you pay for it!</p>\n";
  echo "<p>To complete your purchase, click <a href=$url>here</a> to\n";
  echo "pay \$$cost using PayPal.  Please be sure to click the \"Return\n";
  echo "to Merchant\" button on the PayPal site to return to the\n";
  echo CON_NAME . " website to register your payment for the shirts.</p>\n";
  echo "<p>If you don't want to join PayPal, you can send a check or money\n";
  echo "order for \$$cost made out to\n";
  echo "&quot<b>New&nbsp;England&nbsp;Interactive&nbsp;Literature</b>&quot;.\n";
  echo "Send it to:<br>\n";
  echo "<table>\n";
  echo "  <tr>\n";
  echo "    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
  printf ("    <td><b>%s<br>c/o %s<br>%s</b></td>\n",
	  CON_NAME,
	  NAME_SEND_CHECKS,
	  ADDR_SEND_CHECKS);
  echo "  </tr>\n";
  echo "</table>\n";

  return true;
}

function show_shirt_summary()
{
  // You need ConCom privilege to see this page
  if (! user_has_priv (PRIV_CON_COM))
    return display_access_error ();

  display_header (CON_NAME . ' Shirt Order Summary');

  $shirts = array();
  $quantities =
    array('Small' => 0,
	  'Medium' => 0,
	  'Large' => 0,
	  'XLarge' => 0,
	  'X2Large' => 0,
	  'X3Large' => 0);
  $sizes =
    array('Small', 'Medium', 'Large', 'XLarge', 'X2Large', 'X3Large');

  // Build the array of shirts
  $sql  = 'SELECT ItemId, Color, Gender, Style, Plural';
  $sql .= '  FROM StoreItems';
  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Query for shirts failed', $sql);

  while ($row = mysql_fetch_object ($result))
  {
    $shirts[$row->ItemId] =
      array('Description' =>
	        "$row->Gender $row->Style $row->Plural - $row->Color",
	    'Paid' => $quantities,
	    'Unpaid' => $quantities);
  }

  $sql  = 'SELECT StoreOrders.Status, StoreOrderEntries.ItemId,';
  $sql .= '       StoreOrderEntries.Quantity, StoreOrderEntries.Size';
  $sql .= '  FROM StoreOrders,StoreOrderEntries';
  $sql .= ' WHERE StoreOrders.OrderId=StoreOrderEntries.OrderId';
  $sql .= '   AND StoreOrders.Status!="Cancelled"';

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Query for orders failed', $sql);

  while ($row = mysql_fetch_object ($result))
  {
    $shirts[$row->ItemId][$row->Status][$row->Size] += $row->Quantity;
  }

  echo "<table border=\"1\">\n";
  echo "<tr>\n";
  echo "<th align=\"left\">Shirt</th>\n";
  foreach($sizes as $s)
    echo "<th>$s</th>";
  echo "</tr>\n";
  foreach($shirts as $shirt)
  {
    echo "<tr>\n";
    printf("<td>%s</td>\n", $shirt['Description']);
    foreach($sizes as $s)
    {
      $unpaid = $shirt['Unpaid'][$s];
      $total = $shirt['Paid'][$s] + $unpaid;
      if (0 == $total)
	echo "<td>&nbsp;</td>";
      else
      {
	if (0 == $unpaid)
	  printf("<td align=\"center\">%d</td>", $shirt['Paid'][$s]);
        else
	  printf("<td align=\"center\">%d<br />(%d&nbsp;Unpaid)</td>",
		 $shirt['Paid'][$s] + $unpaid, $unpaid);
      }
    }
    echo "</tr>\n";
  }
  echo "</table>\n";
}

function show_shirt_report ()
{
  // You need Staff privilege to see this page
  if (! user_has_priv (PRIV_REGISTRAR))
    return display_access_error ();

  display_header (CON_NAME . ' Shirt Order Report');

  // Load the available shirts from the database
  $shirts = new StoreShirts();
  if (! $shirts->load_from_db())
    return false;

  // Get the list of orders
  $sql = 'SELECT Users.FirstName, Users.LastName, Users.EMail,';
  $sql .= 'StoreOrders.*';
  $sql .= ' FROM StoreOrders, Users';
  $sql .= ' WHERE Users.UserId=StoreOrders.UserId';
  $sql .= '   AND StoreOrders.Status != "Cancelled"';
  $sql .= ' ORDER BY LastName, FirstName';

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Query for StoreOrders failed', $sql);

  echo "<p>Click on the user name to send mail<br>\n";
  echo "Click on the status to update the order<br>\n";
  
  echo "<table border=\"1\">\n";
  echo "<tr>\n";
  echo "<th align=\"left\">User</th>\n";
  //  echo "<th>Order Id</th>\n";
  echo "<th>Status</th>\n";
  echo "<th>Order Details\n";
  echo "</tr>\n";
  while ($row = mysql_fetch_object($result))
  {
    echo "<tr valign=\"top\">\n";
    echo "<td><a href=mailto:$row->EMail>$row->LastName, " .
         "$row->FirstName</a></td>\n";
    //    echo "<td align=\"center\">$row->OrderId</td>\n";
    printf("<td><a href=\"Shirts.php?action=%d&OrderId=%d\">$row->Status" .
	   "</a></td>\n", SHOW_INDIV_SHIRT_FORM, $row->OrderId);
    echo "<td>\n";
    $order = new StoreOrder;
    if ($order->load_from_order_id($row->OrderId))
      $order->list_entries_for_report();
    unset($order);
    echo "</td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}

function show_indiv_shirt_form()
{
  // Load the available shirts from the database
  $shirts = new StoreShirts();
  if (! $shirts->load_from_db())
    return false;

  // Get the OrderId from the get or post
  $OrderId = 0;
  if (array_key_exists('OrderId', $_REQUEST))
    $OrderId = intval(trim($_REQUEST['OrderId']));
  if (0 == $OrderId)
    return display_error('Failed to find OrderId');

  // Load the specified order
  $order = new StoreOrder();
  if (! $order->load_from_order_id($OrderId))
    return false;
  $order->populate_POST();

  display_header ('Shirt Order for ' . $order->user_name());

  $shirts->render_sales_form($OrderId, $order);
}

?>
