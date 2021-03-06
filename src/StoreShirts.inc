<?php
include_once "StoreItem.inc";

class StoreShirts
{
  // Data members
  private $catalog;
  private $available_sizes;

  // Constructor
  public function __construct()
  {
    $this->catalog = array();
    $this->available_sizes = array();
  }

  public function load_from_db()
  {
    $sql = 'SELECT * FROM StoreItems';
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('Query for StoreItems records failed', $sql);

    while ($row = mysql_fetch_object($result))
    {
      $shirt = new StoreItem();
      $shirt->load_from_row($row);
      $this->catalog[] = $shirt;
      foreach ($shirt->sizes() as $size)
      {
	if (! in_array($size, $this->available_sizes))
	  $this->available_sizes[] = $size;
      }
    }

    return true;
  }

  public function choices($for_sale)
  {
    // Build the array of choices.  The array key will be used as the
    // key for the selection, the array value will be displayed as a choice
    $choices = array();
    foreach ($this->catalog as $shirt)
    {
      if ($shirt->for_sale() == $for_sale)
      {
	$choices[$shirt->id()] = sprintf("%s %s - %s",
					 $shirt->gender(),
					 $shirt->style(),
					 $shirt->color());
      }
    }

    return $choices;
  }

  public function first_choice($for_sale, $gender)
  {
    foreach ($this->catalog as $shirt)
    {
      if (($shirt->for_sale() == $for_sale) && ($shirt->gender() == $gender))
	return $shirt->id();
    }

    return -1;
  }

  public function render_sales_form($order, $admin_form, $action)
  {
    $OrderId = $order->order_id();

    echo "<!-- render_sales_form -->\n";
    /*
    echo "<pre>\n";
    print_r($order);
    echo "\n\n_POST\n";
    print_r($_POST);
    echo "</pre>\n";
    */

    echo "<form method=post action=Shirts.php>\n";
    form_add_sequence();
    form_hidden_value('action', $action);
    form_hidden_value('OrderId', $OrderId);

    if (-1 != $OrderId)
      $order->render_hidden_entry_ids();

    echo "<table>\n";
    foreach ($this->catalog as $shirt)
    {
      $for_sale = $shirt->for_sale();

      // Only include shirts that aren't for sale if this is the admin version
      // of the form
      if ($for_sale || $admin_form)
      {
	if (! $for_sale)
	  $style = 'style="background-color: #cccccc; color: #777777;"';
	else
	  $style = '';

	echo "  <tr valign=\"top\" $style>\n";
	$shirt->render_thumbnail('td', 'align=\"center\" valign=\"middle\"');
	echo '<td align="center" style="padding:0px 5px;">';
	$shirt->render_name(1);
	echo '<br>';
	$shirt->render_price();
	echo " each </td>\n";
	$shirt->render_sizes_for_sale($this->available_sizes,
				      'td', 'align="center" style="padding:0px 5px;"');
	echo "  </tr>\n";
      }
    }
    echo "</table>\n";

    if ($admin_form)
    {
      $status_options = array('Unpaid' => 'Unpaid',
			      'Paid' => 'Paid',
			      'Cancelled' => 'Cancelled');
      echo "<table>\n";
      form_dropdown_list($status_options, 'Status');
      form_text(5, 'Payment');
      form_text(60, 'Payment Note', 'PaymentNote', 120);
      echo "<tr>\n";
      echo "<td align=\"right\">Last Updated By:</td>\n";
      printf("<td>%s</td>\n", $order->updated_by_name());
      echo "</tr>\n";
      echo "<tr>\n";
      echo "<td align=\"right\">Last Updated:</td>\n";
      printf("<td>%s</td>\n", $order->last_updated());
      echo "</tr>\n";
      echo "</table>\n";
    }

    // Include Update (and optionally Cancel) button(s)
    echo "<p align=\"center\">\n";

    if (-1 == $OrderId)
      echo "<input type=\"submit\" name=\"BtnAction\" value=\"Submit\">\n";
    else
    {
      echo "<input type=\"submit\"  name=\"BtnAction\" value=\"Update\">\n";
      if (! $admin_form)
	echo "<input type=\"submit\"  name=\"BtnAction\" value=\"Cancel\">\n";
    }

    echo "</p>\n";

    echo "</form>\n";
  }

  public function render_management_form()
  {
    echo "<table border=\"1\">\n";
    echo "<tr>\n";
    echo "  <th>Thumbnail</th>\n";
    echo "  <th>Name & Color</th>\n";
    echo "  <th>For Sale</th>\n";
    echo "  <th>Price</th>\n";
    echo "  <th>Sizes</th>\n";
    echo "  <th>Last Updated</th>\n";
    echo "  <th>Last Updated By</th>\n";
    echo "  <th>Options</th>\n";
    echo "</tr>\n";

    foreach ($this->catalog as $shirt)
    {
      echo "  <tr valign=\"top\">\n";
      $shirt->render_thumbnail('td', 'align=\"center\" valign=\"middle\"');
      $shirt->render_name(1, 'td', 'align="center"');
      $shirt->render_availability('td', 'align="center"');
      $shirt->render_price('td', 'align="center"');
      $shirt->render_sizes_for_mgmt('td', 'align="center"');
      $shirt->render_last_updated('td', 'align="center"');
      $shirt->render_last_updated_by('td', 'align="center"');
      printf("<td>%s<br \>%s</td>\n",
	      $shirt->build_action_link('Edit', EDIT_STORE_ITEM),
	      $shirt->build_action_link('Delete', DELETE_STORE_ITEM));
      echo "  </tr>\n";
    }
    echo "</table>\n";
    printf("<p><a href=\"Shirts.php?action=%d&ItemId=-1\">Add Shirt</a></p>\n",
	   EDIT_STORE_ITEM);
  }


  public function num_available_shirts()
  {
    $count = 0;
    foreach ($this->catalog as $shirt)
    {
      if ($shirt->for_sale())
	$count++;
    }
    return $count;
  }

  public function num_sizes() { return count($this->available_sizes); }

  public function render_conversion_thumbnails()
  {
    echo "<table>\n";
    echo "<tr>\n";
    foreach ($this->catalog as $shirt)
    {
      if (! $shirt->for_sale())
	continue;

      echo "<td align=\"center\" style=\"padding-right:20px;\">\n";
      $shirt->render_short_name();
      echo "<br />\n";
      $shirt->render_thumbnail();
      echo "</td>\n";
    }
    echo "</tr>\n";
    echo "</table>\n";
  }
}
?>