<?php

// For site isolation, change the session name below to something appropriate
// when deploying a new Intercon site.

session_name ('intercon_j');
session_start ();

// Setting DEVELOPMENT_VERSION turns on additional error checking, as well
// as diverting all mail to the DEVELOPMENT_MAIL_ADDR

define (DEVELOPMENT_VERSION, 1);

// Per-con values - The values of all of these should be visible on
// the status page

define (DB_SERVER, '127.0.0.1');
define (DB_NAME,   'itest');
//define (DB_SERVER, 'mysql.interactiveliterature.org');
//define (DB_NAME,   'intercon_j');

// Database users - These could/should be used to control access to the
// database.  In reality, it's handled by the software.  And the password
// could be (much) stronger, but there's no way to reach the database server
// from outside the domain, so we should be OK.  And it's not like we've got
// credit card numbers or other sensative stuff in here anyway...

define (DB_ADMIN_USR, 'root');
define (DB_ADMIN_PWD, '');

define (DB_STAFF_USR, 'root');
define (DB_STAFF_PWD, '');

define (DB_USER_USR, 'root');
define (DB_USER_PWD, '');


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

define (DEAD_DOG_ENABLED, 1);
define (DEAD_DOG_MAX, 100);

// Set to 0 to disable the Iron GM contest

define (IRONGM_ENABLED, 1);

// Set to 0 to disable allowing players to select events.

define (SELECTEVENTS_ENABLED, 1);

// Defining the program file prefix enables the links on the ConCom menu
// which allow users to download the program booklet sections.  The second
// constant enables the entries on the Ops menu for the .PUB files

//define (PROGRAM_PDF_PREFIX, '02-15-');
//define (PROGRAM_PUB_PREFIX, '02-15-');

// Con identifiers

define (CON_ID, 'J');
define (CON_NAME, 'Intercon ' . CON_ID);
define (CON_NUMBER, 'thirteenth');
define (CON_DOMAIN, 'intercon-j.org');
define (CON_MAX, 310);
//set USE_CON_SHORT_NAME for long convention names.
define (CON_SHORT_NAME, 'Intercon');
define (USE_CON_SHORT_NAME, 0);

define (PAYPAL_ACCOUNT_EMAIL, 'InteractiveLit@yahoo.com');
define (PAYPAL_ITEM_SHIRT, CON_NAME . ' Shirt Payment');
define (PAYPAL_ITEM_CON, CON_NAME . ' Registration');
define (PAYPAL_ITEM_THURSDAY, CON_NAME . ' Thursday');
define (PAYPAL_ITEM_DEAD_DOG, CON_NAME . ' Dead Dog');

define (CON_MAIL_LETTER, 'j');
define (NAME_CON_CHAIR, 'Tim Lasko');
define (EMAIL_CON_CHAIR, 'conchair@' . CON_DOMAIN);
define (ADDR_CON_CHAIR, '88 Perry Street, Unit 281<br>Putnam, CT 06260');

define (NAME_SEND_CHECKS, 'Fake Name');
define (ADDR_SEND_CHECKS, '123 Fake St<br>Fake, MA 90210');

// EMail Addresses

define (NAME_ADVERTISING, '');
define (EMAIL_ADVERTISING, '');

define (NAME_BID_CHAIR, 'Anna Bradley');
define (EMAIL_BID_CHAIR, 'arbradley@gmail.com');

define (NAME_CON_SUITE, 'Anna Bradley');
define (EMAIL_CON_SUITE, 'arbradley@gmail.com');

define (NAME_GM_COORDINATOR, 'Andrew Militello');
define (EMAIL_GM_COORDINATOR, 'gmcoordinator@' . CON_DOMAIN);

define (NAME_HOTEL_LIAISON, 'Alex Bradley');
define (EMAIL_HOTEL_LIAISON, 'hotelliaison@' . CON_DOMAIN);

define (NAME_IRON_GM, 'Viktoriya Fuzaylova');
define (EMAIL_IRON_GM, 'iron-gm@' . CON_DOMAIN);

define (NAME_OPS, 'Dave Kapell');
define (EMAIL_OPS, 'operations@' . CON_DOMAIN);
define (NAME_OPS2, '');
define (EMAIL_OPS2, '');

define (NAME_OUTREACH, 'Thorin Tabor');
define (EMAIL_OUTREACH, 'info@' . CON_DOMAIN);

define (NAME_REGISTRAR, 'Chad Bergeron');
define (EMAIL_REGISTRAR, 'registrar@' . CON_DOMAIN);

define (NAME_THURSDAY, 'Joshua Sheena');
define (EMAIL_THURSDAY, 'thursday@' . CON_DOMAIN);

define (NAME_TREASURER, 'Chad Bergeron');
define (EMAIL_TREASURER, '');

define (NAME_VENDOR_LIAISON, 'Jen Eastman-Lawrence');
define (EMAIL_VENDOR_LIAISON, 'vendors@' . CON_DOMAIN);

define (EMAIL_WEBMASTER, 'webmaster@' . CON_DOMAIN);

// Con dates

define (FRI_TEXT, 'Friday, 12-Mar-2010');
define (SAT_TEXT, 'Saturday, 13-Mar-2010');
define (SUN_TEXT, 'Sunday, 14-Mar-2010');

define (THR_DATE, '11-Mar-2010');
define (FRI_DATE, '12-Mar-2010');

// Deadlines - Format is YYYY-MM-DD

define (REGISTRATION_CLOSE, '2011-02-24');
define (CON_OVER, '2011-03-14');
define (SHIRT_CLOSE, '2013-02-01');
define (BIO_CLOSE, '2010-12-25');

// Information about the next convention for people who can't sign up for this one
define (NEXT_CON_INFO, "Registration for Intercon L will open in March 2011.");

// Shirt stuff
define (SHOW_TSHIRTS, 1);
define (TSHIRT_DOLLARS, 20);

define (SHIRT_IMG_AVAILABLE, 1);
define (SHIRT_TWO_SHIRTS, 1);
define (SHIRT_NAME, CON_NAME . " Men's Polo");
define (SHIRT_2_NAME, CON_NAME . ' Babydoll');

define (BIO_DUE_DATE, "December&nbsp;25,&nbsp;2009");

// Theme colors

//define (COLOR_MENU_PUBLIC_FG, "#24f");
//define (COLOR_MENU_PUBLIC_BG, "#bdf");
//define (COLOR_MENU_PRIV_FG, "#237723");
//define (COLOR_MENU_PRIV_BG, "#bfb");

define (COLOR_PAGE_BG, "#f0f0f0");
define (COLOR_MENU_PUBLIC_FG, "#58a3a7");
define (COLOR_MENU_PUBLIC_BG, "#b0d5d7");
define (COLOR_MENU_PRIV_FG, "#3d2062");
define (COLOR_MENU_PRIV_BG, "#d4b3ff");

//These are for the NavMenu
define (NAV_SHOW_COST, 0);
define (NAV_SHOW_FLYER, 0);
define (NAV_SHOW_LINKS, 0);

// 1 (true) if we're accepting bids, 0 if we're not

define (ACCEPTING_BIDS, 1);

define (RECAPTCHA_PUBLIC_KEY, "6Ld3_70SAAAAABWMABItLF0iRTQlzk9KoQtBCCY7");
define (RECAPTCHA_PRIVATE_KEY, "6Ld3_70SAAAAAPLq2uF5DdkW4DBF5g46svK7IH1o");

// Backend for PEAR::Mail to use.  Should be either "mail", "sendmail", or "smtp"
define(MAIL_BACKEND, "sendmail");

// Define these if you specified "smtp" above.
// Must be an SSL-enabled SMTP server
define(SMTP_HOST, "smtp.gmail.com");
define(SMTP_PORT, 587);
define(SMTP_USERNAME, "web@" . CON_DOMAIN);
define(SMTP_PASSWORD, "");

//Look and feel items
define (MINI_LOGO, 'O_Banner_FNL_200x200.png');
define (FULL_LOGO, 'O_Banner_FNL_400x400.png');

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
//$BID_SLOTS['Sunday'] = array('Morning', 'Afternoon', 'Evening', 'After Midnight');
//$BID_SLOTS['Monday'] = array('Morning', 'Afternoon');
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

    // The price is $25 until 20-Jul-2009 - Was end of Intercon/DEXPO, now just arbitrary date
    case 0:
      $price = 25;
      $start_date = 0;
      $end_date = parse_date ('2009-07-20');
      return true;

    // The price is $30 21-Jul-2009 - 12-Oct-2009 - End of Intercon Mid-Atlantic
    case 1:
      $price = 30;
      $start_date = parse_date ('2009-07-20');
      $end_date = parse_date ('2009-10-12');
      return true;

    // The price is $35 13-Oct-2009 - 20-Jan-2010
    // Wednesday after the end of Arisia '09
    case 2:
      $price = 35;
      $start_date = parse_date ('2009-10-12');
      $end_date = parse_date ('2010-01-20');
      return true;

    // The price is $40 from 21-Jan-2010 - 28-Feb-2010 - End of Boskone '09
    case 3:
      $price = 40;
      $start_date = parse_date ('2010-01-21');
      $end_date = parse_date ('2010-02-28');
      return true;

    // The price is $45 from 29-Feb-2010 until the end of Intercon I
    case 4:
      $price = 45;
      $start_date = parse_date ('2010-02-28');
      $end_date = 0;
      return true;
  }

  $price = 0;
  $start_date = 0;
  $end_date = 0;

  return false;
}

?>
