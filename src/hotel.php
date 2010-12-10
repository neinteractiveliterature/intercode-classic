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

// Do the work

display_the_page ();

// Add the postamble

html_end ();

function display_the_page()
{
echo "<h3>Hotel Information</h3>\n";
echo "<table border=0 width=\"100%\" cellspacing=2 cellpadding=2>\n";
echo "  <tr>\n";
echo "    <td colspan=\"2\">\n";
echo "      Intercon this year will be in the \n";
echo "      <a href=\"http://www.starwoodhotels.com/westin/property/overview/index.html?propertyID=1036\" target=\"_blank\">Westin \n";
echo "      Waltham Boston</a> in Waltham, Massachusetts!\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td valign=\"top\">\n";
echo "      The Westin is \n";
echo "      <a href=\"http://maps.google.com/maps?q=70+third+avenue,+waltham,+ma&oe=utf-8&client=firefox-a&ie=UTF8&hq=&hnear=70+3rd+Ave,+Waltham,+Middlesex,+Massachusetts+02451&gl=us&ei=XupuS6rwHsXU8AbOqb3TDw&ved=0CAgQ8gEwAA&z=16\" target=\"_blank\">conveniently located</a> \n";
echo "      just off exit 27A (Totten Pond Road) on Interstate 95 (Route 128) in Waltham. \n";
echo "      Follow the signs on the highway ramp to the hotel.\n";
echo "      <p>A map is available <a href=\"http://maps.google.com/maps?q=70+third+avenue,+waltham,+ma&oe=utf-8&client=firefox-a&ie=UTF8&hq=&hnear=70+3rd+Ave,+Waltham,+Middlesex,+Massachusetts+02451&gl=us&ei=XupuS6rwHsXU8AbOqb3TDw&ved=0CAgQ8gEwAA&z=16\" target=_blank>here</a>.\n";
echo "    </td>\n";
echo "    <td valign=\"top\" rowspan=\"3\">\n";
echo "      <table class=\"reserve\" width=\"100%\" border=\"0\"  cellspacing=\"2\" cellpadding=\"2\">\n";
echo "        <tr class=\"reserve\">\n";
echo "          <th>How to make a reservation for Intercon</th>\n";
echo "        </tr>\n";
echo "        <tr class=\"reserveBody\">\n";
echo "          <td>\n";
echo "            It's easy!\n";
echo "            <ol>\n";
echo "            <li>Call the Westin toll free number\n";
echo "            <nobr>1-(800) 937-8461</nobr> or their local number\n";
echo "            <nobr>(781) 290-5600</nobr>.\n";
echo "            <p>\n";
echo "            <li>We will have a room block set up for Intercon.\n";
echo "            Once we have more information about how to register\n";
echo "	          for it, we will update this page.<br>\n";
echo "            <table border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
echo "              <tr class=\"reserveBody\">\n";
echo "                <td>&nbsp;&nbsp;&nbsp;</td>\n";
echo "                <td>Room:</td>\n";
echo "                <td>$105/night</td>\n";
echo "              </tr>\n";
echo "            </table>\n";
echo "	    This rate covers 2 people per room.\n";
echo "	    <p>\n";
echo "            <b>Note:</b> It is <a href=\"#whyReserve\">very important</a> that\n";
echo "            you make the reservation as part of the Intercon group!\n";
echo "            <p>\n";
printf ("            <li>The convention runs from %s to %s.\n", FRI_TEXT, SUN_TEXT);
echo "            </ol>\n";
echo "            <p>\n";
echo "            Guaranteed reservations are available for arrival until \n";
echo "            7 AM of the day following first night reserved. Notice \n";
echo "            of cancellation must be made at least 24 hours prior to \n";
echo "            the date of expected arrival.\n";
echo "            <p>\n";
echo "            If there are any problems or questions, please \n";
echo "            feel free to send email to our \n";
 printf ("            <a href=%s>Hotel Liaison</a>.\n",
	 mailto_url (EMAIL_HOTEL_LIAISON, 'Hotel question'));
echo "          </td>\n";
echo "        </tr>\n";
echo "      </table>\n";
echo "    </td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td align=\"center\" valign=\"center\"><img src=\"westin.jpg\" border=\"0\" alt=\"The Westin Waltham Boston\"></td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "    <td align=\"center\" valign=\"center\">\n";
echo "		    <table border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
echo "				<tr>\n";
echo "					<td colspan=\"2\" align=\"center\">\n";
echo "					<a href=\"http://www.starwoodhotels.com/westin/property/overview/index.html?propertyID=1036\"\n";
echo "		               target=\"_blank\">Westin Waltham Boston</a>\n";
echo "				<tr>\n";
echo "				    <td colspan=\"2\" align=\"center\">70 Third Avenue</td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "				    <td colspan=\"2\" align=\"center\">Waltham, MA 02451</td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "				    <td colspan=\"2\" align=\"center\"><hr></td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "				    <th align=\"left\">Phone:</th>\n";
echo "				    <td><nobr>(781) 290-5600</nobr></td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "				    <th align=\"left\">Reservations:</th>\n";
echo "					<td><nobr>(800) 937-8461</nobr></td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "				    <th align=\"left\">Guest Fax:</th>\n";
echo "					<td><nobr>TBA</nobr></td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "				    <th align=\"left\">Sales Fax:</th>\n";
echo "					<td><nobr>TBA</nobr></td>\n";
echo "			    </tr>\n";
echo "				<tr>\n";
echo "				    <td colspan=\"2\" align=\"center\"><hr></td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "				    <th align=\"left\">Checkin Time:</th>\n";
echo "					<td>TBA</td>\n";
echo "			    </tr>\n";
echo "				<tr>\n";
echo "				    <th align=\"left\">Checkout Time:</th>\n";
echo "					<td>TBA</td>\n";
echo "			    </tr>\n";
echo "			</table>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td colspan=\"2\">";
/*echo "      Reserving a room also entitles you to four free \n";
echo "		breakfast vouchers. These vouchers are good at the hotel restaurant \n";
echo "		on Saturday and Sunday morning. If you are interested in purchasing \n";
echo "		additional breakfast vouchers, ask the desk clerk when you check \n";
echo "		in.\n";
echo "		<p>The hotel allows up to four people to occupy a room. \n";
echo "		Please do not exceed this.  The quoted room rates cover\n";
echo "		two people.  There will be an additional charge of\n";
echo "		$10/person for each addition person.\n";
echo "		<p>Non-smoking and handicap-accessible rooms are \n";
echo "		available on request.\n";
echo "		<p>All rooms include:\n";
echo "		<ul>\n";
echo "			<li>Alarm clock / radio\n";
echo "			<li>Coffee maker with coffee / tea\n";
echo "			<li>Hair Dryer\n";
echo "			<li>Iron and full size ironing board\n";
echo "			<li>Telephone with dataport\n";
echo "			<li>Voicemail\n";
echo "			<li>25&quot; color television with cable, free HBO, pay \n";
echo "			movies, and Nintendo\n";
echo "			<li>Complimentary USA Today\n";
echo "		</ul>\n";
echo "		<p>Additionally, suites come with:\n";
echo "		<ul>\n";
echo "			<li>Refrigerator\n";
echo "			<li>Microwave\n";
echo "			<li>VCR\n";
echo "			<li>Free wireless Internet access\n";
echo "		</ul>\n";
echo "		<p>The hotel also has additional guest services:\n";
echo "		<ul>\n";
echo "			<li>Business amenities including fax, copier, and personal computer \n";
echo "			<li>Laundry / valet service \n";
echo "			<li>Safe deposit boxes \n";
echo "			<li>24 hour gift shop \n";
echo "			<li>Area's largest indoor swimming pool with 24 hour fitness room \n";
echo "			<li>Budget Car Rental courtesy phone \n";
echo "		</ul>\n"; */
echo "		<p>Should you arrive on Friday before checkin, or for the remainder of \n";
echo "		the con after Sunday checkout, Intercon and Hotel Staff can provide \n";
echo "		a secure area for your belongings.\n";
echo "		<p>If there are any problems or questions before the con,\n";
 printf ("            please contact our <a href=%s>Hotel Liaison</a>. The\n",
	 mailto_url (EMAIL_HOTEL_LIAISON, 'Hotel question'));
echo "		Hotel Liaison will also be available at the con to solve any problems. \n";
echo "		Please let the Ops Desk know of any issues; they will know where the \n";
echo "		Hotel Liaison is at any given time. We are very good at expediting \n";
echo "		solutions with the hotel, so it's best if you come find us to help out \n";
echo "		first.\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td colspan=\"2\">\n";
echo "		    <table class=\"reserve\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
echo "				<tr class=\"reserve\">\n";
echo "					<th><a name=\"whyReserve\">Why it's important for you to \n";
echo "					reserve your room well in advance!</a></th>\n";
echo "				</tr>\n";
echo "				<tr class=\"reserveBody\">\n";
echo "					<td>Intercon reserves <b>all</b> the function space in \n";
echo "					the hotel. That costs us a <b>significant</b> amount of \n";
echo "					money - several thousand dollars. Our space costs eat \n";
echo "					up, by far, the majority of our con budget. <i>The \n";
echo "					final space cost is determined by how many hotel rooms \n";
echo "					are reserved by people coming to the con, using the \n";
echo "					group reservation process.</i> The more hotel rooms the \n";
echo "					con books, the more we get a break on the function \n";
echo "					space cost. The break can be as much as a thousand \n";
echo "					dollars. We try to make our registration fees cover \n";
echo "					just what we need to pay for the function space, to pay \n";
echo "					for the con suite, and to cover the other costs that go \n";
echo "					into the convention. We try to break even. It's a tough \n";
echo "					balance; we have to guess a year in advance if we can \n";
echo "					meet our budget if we still let you sign up for as \n";
echo "					little as $25. You can help us to keep <b>your own</b> \n";
echo "					registration costs down by reserving your room early, \n";
echo "					through the Westin reservation process.</td>\n";
echo "				</tr>\n";
echo "			</table>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "</table>\n";
echo "<p>\n";
echo "Intercon rents all the conference space available in the hotel. ";
echo "<a href=\"westin-floorplan.png\" target=\"_blank\">Click here for a map of the conference space layout.</a>";
echo "\n";
}
?>
