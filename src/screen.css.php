<? 
require ('intercon_constants.inc');
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
	text-align: center;
}

.navbar ul {
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

.navbar ul.priv_menu {
    border-color: <?echo COLOR_MENU_PRIV_FG; ?>;

    background: -webkit-gradient(linear, left top, left bottom, from(#fff), to(<?echo COLOR_MENU_PRIV_BG; ?>));
    background: -moz-linear-gradient(top, #fff, <?echo COLOR_MENU_PRIV_BG; ?>);

    box-shadow: 0px 0px 5px <?echo COLOR_MENU_PRIV_FG; ?>;
    -moz-box-shadow: 0px 0px 5px <?echo COLOR_MENU_PRIV_FG; ?>;
    -webkit-box-shadow: 0px 0px 5px <?echo COLOR_MENU_PRIV_FG; ?>;
}

.navbar li {
    border-bottom: 1px <?echo COLOR_MENU_PUBLIC_FG; ?> solid;
}

.navbar li a {
    display: block;
    padding: 3px;
    font-size: 90%;
}

.navbar li a:hover {
    background-color: rgba(255, 255, 0, 0.2);
}

.navbar li a, .navbar li a:visited {
    color: black;
    text-decoration: none;
}

.navbar ul.priv_menu li {
    border-bottom-color: <?echo COLOR_MENU_PRIV_FG; ?>;
}

.navbar ul li:last-child {
    border-bottom: none;
}

.navbar ul li.title
{
	background-color: <?echo COLOR_MENU_PUBLIC_FG; ?>;
	color: #FFFFFF;
	font-weight: bold;
        border-bottom: none;
}

.navbar ul.priv_menu li.title
{
	background-color: <?echo COLOR_MENU_PRIV_FG; ?>;
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
