<?PHP

namespace Controller;

class Database {

    protected $db;
    private $config = false;

    // Contructor
    public function __construct() {
        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Load config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
            if ( $this->config === false ) {
                return false;
            }
        }
    }

    // Destructor
    public function __destruct() {
        $this->db = null;
    }

    // Load config file from etc
    private function loadConfig() {
        if ( is_file(__DIR__ . '/../../etc/testdatabase.ini') ) {
            $this->logger->logErrorMessage('Can\'t open etc/database.ini file, not able to handle connections!');
            return false;
        } else {
            $config = parse_ini_file(__DIR__ . '/../../etc/database.ini',true);
            return $config;
            
        }
    }

}