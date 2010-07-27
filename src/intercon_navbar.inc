<?

class NavBar {
  public $items;
  
  function __construct($title) {
    $this->title = $title;
    $this->items = array();
  }
  
  function renderInner() {
    $html = "";
    foreach ($this->items as $item) {
      $html .= $item->render();
    }
    return $html;
  }
  
  function render($class="menu") {
    $html = "<ul class=\"$class\">";
    $html .= $this->renderInner();
    $html .= "</ul>";
    return $html;
  }
  
  function push($item) {
    array_push($this->items, $item);
  }
}

class SubNavBar extends NavBar {
  public $headItem;
  
  function __construct($headItem) {
    $this->headItem = $headItem;
    parent::__construct();
  }
  
  function haveMultipleItems() {
    if (count($this->items) > 1)
      return true;
    if (count($this->items) == 1 && $this->items[0]->url != $this->headItem->url)
      return true;
    
    return false;
  }
  
  function renderInner() {
    $html = $this->headItem->render("title");
    $html .= parent::renderInner();
    return $html;
  }
  
  function render($class="subhead") {
    if ($this->haveMultipleItems() && $this->isCurrent()) {
      $html = "<li class=\"$class\">";
      $html .= parent::render($class);
      $html .= "</li>";
      return $html;
      
    } else {
      return $this->headItem->render();
    }
  }
  
  function isCurrent() {
    if ($this->headItem->isCurrent())
      return true;
    
    foreach ($this->items as $item) {
      if ($item->isCurrent())
        return true;
    }
    
    return false;
  }
}

class NavBarTitle {
  public $text;
  
  function __construct($text) {
    $this->text = $text;
  }
  
  function render() {
    return "<li class=\"title\">$this->text</li>\n";
  }
  
  function isCurrent() {
    return false;
  }
}

class NavBarItem {
  public $title;
  public $url;
  public $class;
  
  function __construct($title, $url, $class=NULL) {
    $this->title = $title;
    $this->url = $url;
    if ($class === NULL) {
      $this->class = "";
    } else {
      $this->class = $class;
    }
  }
  
  public static function newStatic($title, $page, $extraClass="") {
    return new NavBarItem($title, "Static.php?page=".$page, "static $extraClass");
  }
  
  public static function newExternal($title, $url, $extraClass="") {
    return new NavBarItem($title, $url, "external $extraClass");
  }
  
  public static function newProgram($title, $prefix, $page, $class="") {
    return new NavBarItem($title, "$prefix$page", $class);
  }
  
  public static function newAction($title, $page, $action=NULL, $class=NULL) {
    $url = $page;
    if (NULL !== $action)
      $url .= "?action=$action";
      
    return new NavBarItem($title, $url, $class);
  }
  
  public function renderInner() {
    $html = "<a href=\"$this->url\"";
    if (preg_match("/^https?:/", $this->url)) {
      $html .= " target=\"_blank\"";
    }
    $html .= ">$this->title</a>";
    return $html;
  }
  
  public function render($extraClass="") {
    $class = $this->class . " $extraClass";
    if ($this->isCurrent())
      $class .= " current";
    $html = "<li";
    if ($this->class !== NULL) {
      $html .= " class=\"$class\"";
    }
    $html .= ">".$this->renderInner()."</li>";
    return $html;
  }
  
  public function isCurrent() {
    return substr($_SERVER["REQUEST_URI"], strlen($_SERVER["REQUEST_URI"]) - strlen($this->url) - 1) == "/" . $this->url;
  }
}

function navbar_start($priv) {
  if ($priv)
    $cls = 'priv';
  else
    $cls = 'public';

  echo "<ul class=\"menu $cls\">\n";
}

function navbar_subhead_start($title, $page) {
  echo "<li class=\"subhead\">";
  echo "<a href=\"".$page."\">$title</a>";
  echo "<ul class=\"subhead\">\n";
}

function navbar_subhead_end() {
  echo "</ul></li>";
}

function navbar_title ($text)
{
    echo "<li class=\"title\">$text</li>\n";
}

function navbar_entry ($text, $page, $class=NULL)
{
  echo "<li";
  if ($class !== NULL) {
    echo " class=\"$class\"";
  }
  echo "><a href=\"$page\"";
  if (preg_match("^https?:", $page)) {
    echo " target=\"_blank\"";
  }
  echo ">$text</a></li>";
}

function navbar_action ($text, $page, $class=NULL, $action=-1) {
  $url = $page;
  if (-1 != $action)
    $url .= "?action=$action";
  
  navbar_entry($text, $url, $class);
}

function navbar_static ($text, $page, $class=NULL)
{
  navbar_entry($text, "Static.php?page=$page", $class);
}

function navbar_external_static ($text, $dest)
{
  navbar_entry($text, $dest, "external");
}

function navbar_program_entry ($text, $prefix, $page)
{
  navbar_entry($text, "$prefix$page");
}

function navbar_end() {
    echo "</ul>\n";
}

function display_navbar() {
  echo "<div class=navbar>\n";
  
  $accountControl = new NavBar();
  if (is_logged_in()) {
    $userName = $_SESSION[SESSION_LOGIN_USER_NAME];
    $accountControl->push(NavBarItem::newAction("<img src=\"user.png\" alt=\"User\"/> $userName", "index.php", UPDATE_USER));
    $accountControl->push(NavBarItem::newAction("Log Out", "index.php", LOGOUT_USER));
  } else {
    $accountControl->push(NavBarItem::newAction('Login', 'index.php'));
    $accountControl->push(NavBarItem::newAction('Register for <NOBR>'.CON_NAME.'</NOBR>', 'index.php', NEW_USER));
  }
  echo $accountControl->render("menu accountControl priv");

  // Title the menu
  $navbar = new NavBar();
  $navbar->push(new NavBarTitle("Navigation"));

  // Show these options if the user is logged in

  if (is_logged_in()) {
    $navbar->push(NavBarItem::newAction('Home', 'index.php'));
  } else {
   
  }
  
  // LARPs section
  $larpSection = new SubNavBar(NavBarItem::newAction("LARPs at <NOBR>".CON_NAME."</NOBR>",
    "Schedule.php", can_show_schedule() ? NULL : LIST_GAMES));
  $larpSection->push(NavBarItem::newAction("List of Games", "Schedule.php", LIST_GAMES));
  if (IRONGM_ENABLED)
    $larpSection->push(NavBarItem::newExternal('Iron GM Contest', 'http://irongm.rachl.in'));
  if (can_show_schedule()) {
    $larpSection->push(NavBarItem::newAction("Con Schedule", "Schedule.php"));
    if (user_has_priv(PRIV_CON_COM)) {
      $larpSection->push(NavBarItem::newAction("Schedule with Counts", "Schedule.php", SCHEDULE_WITH_COUNTS));            
      $larpSection->push(NavBarItem::newAction('Player Signups', 'UserSignupScan.php'));
      $larpSection->push(NavBarItem::newAction('Last 100 Signups', 'SignupSpy.php'));    
      $larpSection->push(NavBarItem::newAction('All Signups', 'SignupSpy.php', SHOW_ALL_SIGNUPS));
      $larpSection->push(NavBarItem::newAction('Signups with Choice #s', 'Choice1.php'));
      $larpSection->push(NavBarItem::newAction('Games by Choice', 'Choice3.php'));
    }
  }
  $navbar->push($larpSection);
  
  if (THURSDAY_ENABLED) {
    $thursdaySection = new SubNavBar(NavBarItem::newAction("Pre-Convention", "Thursday.php"));
    $thursdaySection->push(NavBarItem::newAction('Pre-Convention Events', 'Thursday.php'));
    if (user_has_priv(PRIV_PRECON_SCHEDULING))
      $thursdaySection->push(NavBarItem::newAction('Manage PreCon Event Schedule', 'Thursday.php', PRECON_MANAGE_EVENTS));
    if (user_has_priv(PRIV_REGISTRAR))
      $thursdaySection->push(NavBarItem::newAction('Edit User PreCon Event Info', 'Thursday.php', THURSDAY_SELECT_USER));
      
    $navbar->push($thursdaySection);
	}
  
  // GM section
  if (user_is_gm() || user_has_priv(PRIV_GM_LIAISON) || user_has_priv(PRIV_CON_COM)) {
    $gmSection = new SubNavBar(NavBarItem::newStatic("GM Information", "GMPolicies"));
    $gmSection->push(NavBarItem::newStatic("GM Benefits and Policies"), "GMPolicies");
    
    if (user_is_gm())
      $gmSection->push(NavBarItem::newAction("Edit My Bio", "index.php", EDIT_BIO));
      
    if (user_has_priv(PRIV_CON_COM))
      $gmSection->push(NavBarItem::newAction('Bio Report', 'index.php', BIO_REPORT));
      
    $navbar->push($gmSection);
  }
  
  if ((SHOW_TSHIRTS && is_logged_in()) || user_has_priv(PRIV_CON_COM))
  {
    $shirtsSection = new SubNavBar(NavBarItem::newAction(CON_NAME . " <NOBR>Shirts</NOBR>", "TShirts.php"));
    $shirtsSection->push(NavBarItem::newAction("Order Shirts", "TShirts.php"));
    if (user_has_priv(PRIV_CON_COM)) {
      $shirtsSection->push(NavBarItem::newAction('Shirt Order Summary', 'TShirts.php', SHOW_TSHIRT_SUMMARY));
      $shirtsSection->push(NavBarItem::newAction('Shirt Order Report', 'TShirts.php', SHOW_TSHIRT_REPORT));
    }
    $navbar->push($shirtsSection);
  }
  
  // ConCom section
  if (user_has_priv(PRIV_CON_COM)) {
    $concomSection = new SubNavBar(NavBarItem::newAction("ConCom Tools", "ConComSchedule.php"));
    $concomSection->push(NavBarItem::newAction('Referring Sites', 'Referrers.php', VIEW_REFERRERS));
    $concomSection->push(NavBarItem::newAction('Referring Site Summary',
		  'Referrers.php', SUMMARIZE_REFERRERS));
    $concomSection->push(NavBarItem::newExternal("Intercon Wiki", "http://www.interactiveliterature.org/Wiki"));
    $concomSection->push(NavBarItem::newExternal(CON_NAME . " Google Login", "http://www.google.com/a/" . CON_DOMAIN));
    if (defined ('PROGRAM_PDF_PREFIX'))
    {
      $concomSection->push(NavBarItem::newProgram("Program Cover", PROGRAM_PDF_PREFIX, "Cover.pdf"));
      $concomSection->push(NavBarItem::newProgram("Program Interior", PROGRAM_PDF_PREFIX, "Interior.pdf"));
    }
		  
    $navbar->push($concomSection);
  }
  
  // Bids section
  if (ACCEPTING_BIDS || user_has_priv(PRIV_BID_COM) || user_has_priv(PRIV_GM_LIAISON)) {
    $bidGameItem = NavBarItem::newAction("Bid a Game!", "biddingAGame.php");
    
    if (user_has_priv(PRIV_BID_COM) || user_has_priv(PRIV_GM_LIAISON))
      $bidSectionHead = NavBarItem::newAction("Bids", "Bids.php", BID_REVIEW_BIDS);
    else
      $bidSectionHead = $bidGameItem;
      
    $bidsSection = new SubNavBar($bidSectionHead);
    $bidsSection->push($bidGameItem);
    
    if (user_has_priv (PRIV_BID_COM) || user_has_priv(PRIV_GM_LIAISON))
    {  
      $bidsSection->push(NavBarItem::newAction("Status of Bids", "Bids.php", BID_REVIEW_BIDS));
      $bidsSection->push(NavBarItem::newAction("BidCom Feedback Summary", "Bids.php", BID_FEEDBACK_SUMMARY));
      if (user_has_priv(PRIV_GM_LIAISON))
        $bidsSection->push(NavBarItem::newStatic("Bid Commmittee Responsibilities", "BidCommittee"));
    }
    
    if (user_has_priv (PRIV_SCHEDULING))
      $bidsSection->push(NavBarItem::newAction('Manage Bid Page Info', 'biddingAGame.php', BAG_SHOW_FORM));

    $navbar->push($bidsSection);
  }
  
  // Attendees section
  if (user_has_priv(PRIV_CON_COM)) {
    $usersSection = new SubNavBar(NavBarItem::newAction("Attendees", "index.php", SELECT_USER_TO_VIEW));
    $usersSection->push(NavBarItem::newAction("View User", "index.php", SELECT_USER_TO_VIEW));
    if (user_has_priv(PRIV_REGISTRAR)) {
      $usersSection->push(NavBarItem::newAction('Add or Edit a User', 'index.php', SELECT_USER_TO_EDIT));
      $usersSection->push(NavBarItem::newAction('Set User Password', 'index.php', SELECT_USER_TO_SET_PASSWORD));
      $usersSection->push(NavBarItem::newAction('Delete a User', 'index.php', SELECT_USER_TO_DELETE));
    }
    $usersSection->push(NavBarItem::newAction("Comp'd Users", 'index.php', SHOW_COMPED_USERS));
    $usersSection->push(NavBarItem::newAction('Attendence by Payment Amount', 'Attendees.php'));
    $usersSection->push(NavBarItem::newAction('How Users Heard', 'Reports.php', REPORT_HOW_HEARD));

    if (user_has_priv(PRIV_REGISTRAR))
      $usersSection->push(NavBarItem::newAction('Users By Age', 'Reports.php', REPORT_BY_AGE));
      
    if (user_has_priv(PRIV_STAFF))
      $usersSection->push(NavBarItem::newAction('Become User', 'index.php', SELECT_USER_TO_BECOME));

    $navbar->push($usersSection);
    
  }

  // Mail section
  if (user_has_any_mail_priv ()) {
    $mailSection = new SubNavBar(NavBarItem::newAction('Mail', "MailTo.php", MAIL_LISTS));
    $mailSection->push(NavBarItem::newAction('Show Lists', 'MailTo.php', MAIL_LISTS));
    $mailSection->push(NavBarItem::newAction('GM Lists', 'MailTo.php', MAIL_GM_LISTS));
    $mailSection->push(NavBarItem::newAction('Iron GM List', 'MailTo.php', MAIL_IRON_GM_LIST));
    $mailSection->push(NavBarItem::newAction('Bid Submitters', 'MailTo.php', MAIL_BID_SUBMITTERS));
    $mailSection->push(NavBarItem::newAction('Bio Lists', 'MailTo.php', MAIL_BIO_LISTS));
    $mailSection->push(NavBarItem::newAction('Waitlisted Players', 'MailTo.php', MAIL_WAITLISTED));
    $mailSection->push(NavBarItem::newAction('Who\'s Not Playing When', 'Reports.php', REPORT_WHOS_NOT_PLAYING_FORM));
    
    $navbar->push($mailSection);
  }
  
  // Ops section

  if (user_has_priv (PRIV_CON_COM))
  {
    $opsSection = new SubNavBar(NavBarItem::newAction("Ops Reports", "Reports.php", REPORT_OPS_TRACK));
    if (defined ('PROGRAM_PUB_PREFIX'))
    {
      $opsSection->push('Program Cover.pub', NavBarItem::newProgram(PROGRAM_PUB_PREFIX, 'Cover.pub'));
      $opsSection->push('Program Interior.pub', NavBarItem::newProgram(PROGRAM_PUB_PREFIX, 'Interior.pub'));
    }
    $opsSection->push(NavBarItem::newAction('Per User Report', 'Reports.php', REPORT_PER_USER));
    $opsSection->push(NavBarItem::newAction('Per Game Report', 'Reports.php', REPORT_PER_GAME));
    $opsSection->push(NavBarItem::newAction('Per Room Report', 'Reports.php', REPORT_PER_ROOM));
    $opsSection->push(NavBarItem::newAction('Ops Report', 'Reports.php', REPORT_OPS_TRACK));
    $opsSection->push(NavBarItem::newAction('ConSuite Report', 'Reports.php', REPORT_CONSUITE_TRACK));
    $opsSection->push(NavBarItem::newAction('Games by Time', 'Reports.php', REPORT_GAMES_BY_TIME));
    $opsSection->push(NavBarItem::newAction('Users CSV', 'Reports.php', REPORT_USERS_CSV));
    $opsSection->push(NavBarItem::newAction('Registration Report', 'Reports.php', REPORT_REGISTRATION));
    if (THURSDAY_ENABLED)
      $opsSection->push(NavBarItem::newAction('PreCon Events Report', 'Thursday.php', THURSDAY_REPORT));

    $navbar->push($opsSection);
  }
  
  // Add these menu items if the user has scheduling privilege

  if (user_has_priv (PRIV_SCHEDULING))
  {
    $scheduleSection = new SubNavBar(NavBarItem::newAction("Scheduling", "ListGames.php"));
    $scheduleSection->push(NavBarItem::newAction('Manage Schedule', 'ListGames.php'));
    $scheduleSection->push(NavBarItem::newAction('Schedule Ops', 'ListGames.php', LIST_ADD_OPS));
    $scheduleSection->push(NavBarItem::newAction('Schedule ConSuite', 'ListGames.php', LIST_ADD_CONSUITE));
//    $scheduleSection->push(NavBarItem::newAction('Add a Parallel Run', 'ListGames.php', LIST_TO_ADD_PARALLEL_RUN));
    $scheduleSection->push(NavBarItem::newAction('Add Special Event', 'SpecialEvents.php'));
    $scheduleSection->push(NavBarItem::newAction('Manage Special Events', 'SpecialEvents.php', SPECIAL_EVENT_LIST));
    
    $navbar->push($scheduleSection);
  }
    
  // About Intercon section
  $aboutSection = new SubNavBar(NavBarItem::newStatic("About <NOBR>".CON_NAME."</NOBR>", "faq"));
  $aboutSection->push(NavBarItem::newStatic("Frequently Asked Questions", "faq"));
  $aboutSection->push(NavBarItem::newStatic("Convention Rules", "ConRules"));
  $aboutSection->push(NavBarItem::newAction("What Does It Cost?", "cost.php"));
  $aboutSection->push(NavBarItem::newAction("Contacts", "Contacts.php"));
  $aboutSection->push(NavBarItem::newAction("Hotel Info", "hotel.php"));
  $aboutSection->push(NavBarItem::newAction("Who's Who", "index.php", WHO_IS_WHO));
  $aboutSection->push(NavBarItem::newStatic("Volunteering", "volunteering"));
  $aboutSection->push(NavBarItem::newAction("ConCom Schedule", "ConComSchedule.php"));
  $aboutSection->push(NavBarItem::newAction(CON_NAME.' Flyer', 'InterconFlyer.pdf'));
  $aboutSection->push(NavBarItem::newExternal('New England Interactive Literature',
			  'http://www.interactiveliterature.org'));
  $navbar->push($aboutSection);
  
  // Shameless Plugs section
  $plugsSection = new SubNavBar(NavBarItem::newAction("Shameless Plugs", "Plugs.php"));
  $plugsSection->push(NavBarItem::newExternal("LARPA", "http://www.larpaweb.net"));
  $plugsSection->push(NavBarItem::newExternal("Intercon Mid-Atlantic", "http://www.larpaweb.net"));
  $plugsSection->push(NavBarItem::newAction("Shameless Plugs", "Plugs.php"));
  if (user_has_priv(PRIV_STAFF))
    $plugsSection->push(NavBarItem::newAction('Manage Shameless Plugs', 'Plugs.php', PLUGS_MANAGE_PLUGS));
  $navbar->push($plugsSection);

    
  if (user_has_priv(PRIV_STAFF))
    $navbar->push(NavBarItem::newAction('Change Con Settings', 'Status.php'));

  echo $navbar->render();


  echo "</div> <!-- navbar -->\n\n";
}

?>