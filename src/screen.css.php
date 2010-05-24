<? 
require ('intercon_db.inc');
header("Content-type: text/css");
?>
body
{
	margin: 135px 0 0 0;
	padding: 0 7% 0 180px;
	background-image: url("PageBanner.png");
	background-repeat: no-repeat;
	background-color: #ffffff;
	background-position: 9px 5px;
      	color: #000000;
	font-family: sans-serif;
}

.navbar
{
	position: absolute;
	z-index: 5;
	width: 150px;
	top: 120px;
	left: 9px;
	margin: 0;
	padding: 0;
}

.navbar ul.menu {
  margin-top: 16px;
  margin-bottom: 16px;
}

#game_admin {
  float: right;
  width: 150px;
}

ul.menu {
    margin: 0;
	  text-align: center;
	  list-style-type: none;
    margin-left: 0;
    padding-left: 0;
    border: 2px <?echo COLOR_MENU_PUBLIC_FG; ?> solid;
    
    border-radius: 5px;
    -moz-border-radius: 5px;
    -webkit-border-radius: 5px;

    background: -webkit-gradient(linear, left top, left bottom, from(#fff), to(<? echo COLOR_MENU_PUBLIC_BG; ?>));
    background: -moz-linear-gradient(top, #fff, <?echo COLOR_MENU_PUBLIC_BG; ?>);

    box-shadow: 0px 0px 5px <?echo COLOR_MENU_PUBLIC_FG; ?>;
    -moz-box-shadow: 0px 0px 5px <?echo COLOR_MENU_PUBLIC_FG; ?>;
    -webkit-box-shadow: 0px 0px 5px <?echo COLOR_MENU_PUBLIC_FG; ?>;
}

ul.menu.priv {
    border-color: <?echo COLOR_MENU_PRIV_FG; ?>;

    background: -webkit-gradient(linear, left top, left bottom, from(#fff), to(<?echo COLOR_MENU_PRIV_BG; ?>));
    background: -moz-linear-gradient(top, #fff, <?echo COLOR_MENU_PRIV_BG; ?>);

    box-shadow: 0px 0px 5px <?echo COLOR_MENU_PRIV_FG; ?>;
    -moz-box-shadow: 0px 0px 5px <?echo COLOR_MENU_PRIV_FG; ?>;
    -webkit-box-shadow: 0px 0px 5px <?echo COLOR_MENU_PRIV_FG; ?>;
}

ul.menu li {
    border-bottom: 1px <?echo COLOR_MENU_PUBLIC_FG; ?> solid;
}

ul.menu li a {
    display: block;
    padding: 3px;
    font-size: 90%;
}

ul.menu li a:hover {
    background-color: rgba(255, 255, 0, 0.2);
}

ul.menu li a, ul.menu li a:visited {
    color: black;
    text-decoration: none;
}

ul.menu.priv li {
    border-bottom-color: <?echo COLOR_MENU_PRIV_FG; ?>;
}

ul.menu li:last-child {
    border-bottom: none;
}

ul.menu li.title
{
	background-color: <?echo COLOR_MENU_PUBLIC_FG; ?>;
	color: #FFFFFF;
	font-weight: bold;
        border-bottom: none;
}

ul.menu.priv li.title
{
	background-color: <?echo COLOR_MENU_PRIV_FG; ?>;
}

ul.menu li.alert {
  background-color: rgba(255, 0, 0, 0.3);
}

ul.menu li.info {
  background-color: rgba(255, 255, 255, 0.5);
  font-size: 80%;
  padding: 2px;
}

.print_logo
{
	display: none;
}

.copyright
{
	font-size: small;
	text-align: center;
}

.print_copyright
{
	display: none;
}

p.dev_warning
{
	font-size: large;
	color: red;
	font-weight: bold;
}
