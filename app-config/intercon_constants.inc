<?

// For site isolation, change the session name below to something appropriate
// when deploying a new Intercon site.

session_name ('intercon');
session_start ();

// Per-con values - The values of all of these should be visible on
// the status page

define (DB_SERVER, getenv('INTERCON_DB_HOST'));
define (DB_NAME,   getenv('INTERCON_DB_NAME'));
//define (DB_SERVER, 'mysql.interactiveliterature.org');
//define (DB_NAME,   'intercon_j');

// Database users - These could/should be used to control access to the
// database.  In reality, it's handled by the software.  And the password
// could be (much) stronger, but there's no way to reach the database server
// from outside the domain, so we should be OK.  And it's not like we've got
// credit card numbers or other sensative stuff in here anyway...

define (DB_ADMIN_USR, getenv('INTERCON_DB_USER'));
define (DB_ADMIN_PWD, getenv('INTERCON_DB_PASSWORD'));

define (DB_STAFF_USR, getenv('INTERCON_DB_USER'));
define (DB_STAFF_PWD, getenv('INTERCON_DB_PASSWORD'));

define (DB_USER_USR, getenv('INTERCON_DB_USER'));
define (DB_USER_PWD, getenv('INTERCON_DB_PASSWORD'));

require ('con_settings.inc');

?>
