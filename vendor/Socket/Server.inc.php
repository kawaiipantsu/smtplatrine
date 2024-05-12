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