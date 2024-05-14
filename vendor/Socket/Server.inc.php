<?PHP
namespace Socket;

class Server {

	protected $server;
	protected $address;
	protected $port;
	protected $listenLoop;
	protected $connectionHandler;
	private $logger;
	private $config = false;

	private $acl_blacklist_ip = array();
	private $acl_blacklist_geo = array();

	public $childProcesses = array();	// Array of child processes
	private $maxClients = 0;

	// Constructor
	public function __construct() {

		// Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

		$this->listenLoop = false;
		if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }
		$this->address = strtolower(trim($this->config['server']['server_listen']));
		$this->port = strtolower(trim($this->config['server']['server_port']));

		// Check if protection acl is enabled and then refresh acl lists
		if ( array_key_exists('protection',$this->config) ) {

			if (array_key_exists('protection_acl_blacklist_ip',$this->config['protection']) || array_key_exists('protection_acl_blacklist_geo',$this->config['protection']) ) {
				$this->logger->logMessage('[server] Protection ACL enabled, refreshing appropriate local cached blacklists','NOTICE');
			}

			if ( array_key_exists('protection_acl_blacklist_ip',$this->config['protection']) ) {
				if ( trim($this->config['protection']['protection_acl_blacklist_ip']) == "1" ) {
					$this->refreshACL('ip');
				}
			}
			if ( array_key_exists('protection_acl_blacklist_geo',$this->config['protection']) ) {
				if ( trim($this->config['protection']['protection_acl_blacklist_geo']) == "1" ) {
					$this->refreshACL('geo');
				}
			}
		}

		// Get max connections allowed from protection
		$this->maxClients = array_key_Exists("protection_max_connections",$this->config['protection']) ? intval($this->config['protection']['protection_max_connections']) : 10;
		$this->logger->logMessage('[server] Max concurrent connections set to: ' . $this->maxClients);
	}

	// Destructor
	public function __destruct() {
		socket_close($this->server);
	}

	// Load config file from etc
    private function loadConfig() {
        $config = parse_ini_file(__DIR__ . '/../../etc/server.ini',true);
        return $config;
    }

	// Start the server
	public function start() {
		$this->logger->logDebugMessage('[server] Creating socket on ' . $this->address . ':' . $this->port);
		$this->createSocket();
		$this->bindSocket();
	}

	// Listen for connections
	public function listen() {
		if ( socket_listen($this->server, 5) === false) {
			throw new SocketException( 
				SocketException::CANT_LISTEN, socket_strerror(socket_last_error( $this->server ) ) 
			);
		}
		
		$this->listenLoop = true;
		$this->beforeServerLoop();
		$this->serverLoop();
		socket_close( $this->server );
	}

	// Before server loop
	protected function beforeServerLoop() {
		$this->logger->logMessage('[server] Listning on ' . $this->address . ':' . $this->port . ' ...');
	}

	// Set connection handler
	public function setConnectionHandler( $handler ) {
		$this->connectionHandler = $handler;
		$this->logger->logDebugMessage("[server] Connection handler set to: " . $handler);
	}

	// Server loop
	protected function serverLoop() {

		// Activate non-blocking mode
		//socket_set_nonblock( $this->server );

		while( $this->listenLoop ) {
			
			/*
			if ( ( $client = @socket_accept( $this->server ) ) === false ) {
				throw new SocketException (
					SocketException::CANT_ACCEPT, socket_strerror(socket_last_error( $this->server ) ) 
				);
				continue;
			}
			*/
			

			// check if pids exits
			foreach( $this->childProcesses as $key => $pid ) {
				$pidStatus = pcntl_waitpid( $pid, $status, WNOHANG );
				if ( $pidStatus == -1 || $pidStatus > 0 ) {
					unset( $this->childProcesses[$key] );
				}
			}

			$this->acceptConnection();

			// As we are in a loop and now in non-blocking mode we need to sleep
			// or we will consume all CPU! Or at least a lot of it :)

			usleep(100000); // Sleep for 100ms

		}
	}

	// Accept connection
	function acceptConnection() {
        $read_socket = array($this->server);
        $write = null;
        $except = null;
        $changed = socket_select($read_socket, $write, $except, 0, 0);

        if(!$changed) {
            return false;
        }
        $handle = socket_accept($this->server);
        if(!$handle) {
            return false;
        }

        $socketClient = new SocketClient( $handle );

		// Check if IP is blacklisted
		if ( $this->aclCheckIfBlacklisted('ip',$socketClient->getPeerAddress()) ) {
			$this->logger->logMessage("[".$socketClient->getPeerAddress()."] Closed connection (Rejected: Blacklisted IP)");
			$socketClient->close();
			return false;
		}

		if ( is_array( $this->connectionHandler ) ) {
			$object = $this->connectionHandler[0];
			$method = $this->connectionHandler[1];
			$object->$method( $socketClient );
		} else {
			$function = $this->connectionHandler;
			$spawnClient = new $function( $socketClient, $this->childProcesses );
			$this->childProcesses[] = $spawnClient->getPID();

			// If max clients reached print different log message
			if ( count($this->childProcesses) > $this->maxClients ) {
				$this->logger->logMessage('[server] Max clients reached, claiming to be busy :)', 'WARNING');
			} else {
				$this->logger->logMessage('[server] Clients connected ' . count($this->childProcesses). ' of ' . $this->maxClients);
			}
			$this->logger->logMessage('[server] Spawned client on pid '.$spawnClient->getPID());
		}
    }

	// Refresh ACL array from DB
	private function refreshACL( $blacklist = false ) {
		if ( $blacklist ) {
			switch( $blacklist ) {
				case "ip":
					$this->acl_blacklist_ip = array();
					$this->logger->logMessage('[server] Refreshing ACL IP blacklist from database', 'NOTICE');
					$this->acl_blacklist_ip = $this->getBlacklist('ip');
					break;
				case "geo":
					$this->acl_blacklist_geo = array();
					$this->logger->logMessage('[server] Refreshing ACL Geo blacklist from database', 'NOTICE');
					$this->acl_blacklist_geo = $this->getBlacklist('geo');
					break;
				case "all":
					$this->acl_blacklist_geo = array();
					$this->acl_blacklist_ip = array();
					$this->logger->logMessage('[server] Refreshing ACL Geo blacklist from database', 'NOTICE');
					$this->logger->logMessage('[server] Refreshing ACL IP blacklist from database', 'NOTICE');
					$this->acl_blacklist_geo = $this->getBlacklist('geo');
					$this->acl_blacklist_ip = $this->getBlacklist('ip');
					break;
				default:
					$this->logger->logErrorMessage('[server] Blacklist not supported: ' . $blacklist);
					break;
			}
		}

	}

	// Get blacklist from database
	private function getBlacklist( $blacklist = "ip" ) {
		$blacklistArray = array();
		// Load up Database functionality
        $db = new \Controller\Database;

		switch( $blacklist ) {
			case "ip":
				$query = "SELECT ip_addr FROM acl_blacklist_ip";
				$result = $db->dbMysqlRawQuery($query);
				if ( mysqli_num_rows($result) > 0 ) {
					while( $row = mysqli_fetch_assoc($result) ) {
						$blacklistArray[] = $row['ip_addr'];
					}
				}
				$this->logger->logMessage('[server] ACL Loaded ' . count($blacklistArray) . ' IP addresses to blacklist', 'NOTICE');
				break;
			case "geo":
				$query = "SELECT geo_code FROM acl_blacklist_geo";
				$result = $db->dbMysqlRawQuery($query);
				if ( mysqli_num_rows($result) > 0 ) {
					while( $row = mysqli_fetch_assoc($result) ) {
						$blacklistArray[] = $row['geo_code'];
					}
				}
				$this->logger->logMessage('[server] ACL Loaded ' . count($blacklistArray) . ' Geo codes to blacklist', 'NOTICE');
				break;
			default:
				$this->logger->logErrorMessage('[server] Blacklist not supported: ' . $blacklist);
				break;
		}
		return $blacklistArray;
	}

	// Protection: Check against acl blacklist
	// Will return "FALSE" if not blacklisted
	// Will return "TRUE" if blacklisted
	private function aclCheckIfBlacklisted( $blacklist = "ip", $value = false ) {

		// Default deny?
		$defaultAction = true;

		if ( $value === false ) {
			return $defaultAction;
		}

		switch( strtolower(trim($blacklist)) ) {
			case "ip":
				$check = trim($this->config['protection']['protection_acl_blacklist_ip']) == "1" ? true : false;
				if ( $check  && filter_var($value, FILTER_VALIDATE_IP) ) {
					if ( in_array($value,$this->acl_blacklist_ip) ) {
						$this->logger->logMessage('[server] Blacklisted IP tried to connect: '.$value.' (Protection: enabled)', 'NOTICE');
						return true;
					}
				}
				break;
			default:
				$this->logger->logErrorMessage('[server] Blacklist not supported: ' . $blacklist);
				return $defaultAction;
				break;
		}

	}


	// Create the socket
	private function createSocket() {
		$this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if( $this->server === false ) {
			throw new SocketException( 
				SocketException::CANT_CREATE_SOCKET, socket_strerror( socket_last_error() )
			);
		}
		socket_set_option( $this->server, SOL_SOCKET, SO_REUSEADDR, 1);
		$this->logger->logDebugMessage('[server] Socket created, ready for binding');
	}

	// Bind the socket
	private function bindSocket() {
		if ( socket_bind( $this->server, $this->address, $this->port ) === false ) {
			throw new SocketException( 
				SocketException::CANT_BIND, socket_strerror(socket_last_error( $this->server ) ) 
			);
		}
		$this->logger->logDebugMessage('[server] Socket binded to ' . $this->address . ':' . $this->port);
	}

}

?>