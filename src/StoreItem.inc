<?php

class StoreItem
{
  // Class data
  private $item_id;
  private $available;
  private $price;
  private $style;
  private $singular;
  private $plural;
  private $gender;
  private $color;
  private $sizes;
  private $thumbnail;
  private $image;
  private $last_updated;
  private $updated_by_id;

  // Simple accessors
  public function id() { return $this->item_id; }

  public function num_sizes() { return substr_count(",", $this->sizes); }

  public function sizes() { return explode(",", $this->sizes); }

  public function color() { return $this->color; }

  public function for_sale() { return $this->available == 'Y'; }

  public function gender() { return $this->gender; }

  public function name($quantity = 1)
  {
    if (1 == $quantity)
      $noun = $this->singular;
    else
      $noun = $this->plural;

    return "$this->gender $this->style $noun";
  }

  public function style() { return $this->style; }

  public function load_from_db($id)
  {
    $sql = 'SELECT * FROM StoreItems';
    $sql .= "  WHERE ItemId=$id";
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('Query for StoreItems records failed', $sql);

    $n = mysql_num_rows($result);
    if (1 != $n)
      return display_mysql_error("Got $n rows when expecting 1", $sql);

    $row = mysql_fetch_object($result);
    if (! $this->load_from_row($row))
      return false;

    return true;
  }

  public function load_from_row($row)
  {
    $this->item_id = $row->ItemId;
    $this->price = $row->PriceCents;
    $this->gender = $row->Gender;
    $this->singular = $row->Singular;
    $this->plural = $row->Plural;
    $this->style = $row->Style;
    $this->color = $row->Color;
    $this->sizes = $row->Sizes;
    $this->thumbnail = $row->ThumbnailFilename;
    $this->image = $row->ImageFilename;
    $this->last_updated = $row->LastUpdated;
    $this->updated_by_id = $row->UpdatedById;
    $this->available = $row->Available;

    return true;
  }

  public function render_thumbnail($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>\n";
    //    printf ("<td align=\"center\" valign=\"middle\"><a href=\"img/%s\" target=\"_blank\"><img src=\"img/%s\" alt=\"Click for larger image\" title=\"Click for larger image\"></a></td>\n",
    printf ("<a href=\"img/%s\" target=\"_blank\">" .
	    "<img src=\"img/%s\" alt=\"Click for larger image\"" .
	        "title=\"Click for larger image\"></a>\n",
	    $this->image,
	    $this->thumbnail);
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_gender($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    echo "$this->gender";
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_name($quantity, $tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    printf("%s<br />$this->color", $this->name($quantity));
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_short_name($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    echo "$this->gender $this->style<br />$this->color";
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_singular($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    echo "$this->singular";
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_plural($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    echo "$this->plural";
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_style($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    echo "$this->style";
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_availability($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    echo "$this->available";
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_price($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    printf ('$%d.%02d', $this->price/100, $this->price % 100);
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_sizes_for_sale(&$all_sizes, $tag='', $attributes='')
  {
    $item_sizes = explode(",", $this->sizes);
    foreach ($all_sizes as $size)
    {
      if ('' != $tag)
	echo "<$tag $attributes>";
      echo "$size<br />\n";
      if (in_array($size, $item_sizes))
	quantity_form_text($size, "size-$size-$this->item_id");
      else
	echo 'N/A';
      if ('' != $tag)
	echo "</$tag>\n";
    }
  }

  public function render_sizes_for_mgmt($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    $item_sizes = explode(",", $this->sizes);
    $last_size = array_pop($item_sizes);
    foreach($item_sizes as $size)
    {
      echo "$size<br />\n";
    }
    echo "$last_size";
    if ('' != $tag)
      echo "</$tag>";
  }

  public function render_last_updated($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    echo "$this->last_updated";
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function render_last_updated_by($tag='', $attributes='')
  {
    if ('' != $tag)
      echo "<$tag $attributes>";
    if (0 == $this->updated_by_id)
      echo 'Unknown';
    else
    {
      if (user_id_to_name($this->updated_by_id, $name, false))
	echo $name;
    }
    if ('' != $tag)
      echo "</$tag>\n";
  }

  public function build_action_link($action, $action_id)
  {
    return sprintf("<a href=\"Shirts.php?action=%d&ItemId=%d\">%s</a>",
		   $action_id, $this->item_id, $action);
  }

  public function load_POST()
  {
    // Initialize the $_POST array
    if (! array_key_exists('PriceCents', $_POST))
      $_POST['PriceCents'] = $this->price;
    if (! array_key_exists('Color', $_POST))
      $_POST['Color'] = $this->color;
    if (! array_key_exists('Sizes', $_POST))
      $_POST['Sizes'] = $this->sizes;
    if (! array_key_exists('ThumbnailFilename', $_POST))
      $_POST['ThumbnailFilename'] = $this->thumbnail;
    if (! array_key_exists('ImageFilename', $_POST))
      $_POST['ImageFilename'] = $this->image;
    if (! array_key_exists('Available', $_POST))
      $_POST['Available'] = $this->available;
    if (! array_key_exists('Gender', $_POST))
      $_POST['Gender'] = $this->gender;
    if (! array_key_exists('Singular', $_POST))
      $_POST['Singular'] = $this->singular;
    if (! array_key_exists('Plural', $_POST))
      $_POST['Plural'] = $this->plural;
    if (! array_key_exists('Style', $_POST))
      $_POST['Style'] = $this->style;
  }

  static public function load_POST_defaults()
  {
    $_POST['PriceCents'] = 0;
    $_POST['Color'] = '';
    $_POST['Sizes'] = '';
    $_POST['ThumbnailFilename'] = '';
    $_POST['ImageFilename'] = '';
    $_POST['Available'] = 'N';
    $_POST['Gender'] = 'Unisex';
    $_POST['Singular'] = 'Shirt';
    $_POST['Plural'] = 'Shirts';
    $_POST['Style'] = '';
  }

  static public function validate_POST_data()
  {
    // Most of the strings just have to be present
    $ok = validate_string('Color');
    $ok &= validate_string('Sizes');
    $ok &= validate_string('Singular');
    $ok &= validate_string('Plural');
    $ok &= validate_string('Style');

    // Sizes may not contain any '-' characters. Those will be used to
    // separate the item ID on the sales form
    if (strstr($_POST['Sizes'], '-'))
    {
      display_error("The Sizes field may not contain any \"-\" characters.");
      $ok = false;
    }

    // Availability must be 'Y' or 'N'
    if (($_POST['Available'] != 'Y') && ($_POST['Available'] != 'N'))
    {
      display_error("The Available field may only be 'Y' or 'N'");
      $ok = false;
    }

    // Gender must be "Men's", "Women's" or 'Unisex'
    switch ($_POST['Gender'])
    {
      case "Men's": break;
      case "Women's": break;
      case 'Unisex': break;
      default:
	display_error("Gender must be Men's, Women's or Unisex");
	$ok = false;
    }

    // Sanity check the price.  Note that the max of $100 is arbitrary
    $ok &= validate_int('PriceCents', 0, 10000);

    // Verify that the files exist
    $ok &= validate_file_exists('ThumbnailFilename', 'img');
    $ok &= validate_file_exists('ImageFilename', 'img');

    return $ok;
  }

  static public function write_db_from_POST()
  {
    //    print_r ($_POST);

    $ItemId = 0;
    if (array_key_exists ('ItemId', $_POST))
      $ItemId = intval($_POST['ItemId']);

    if (0 == $ItemId)
      return display_error ('Invalid ItemId');

    // Make sure that there's no leading or trailing whitespace in the
    // sizes
    $Sizes = implode(',', array_map('trim', explode(',', $_POST['Sizes'])));

    if (-1 == $ItemId)
      $cmd = 'INSERT';
    else
      $cmd = 'UPDATE';

    $sql = $cmd . ' StoreItems SET ';

    $sql .= build_sql_string('Available', '', false);
    $sql .= build_sql_string('Gender');
    $sql .= build_sql_string('PriceCents');
    $sql .= build_sql_string('Singular');
    $sql .= build_sql_string('Plural');
    $sql .= build_sql_string('Style');
    $sql .= build_sql_string('Color');
    $sql .= build_sql_string('Sizes', $Sizes);
    $sql .= build_sql_string('ThumbnailFilename');
    $sql .= build_sql_string('ImageFilename');
    $sql .= build_sql_string('UpdatedById',$_SESSION[SESSION_LOGIN_USER_ID]);
    if (-1 != $ItemId)
      $sql .= " WHERE ItemId=$ItemId";

    //    echo "<p>$sql</p>\n";

    $result = mysql_query($sql);
    if (! $result)
      display_mysql_error("Failed to $cmd StoreItem record", $sql);

    if (-1 == $ItemId)
      $ItemId = mysql_insert_id();

    return $ItemId;
  }

  static public function delete_from_db($ItemId = 0)
  {
    //  dump_array ('REQUEST', $_REQUEST);

    if (array_key_exists('ItemId', $_REQUEST))
      $ItemId = intval($_REQUEST['ItemId']);

    if (0 == $ItemId)
      return display_error('Invalid ItemId');

    $sql = "DELETE FROM StoreItems WHERE ItemId=$ItemId";
    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error("Delete from StoreItems failed");

    echo "<p>Item $ItemId deleted</p>\n";

    return true;
  }
}

?>