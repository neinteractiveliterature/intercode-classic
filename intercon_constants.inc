<?
// Note that PowWeb insists on setting the session save path!  Comment this
// out, or set it appropriately for your server

session_save_path("/home/users/web/b2078/pow.neilmaster/phpsessions");
session_start ();

// Setting DEVELOPMENT_VERSION turns on additional error checking, as well
// as diverting all mail to the DEVELOPMENT_MAIL_ADDR

define (DEVELOPMENT_VERSION, 0);

// Per-con values - The values of all of these should be visible on
// the status page

//define (DB_SERVER, 'localhost');
//define (DB_NAME,   'itest');
define (DB_SERVER, 'neilmaster.powwebmysql.com');
define (DB_NAME,   'intercon_j');

// Database users - These could/should be used to control access to the
// database.  In reality, it's handled by the software.  And the password
// could be (much) stronger, but there's no way to reach the database server
// from outside the domain, so we should be OK.  And it's not like we've got
// credit card numbers or other sensative stuff in here anyway...

define (DB_ADMIN_USR, 'interconuser');
define (DB_ADMIN_PWD, 'LARP');

define (DB_STAFF_USR, 'interconuser');
define (DB_STAFF_PWD, 'LARP');

define (DB_USER_USR, 'interconuser');
define (DB_USER_PWD, 'LARP');


// When DEVELOPMENT_VERSION is set to 1, all mail will be diverted to this
// address

define (DEVELOPMENT_MAIL_ADDR, 'barry@tannenbaum.mv.com');

// Set to 0 to disable the poll

define (POLL_ENABLED, 0);

// Set to 0 to disable the Thursday Thing stuff (needs to be implemented)

define (THURSDAY_ENABLED, 0);

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

define (PAYPAL_ITEM_SHIRT, CON_NAME . ' Shirt Payment');
define (PAYPAL_ITEM_CON, CON_NAME . ' Registration');
define (PAYPAL_ITEM_THURSDAY, CON_NAME . ' Thursday');

define (CON_MAIL_LETTER, 'j');
define (NAME_CON_CHAIR, 'Tim Lasko');
define (EMAIL_CON_CHAIR, 'conchair@' . CON_DOMAIN);
define (ADDR_CON_CHAIR, '88 Perry Street, Unit 281<br>Putnam, CT 06260');

// EMail Addresses

define (NAME_ADVERTISING, '');
define (EMAIL_ADVERTISING, '');

define (NAME_BID_CHAIR, 'Anna Bradley');
define (EMAIL_BID_CHAIR, 'bidchair@' . CON_DOMAIN);

define (NAME_CON_SUITE, 'Renee Cyr');
define (EMAIL_CON_SUITE, 'consuite@' . CON_DOMAIN);

define (NAME_GM_COORDINATOR, 'Josh Rachlin');
define (EMAIL_GM_COORDINATOR, 'gmcoordinator@' . CON_DOMAIN);

define (NAME_HOTEL_LIAISON, 'Alex Bradley');
define (EMAIL_HOTEL_LIAISON, 'hotelliaison@' . CON_DOMAIN);

define (NAME_OPS, 'Dave Kapell');
define (EMAIL_OPS, 'operations@' . CON_DOMAIN);
define (NAME_OPS2, '');
define (EMAIL_OPS2, '');

define (NAME_OUTREACH, 'Tim Lasko');
define (EMAIL_OUTREACH, 'info@' . CON_DOMAIN);

define (NAME_REGISTRAR, 'Chad Bergeron');
define (EMAIL_REGISTRAR, 'registrar@' . CON_DOMAIN);

define (NAME_THURSDAY, 'Joshua Sheena');
define (EMAIL_THURSDAY, 'thursday@' . CON_DOMAIN);

//define (NAME_TREASURER, 'Mike McAfee');
//define (EMAIL_TREASURER, 'mdm@haven.org');
define (NAME_TREASURER, 'Chad Bergeron');
define (EMAIL_TREASURER, '');

define (NAME_VENDOR_LIAISON, 'Jen Eastman-Lawrence');
define (EMAIL_VENDOR_LIAISON, 'vendors@' . CON_DOMAIN);

define (EMAIL_WEBMASTER, 'webmaster@' . CON_DOMAIN);

// Con dates

define (FRI_TEXT, 'Friday, 6-Mar-2010');
define (SAT_TEXT, 'Saturday, 7-Mar-2010');
define (SUN_TEXT, 'Sunday, 8-Mar-2010');

// Deadlines - Format is YYYY-MM-DD

define (REGISTRATION_CLOSE, '2010-02-24');
define (CON_OVER, '2010-03-03');
define (SHIRT_CLOSE, '2010-02-01');
define (BIO_CLOSE, '2009-12-25');

// Shirt stuff

define (SHIRT_IMG_AVAILABLE, 0);
define (SHIRT_TWO_SHIRTS, 0);
define (SHIRT_NAME, CON_NAME . " Men's Polo");
define (SHIRT_2_NAME, CON_NAME . ' Babydoll');

define (BIO_DUE_DATE, "December&nbsp;25,&nbsp;2009");

// 1 (true) if we're accepting bids, 0 if we're not

define (ACCEPTING_BIDS, 1);

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
