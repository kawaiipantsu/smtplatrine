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

    }

    // Destructor
    public function __destruct() {
        // Nothing to do
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

    // Socket Client Close
    public function close() {
        if ( false === @socket_shutdown( $this->connection, 2 ) ) {
            $this->logger->logMessage('[client] '.socket_strerror(socket_last_error()), 'WARNING');
        }
        socket_close( $this->connection );
    }

}