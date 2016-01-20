<?php

include_once "StoreOrderEntry.inc";

class StoreOrder
{
  private $order_id;
  private $user_id;
  private $status;
  private $payment;  /* In cents */
  private $payment_note;
  private $updated_by_id;
  private $last_updated;
  private $entries;

  public function __construct()
  {
    $this->entries = array();
    $this->order_id = -1;
  }

  public function last_updated() { return $this->last_updated; }

  public function order_id() { return $this->order_id; }

  public function payment()
  {
    return sprintf("%d.%02d", $this->payment / 100, $this->payment % 100);
  }

  public function status() { return $this->status; }

  public function updated_by_name()
  {
    $name = 'Unknown';
    user_id_to_name($this->updated_by_id, $name, true);

    return $name;
  }

  public function load_from_POST()
  {
    /*
    echo "<p>StoreOrder::load_from_POST</p>\n";
    echo "<pre> _POST:\n";
    print_r($_POST);
    echo "</pre>\n";
    */

    $ok = true;

    $this->order_id = 0;
    if (array_key_exists('OrderId', $_POST))
      $this->order_id = trim($_POST['OrderId']);

    if (0 == $this->order_id)
      $ok = display_error("Failed to find OrderId in _POST data");

    $this->status = '';
    if (array_key_exists('Status', $_POST))
    {
      $this->status = trim($_POST['Status']);
      if (('Unpaid' != $this->status) &&
	  ('Paid' != $this->status) &&
	  ('Cancelled' != $this->status))
	$ok = display_error("Invalid status: $this->status");
    }

    if (array_key_exists('PaymentNote', $_POST))
      $this->payment_note = trim($_POST['PaymentNote']);

    $payment_ok = false;
    if (array_key_exists('Payment', $_POST))
    {
      $Payment = $_POST['Payment'];
      $dollars = 0;
      $cents = 0;
      $parts = explode('.', $Payment);
      switch(count($parts))
      {
        case 1:
	  if (! is_numeric($parts[0]))
	    $ok = display_error("Invalid payment value: $Payment");
	  else
	  {
	    $dollars = intval($parts[0]);
	    $payment_ok = true;
	  }
	  break;

        case 2:
	  if (! (is_numeric($parts[0]) && is_numeric($parts[1])))
	    $ok = display_error("Invalid payment value: $Payment");
	  else
	  {
	    $dollars = intval($parts[0]);
	    $cents = intval($parts[1]);
	    if (($cents > 99) || ($cents < 0))
	      $ok = display_error("Invalid payment value: $Payment");
	    else
	      $payment_ok = true;
	  }
	  break;

        default:
	  $ok = display_error("Invalid payment value: $Payment");
      }
      if ($payment_ok)
	$this->payment = ($dollars * 100) + $cents;
    }

    foreach($_POST as $k => $v)
    {
      if ('' == $v)
	continue;

      $components = explode('-', $k);
      if (3 != count($components))
	continue;

      if ('size' != $components[0])
	continue;

      $size = $components[1];
      $item_id = $components[2];

      $quantity = trim($v);
      if (! is_numeric($quantity))
      {
	$ok = display_error("<p>Invalid value \"$quantity\" for $size</p>\n");
	continue;
      }

      if ($quantity < 0)
      {
	$ok = display_error("<p>Invalid value \"$quantity\" for $size</p>\n");
	continue;
      }

      $entry_key = "entry-$size-$item_id";
      //      echo "<p>Checking for $entry_key</p>";
      if (array_key_exists($entry_key, $_POST))
	$entry_id = intval(trim($_POST[$entry_key]));
      else
	$entry_id = -1;
      //      echo "<p>entry_id: $entry_id</p>";

      $entry = new StoreOrderEntry();
      $entry->fill_from_POST_data($entry_id, $item_id, $size, $quantity);

      $this->entries[] = $entry;
    }

    return $ok;
  }

  public function load_from_order_id($order_id)
  {
    $sql = 'SELECT * FROM StoreOrders';
    $sql .= " WHERE OrderId=$order_id";

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('Query for StoreOrderEntries records failed',
				 $sql);
    $n = mysql_num_rows($result);
    if (1 != $n)
      return display_error("Got $n rows when expecting 1");

    $row = mysql_fetch_object($result);

    return $this->load_from_row($row);
  }

  public function load_from_row($row)
  {
    $this->order_id = $row->OrderId;
    $this->user_id = $row->UserId;
    $this->status = $row->Status;
    $this->payment = $row->PaymentCents;
    $this->payment_note = $row->PaymentNote;
    $this->updated_by_id = $row->UpdatedById;
    $this->last_updated = $row->LastUpdated;

    $sql = 'SELECT * FROM StoreOrderEntries';
    $sql .= " WHERE OrderId=$this->order_id";

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('Query for StoreOrderEntries records failed',
				 $sql);

    while ($row = mysql_fetch_object($result))
    {
      $entry = new StoreOrderEntry();
      $entry->fill_from_db_row($row);

      $this->entries[] = $entry;
    }

    return true;
  }

  public function populate_POST()
  {
    $_POST['OrderId'] = $this->order_id;
    $_POST['Status'] = $this->status;
    $_POST['PaymentNote'] = $this->payment_note;
    $_POST['Payment'] = sprintf("%d.%02d",
				$this->payment / 100,
				$this->payment % 100);

    foreach($this->entries as $entry)
    {
      $entry->populate_POST();
    }
  }

  public function cancel_order()
  {
    if ($this->order_id <= 0)
      return display_error("Invalid OrderId for cancellation: $this->order_id");

    // Verify that the order is Unpaid
    $sql = "SELECT Status FROM StoreOrders WHERE OrderId=$this->order_id";
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('StoreOrders query failed', $sql);

    $n = mysql_num_rows($result);
    if (1 != $n)
      return display_mysql_error("Got $n rows when expecting 1", $sql);

    $row = mysql_fetch_object($result);
    if ($row->Status != 'Unpaid')
      return display_error("Attempt to cancel $row->Status order");

    // Mark the order cancelled
    $sql  = 'UPDATE StoreOrders';
    $sql .= '   SET Status="Cancelled",';
    $sql .= '       UpdatedById=' . $_SESSION[SESSION_LOGIN_USER_ID];
    $sql .= " WHERE OrderId=$this->order_id";
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('StoreOrders cancellation failed', $sql);

    // Now clean out the _POST entries
    unset($_POST['OrderId']);
    foreach($this->entries as $entry)
    {
      $entry->depopulate_POST();
    }

    return true;
  }

  public function delete_from_db()
  {
    if ($this->order_id <= 0)
      return display_error("Invalid OrderId for deletion: $this->order_id");

    // Delete the order from the database
    $sql = "DELETE FROM StoreOrders WHERE OrderId=$this->order_id";
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('StoreOrders deletion failed', $sql);

    $sql = "DELETE FROM StoreOrderEntries WHERE OrderId=$this->order_id";
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('StoreOrderEntries deletion failed', $sql);

    // Now clean out the _POST entries
    unset($_POST['OrderId']);
    foreach($this->entries as $entry)
    {
      $entry->depopulate_POST();
    }

    return true;
  }

  public function render_hidden_entry_ids()
  {
    foreach($this->entries as $entry)
    {
      $entry->render_hidden_entry_id();
    }
  }

  public function list_entries()
  {
    $total_cost = 0;

    echo "<table border=\"1\">\n";
    echo "<tr>\n";
    echo "<th>Quantity</th>\n";
    echo "<th>Size</th>\n";
    echo "<th>Item</th>\n";
    echo "<th>Color</th>\n";
    echo "<th>Cost</th>\n";
    echo "</tr>\n";

    foreach($this->entries as $entry)
    {
      if (0 == $entry->quantity())
	continue;

      echo "<tr>\n";
      $entry->render_quantity('td', 'align="center"');
      $entry->render_size('td', 'align="center"');
      $entry->render_name('td', 'align="center"');
      $entry->render_color('td', 'align="center"');
      $entry->render_cost('td', 'align="right"');
      echo "</tr>\n";

      $total_cost += $entry->cost();
    }
    echo "<tr>\n";
    echo "<td colspan=\"4\"><b>Total Cost</b></td>\n";
    printf ("<td><b>$%d.%02d</b></td>\n", $total_cost / 100, $total_cost % 100);
    echo "</table>\n";

    return true;
  }

  public function cost()
  {
    // Sum the cost, in cents
    $total_cost = 0;
    foreach($this->entries as $entry)
    {
      if (0 == $entry->quantity())
	continue;

      $total_cost += $entry->cost();
    }

    // Convert to dollars
    return sprintf("%d.%02d", $total_cost / 100, $total_cost % 100);
  }

  public function list_entries_for_report()
  {
    $first_line = true;
    foreach($this->entries as $entry)
    {
      if (0 == $entry->quantity())
	continue;
      if ($first_line)
	$first_line = false;
      else
	echo "<br />\n";
      printf("%d %s %s %s\n",
	     $entry->quantity(),
	     $entry->size(),
	     $entry->color(),
	     $entry->description());
    }
  }

  public function write_to_db($admin_form)
  {
    /*
    echo "<p>StoreOrder::write_to_db</n>\n";
    echo "<pre>\n";
    print_r($this);
    echo "</pre>\n";
    */

    $OrderId = 0;
    if (array_key_exists('OrderId', $_POST))
      $OrderId = intval($_POST['OrderId']);
    if (0 == $OrderId)
      return display_error('Invalid OrderId');

    if ((-1 == $OrderId) && $admin_form)
      return display_error('Admin interface not allowed to create new order');

    if (-1 == $OrderId)
      $cmd = 'INSERT';
    else
      $cmd = 'UPDATE';

    $sql = "$cmd StoreOrders SET ";
    $sql .= build_sql_string('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID],
			     false);
    if (! $admin_form)
      $sql .= build_sql_string('Status', 'Unpaid');
    else
    {
      $sql .= build_sql_string('Status');
      $sql .= build_sql_string('PaymentCents', $this->payment);
      $sql .= build_sql_string('PaymentNote');
    }

    if (-1 == $OrderId)
    {
      // Only set the UserId when creating a new order
      $sql .= build_sql_string('UserId', $_SESSION[SESSION_LOGIN_USER_ID]);
    }
    else
      $sql .= " WHERE OrderId=$OrderId";

    //    echo "<p>$sql</p>\n";

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error("Failure to $cmd StoreOrders record",
				 $sql);

    if (-1 == $OrderId)
      $this->order_id = mysql_insert_id();

    $ok = true;
    foreach ($this->entries as $entry)
    {
      $ok &= $entry->write_to_db($this->order_id);
    }

    return $ok;
  }

  public function render_user_name($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    if (0 == $this->user_id)
      echo 'Unknown';
    else
    {
      if (user_id_to_name($this->user_id, $name, false))
	echo $name;
    }
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function user_name($first_name_first = true)
  {
    $name = $this->user_id;
    if ((0 != $this->user_id) &&
	user_id_to_name($this->user_id, $name, $first_name_first))
      return $name;
    else
      return 'Unknown';
  }

  public function has_unavailable_shirt()
  {
    foreach($this->entries as $entry)
    {
      if (! $entry->available())
	return true;
    }

    return false;
  }

  public function render_conversion_form($catelog)
  {
    echo "<!-- render_conversion_form -->\n";
    echo "<form method=post action=Shirts.php>\n";
    form_add_sequence();
    form_hidden_value('action', PROCESS_CONVERSION_FORM);
    form_hidden_value('OrderId', $this->order_id());
    form_hidden_value('Status', $this->status());

    $this->render_hidden_entry_ids();

    // Build the array of choices.  The array key will be used as the
    // key for the selection, the array value will be displayed as a choice
    $choices = $catelog->choices(true);

    echo "<table>\n";
    foreach ($this->entries as $entry)
    {
      $quantity = $entry->quantity();

      if ($entry->available() || (0 == $quantity))
	continue;

      echo "  <tr valign=\"top\">\n";
      printf("    <td>%d %s %s - select available style\n",
	     $quantity, $entry->size(), $entry->noun());

      $key = 'entry_id-' . $entry->entry_id();
      if (! array_key_exists($key, $_POST))
      {
	$selected_choice = $catelog->first_choice(true, $entry->gender());
	if (-1 != $selected_choice)
	  $_POST[$key] = $selected_choice;
      }

      render_dropdown_list($choices, $key);
      echo "    </td>\n";
      echo "  </tr>\n";
    }

    // Include Update and Cancel buttons
    echo "<tr>\n";
    echo "<td colspan=\"3\" align=\"center\">\n";
    echo "<input type=\"submit\"  name=\"BtnAction\" value=\"Update\">\n";
    if ('Paid' != $this->status)
      echo "<input type=\"submit\"  name=\"BtnAction\" value=\"Cancel\">\n";
    echo "</td>\n</tr>\n";
    echo "</table>\n";

    echo "</form>\n";

    $catelog->render_conversion_thumbnails();
  }

  private static function show_status_shirts($status, $desc,
					     &$matched_status,
					     &$unavailable)
  {
    // Count up the number of shirts the user has paid for
    $sql  = 'SELECT StoreOrderEntries.Size, StoreOrderEntries.Quantity,';
    $sql .= '       StoreItems.Gender, StoreItems.Style,';
    $sql .= '       StoreItems.Singular, StoreItems.Plural, StoreItems.Color,';
    $sql .= '       StoreItems.ThumbnailFilename, StoreItems.ImageFilename,';
    $sql .= '       StoreItems.Available';
    $sql .= '  FROM StoreOrders,StoreOrderEntries,StoreItems';
    $sql .= " WHERE StoreOrders.Status='$status'";
    $sql .= '   AND StoreOrders.UserId=' . $_SESSION[SESSION_LOGIN_USER_ID];
    $sql .= '   AND StoreOrderEntries.OrderId=StoreOrders.OrderId';
    $sql .= '   AND StoreItems.ItemId=StoreOrderEntries.ItemId';

    $result = mysql_query ($sql);
    if (! $result)
      return display_mysql_error ('Query for paid shirts failed', $sql);

    $count = array();
    $thumbnail = array();
    $fullimage = array();
    $singular = array();
    $plural = array();

    while ($row = mysql_fetch_object ($result))
    {
      if (0 == $row->Quantity)
	continue;

      $matched_status += $row->Quantity;
      if ('N' == $row->Available)
	$unavailable += $row->Quantity;

      $key = "$row->Size $row->Color $row->Gender $row->Style";
      if (array_key_exists($key, $count))
	$count[$key] += $row->Quantity;
      else
      {
	$count[$key] = $row->Quantity;
	$thumbnail[$key] = $row->ThumbnailFilename;
	$fullimage[$key] = $row->ImageFilename;
      }
      $singular[$key] = $row->Singular;
      $plural[$key] = $row->Plural;
    }

    // If we didn't find any shirts, we're done
    if (0 == count($count))
      return false;

    // Show what we found
    echo "<p>$desc:</p>\n";
    echo "<table>\n";
    foreach ($count as $k=>$v)
    {
      if (1 == $v)
	$noun = $singular[$k];
      else
	$noun = $plural[$k];

      echo "<tr>\n";
      printf('<td><a href="img/%s" target="_blank">' .
	     '<img src="img/%s" alt="Click for larger image"' .
	           'title="Click for larger image"></a>' .
	     "</td>\n", $fullimage[$k], $thumbnail[$k]);
      echo "<td>$v</td>\n";
      echo "<td>$k $noun</td>\n";
      echo "</tr>\n";
    }
    echo "</table>\n";

    return true;
  }

  public static function show_shirts_for_homepage()
  {
    $paid_displayed = 0;
    $unpaid_displayed = 0;
    $unavailable = 0;

    // Show any shirts the user has paid for
    StoreOrder::show_status_shirts('Paid',
				   'You have paid for the following shirt(s)',
				   $paid_displayed, $unavailable);

    // Show any shirts the user hasn't paid for
    StoreOrder::show_status_shirts('Unpaid',
    			           'Payment is still due for the following ' .
				       'shirt(s)',
				   $unpaid_displayed, $unavailable);

    if ($_SESSION[SESSION_CON_IS_FROZEN])
    {
      echo "The " . CON_NAME . " database is frozen.  No modifications are\n";
      echo "allowed at this time.\n";
      return;
    }

    // If any unavailable shirts are ordered, they must be converted to
    // available shirts before anything else happens
    if ($unavailable > 0)
    {
      echo "<p>You have ordered $unavailable shirt(s) that are no\n";
      echo "longer available.  You must select one of the available shirts\n";
      echo "using the <a href='Shirts.php'>Shirt Order Form</a>.</p>\n";
      return;
    }

    // If there are any unpaid for orders (for available shirts), they must
    // be paid for before any more orders are placed.
    if ($unpaid_displayed > 0)
    {
      echo "<p>You can modify or pay for the pending order using the ";
      echo "<a href='Shirts.php'>Shirt Order Form</a>.</p>\n";
      return;
    }

    // If there are any paid orders, they can order more shirts if it's not
    // past the shirt ordering deadline
    if ($paid_displayed > 0)
    {
      if (! past_shirt_deadline())
      {
	echo '<p>You can order more shirts using the ';
	echo "<a href='Shirts.php'>Shirt Order Form</a>.</p>\n";
      }
      return;
    }

    echo '<p>You have not ordered any ' . CON_NAME . " Shirts.\n";
    if (! past_shirt_deadline())
      echo "Visit the <a href='Shirts.php'>Shirt Order Form</a> to order " .
	   "shirts.<p>\n";
    else
    {
      echo "A limited number of shirts will be available at the con.\n";
      echo "If you want a shirt, check at the registration desk to see\n";
      echo "if any are available in your size.\n";
    }
    echo "</p>\n";
  }
}

?>