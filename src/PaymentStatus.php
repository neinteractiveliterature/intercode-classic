<?php
include ("intercon_db.inc");

// Connect to the database

if (! intercon_db_connect ())
{
  display_mysql_error ('Failed to establish connection to the database');
  exit ();
}

// Display boilerplate

html_begin ();

if (!is_logged_in()) {
  display_access_error();
} else {
  $sql = 'SELECT CanSignup, CompEventId FROM Users';
  $sql .= '  WHERE UserId=' . $_SESSION[SESSION_LOGIN_USER_ID];

  //  echo "$sql<p>\n";

  $result = mysql_query ($sql);
  if (! $result)
    return display_mysql_error ('Cannot query user information');

  // Sanity check.  There should only be a single row

  if (0 == mysql_num_rows ($result))
    return display_error ('Failed to find user information');

  $row = mysql_fetch_object ($result);

  if ($row->CanSignup == "Alumni" || $row->CanSignup == "Unpaid") {
    // they're unpaid

    if (attendees_at_max()) {
      echo "<h2>Sorry!</h2>\n";
      echo "<p>".CON_NAME." has reached its attendance limit.  We cannot accept \n";
      echo "any more registrations at this time.</p>\n";
      echo "<p>".NEXT_CON_INFO."</p>\n";
    } else {

      $now = time();

      // Figure out where we are in the sequence

      $k = 0;
      while (get_con_price ($k++, $price, $start_date, $end_date))
      {
        if (0 == $end_date)
          break;
        if ($now < $end_date)
          break;
      }

      $cost = "$price.00";

      // If this is a development installation, force the price down to a nickle.
      // I'm willing to spend 5 cents/test, but no full price

      if (DEVELOPMENT_VERSION)
        $cost = '0.05';

      // Build the URL for the PayPal links

      $return_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php";
      $cancel_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php";

      $url = 'https://www.paypal.com/cgi-bin/webscr?';
      $url .= build_url_string ('cmd', '_xclick');
      $url .= build_url_string ('business', PAYPAL_ACCOUNT_EMAIL);
      $url .= build_url_string ('item_name', PAYPAL_ITEM_CON);
      $url .= build_url_string ('no_note', '0');
      $url .= build_url_string ('cn', 'Any notes about your payment?');
      $url .= build_url_string ('no_shipping', '1');
      $url .= build_url_string ('custom', $_SESSION[SESSION_LOGIN_USER_ID]);
      $url .= build_url_string ('currency_code', 'USD');
      $url .= build_url_string ('amount', $cost);
      $url .= build_url_string ('rm', '2');
      $url .= build_url_string ('cancel_return', $cancel_url);
      $url .= build_url_string ('return', $return_url, FALSE);

      //  echo "Encoded URL: $url<p>\n";
      //  printf ("%d characters<p>\n", strlen ($url));

      echo "<h2>Pay for ".CON_NAME."</h2>\n";

      echo "You are currently <b>unpaid</b> for ".CON_NAME.".\n";
      echo "You must complete your registration by paying the \$$cost\n";
      echo "convention fee before you can register for any games.  Here\n";
      echo "are the ways you can pay:<P>\n";

      echo "<h3>PayPal</h3>\n";

      echo "<A HREF=\"$url\" style=\"float: right; width: 100px; text-align: center;\">\n";
      echo "<IMG SRC=http://images.paypal.com/images/x-click-but3.gif BORDER=0\n";
      echo "ALT=\"Click to pay for Intercon D membership\">";
      echo "</A>";
      echo "<div style=\"margin-right: 100px;\">\n";
      echo "You can <A HREF=$url>pay now</A> through\n";
      echo "PayPal.  If you sign up for PayPal, please be sure to say that\n";
      echo "you were referred by <A HREF=mailto:".PAYPAL_ACCOUNT_EMAIL.">".PAYPAL_ACCOUNT_EMAIL."</A>\n";
      echo "and PayPal will give an extra $5 to the con!<P>\n";
      echo "If you pay for multiple people using PayPal, please tell us what\n";
      echo "you're doing in the Note field on the PayPal site so we can\n";
      echo "correlate the payments to the users.<P>\n";
      echo "</div>\n";
      echo "<div style=\"clear: both;\"></div>\n";

      echo "<h3>Mail a check or money order</h3>\n";

      echo "If you don't want to join PayPal, you can send a check or money\n";
      echo "order made out to\n";
      echo "&quot<B>New&nbsp;England&nbsp;Interactive&nbsp;Literature</B>&quot;\n";
      echo "to<br>\n";
      echo "<table>\n";
      echo "  <tr>\n";
      echo "    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
      printf ("    <td><b>%s<br>c/o %s<br>%s</b></td>\n",
          CON_NAME,
          NAME_SEND_CHECKS,
          ADDR_SEND_CHECKS);
      echo "  </tr>\n";
      echo "</table>\n";
      echo "<p>\n";
    }

  } else {

    echo "<h2>Congratulations!</h2>\n";
    echo "<p>You are paid up for ".CON_NAME.".</p>\n";

  }
}

// Add the postamble

html_end ();

?>