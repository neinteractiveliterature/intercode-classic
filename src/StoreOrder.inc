<?

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

  public function order_id() { return $this->order_id; }

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

    foreach($_POST as $k => $v)
    {
      if ('' == $v)
	continue;

      $components = explode('-', $k);
      if (3 != count($components) > 1)
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
      return display_mysql_error('StoreOrders deletion failed', $sql);

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
      /*
      echo "<!--\n";
      print_r($this);
      echo "-->\n";
      */
      if ($first_line)
	$first_line = false;
      else
	echo "<br />\n";
      printf("%d %s\n", $entry->quantity(), $entry->description());
    }
  }

  public function write_to_db()
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

    if (-1 == $OrderId)
      $cmd = 'INSERT';
    else
      $cmd = 'UPDATE';

    $sql = "$cmd StoreOrders SET ";
    $sql .= build_sql_string('Status', 'Unpaid', false);
    $sql .= build_sql_string('UserId', $_SESSION[SESSION_LOGIN_USER_ID]);
    $sql .= build_sql_string('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID]);
    if (-1 != $OrderId)
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
    form_hidden_value('action', PROCESS_SHIRT_FORM);
    form_hidden_value('OrderId', $this->order_id());

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
      printf("    <td>%d %s - select available style\n",
	     $quantity, $entry->noun());

      $key = 'entry-id:' . $entry->entry_id();
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
    echo "<input type=\"submit\"  name=\"BtnAction\" value=\"Cancel\">\n";
    echo "</td>\n</tr>\n";
    echo "</table>\n";

    echo "</form>\n";

    $catelog->render_conversion_thumbnails();
  }
}

?>