<?PHP

namespace Socket;

class SocketClient {

    private $connection;
    private $address;
    private $port;
    private $peeraddress;
    private $peerport;
    private $logger;
    private $config = false;
    private $encryptionEnabled = false;
    private $encryptionMethod = STREAM_CRYPTO_METHOD_ANY_SERVER;
    private $socketRecvTimeout = 60;
    private $socketSendTimeout = 60;
    private $clientIdleTimer = 0;
    private $clientIdle = false;
    private $defaultBufferSize = 1024;

    // Constructor
    public function __construct( $connection ) {

        // Setup vendor logger
        // (We dont actually need the logger in here ... but we will enable it anyway)
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Read config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }

        $address = ''; 
        $port = '';
        socket_getsockname($connection, $address, $port);
        $this->address = $address;
        $this->port = $port;

        $peeraddress = ''; 
        $peerport = '';
        socket_getpeername($connection, $peeraddress, $peerport);
        $this->peeraddress = $peeraddress;
        $this->peerport = $peerport;
        
        $this->connection = $connection;

        // Get timeout value fro config or set default
		$this->socketRecvTimeout = array_key_exists('server_idle_timeout',$this->config['server']) ? intval($this->config['server']['server_idle_timeout']) : 60;
		$this->socketSendTimeout = array_key_exists('server_idle_timeout',$this->config['server']) ? intval($this->config['server']['server_idle_timeout']) : 60;

    }

    // Destructor
    public function __destruct() {
        // Nothing to do
    }

    // Refresh idletimer with unix timestamp
    private function refreshIdleTimer() {
        $this->clientIdleTimer = time();
        $this->clientIdle = false;
    }

    // Return timeouts
    public function getTimeouts() {
        return array('recv' => $this->socketRecvTimeout, 'send' => $this->socketSendTimeout);
    }

    // Tell how long since last idletimer was refreshed
    public function getIdleTime() {
        return time() - $this->clientIdleTimer;
    }

    // Return if client is idle
    public function isIdle() {
        if ( time() - $this->clientIdleTimer > $this->socketRecvTimeout-1 ) {
            $this->clientIdle = true;
        } else {
            $this->clientIdle = false;
        }

        return $this->clientIdle;
    }

    // Load config file from etc
    private function loadConfig() {
        $config = parse_ini_file(__DIR__ . '/../../etc/server.ini',true);
        return $config;
    }

    // Socket Client Send data
    public function send( $message ) {
        if ( false === @socket_write($this->connection, $message, strlen($message)) ) {
            $this->logger->logMessage('[client] '.socket_strerror(socket_last_error()), 'WARNING');
        }
        $this->refreshIdleTimer(); // We sent data so we cant expect the client to be idle just yet
    }

    // Socket Client Read data
    public function read( $overrideBufferSize = false ) {

        // Set buffer size
        if ( $overrideBufferSize === false ) {
            $len = $this->defaultBufferSize;
        } else {
            $len = intval($overrideBufferSize);
        }

        if ( ( $buf = @socket_read( $this->connection, $len, PHP_BINARY_READ  ) ) === false ) {
            return null;
        }

        $this->refreshIdleTimer(); // We received data so we are not idle

        return $buf;
    }

    // Socket Client Get Address
    public function getAddress() {
        return $this->address;
    }

    // Socket Client Get Port
    public function getPort() {
        return $this->port;
    }

    // Socket Client Get Peer Address
    public function getPeerAddress() {
        return $this->peeraddress;
    }

    // Socket Client Get Peer Port
    public function getPeerPort() {
        return $this->peerport;
    }

    // Enable encyption
    // WARNING - DOES NOT WORK!!!!
    public function enableEncryption() {

        //$stream = socket_export_stream($this->connection);
        //$context = socket_export_stream($this->connection, true);
        stream_context_set_option($this->connection, [
            "ssl" => [
                "local_cert" => __DIR__."/../../contrib/test.pem",
                "allow_self_signed" => true,
                "verify_peer" => false,
                "verify_peer_name" => false,
                "verify_host" => false
            ]

        ]);
        stream_socket_enable_crypto($this->connection, true, $this->encryptionMethod);

    }

    // Socket Client Close
    public function close() {
        if ( false === @socket_shutdown( $this->connection, 2 ) ) {
            $this->logger->logMessage('[client] '.socket_strerror(socket_last_error()), 'WARNING');
        }
        socket_close( $this->connection );
    }

}