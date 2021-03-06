<?php

class NavBar {
  public $items;

  function __construct() {
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
  public $title;
  public $useFirstItemTitleIfSingle;
  public $class;

  function __construct($title, $useFirstItemTitleIfSingle=false, $class=NULL) {
    $this->title = $title;
    $this->class = $class;
    $this->useFirstItemTitleIfSingle = $useFirstItemTitleIfSingle;
    parent::__construct();
  }

  function haveMultipleItems() {
    return (count($this->items) > 1);
  }

  function getHeadItem() {
    $firstItem = $this->items[0];
    if ($this->useFirstItemTitleIfSingle && !$this->haveMultipleItems()) {
      $title = $firstItem->title;
    } else {
      $title = $this->title;
    }
    return new NavBarItem($title, $firstItem->url, $firstItem->class);
  }

  function renderInner() {
    $html = $this->getHeadItem()->render("title");
    $html .= parent::renderInner();
    return $html;
  }

  function render($class=NULL) {
    if ($class === NULL) {
      $class = $this->class;
    }
    $class = "subhead $class";

    if ($this->haveMultipleItems() && $this->isCurrent()) {
      $html = "<li class=\"$class\">";
      $html .= parent::render($class);
      $html .= "</li>";
      return $html;

    } elseif ($this->haveMultipleItems()) {
      return $this->getHeadItem()->render("expandable");
    } else {
      return $this->getHeadItem()->render();
    }
  }

  function isCurrent() {
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

function build_larps_subnavbar() {
  $larpSection = new SubNavBar("Events at <NOBR>".(USE_CON_SHORT_NAME ? CON_SHORT_NAME : CON_NAME)."</NOBR>");
  if (can_show_schedule()) {
    $larpSection->push(NavBarItem::newAction("Con Schedule", "Schedule.php"));
    if (user_has_priv(PRIV_CON_COM))
      $larpSection->push(NavBarItem::newAction("Schedule with Counts", "Schedule.php", SCHEDULE_WITH_COUNTS));
  }
  $larpSection->push(NavBarItem::newAction("List of Games", "Schedule.php", LIST_GAMES));
  if (IRONGM_ENABLED)
    $larpSection->push(NavBarItem::newStatic('Iron GM Contest', 'irongm'));
  if (can_show_schedule() && user_has_priv(PRIV_CON_COM)) {
    $larpSection->push(NavBarItem::newAction('Player Signups', 'UserSignupScan.php'));
    $larpSection->push(NavBarItem::newAction('Last 100 Signups', 'SignupSpy.php'));
    $larpSection->push(NavBarItem::newAction('All Signups', 'SignupSpy.php', SHOW_ALL_SIGNUPS));
    $larpSection->push(NavBarItem::newAction('Signups with Choice #s', 'Choice1.php'));
    $larpSection->push(NavBarItem::newAction('Games by Choice', 'Choice3.php'));
  }
  return $larpSection;
}

function build_precon_subnavbar() {
  $thursdaySection = new SubNavBar("Panels");
  $thursdaySection->push(NavBarItem::newAction('Panels', 'Thursday.php'));
  if (user_has_priv(PRIV_PRECON_SCHEDULING))
    $thursdaySection->push(NavBarItem::newAction('Manage Panel Schedule', 'Thursday.php', PRECON_MANAGE_EVENTS));
  /*
  if (user_has_priv(PRIV_REGISTRAR))
    $thursdaySection->push(NavBarItem::newAction('Edit User PreCon Event Info', 'Thursday.php', THURSDAY_SELECT_USER));
  */
  return $thursdaySection;
}

function build_dead_dog_subnavbar() {
  $deadDogSection = new SubNavBar("Dead Dog");
  $deadDogSection->push(NavBarItem::newAction('Dead Dog Info', 'DeadDog.php'));
  if (user_has_priv(PRIV_REGISTRAR))
    $deadDogSection->push(NavBarItem::newAction('Edit User Dead Dog Info', 'DeadDog.php', DEAD_DOG_SELECT_USER));
  return $deadDogSection;
}

function build_gm_subnavbar() {
  $gmSection = new SubNavBar("GM Information");
  $gmSection->push(NavBarItem::newStatic("GM Benefits and Policies", "GMPolicies"));
  return $gmSection;
}

function build_shirts_subnavbar() {
  $shirtsSection = new SubNavBar(CON_NAME . ' Shirts');
  $shirtsSection->push(NavBarItem::newAction('Order Shirts', 'Shirts.php'));
  if (user_has_priv(PRIV_CON_COM)) {
    $shirtsSection->push(NavBarItem::newAction('Shirt Order Summary',
                 'Shirts.php',
                 SHOW_SHIRT_SUMMARY));
    $shirtsSection->push(NavBarItem::newAction('Shirt Order Report',
                 'Shirts.php',
                 SHOW_SHIRT_REPORT));
  }
  return $shirtsSection;
}

function build_bids_subnavbar() {
  $bidsSection = new SubNavBar("Proposals", true);
  if (user_has_priv(PRIV_BID_COM) || user_has_priv(PRIV_GM_LIAISON))
    $bidsSection->push(NavBarItem::newAction("Status of Proposals", "Bids.php", BID_REVIEW_BIDS));

  if (user_has_priv (PRIV_BID_COM) || user_has_priv(PRIV_GM_LIAISON)) {
    $bidsSection->push(NavBarItem::newAction("BidCom Feedback Summary", "Bids.php", BID_FEEDBACK_SUMMARY));
    if (user_has_priv(PRIV_GM_LIAISON))
      $bidsSection->push(NavBarItem::newStatic("Bid Commmittee Responsibilities", "BidCommittee"));
  }

  if (user_has_priv (PRIV_SCHEDULING))
    $bidsSection->push(NavBarItem::newAction('Manage Proposal Page Info', 'biddingAGame.php', BAG_SHOW_FORM));

  $bidsSection->push(NavBarItem::newAction("Propose a Game!", "biddingAGame.php"));

  return $bidsSection;
}

function build_attendees_subnavbar() {
  $usersSection = new SubNavBar("Attendees");
  $usersSection->push(NavBarItem::newAction("View User", "index.php", SELECT_USER_TO_VIEW));
  if (user_has_priv(PRIV_REGISTRAR)) {
    $usersSection->push(NavBarItem::newAction('Add or Edit a User', 'index.php', SELECT_USER_TO_EDIT));
    $usersSection->push(NavBarItem::newAction('Set User Password', 'index.php', SELECT_USER_TO_SET_PASSWORD));
    $usersSection->push(NavBarItem::newAction('Delete a User', 'index.php', SELECT_USER_TO_DELETE));
  }
  $usersSection->push(NavBarItem::newAction("Comp'd Users", 'index.php', SHOW_COMPED_USERS));
  $usersSection->push(NavBarItem::newAction('Attendence by Payment Amount', 'Attendees.php'));
  $usersSection->push(NavBarItem::newAction('How Users Heard', 'Reports.php', REPORT_HOW_HEARD));
  $usersSection->push(NavBarItem::newAction('Bio Report', 'index.php', BIO_REPORT));

  if (user_has_priv(PRIV_REGISTRAR) ||
      user_has_priv(PRIV_GM_LIAISON))
    $usersSection->push(NavBarItem::newAction('Users By Age', 'Reports.php', REPORT_BY_AGE));

  if (user_has_priv(PRIV_STAFF))
    $usersSection->push(NavBarItem::newAction('Become User', 'index.php', SELECT_USER_TO_BECOME));

  return $usersSection;
}

function build_mail_subnavbar() {
  $mailSection = new SubNavBar('Mail');
  $mailSection->push(NavBarItem::newAction('Show Lists', 'MailTo.php', MAIL_LISTS));
  $mailSection->push(NavBarItem::newAction('GM Lists', 'MailTo.php', MAIL_GM_LISTS));
  if (IRONGM_ENABLED)
    $mailSection->push(NavBarItem::newAction('Iron GM List', 'MailTo.php', MAIL_IRON_GM_LIST));
  $mailSection->push(NavBarItem::newAction('Bid Submitters', 'MailTo.php', MAIL_BID_SUBMITTERS));
  $mailSection->push(NavBarItem::newAction('Bio Lists', 'MailTo.php', MAIL_BIO_LISTS));
  $mailSection->push(NavBarItem::newAction('Waitlisted Players', 'MailTo.php', MAIL_WAITLISTED));
  $mailSection->push(NavBarItem::newAction('Who\'s Not Playing When', 'Reports.php', REPORT_WHOS_NOT_PLAYING_FORM));

  return $mailSection;
}

function build_print_materials_subnavbar() {
  $opsSection = new SubNavBar("Print Materials");
  $opsSection->push(NavBarItem::newAction('Registration Checkin', 'Reports.php',
					    REPORT_USER_ON_DEMAND_SELECT));
  $opsSection->push(NavBarItem::newAction('Per Game Report', 'Reports.php', REPORT_PER_GAME));
  $opsSection->push(NavBarItem::newAction('Per User Report', 'Reports.php', REPORT_PER_USER));
  $opsSection->push(NavBarItem::newAction('Per Room Report', 'Reports.php', REPORT_PER_ROOM));
  $opsSection->push(NavBarItem::newAction('Ops Report', 'Reports.php', REPORT_OPS_TRACK));
  $opsSection->push(NavBarItem::newAction('ConSuite Report', 'Reports.php', REPORT_CONSUITE_TRACK));
  $opsSection->push(NavBarItem::newAction('Games by Time', 'Reports.php', REPORT_GAMES_BY_TIME));
  $opsSection->push(NavBarItem::newAction('Users CSV', 'Reports.php', REPORT_USERS_CSV));
  $opsSection->push(NavBarItem::newAction('Registration Report', 'Reports.php', REPORT_REGISTRATION));
  if (THURSDAY_ENABLED)
    $opsSection->push(NavBarItem::newAction('PreCon Events Report', 'Thursday.php', THURSDAY_REPORT));
  if (DEAD_DOG_ENABLED)
    $opsSection->push(NavBarItem::newAction('Dead Dog Report', 'DeadDog.php', DEAD_DOG_REPORT));
  if (defined ('PROGRAM_PUB_PREFIX'))
  {
    $opsSection->push('Program Cover.pub', NavBarItem::newProgram(PROGRAM_PUB_PREFIX, 'Cover.pub'));
    $opsSection->push('Program Interior.pub', NavBarItem::newProgram(PROGRAM_PUB_PREFIX, 'Interior.pub'));
  }
  if (defined ('PROGRAM_PDF_PREFIX'))
  {
    $opsSection->push(NavBarItem::newProgram("Program Cover", PROGRAM_PDF_PREFIX, "Cover.pdf"));
    $opsSection->push(NavBarItem::newProgram("Program Interior", PROGRAM_PDF_PREFIX, "Interior.pdf"));
  }

  return $opsSection;
}

function build_schedule_subnavbar() {
  $scheduleSection = new SubNavBar("Scheduling");
  $scheduleSection->push(NavBarItem::newAction('Manage Schedule', 'ListGames.php'));
//    $scheduleSection->push(NavBarItem::newAction('Add a Parallel Run', 'ListGames.php', LIST_TO_ADD_PARALLEL_RUN));
  $scheduleSection->push(NavBarItem::newAction('Add Special Event', 'SpecialEvents.php'));
  $scheduleSection->push(NavBarItem::newAction('Manage Special Events', 'SpecialEvents.php', SPECIAL_EVENT_LIST));

  return $scheduleSection;
}

function build_about_subnavbar() {
  $aboutSection = new SubNavBar("About <NOBR>".(USE_CON_SHORT_NAME ? CON_SHORT_NAME : CON_NAME)."</NOBR>");
  $aboutSection->push(NavBarItem::newStatic("Table of Contents", "about"));
  $aboutSection->push(NavBarItem::newStatic("Convention Rules", "ConRules"));
  if (NAV_SHOW_COST)
    $aboutSection->push(NavBarItem::newAction("What Does It Cost?", "cost.php"));
  $aboutSection->push(NavBarItem::newAction("Contacts", "Contacts.php"));
  $aboutSection->push(NavBarItem::newStatic("Hotel Info", "hotel"));
  $aboutSection->push(NavBarItem::newAction("Who's Who", "index.php", WHO_IS_WHO));
  $aboutSection->push(NavBarItem::newStatic("Volunteering", "volunteering"));
  $aboutSection->push(NavBarItem::newAction("ConCom Schedule", "ConComSchedule.php"));
  if (NAV_SHOW_FLYER)
    $aboutSection->push(NavBarItem::newAction((USE_CON_SHORT_NAME ? CON_SHORT_NAME : CON_NAME).' Flyer', 'InterconFlyer.pdf'));
  if (NAV_SHOW_PROGRAM)
    $aboutSection->push(NavBarItem::newAction((USE_CON_SHORT_NAME ? CON_SHORT_NAME : CON_NAME).' Program', 'program-page-order.pdf'));

  return $aboutSection;
}

function build_website_subnavbar() {
  $websiteSection = new SubNavBar("Web Site");
  if (user_has_priv(PRIV_STAFF))
    $websiteSection->push(NavBarItem::newAction('Change Con Settings', 'Status.php'));
  $websiteSection->push(NavBarItem::newAction('Referring Sites', 'Referrers.php', VIEW_REFERRERS));
  $websiteSection->push(NavBarItem::newAction('Referring Site Summary',
	  'Referrers.php', SUMMARIZE_REFERRERS));
  if (user_has_priv(PRIV_STAFF))
  {
    $websiteSection->push(NavBarItem::newAction('Store Items',
						'Shirts.php',
						SHOW_STORE_ITEM_FORM));
    $websiteSection->push(NavBarItem::newAction('Import TShirts',
						'Shirts.php',
						IMPORT_TSHIRTS));
  }
  return $websiteSection;
}

function display_account_control_navbar() {
  $accountControl = new NavBar();
  if (is_logged_in()) {
    $userName = $_SESSION[SESSION_LOGIN_USER_NAME];
    $accountControl->push(new NavBarTitle($userName));
    $accountControl->push(NavBarItem::newAction("Log Out", "index.php", LOGOUT_USER, "logout"));
    $accountControl->push(NavBarItem::newAction("My Profile", "index.php", UPDATE_USER, "profile"));
    if (user_is_gm() || user_has_priv(PRIV_CON_COM, true))
      $accountControl->push(NavBarItem::newAction("Edit My Bio", "index.php", EDIT_BIO, "bio"));
  } else {
    $accountControl->push(NavBarItem::newAction('Login', 'index.php', NULL, "login"));
    $accountControl->push(NavBarItem::newAction('Register for <NOBR>'.(USE_CON_SHORT_NAME ? CON_SHORT_NAME : CON_NAME).'</NOBR>', 'index.php', NEW_USER, "register"));
  }
  echo $accountControl->render("menu accountControl priv");
}

function display_main_navbar() {
  // Title the menu
  $navbar = new NavBar();
  $navbar->push(new NavBarTitle("Navigation"));
  $navbar->push(NavBarItem::newAction('Home', 'index.php'));

  $navbar->push(build_about_subnavbar());
  $navbar->push(build_larps_subnavbar());
  if (accepting_bids() || user_has_priv(PRIV_BID_COM) || user_has_priv(PRIV_GM_LIAISON))
    $navbar->push(build_bids_subnavbar());
  if (user_is_gm() || user_has_priv(PRIV_GM_LIAISON) || user_has_priv(PRIV_CON_COM))
    $navbar->push(build_gm_subnavbar());
  if (THURSDAY_ENABLED)
    $navbar->push(build_precon_subnavbar());
  if (DEAD_DOG_ENABLED)
    $navbar->push(build_dead_dog_subnavbar());
  if (SHOW_TSHIRTS)
  if (is_logged_in() || user_has_priv(PRIV_CON_COM))
    $navbar->push(build_shirts_subnavbar());
  if (user_has_priv(PRIV_CON_COM))
    $navbar->push(build_attendees_subnavbar());
  if (user_has_any_mail_priv ())
    $navbar->push(build_mail_subnavbar());
  if (user_has_priv (PRIV_CON_COM))
    $navbar->push(build_print_materials_subnavbar());
  if (user_has_priv (PRIV_SCHEDULING))
    $navbar->push(build_schedule_subnavbar());
  if (user_has_priv(PRIV_CON_COM) || user_has_priv(PRIV_STAFF))
    $navbar->push(build_website_subnavbar());

  echo $navbar->render();
}

function display_links_navbar() {
  $links = new NavBar();
  $links->push(new NavBarTitle("Links"));
  $links->push(NavBarItem::newExternal('New England Interactive Literature',
			  'http://www.interactiveliterature.org'));
	if (user_has_priv(PRIV_CON_COM)) {
	  $links->push(NavBarItem::newExternal("Intercon Wiki", "http://www.interactiveliterature.org/Wiki"));
    $links->push(NavBarItem::newExternal((USE_CON_SHORT_NAME ? CON_SHORT_NAME : CON_NAME) . " Google Login", "http://www.google.com/a/" . CON_DOMAIN));
	}

  // Shameless Plugs section
  $plugsSection = new SubNavBar("Community Links");
  $plugsSection->push(NavBarItem::newAction("Shameless Plugs", "Plugs.php"));
  if (user_has_priv(PRIV_STAFF))
    $plugsSection->push(NavBarItem::newAction('Manage Shameless Plugs', 'Plugs.php', PLUGS_MANAGE_PLUGS));
  $links->push($plugsSection);

	echo $links->render("menu links");
}

function display_mini_logo() {
  if (defined("MINI_LOGO")) {
    echo '<img class="mini-logo" src="' . MINI_LOGO . '" alt="' . CON_NAME .' logo">';
  } elseif (defined("FULL_LOGO")) {
    // we need a spacer to bump the navbar down since there is a page banner on the right
    echo '<div class="mini-logo" style="height: 120px">&nbsp</div>';
  }
}

function display_navbar() {
  echo "<div class=navbar>\n";

  display_mini_logo();

  display_account_control_navbar();
  display_main_navbar();
  if (NAV_SHOW_LINKS)
    display_links_navbar();

  echo "</div> <!-- navbar -->\n\n";
}

?>