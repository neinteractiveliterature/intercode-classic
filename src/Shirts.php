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

  case SHOW_ADMIN_SHIRT_FORM:
    show_admin_shirt_form();
    break;

  case PROCESS_ADMIN_SHIRT_FORM:
    if (! process_admin_shirt_form())
      show_admin_shirt_form();
    else
      show_shirt_report();
    break;

  case IMPORT_TSHIRTS:
    import_tshirts();
    break;

  case PROCESS_CONVERSION_FORM:
    if (! process_conversion_form())  // Returns false for unpaid orders
      show_shirt_form();
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

function convert_unavailable_shirts($OrderId, &$shirts)
{
  $order = new StoreOrder();

  $order->load_from_order_id($OrderId);

  if ('Unpaid' == $order->status())
  {
    echo "<p>You have ordered one or more unavailable shirts and not yet\n";
    echo "paid for them. Please do one of the following:</p>\n";
    echo "<ul>";
    echo "<li>You can cancel your order by clicking the \"Cancel\" ";
    echo "button.</li>\n";
    echo "<li>You can update your order by select from the available\n";
    echo "shirts and then click \"Update\" button.</li>\n";
    echo "</ul>\n";
    echo "<p>You'll be able to pay for your shirts once you've selected\n";
    echo "shirts from the list available for " . CON_NAME . " and updated\n";
    echo "your order.</p>\n";
  }
  else
  {
    echo "<p>You have paid for one or more unavailable shirts.  Please\n";
    echo "update your order by selecting from the available shirts in the\n";
    echo "dropdown list(s) and click the \"Update\" button.</p>\n";
  }
  $order->render_conversion_form($shirts);
}

function show_shirt_form()
{
  display_header ('Don\'t Lose Your Shirt!');

  $shirt_close = strftime ('%d-%b-%Y', parse_date (SHIRT_CLOSE));
  $email = mailto_or_obfuscated_email_address (EMAIL_OPS);

  // Load the available shirts from the database
  $shirts = new StoreShirts();
  if (! $shirts->load_from_db())
    return false;

  echo "Only a small number of " . CON_NAME . " shirts will be available\n";
  echo "for sale at the convention.  The only way to guarantee that you get\n";
  echo "the shirt you want is to order and pay for it now.<p>\n";

  // Display what's for sale
  $num_shirts = $shirts->num_available_shirts();
  echo "<p>This year there are $num_shirts 100% cotton shirts available.\n";
  echo "Click on a shirt image to see a larger image with details of the\n";
  echo CON_NAME . " logo.</p>\n";

  // See if there are any orders with shirts that need to be converted
  $sql  = 'SELECT StoreOrders.OrderId';
  $sql .= '  FROM StoreOrders, StoreOrderEntries, StoreItems';
  $sql .= ' WHERE StoreOrders.UserId=' . $_SESSION[SESSION_LOGIN_USER_ID];
  $sql .= '   AND StoreOrderEntries.OrderId=StoreOrders.OrderId';
  $sql .= '   AND StoreItems.ItemId=StoreOrderEntries.ItemId';
  $sql .= '   AND StoreItems.Available="N"';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for unavailable shirts failed', $sql);

  $row = mysql_fetch_object($result);
  if ($row)
  {
    return convert_unavailable_shirts($row->OrderId, $shirts);
  }

  $order = new StoreOrder();

  // See if there are any unpaid orders in the database for this user?
  $sql = 'SELECT * FROM StoreOrders';
  $sql .= ' WHERE UserId=' . $_SESSION[SESSION_LOGIN_USER_ID];
  $sql .= '   AND Status="Unpaid"';

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for StoreOrders record failed', $sql);

  $row = mysql_fetch_object($result);
  if ($row)
  {
    $order->load_from_row($row);
    $order->populate_POST();
  }

  /*
  // If it's past the shirt deadline, you can pay for existing orders
  // but we're not accepting new orders
  if (past_shirt_deadline())
    return no_new_shirt_orders();
  */

  $shirts->render_sales_form($order, false, PROCESS_SHIRT_FORM);

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
  /*
  echo "<!-- process_shirt_form -->\n";
  echo "<pre>\n";
  print_r($_POST);
  echo "</pre>\n";
  */

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
  if (! $order->write_to_db(false))
    return false;

  // Display it to the user
  $order->list_entries();

  $cost = $order->cost();

  $OrderId = $order->order_id();
  $url = build_paypal_url($order->order_id(), $cost);

  // echo "<p>Paypal URL (pay no attention to the browser parsing error\n";
  // echo "around &amp;curren): $url</p>\n";

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

function process_admin_shirt_form()
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

  // Load the order information from the $_POST array
  $order = new StoreOrder();
  if (! $order->load_from_POST())
    return false;

  // Add the order to the database
  if (! $order->write_to_db(true))
    return false;

  // Display it to the user
  //  $order->list_entries();

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
  // You need ConCom privilege to see this page
  if (! user_has_priv (PRIV_CON_COM))
    return display_access_error ();

  display_header (CON_NAME . ' Shirt Order Report');

  $can_edit = user_has_priv(PRIV_REGISTRAR);

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

  if ($can_edit)
  {
    echo "<p>Click on the user name to send mail<br>\n";
    echo "Click on the status to update the order<br>\n";
  }
  
  echo "<table border=\"1\">\n";
  echo "<tr>\n";
  echo "<th align=\"left\">User</th>\n";
  //  echo "<th>Order Id</th>\n";
  echo "<th>Status</th>\n";
  echo "<th>Order Details\n";
  echo "</tr>\n";
  while ($row = mysql_fetch_object($result))
  {
    $bgcolor = '';
    $payment = '';
    if ('Unpaid' == $row->Status)
      $bgcolor = " style=\"background-color: #ffcccc;\"";
    else
      $payment = sprintf(' $%d.%02d',
			 $row->PaymentCents / 100,
			 $row->PaymentCents % 100);

    echo "<tr valign=\"top\"$bgcolor>\n";
    if ($can_edit)
    {
      echo "<td><a href=mailto:$row->EMail>$row->LastName, " .
	"$row->FirstName</a></td>\n";
      printf("<td align=\"center\">" .
	     "<a href=\"Shirts.php?action=%d&OrderId=%d\">%s%s</a></td>\n",
	     SHOW_ADMIN_SHIRT_FORM, $row->OrderId, $row->Status, $payment);
    }
    else
    {
      echo "<td>$row->LastName, $row->FirstName</td>\n";
      echo "<td align=\"center\">$row->Status</td>\n";
    }
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

function show_admin_shirt_form()
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

  display_header ('Shirt Order # ' . $order->order_id() .
		  ' - ' . $order->user_name());

  $shirts->render_sales_form($order, true, PROCESS_ADMIN_SHIRT_FORM);
}

function add_nonzero_entry(&$row, $size, $OrderId, &$shirts)
{
  /*
  echo "<pre>\n";
  print_r($row);
  echo "</pre>\n";
  */
  // If there's nothing in this size, skip it
  if (0 == $row[$size])
    return true;

  // If this is from shirt 2, then there will be a trailing "_2"
  $parts = explode("_", $size);
  if (1 == count($parts))
    $ItemId = 1;
  else
    $ItemId = 2;
  $new_size = $parts[0];
  if ('XXLarge' == $new_size)
    $new_size = 'X2Large';

  $sql = 'INSERT StoreOrderEntries SET ';
  $sql .= build_sql_string('OrderId', $OrderId, false);
  $sql .= build_sql_string('ItemId', $ItemId);
  $sql .= build_sql_string('PricePerItemCents', 2000);
  $sql .= build_sql_string('Quantity', $row[$size]);
  $sql .= build_sql_string('Size', $new_size);
  $sql .= build_sql_string('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID]);

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Failure to INSERT StoreOrders record', $sql);

  $shirts += $row[$size];

  return true;
}

function import_tshirts()
{
  // You need Staff privilege to see this page
  if (! user_has_priv (PRIV_REGISTRAR))
    return display_access_error ();

  $sql = 'SELECT * FROM TShirts';
  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('Query for TShirts failed', $sql);

  $skipped = 0;
  $recs = 0;
  $shirts = 0;

  while ($row = mysql_fetch_object($result))
  {
    if ('Cancelled' == $row->Status)
      continue;

    // Users who don't follow instructions and enter new orders are a
    // pain in the butt.  Yes, I'm thinking of you Anna.
    if ('Unpaid' == $row->Status)
    {
      $sql  = 'SELECT OrderId FROM StoreOrders';
      $sql .= " WHERE UserId=$row->UserId";
      $sql .= '   AND Status="Unpaid"';

      $check_result = mysql_query($sql);
      if (! $check_result)
	return display_mysql_error("Check for user $row->UserId failed", $sql);

      if (0 != mysql_num_rows($check_result))
      {
	user_id_to_name ($row->UserId, $name, true);
	echo "<p>Skipping order for $name since there's already an Unpaid\n";
	echo "StoreOrders record for them.</p>\n";
	$skipped++;
	unset($check_results);
	continue;
      }
    }

    if ('Paid' == $row->Status)
    {
      $sql  = 'SELECT PaymentNote FROM StoreOrders';
      $sql .= " WHERE UserId=$row->UserId";
      $sql .= '   AND Status="Paid"';

      $check_result = mysql_query($sql);
      if (! $check_result)
	return display_mysql_error("Check for user $row->UserId failed", $sql);

      $paid_order_found = false;
      while ($check_row = mysql_fetch_object($check_result))
      {
	if ($check_row->PaymentNote == $row->PaymentNote)
	{
	  $paid_order_found = true;
	  break;
	}
      }
      unset($check_row);
      unset($check_result);
      if ($paid_order_found)
      {
	user_id_to_name ($row->UserId, $name, true);
	echo "<p>Skipping order for $name since there's already a Paid\n";
	echo "order for them with the same transaction note.</p>\n";
	$skipped++;
	continue;
      }
    }

    $recs++;

    $sql = 'INSERT StoreOrders SET ';
    $sql .= build_sql_string('UserId', $row->UserId, false);
    $sql .= build_sql_string('Status', $row->Status);
    $sql .= build_sql_string('PaymentCents', $row->PaymentAmount);
    $sql .= build_sql_string('PaymentNote', $row->PaymentNote);
    $sql .= build_sql_string('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID]);

    $order_result = mysql_query($sql);
    if (! $order_result)
      return display_mysql_error('Failure to INSERT StoreOrders record', $sql);

    $OrderId = mysql_insert_id();

    $row_as_array = (array)$row;

    add_nonzero_entry($row_as_array, 'Small', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'Medium', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'Large', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'XLarge', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'XXLarge', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'X3Large', $OrderId, $shirts);

    add_nonzero_entry($row_as_array, 'Small_2', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'Medium_2', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'Large_2', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'XLarge_2', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'XXLarge_2', $OrderId, $shirts);
    add_nonzero_entry($row_as_array, 'X3Large_2', $OrderId, $shirts);
  }

  echo "<p>Converted $recs TShirt records, containing $shirts shirts.</p>\n";
  echo "<p>$skipped records skipped.</p>";
}

function process_conversion_form()
{
  $OrderId = 0;
  if (array_key_exists('OrderId', $_POST))
    $OrderId = intval(trim($_POST['OrderId']));

  if (0 == $OrderId)
    return display_error("Invalid OrderId");

  $Status = '';
  if (array_key_exists('Status', $_POST))
    $Status = trim($_POST['Status']);
  if (('Paid' != $Status) && ('Unpaid' != $Status))
    return display_error("Invalid status $Status");

  if ('Cancel' == $_POST['BtnAction'])
  {
    $sql = "UPDATE StoreOrders SET Status='Cancelled',";
    $sql .= '       UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];
    $sql .= " WHERE OrderId=$OrderId";

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error("Failed to cancel order", $sql);
    return true;
  }

  foreach ($_POST as $k => $v)
  {
    if ('' == $v)
      continue;

    $components = explode('-', $k);
    if (3 != count($components))
      continue;

    if ('entry' != $components[0])
      continue;

    $size = $components[1];
    $entry_id = $v;

    $key = "entry_id-$entry_id";
    $new_item_id = 0;
    if (array_key_exists($key, $_POST))
      $new_item_id = intval(trim($_POST[$key]));

    if (0 == $new_item_id)
      return display_error("Failed to find key $key");

    $sql  = "UPDATE StoreOrderEntries SET ItemId=$new_item_id,";
    $sql .= '       UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];
    $sql .= " WHERE OrderEntryId=$entry_id";

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('StoreOrderEntries update failed', $sql);
  }

  $sql  = 'UPDATE StoreOrders SET  UpdatedById=' .
              $_SESSION[SESSION_LOGIN_USER_ID];
  $sql .= " WHERE OrderId=$OrderId";

  $result = mysql_query($sql);
  if (! $result)
    return display_mysql_error('StoreOrders update failed', $sql);

  if ('Unpaid' == $Status)
    return false;

  StoreOrder::show_shirts_for_homepage();
  return true;
}

?>
