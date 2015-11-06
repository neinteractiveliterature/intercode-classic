<?

// Setting DEVELOPMENT_VERSION turns on additional error checking, as well
// as diverting all mail to the DEVELOPMENT_MAIL_ADDR

define (DEVELOPMENT_VERSION, 1);

// When DEVELOPMENT_VERSION is set to 1, all mail will be diverted to this
// address

define (DEVELOPMENT_MAIL_ADDR, 'natbudin@gmail.com');

//Set this to the name of the directory that contains your con specific text.
//Copy the intercontext directory to yours and then edit the files there
//For your con specific text.  NEIL Intercons can skip this step.

define (TEXT_DIR, 'intercontext');

// Set to 0 to disable the poll

define (POLL_ENABLED, 0);

// Set to 0 to disable the Thursday Thing

define (THURSDAY_ENABLED, 1);

// Set to 0 to disable the Dead Dog

define (DEAD_DOG_ENABLED, 0);
define (DEAD_DOG_MAX, 95);

// Set to 0 to disable the Iron GM contest

define (IRONGM_ENABLED, 1);

// Defining the program file prefix enables the links on the ConCom menu
// which allow users to download the program booklet sections.  The second
// constant enables the entries on the Ops menu for the .PUB files

//define (PROGRAM_PDF_PREFIX, '01-29-');
//define (PROGRAM_PUB_PREFIX, '01-29-');

// Con identifiers

define (CON_ID, 'P');
define (CON_NAME, 'Intercon ' . CON_ID);
define (CON_NUMBER, 'nineteenth');
define (CON_DOMAIN, 'p.interconlarp.org');
define (CON_MAX, 450);
define (USE_CON_SHORT_NAME, 0);
define (CON_SHORT_NAME, CON_NAME);

define (NAV_SHOW_COST, 1);
define (NAV_SHOW_FLYER, 1);
define (NAV_SHOW_LINKS, 1);
define (NAV_SHOW_PROGRAM, 0);
define (TEXT_DIR, 'intercontext');
//define (MINI_LOGO, 'O_Banner_FNL_200x200.png');
define (FULL_LOGO, 'img/Intercon_P_banner.jpg');
define (FACEBOOK_ICON_URL, "http://interactiveliterature.org/P/" . MINI_LOGO);

define (PAYPAL_ACCOUNT_EMAIL, 'InteractiveLit@yahoo.com');
define (PAYPAL_ITEM_SHIRT, CON_NAME . ' Shirt Payment');
define (PAYPAL_ITEM_CON, CON_NAME . ' Registration');
define (PAYPAL_ITEM_THURSDAY, CON_NAME . ' Pre-Convention Registration');
define (PAYPAL_ITEM_DEAD_DOG, CON_NAME . ' Dead Dog');

define (CON_MAIL_LETTER, 'p');
define (NAME_CON_CHAIR, "Nat Budin");
define (EMAIL_CON_CHAIR, 'conchair@' . CON_DOMAIN);
define (ADDR_CON_CHAIR, 'Email conchair@' . CON_DOMAIN . ' for mailing address.');

define (NAME_SEND_CHECKS, 'Chad Bergeron');
define (ADDR_SEND_CHECKS, 'Email conchair@' . CON_DOMAIN . ' for mailing address.');

// EMail Addresses

define (NAME_ADVERTISING, '');
define (EMAIL_ADVERTISING, 'advertising@' . CON_DOMAIN);

define (NAME_BID_CHAIR, 'Kim Sward');
define (EMAIL_BID_CHAIR, 'bidchair@' . CON_DOMAIN);

define (NAME_CON_SUITE, '');
define (EMAIL_CON_SUITE, 'hospitality@' . CON_DOMAIN);

define (NAME_GM_COORDINATOR, 'Quinn D.');
define (EMAIL_GM_COORDINATOR, 'gmcoordinator@' . CON_DOMAIN);

define (NAME_HOTEL_LIAISON, 'Dave Kapell');
define (EMAIL_HOTEL_LIAISON, 'hotelliaison@' . CON_DOMAIN);

define (NAME_IRON_GM, 'Marsha Gershon');
define (EMAIL_IRON_GM, 'iron-gm@' . CON_DOMAIN);

define (NAME_OPS, 'Jaelen Rachlin');
define (EMAIL_OPS, 'ops@' . CON_DOMAIN);
define (NAME_OPS2, 'Diana Hsu');
define (EMAIL_OPS2, '');

define (NAME_OUTREACH, 'Joshua Sheena');
define (EMAIL_OUTREACH, 'info@' . CON_DOMAIN);

define (NAME_REGISTRAR, 'Tim Lasko');
define (EMAIL_REGISTRAR, 'registrar@' . CON_DOMAIN);

define (NAME_THURSDAY, 'Kristen Patten');
define (EMAIL_THURSDAY, 'precon@' . CON_DOMAIN);

define (NAME_TREASURER, 'Chad Bergeron');
define (EMAIL_TREASURER, '');

define (NAME_VENDOR_LIAISON, 'Jen Eastman-Lawrence');
define (EMAIL_VENDOR_LIAISON, 'vendors@' . CON_DOMAIN);

define (NAME_ATTENDEE_COORDINATOR, 'Adina Schreiber');
define (EMAIL_ATTENDEE_COORDINATOR, 'attendees@' . CON_DOMAIN);

define (NAME_STAFF_COORDINATOR, 'Stephanie Davis');
define (EMAIL_STAFF_COORDINATOR, 'staffcoordinator@' . CON_DOMAIN);

define (EMAIL_WEBMASTER, 'webmaster@' . CON_DOMAIN);

// Con dates

define (THU_TEXT, 'Thursday, 18-Feb-2016');
define (FRI_TEXT, 'Friday, 19-Feb-2016');
define (SAT_TEXT, 'Saturday, 20-Feb-2016');
define (SUN_TEXT, 'Sunday, 21-Feb-2016');

define (THR_DATE, '18-Feb-2016');
define (FRI_DATE, '19-Feb-2016');

// Deadlines - Format is YYYY-MM-DD

define (REGISTRATION_CLOSE, '2016-02-20');
define (CON_OVER, '2016-03-02');
define (SHIRT_CLOSE, '2016-01-31');
define (BIO_CLOSE, '2016-01-15');
define (BIO_DUE_DATE, "January 15, 2016.");

// Shirt stuff

define (SHIRT_IMG_AVAILABLE, 0);
define (SHIRT_TWO_SHIRTS, 1);
define (SHIRT_NAME, CON_NAME . " Shirts");
define (SHIRT_2_NAME, CON_NAME . ' Shirts');

//"December&nbsp;25,&nbsp;2010");

// Theme colors

define (COLOR_PAGE_BG, "#f0ece4");
define (COLOR_MENU_PUBLIC_FG, "#8a1a16");
define (COLOR_MENU_PUBLIC_BG, "#e0a9a8");
define (COLOR_MENU_PRIV_FG, "#ab8e26");
define (COLOR_MENU_PRIV_BG, "#f5eac4");

// 1 (true) if we're accepting bids, 0 if we're not

define (ACCEPTING_BIDS, 1);

define (RECAPTCHA_PUBLIC_KEY, "6LdT_r0SAAAAAH4jQl7tPF6lYgVVwRviR0cwkyz8");
define (RECAPTCHA_PRIVATE_KEY, "6LdT_r0SAAAAAIDqtj-Jzp6YRsT2xZA_9Kdhales");

// Backend for PEAR::Mail to use.  Should be either "mail", "sendmail", or "smtp"
define(MAIL_BACKEND, "smtp");

// Define these if you specified "smtp" above.
// Must be an SSL-enabled SMTP server listening on port 485
define(SMTP_HOST, "ssl://smtp.mandrillapp.com");
define(SMTP_PORT, 465);
define(SMTP_USERNAME, "natbudin@gmail.com");
define(SMTP_PASSWORD, "nsoV0qZIZL9bqeHS3es4yQ");

//Items for Bids/Schedule
global $CON_DAYS;
$CON_DAYS = array('Thursday', 'Friday', 'Saturday', 'Sunday');

//These must match the text values in the BidTimes table slot field.
global $BID_SLOTS;
$BID_SLOTS = array();
$BID_SLOTS['Thursday'] = array('Evening', 'After Midnight');
$BID_SLOTS['Friday'] = array('Afternoon', 'Evening', 'After Midnight');
$BID_SLOTS['Saturday'] = array('Morning', 'Afternoon', 'Evening', 'After Midnight');
$BID_SLOTS['Sunday'] = array('Morning');
$BID_SLOTS['All'] = array('Morning', 'Afternoon', 'Evening', 'After Midnight'); //all possible bid slots.

global $BID_SLOT_ABBREV;
$BID_SLOT_ABBREV = array();
$BID_SLOT_ABBREV['Morning'] = 'AM';
$BID_SLOT_ABBREV['Afternoon'] = 'PM';
$BID_SLOT_ABBREV['Evening'] = 'Eve';
$BID_SLOT_ABBREV['After Midnight'] = 'Late';
$BID_SLOT_ABBREV['Lunch'] = 'Lunch';
$BID_SLOT_ABBREV['Dinner'] = 'Din';

define(BID_SHOW_COMPS, 1);
define(ALLOW_EVENT_FEES, 0);  //set to 1 to allow additional fees for events.

//Types of bids; only use the ones you will accept.
global $BID_TYPES;
//$BID_TYPES = array('LARP', 'Tabletop RPG', 'Board Game', 'Panel', 'Other');
$BID_TYPES = array('LARP');


function parse_date ($d)
{
  $a = sscanf ($d, '%d-%d-%d');

  $year = $a[0];
  $month = $a[1];
  $day = $a[2];

  return mktime (0, 0, 0, $month, $day, $year);
}

/*
 * get_con_price
 *
 * The ONE routine that returns the dates and prices for the con
 *
 * Note - start_date is always the correct date.
 *      - end_date is always the end of the range + 1
 */

function get_con_price ($i, &$price, &$start_date, &$end_date)
{
  switch ($i)
  {

    // The price is $25 until 01-Aug-2014 - Was end of Intercon/DEXPO, now just arbitrary date
    case 0:
      $price = 25;
      $start_date = 0;
      $end_date = parse_date ('2015-08-01');
      return true;

    // The price is $30 01-Aug-2014 - 30-Sep-2014 - (Used to be end of Intercon Mid-Atlantic until Mid-Atlantic ended in 2008)
    case 1:
      $price = 30;
      $start_date = parse_date ('2015-08-01');
      $end_date = parse_date ('2015-10-02');
      return true;

    // The price is $40 01-Oct-2014 - 17-Jan-2015
    // Wednesday after the end of Arisia '11
    case 2:
      $price = 40;
      $start_date = parse_date ('2015-10-02');
      $end_date = parse_date ('2016-01-02');
      return true;

    // The price is $35 from 18-Jan-2015 - 20-Feb-2015 - Online Registration closes
    case 3:
      $price = 35;
      $start_date = parse_date ('2016-01-02');
      $end_date = parse_date ('2016-02-18');
      return true;

    // The price is $10 from 24-Feb-2015 until the end of Intercon O
    case 4:
      $price = 10;
      $start_date = parse_date ('2016-02-22');
      $end_date = 0;
      return true;
  }

  $price = 0;
  $start_date = 0;
  $end_date = 0;

  return false;
}

?>
