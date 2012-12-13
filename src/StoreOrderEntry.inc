<?

class StoreOrderEntry
{
  private $entry_id;
  private $item_id;
  private $price_per_item; // In cents
  private $quantity;
  private $size;

  // Fetched from store item record
  private $color;
  private $style;
  private $singular;
  private $plural;
  private $gender;
  private $available;

  public function __construct()
  {
    $this->entry_id = -1;
    $this->item_id = -1;
  }

  public function available()
  {
    if (! isset($this->available))
      $this->fill_in_from_StoreItem();

    return $this->available;
  }

  public function cost()
  {
    if (! isset($this->price_per_item))
      $this->fill_in_from_StoreItem();

    return $this->quantity * $this->price_per_item;
  }

  public function entry_id() { return $this->entry_id; }

  public function gender()
  {
    if (! isset($this->gender))
      $this->fill_in_from_StoreItem();
    return $this->gender;
  }

  public function noun()
  {
    if (1 == $this->quantity)
      return $this->singular;
    else
      return $this->plural;
  }

  public function quantity() { return $this->quantity; }

  public function fill_from_db_row($row)
  {
    $this->entry_id = $row->OrderEntryId;
    $this->item_id = $row->ItemId;
    $this->price_per_item = $row->PricePerItemCents;
    $this->quantity = $row->Quantity;
    $this->size = $row->Size;
  }

  private function fill_in_from_StoreItem()
  {
    $sql = 'SELECT PriceCents, Color, Style, Gender, Singular, Plural,';
    $sql .= ' Available';
    $sql .= ' FROM StoreItems';
    $sql .= " WHERE ItemId=$this->item_id";

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error('Query for StoreItems record failed', $sql);

    $n = mysql_num_rows($result);
    if (1 != $n)
      return display_error("Got $n rows when expecting 1");

    $row = mysql_fetch_object($result);

    $this->price_per_item = $row->PriceCents;
    $this->style = $row->Style;
    $this->gender = $row->Gender;
    $this->singular = $row->Singular;
    $this->plural = $row->Plural;
    $this->color = $row->Color;
    $this->available = ('Y' == $row->Available);
  }

  public function fill_from_POST_data($entry_id, $item_id, $size, $quantity)
  {
    //    echo "<p>fill_from_POST_data - entry: $entry_id, item: $item_id, size: $size, quantity: $quantity</p>\n";

    $this->entry_id = $entry_id;
    $this->item_id = $item_id;
    $this->size = $size;
    $this->quantity = $quantity;

    return $this->fill_in_from_StoreItem();
  }

  public function populate_POST()
  {
    $_POST["size-$this->size-$this->item_id"] = $this->quantity;
    if (-1 != $this->entry_id)
      $_POST["entry-$this->size-$this->item_id"] = $this->entry_id;
  }

  public function depopulate_POST()
  {
    unset($_POST["size-$this->size-$this->item_id"]);
    unset($_POST["entry-$this->size-$this->item_id"]);
  }

  public function description()
  {
    if (! isset($this->singular))
      $this->fill_in_from_StoreItem();

    if (1 == $this->quantity)
      $noun = $this->singular;
    else
      $noun = $this->plural;
    return "$this->gender $this->style $noun";
  }

  public function render_color($tag='', $attributes='')
  {
    render_field("$this->color", $tag, $attributes);
  }

  public function render_cost($tag='', $attributes='')
  {
    $cost = $this->cost();
    render_field(sprintf("$%d.%02d", $cost / 100, $cost % 100),
		 $tag, $attributes);
  }

  public function render_name($tag='', $attributes='')
  {
    render_field($this->description(), $tag, $attributes);
  }

  public function render_quantity($tag='', $attributes='')
  {
    render_field ($this->quantity, $tag, $attributes);
  }

  public function render_size($tag='', $attributes='')
  {
    render_field($this->size, $tag, $attributes);
  }

  public function render_hidden_entry_id()
  {
    form_hidden_value("entry-$this->size-$this->item_id",
		      $this->entry_id);
  }

  public function write_to_db($OrderId)
  {
    //    echo "<p>StoreOrderEntry::write_to_db order $OrderId, entry $this->entry_id</n>\n";
    if (-1 == $this->entry_id)
      $cmd = 'INSERT';
    else
      $cmd = 'UPDATE';

    $sql = "$cmd StoreOrderEntries SET ";
    $sql .= build_sql_string('OrderId', $OrderId, false);
    $sql .= build_sql_string('ItemId', $this->item_id);
    $sql .= build_sql_string('PricePerItemCents', $this->price_per_item);
    $sql .= build_sql_string('Quantity', $this->quantity);
    $sql .= build_sql_string('Size', $this->size);
    $sql .= build_sql_string('UpdatedById', $_SESSION[SESSION_LOGIN_USER_ID]);
    if (-1 != $this->entry_id)
      $sql .= " WHERE OrderEntryId=$this->entry_id";

    //    echo "<p>$sql</p>\n";

    $result = mysql_query($sql);
    if (! $result)
      return display_mysql_error("Failure to $cmd StoreOrdersEntries record",
				 $sql);

    if (-1 == $this->entry_id)
    {
      $this->entry_id = mysql_insert_id();
      //      echo "Created record $this->entry_id\n";
    }

    return true;
  }
}

?>