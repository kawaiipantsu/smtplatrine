<?PHP

namespace Socket;

class SocketClient {

    private $connection;
    private $address;
    private $port;
    private $clientStreamKey = false;
    private $peeraddress;
    private $peerport;
    private $logger;
    private $config = false;
    private $encryptionEnabled = false;
    private $encryptionMethod = STREAM_CRYPTO_METHOD_TLS_SERVER;
    private $socketRecvTimeout = 60;
    private $socketSendTimeout = 60;
    private $enableEncryption = false;
	private $encryptionCertPEM = false;
	private $encryptionCertKEY = false;
    private $isStream = false;
    private $isEncrypted = false;
    private $encryptionMeta = false;
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

        if ( is_resource($connection) ) {
            $this->isStream = true;
        }

        $address = ''; 
        $port = '';
        $peeraddress = '169.254.1.1'; 
        $peerport = '0';

        if ( $this->isStream === true ) {
            $srvInfo = @stream_socket_get_name($connection, false);
            $srvInfoArray = explode(':',$srvInfo);
            $address = $srvInfoArray[0];
            $port = $srvInfoArray[1];

            $peerInfo = @stream_socket_get_name($connection, true);
            if ( $peerInfo === false ) {
                $this->logger->logErrorMessage('[client] Could not get peer info: '.socket_strerror(socket_last_error()));
            } else {
                $this->clientStreamKey = $peerInfo;
                $peerInfoArray = explode(':',$peerInfo);
                $peeraddress = $peerInfoArray[0];
                $peerport = $peerInfoArray[1];
            }
        } else {
            $_status = @socket_getsockname($connection, $address, $port);
            if ( $_status === false ) {
                $this->logger->logErrorMessage('[client] Could not get socket info: '.socket_strerror(socket_last_error()));
            }
            $_status = @socket_getpeername($connection, $peeraddress, $peerport);
            if ( $_status === false ) {
                $this->logger->logErrorMessage('[client] Could not get peer info: '.socket_strerror(socket_last_error()));
            }
        }

        $this->address = $address;
        $this->port = $port;
        $this->peeraddress = $peeraddress;
        $this->peerport = $peerport;
        
        $this->connection = $connection;

        $this->enableEncryption = trim($this->config['server']['server_encryption']) == "1" ? true : false;
		$this->encryptionCertPEM = array_key_exists('server_cert_pem',$this->config['server']) ? trim($this->config['server']['server_cert_pem']) : false;
		$this->encryptionCertKEY = array_key_exists('server_cert_key',$this->config['server']) ? trim($this->config['server']['server_cert_key']) : false;

        // Get timeout value fro config or set default
		$this->socketRecvTimeout = array_key_exists('server_idle_timeout',$this->config['server']) ? intval($this->config['server']['server_idle_timeout']) : 60;
		$this->socketSendTimeout = array_key_exists('server_idle_timeout',$this->config['server']) ? intval($this->config['server']['server_idle_timeout']) : 60;

        $this->refreshIdleTimer();

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
        if ( $this->isStream === true ) {
            // Check if isEncrypted then use fwrite instead
            if ( $this->isEncrypted === true ) {
                if ( false === @fwrite( $this->connection, $message ) ) {
                    $this->logger->logMessage('[client] '.socket_strerror(socket_last_error()), 'WARNING');
                }
            } else {
                if ( false === @stream_socket_sendto( $this->connection, $message ) ) {
                    $this->logger->logMessage('[client] '.socket_strerror(socket_last_error()), 'WARNING');
                }
            }
            $this->refreshIdleTimer(); // We sent data so we cant expect the client to be idle just yet
        } else {
            if ( false === @socket_write($this->connection, $message, strlen($message)) ) {
                $this->logger->logMessage('[client] '.socket_strerror(socket_last_error()), 'WARNING');
            }
            $this->refreshIdleTimer(); // We sent data so we cant expect the client to be idle just yet
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

        if ( $this->isStream === true ) {
           // heck if isEncrypted then use fread instead
            if ( $this->isEncrypted === true ) {
                $buf = @fread( $this->connection, $len );
            } else {
                if ( ( $buf = @stream_socket_recvfrom( $this->connection, $len, 0 ) ) === false ) {
                    return null;
                }
            }
        } else {
            // Set buffer size
            if ( $overrideBufferSize === false ) {
                $len = $this->defaultBufferSize;
            } else {
                $len = intval($overrideBufferSize);
            }
            if ( ( $buf = @socket_read( $this->connection, $len, PHP_BINARY_READ  ) ) === false ) {
                return null;
            }
        }
        

        $this->refreshIdleTimer();

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

        // Log debug about loading keys
        $this->logger->logMessage('[client] TLS: Loading PEM file: '.__DIR__ . '/../../etc/certs/' . $this->encryptionCertPEM, 'DEBUG');
        $this->logger->logMessage('[client] TLS: Loading KEY file: '.__DIR__ . '/../../etc/certs/' . $this->encryptionCertKEY, 'DEBUG');

        $opts = array(
			'socket' => array(
				'backlog' => 5,
				'so_reuseport' => true,
			),
			'ssl' => array(
				'local_cert' => __DIR__ . '/../../etc/certs/' . $this->encryptionCertPEM,
				'local_pk' => __DIR__ . '/../../etc/certs/' . $this->encryptionCertKEY,
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);
        stream_context_set_option($this->connection, $opts);
        $status = stream_socket_enable_crypto($this->connection, true, $this->encryptionMethod);
        if ( $status === false ) {
            $this->logger->logMessage('[client] '.socket_strerror(socket_last_error()), 'WARNING');
        } else {
            $this->logger->logMessage('[client] Encryption enabled', 'DEBUG');
            $this->isEncrypted = true;
            $_array = stream_get_meta_data($this->connection);
            $this->encryptionMeta = $_array['crypto'];
        }
    }

    public function disableEncryption() {
        stream_socket_enable_crypto($this->connection, false, $this->encryptionMethod);
    }

    // GEt if encrypted
    public function isEncrypted() {
        return $this->isEncrypted;
    }

    // Public get crypto meta
    public function getEncryptionMeta() {
        return $this->encryptionMeta;
    }

    // Socket Client Close
    public function close() {
        // IF resource is a stream
        if ( $this->isStream === true ) {
            if ( false === @stream_socket_shutdown( $this->connection, 2 ) ) {
                $this->logger->logMessage('[client] '.socket_strerror(socket_last_error()), 'WARNING');
            }
            fclose( $this->connection );
        } else {
            if ( false === @socket_shutdown( $this->connection, 2 ) ) {
                $this->logger->logMessage('[client] '.socket_strerror(socket_last_error()), 'WARNING');
            }
            socket_close( $this->connection );
        }
    }

}