<?
include ("intercon_db.inc");

// Connect to the database

if (! intercon_db_connect ())
{
  display_mysql_error ('Failed to connect to ' . DB_NAME);
  exit ();
}

// Display boilerplate

html_begin ();

// Show the current cost

show_cost ();

// Standard postamble

html_end ();
/*
function parse_date ($d)
{
  $a = sscanf ($d, '%d-%d-%d');

  $year = $a[0];
  $month = $a[1];
  $day = $a[2];

  return mktime (0, 0, 0, $month, $day, $year);
}

function show_cost ()
{
  $prices = array (DATE_1_PRICE,
		   DATE_2_PRICE,
		   DATE_3_PRICE,
		   DATE_4_PRICE,
		   DATE_5_PRICE,
		   DATE_6_PRICE);
  $cutoff = array ();

  array_push ($cutoff, parse_date (DATE_1_CUTOFF));
  array_push ($cutoff, parse_date (DATE_2_CUTOFF));
  array_push ($cutoff, parse_date (DATE_3_CUTOFF));
  array_push ($cutoff, parse_date (DATE_4_CUTOFF));
  array_push ($cutoff, parse_date (DATE_5_CUTOFF));
  array_push ($cutoff, 0x7fffffff);

  // Get today's date.  If the con is over, warn the user and show the whole
  // price schedule.

  $now = time ();

  //  $now = parse_date ('2005-03-14');

  if ($now > parse_date ('2005-03-06'))
  {
    printf ("<font color=red>%s is over.  The following was the price schedule for %s</font><p>\n",
	    CON_NAME,
	    CON_NAME);
    $now = $cutoff[0] - 1;
  }

  $one_day = 60 * 60 * 24;

  // Figure out where we are in the sequence

  for ($k = 0; $k < count($cutoff); $k++)
  {
    if ($now < $cutoff[$k])
      break;
  }

  // If we're after the last cutoff, just display the final price.  Otherwise,
  // show the list

  if ($k == count($cutoff))
    printf ("<h1>%s is only $%s!</h1>\n",
	    CON_NAME,
	    $prices[count($prices)-1]);
  else
  {
    echo "<h1>Save BIG if you pay today!</h1>\n";
    printf ("<h2>%s is only $%s!</h2>\n",
	    CON_NAME,
	    $prices[$k]);

    while (++$k < (count($prices) - 1))
    {
      printf ("$%s after %s<br>\n",
	      $prices[$k],
	      strftime ('%d-%b-%Y', $cutoff[$k-1] - $one_day));
    }

    printf ("$%s after %s or at the door.<p>\n",
	    $prices[$k],
	    strftime ('%d-%b-%Y', $cutoff[$k-1] - $one_day));
  }

//  $reg_close = strftime ('%d-%b-%Y', parse_date (REGISTRATION_CLOSE));
//  echo "Online registration will close $reg_close<p>\n";
}
*/

function show_cost ()
{
  // Get today's date.  If the con is over, warn the user and show the whole
  // price schedule.

  $now = time ();

  //  $now = parse_date ('2005-03-14');

  if ($now > parse_date (CON_OVER))
  {
    printf ("<font color=red>%s is over.  The following was the price schedule for %s</font><p>\n",
	    CON_NAME,
	    CON_NAME);
    get_con_price (0, $price, $start_date, $end_date);
    $now = $start_date - 1;
  }

  $one_day = 60 * 60 * 24;

/*
  $k = 0;
  while (get_con_price (++$k, $price, $start_date, $end_date))
  {
    printf ("%d: $%d.00 after %s<br>\n", $k,
	      $price,
	      strftime ('%d-%b-%Y', $start_date - $one_day));
  }
  echo "<p>\n";
*/

  // Figure out where we are in the sequence

  $k = 0;
  while (get_con_price ($k++, $price, $start_date, $end_date))
  {
    if (0 == $end_date)
      break;
    if ($now < $end_date)
      break;
  }

  // If the con is over, warn the user and show the whole price
  // schedule

  if (0 == $end_date)
  {
    printf ("<font color=red>%s is over.  The following was the price schedule for %s</font><p>\n",
	    CON_NAME,
	    CON_NAME);
    $k = 0;
    get_con_price (0, $price, $start_date, $end_date);
    $now = $end_date - 1;
  }

//  printf ("%d: $%d.00, cutoff: %s, now: %s<p>\n", $k, $price,
//	  strftime ('%d-%b-%Y', $end_date),
//	  strftime ('%d-%b-%Y', $now));

	  
  // If we're after the last cutoff, just display the final price.  Otherwise,
  // show the list

  if (0 == $end_date)
    printf ("<h1>%s is only $%s!</h1>\n",
	    CON_NAME,
	    $prices[count($prices)-1]);
  else
  {
    echo "<h1>Save BIG if you pay today!</h1>\n";
    printf ("<h2>%s is only $%d.00!</h2>\n",
	    CON_NAME,
	    $price);

    while (1)
    {
      get_con_price ($k++, $price, $start_date, $end_date);
      if (0 == $end_date)
	break;

      printf ("$%d.00 after %s<br>\n",
	      $price,
	      strftime ('%d-%b-%Y', $start_date));
    }

    printf ("$%d.00 after %s or at the door.<p>\n",
	    $price,
	    strftime ('%d-%b-%Y', $start_date));
  }

  $reg_close = strftime ('%d-%b-%Y', parse_date (REGISTRATION_CLOSE));
  echo "Online registration will close $reg_close<p>\n";
}
?>