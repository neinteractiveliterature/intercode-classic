<?

include("intercon_upcharge.inc");

class DeadDogManager extends UpchargeItemManager {
    protected function getCollectionName() {
        return "Dead Dog";
    }
    
    protected function getTableName() {
        return "DeadDog";
    }
    
    protected function getPrimaryKeyColumn() {
        return "UserId";
    }
    
    protected function getReportAction() {
        return DEAD_DOG_REPORT;
    }
    
    protected function getSelectUpchargeAction() {
        return DEAD_DOG_SELECT_USER;
    }
    
    protected function getEditUpchargeAction() {
        return DEAD_DOG_EDIT_USER;
    }
    
    protected function getNewUpchargeAction() {
        return DEAD_DOG_NEW_USER;
    }
    
    protected function getProcessUpchargeAction() {
        return DEAD_DOG_PROCESS_USER;
    }
    
    protected function getCreateUpchargeAction() {
        return DEAD_DOG_CREATE_USER;
    }
    
    protected function userCanView() {
        return user_has_priv(PRIV_CON_COM);
    }
    
    protected function userCanEdit() {
        return user_has_priv(PRIV_REGISTRAR);
    }
}

// Connect to the database

if (! intercon_db_connect ())
{
  display_mysql_error ('Failed to establish connection to the database');
  exit ();
}

// Standard header stuff

html_begin ();

$action = request_int('action', DEAD_DOG);
$manager = new DeadDogManager();

if ($action == DEAD_DOG) {
    dead_dog($manager);
} else if (!$manager->processAction($action)) {
    echo "Unknown action code: $action\n";
}

function dead_dog($manager) {
    printf ("<h2>%s Dead Dog</h2>\n", CON_NAME);
    readfile("DeadDogInfo.html");
    
    echo "<div class=\"dead_dog_signup\">";
    if (is_logged_in()) {
        if (count($manager->fetchRowsForLoggedInUser(array("where" => "i.Status = 'Paid'"))) > 0) {
            echo "<p>You are registered and paid for the Dead Dog.  Thank you!</p>";
        } else {
            $cost = '20.58';  // $20 + 2.9%
              if (DEVELOPMENT_VERSION)
                $cost= '0.05';
            
            echo "<h3>Sign up for the Dead Dog!</h3>";
            
            echo "<div style=\"float: right;\">";
            $manager->displayPaypalButton(PAYPAL_ITEM_DEAD_DOG, $cost);
            echo "</div>";

            echo "<p style=\"margin-top: 0;\">You can pay in\n";
            echo "advance using PayPal by clicking <a href=\"";
            echo $manager->buildPaypalUrl(PAYPAL_ITEM_DEAD_DOG, $cost)."\">here</a>.\n";
            echo "Please note that we cannot guarantee availability unless you\n";
            echo "pay in advance!</p>\n";
        }
    } else {
        echo "<p>To register for the Dead Dog, please <a href=\"index.php\">log in</a>.</p>";
    }
    echo "</div>\n";
}

html_end();

?>