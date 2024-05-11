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
		while( $this->listenLoop ) {
			if ( ( $client = @socket_accept( $this->server ) ) === false ) {
				throw new SocketException (
					SocketException::CANT_ACCEPT, socket_strerror(socket_last_error( $this->sockServer ) ) 
				);
				continue;
			}

			$socketClient = new SocketClient( $client );

			if ( is_array( $this->connectionHandler ) ) {
				$object = $this->connectionHandler[0];
				$method = $this->connectionHandler[1];
				$object->$method( $socketClient );
			} else {
				$function = $this->connectionHandler;
				new $function( $socketClient );
			}
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