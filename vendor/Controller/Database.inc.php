<?PHP

namespace Controller;

class Database {

    protected $db;

    private $dbBackendAvailable = false;
    private $dbConnected = false;
    private $dbReuseConnection = false;
    private $dbHostinfo = '';

    private $dbHost;
    private $dbPort;
    private $dbUser;
    private $dbPass;
    private $dbName = 'smtplatrine';

    private $logger;
    private $mysqlReporting = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
    private $config = false;

    // Contructor
    public function __construct( $reuseConnection = false ) {
        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Load config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
            if ( $this->config === false ) {
                $this->dbBackendAvailable = false;
            } else {
                $this->dbBackendAvailable = array_key_exists('engine_backend',$this->config['engine']) ? trim($this->config['engine']['engine_backend']) : 'mysql';
                $this->dbBackendAvailable = strtolower(trim($this->dbBackendAvailable));
            }
        }

        // Only continue if we have a available backend
        if ( $this->dbBackendAvailable ) {
            // Set database credentials
            $this->setDBcredentials(); 

            // Set if we want persistent connection
            if ( $reuseConnection ) $this->dbReuseConnection = true;

            // Switch backend
            switch ( strtolower(trim($this->dbBackendAvailable)) ) {
                case 'mysql':
                    $this->logger->logMessage('[database] Using MySQL backend');
                    $this->dbBackendAvailable = 'mysql';
                    break;
                default:
                    $this->logger->logErrorMessage('[database] Backend not supported: ' . $this->dbBackendAvailable);
                    $this->dbBackendAvailable = false;
                    break;
            }

        } else {
            $this->logger->logErrorMessage('[database] Backend not available');
        }
        
    }

    // Destructor
    public function __destruct() {
        $this->logger->logDebugMessage('[database] Closing connection to ' . $this->dbHost . ' on port ' . $this->dbPort . ' as ' . $this->dbUser . ' to database ' . $this->dbName);
        $this->dbMysqlClose();
    }

    // Check if we could load config
    public function isDBready() {
        if ( $this->dbBackendAvailable === false ) {
            return false;
        } else {
            return true;
        }
    }

    // Load config file from etc
    private function loadConfig() {
        if ( is_file(__DIR__ . '/../../etc/database.ini') === false ) {
            $this->logger->logErrorMessage('Can\'t open etc/database.ini file, not able to handle connections!');
            return false;
        } else {
            $config = parse_ini_file(__DIR__ . '/../../etc/database.ini',true);
            return $config;
            
        }
    }

    // Set host,port,user and password
    private function setDBcredentials( ) {
        if ( $this->config ) {
            $this->dbHost = trim($this->config['mysql']['mysql_host']);
            $this->dbPort = intval($this->config['mysql']['mysql_port']);
            $this->dbUser = trim($this->config['mysql']['mysql_username']);
            $this->dbPass = $this->config['mysql']['mysql_password'];
            $this->dbName = trim($this->config['mysql']['mysql_database']);
        }
    }

    // Connect to the database
    private function dbMysqlConnect() {
        if ( $this->dbConnected === false ) {

            // Prepend p: to hostname to indicate persistent connection
            if ( $this->dbReuseConnection ) {
                $dbHost = 'p:'.trim($this->dbHost);
            } else {
                $dbHost = str_replace("p:","",trim($this->dbHost));
            }

            // Connect to the database
            $db = mysqli_connect($dbHost, $this->dbUser, $this->dbPass, $this->dbName, $this->dbPort);

            if ( mysqli_connect_errno() ) {
                $this->logger->logErrorMessage('[database] Failed connection: ' . trim(mysqli_connect_error()));
                $this->dbConnected = false;
            } else {
                $this->logger->logDebugMessage('[database] Connected to ' . $this->dbHost . ' on port ' . $this->dbPort . ' as ' . $this->dbUser . ' to database ' . $this->dbName);
                $this->db = $db;
                $this->dbConnected = true;
                $this->dbHostinfo = trim(mysqli_get_host_info($db));

                mysqli_set_charset($db, 'utf8mb4');
                if (mysqli_errno($db)) {
                    // Log that setting charset failed
                    $this->logger->logErrorMessage('[database] Failed setting charset: ' . trim(mysqli_error($db)));
                }
            }
        } else {
            // Already connected
        }
    }

    // Close the database connection
    private function dbMysqlClose() {
        if ( $this->dbConnected ) {
            $this->db->close();
            $this->dbConnected = false;
        }
    }

    // Do query ( NOTE THIS HAS NO ESCAPING! PLEASE USE PREPARED STATEMENTS! )
    // This i just for quick and dirty queries
    public function dbMysqlRawQuery( $query ) {
        // Set MySQLi reporting
        mysqli_report($this->mysqlReporting);
        // Establish a connection to the database
        $this->dbMysqlConnect();

        if ( $this->dbConnected ) {
            $result = mysqli_query($this->db, $query);
            $rowcount = mysqli_num_rows($result);
            if ( $result === false ) {
                $this->logger->logErrorMessage('[database] Query failed: ' . trim(mysql_error()));
            } else {
                $this->logger->logDebugMessage('[database] Query success: ' . $query . ' returned ' . $rowcount . ' rows');
                return $result;
            }
            // Close the connection
            $this->dbMysqlClose();
        } else {
            return false;
        }
    }

    // Ping the database connection
    public function dbMysqlPing() {
        if ( $this->db->ping() && $this->dbConnected ) {
            // Log the result
            $this->logger->logMessage('[database] Ping: Our connection is ok!');
        } else {
            // Log the result and show error
            $this->logger->logErrorMessage('[database] Ping: Failed ('.trim($this->db->error).')');
            $this->dbConnected = false;
        }
    }

    // 


}