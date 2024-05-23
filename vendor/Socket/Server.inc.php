<?PHP

namespace Socket;

class Server {

	protected $server;
	protected $address;
	protected $port;
	protected $listenLoop;
	protected $connectionHandler;
	private $logger;
	private $meta;
	private $db;
	private $config = false;
	public $serverPid = false;

	private $socketRecvTimeout = 60;
	private $socketSendTimeout = 60;

	private $clientStreams = array();
	private $isStream = false;
	private $serverVars = array();
    
	private $enableEncryption = false;
	private $encryptionCertCRT = false;
	private $encryptionCertKEY = false;

	private $acl_blacklist_ip = array();
	private $acl_blacklist_geo = array();

	public $childProcesses = array();	// Array of child processes
	private $maxClients = 0;

	// Constructor
	public function __construct() {

		// Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

		// Enable Meta services if we got em
		$this->meta = new \Controller\Meta;

		// Load up Database functionality
        $this->db = new \Controller\Database;

		$this->listenLoop = false;
		if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }
		$this->address = strtolower(trim($this->config['server']['server_listen']));
		$this->port = strtolower(trim($this->config['server']['server_port']));

		// Get timeout value fro config or set default
		$this->socketRecvTimeout = array_key_exists('server_idle_timeout',$this->config['server']) ? intval($this->config['server']['server_idle_timeout']) : 60;
		$this->socketSendTimeout = array_key_exists('server_idle_timeout',$this->config['server']) ? intval($this->config['server']['server_idle_timeout']) : 60;

		$this->enableEncryption = trim($this->config['server']['server_encryption']) == "1" ? true : false;
		$this->encryptionCertCRT = array_key_exists('server_cert_crt',$this->config['server']) ? intval($this->config['server']['server_cert_crt']) : false;
		$this->encryptionCertKEY = array_key_exists('server_cert_key',$this->config['server']) ? intval($this->config['server']['server_cert_key']) : false;

		// Check if protection acl is enabled and then refresh acl lists
		if ( array_key_exists('protection',$this->config) ) {

			if (array_key_exists('protection_acl_blacklist_ip',$this->config['protection']) || array_key_exists('protection_acl_blacklist_geo',$this->config['protection']) ) {
				$this->logger->logMessage('[server] Protection ACL enabled, refreshing appropriate local cached blacklists');
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

		// Get my pid
		$this->serverPid = getmypid();

		// Since the serer class is also using the DB class i have chosen to place the creation of the "default" admin password
		// here. This will create a random admin password that you can use to get access to the web ui to look at the data.
		// The password will be outputted in the log file for you to grep for. etc.
		$randomAdminPassword = $this->db->generateRandomPassword(12);
		// Set admin password if user table is empty!
        $this->db->setDefaultAdminPassword($randomAdminPassword);


		// Get max connections allowed from protection
		$this->maxClients = array_key_Exists("protection_max_connections",$this->config['protection']) ? intval($this->config['protection']['protection_max_connections']) : 10;
		$this->logger->logMessage('[server] Max concurrent connections set to: ' . $this->maxClients);
	}

	// Destructor
	public function __destruct() {
		// If stream close it
		if ( $this->isStream ) {
			//stream_socket_shutdown($this->server, STREAM_SHUT_RDWR);
		} else {
			socket_shutdown($this->server, 2);
		}
	}

	// Load config file from etc
    private function loadConfig() {
        $config = parse_ini_file(__DIR__ . '/../../etc/server.ini',true);
        return $config;
    }

	// Reload config file from etc
    public function reloadConfig() {
        $this->config = $this->loadConfig();
    }

	// Start the server
	public function start() {
		$this->logger->logDebugMessage('[server] Creating socket on ' . $this->address . ':' . $this->port);
		//$this->createSocket();
		//$this->bindSocket();
		$this->createStreamSocket();
	}

	// Listen for connections
	public function listen() {
		// IF not resource
		if ( !is_resource($this->server) ) {
			if ( socket_listen($this->server, 5) === false) {
				throw new SocketException( 
					SocketException::CANT_LISTEN, socket_strerror(socket_last_error( $this->server ) ) 
				);
			}
		}

		$this->listenLoop = true;
		$this->beforeServerLoop();
		$this->serverLoop();

		if ( $this->isStream ) {
			//stream_socket_shutdown($this->server, STREAM_SHUT_RDWR);
			fclose($this->server);
		} else {
			socket_close($this->server);
		}
	}

	// Before server loop
	protected function beforeServerLoop() {
		$this->logger->logMessage('[server] Listening on ' . $this->address . ':' . $this->port . ' ...');
		// Let's build the server vars array so clients can hae some info about the server
		$this->serverVars = array(
			'address' => $this->address,
			'port' => $this->port,
			'pid' => $this->serverPid,
			'clients' => $this->maxClients,
			'geoip' => $this->meta->isGeoIPavailable() ? 'yes' : 'no',
			'blacklist_ip' => count($this->acl_blacklist_ip),
			'blacklist_geo' => count($this->acl_blacklist_geo),
			'isStream' => $this->isStream ? 'yes' : 'no'
		);
	}

	// Set connection handler
	public function setConnectionHandler( $handler ) {
		$this->connectionHandler = $handler;
		$this->logger->logDebugMessage("[server] Connection handler set to: " . $handler);
	}

	// Remove clientStream
	public function removeClientStream( $key ) {
		if ( array_key_exists($key,$this->clientStreams) ) {
			fclose($this->clientStreams[$key]);
			unset($this->clientStreams[$key]);
		}
	}

	// public kill children properly
	public function killChildren() {
		foreach ( $this->childProcesses as $pid => $clientStream ) {
			$this->logger->logMessage("[server] >>> Forcing connection '".$clientStream->getPeerInfo()."' to close","WARNING");
			$clientStream->killDisconnect();
			unset($this->childProcesses[$pid]);
		}
	}

	// Server loop
	protected function serverLoop() {

		// Activate non-blocking mode
		//socket_set_nonblock( $this->server );
		stream_set_blocking($this->server, 0);
		while( $this->listenLoop ) {

			// Keep an eye on idlers! If found, disconnect them
			foreach ( $this->childProcesses as $pid => $clientStream ) {
				if ( $clientStream->isIdle() ) {
					$clientStream->idleDisconnect();
					unset($this->childProcesses[$pid]);
				}
			}

			// Children clean up - Zombies, hanging, dead ... 
			foreach( $this->childProcesses as $pid => $object ) {
				$pidStatus = pcntl_waitpid( $pid, $status, WNOHANG );
				if ( $pidStatus == -1 || $pidStatus > 0 ) {
					unset( $this->childProcesses[$pid] );
				}
			}

			if ( $this->isStream === false ) $this->acceptConnection();
			else $this->acceptConnectionStream();

			// As we are in a loop and now in non-blocking mode we need to sleep
			// or we will consume all CPU! Or at least a lot of it :)

			usleep(10000);	// 10ms

		}
	}

	// Accept connection (stream)
	private function acceptConnectionStream() {
		$read = array($this->server);
		$write = null;
		$except = null;
		$changed = @stream_select($read, $write, $except, 0, 0);
		if(!$changed) {
			return false;
		}
		$handle = stream_socket_accept($this->server);
		if(!$handle) {
			return false;
		}

		stream_set_timeout( $handle, $this->socketRecvTimeout);

		$socketClient = new SocketClient( $handle );

		// Check if IP is blacklisted
		$checkIP = trim($this->config['protection']['protection_acl_blacklist_ip']) == "1" ? true : false;
		if ( $checkIP ) {
			$blocked = $this->aclCheckIfBlacklisted('ip',$socketClient->getPeerAddress());
			// If not allowed, close connection
			if ( $blocked ) {
				$this->logger->logMessage("[".$socketClient->getPeerAddress()."] Closed connection (Rejected: Blacklisted IP)");
				$socketClient->close();
				return false;
			}
		}

		// Check if Geo is blacklisted
		$checkGeo = trim($this->config['protection']['protection_acl_blacklist_geo']) == "1" ? true : false;
		if ( $checkGeo ) {
			if ( $this->meta->isGeoIPavailable() ) {
				// Get Geo code
				$geoCode = $this->meta->getGeoIPMain($socketClient->getPeerAddress());
				$countryCode = false;
				if ( is_array($geoCode) ) {
					$countryCode = array_key_exists('country',$geoCode) ? $geoCode['country']['iso_code'] : false;
				}
				if ( $countryCode && $this->aclCheckIfBlacklisted('geo',$countryCode) ) {
					$this->logger->logMessage("[".$socketClient->getPeerAddress()."] Closed connection (Rejected: Blacklisted GEO)");
					$socketClient->close();
					return false;
				}
			} else {
				// log about it
				$this->logger->logErrorMessage('[server] GeoIP not available, can\'t check GEO blacklist');
			}
			
		}

		if ( is_array( $this->connectionHandler ) ) {
			$object = $this->connectionHandler[0];
			$method = $this->connectionHandler[1];
			$object->$method( $socketClient );
		} else {
			$function = $this->connectionHandler;
			$spawnClient = new $function( $socketClient, $this->childProcesses );
			$this->childProcesses[$spawnClient->getPID()] = $spawnClient;

			// If max clients reached print different log message
			if ( count($this->childProcesses) > $this->maxClients ) {
				$this->logger->logMessage('[server] Max clients reached, claiming to be busy :)', 'WARNING');
			} else {
				$this->logger->logMessage('[server] Clients connected ' . count($this->childProcesses). ' of ' . $this->maxClients);
			}
			$this->logger->logMessage('[server] Spawned client on pid '.$spawnClient->getPID());
		}
    }

	// Accept connection
	private function acceptConnection() {
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
		$checkIP = trim($this->config['protection']['protection_acl_blacklist_ip']) == "1" ? true : false;
		if ( $checkIP ) {
			$blocked = $this->aclCheckIfBlacklisted('ip',$socketClient->getPeerAddress());
			// If not allowed, close connection
			if ( $blocked ) {
				$this->logger->logMessage("[".$socketClient->getPeerAddress()."] Closed connection (Rejected: Blacklisted IP)");
				$socketClient->close();
				return false;
			}
		}

		// Check if Geo is blacklisted
		$checkGeo = trim($this->config['protection']['protection_acl_blacklist_geo']) == "1" ? true : false;
		if ( $checkGeo ) {
			if ( $this->meta->isGeoIPavailable() ) {
				// Get Geo code
				$geoCode = $this->meta->getGeoIPMain($socketClient->getPeerAddress());
				$countryCode = false;
				if ( is_array($geoCode) ) {
					$countryCode = array_key_exists('country',$geoCode) ? $geoCode['country']['iso_code'] : false;
				}
				if ( $countryCode && $this->aclCheckIfBlacklisted('geo',$countryCode) ) {
					$this->logger->logMessage("[".$socketClient->getPeerAddress()."] Closed connection (Rejected: Blacklisted GEO)");
					$socketClient->close();
					return false;
				}
			} else {
				// log about it
				$this->logger->logErrorMessage('[server] GeoIP not available, can\'t check GEO blacklist');
			}
			
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

	// Get child pids
	public function getChildPIDs() {
		// PIDs are the keys
		$array = $this->childProcesses;
		return array_keys($array);
	}

	// Remove pid from childProcesses
	public function removeChildPID( $pid ) {
		if ( in_array($pid,$this->childProcesses) ) {
			$key = array_search($pid,$this->childProcesses);
			unset($this->childProcesses[$key]);
		}
	}

	// Initiate refresh of ACL public
	public function reloadACL( $blacklist = false ) {
		switch ( strtolower(trim($blacklist)) ) {
			case "ip":
				if ( array_key_exists('protection_acl_blacklist_ip',$this->config['protection']) ) {
					if ( trim($this->config['protection']['protection_acl_blacklist_ip']) == "1" ) {
						$this->refreshACL($blacklist);
					}
				}
				break;
			case "geo":
				if ( array_key_exists('protection_acl_blacklist_geo',$this->config['protection']) ) {
					if ( trim($this->config['protection']['protection_acl_blacklist_geo']) == "1" ) {
						$this->refreshACL($blacklist);
					}
				}
				break;
			case "all":
				if ( array_key_exists('protection_acl_blacklist_ip',$this->config['protection']) ) {
					if ( trim($this->config['protection']['protection_acl_blacklist_ip']) == "1" ) {
						$this->refreshACL($blacklist);
					}
				}
				if ( array_key_exists('protection_acl_blacklist_geo',$this->config['protection']) ) {
					if ( trim($this->config['protection']['protection_acl_blacklist_geo']) == "1" ) {
						$this->refreshACL($blacklist);
					}
				}
				break;
		}
		
	}

	// Refresh ACL array from DB
	private function refreshACL( $blacklist = false ) {
		if ( $blacklist ) {
			switch( $blacklist ) {
				case "ip":
					$this->acl_blacklist_ip = array();
					$this->logger->logMessage('[server] Refreshing ACL IP blacklist from database');
					$this->acl_blacklist_ip = $this->getBlacklist('ip');
					break;
				case "geo":
					$this->acl_blacklist_geo = array();
					$this->logger->logMessage('[server] Refreshing ACL Geo blacklist from database');
					$this->acl_blacklist_geo = $this->getBlacklist('geo');
					break;
				case "all":
					$this->acl_blacklist_geo = array();
					$this->acl_blacklist_ip = array();
					$this->logger->logMessage('[server] Refreshing ACL Geo blacklist from database');
					$this->logger->logMessage('[server] Refreshing ACL IP blacklist from database');
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
	// For now we use the quick and dirty way to get the blacklist by running SQL queries directly against the database
	// But in the long term we should use prepared statements and a more secure way to get the data
	private function getBlacklist( $blacklist = "ip" ) {
		$blacklistArray = array();
		
		switch( $blacklist ) {
			case "ip":
				$query = "SELECT ip_addr FROM acl_blacklist_ip";
				$result = $this->db->dbMysqlRawQuery($query, true, false); // Query, No return sql resource, No logging
				if ( $result && mysqli_num_rows($result) > 0 ) {
					while( $row = mysqli_fetch_assoc($result) ) {
						$blacklistArray[] = $row['ip_addr'];
					}
				}
				$this->logger->logMessage('[server] ACL Loaded ' . count($blacklistArray) . ' IP addresses to blacklist', 'NOTICE');
				break;
			case "geo":
				$query = "SELECT geo_code FROM acl_blacklist_geo";
				$result = $this->db->dbMysqlRawQuery($query, true, false); // Query, No return sql resource, No logging
				if ( $result && mysqli_num_rows($result) > 0 ) {
					while( $row = mysqli_fetch_assoc($result) ) {
						$blacklistArray[] = strtoupper(trim($row['geo_code']));
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
				if ( filter_var($value, FILTER_VALIDATE_IP) ) {
					if ( in_array($value,$this->acl_blacklist_ip) ) {
						$this->logger->logMessage('[server] Blacklisted IP tried to connect: '.$value.' (Protection: enabled)', 'NOTICE');
						$query = "SELECT geo_code FROM acl_blacklist_geo";
						$this->db->blacklistUpdateEntry('ip',$value);
						return true;
					}
				}
				break;
			case "geo":
				if ( strlen($value) == 2 ) {
					$value = strtoupper($value);
					if ( in_array($value,$this->acl_blacklist_geo) ) {
						$this->logger->logMessage('[server] Blacklisted Country tried to connect: '.$value.' (Protection: enabled)', 'NOTICE');
						$this->db->blacklistUpdateEntry('geo',$value);
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
	
	// Create a stream socket
	private function createStreamSocket() {
		//$context = stream_context_create();
		$opts = array(
			'socket' => array(
				'backlog' => 5,
				'so_reuseport' => true,
			),
			'ssl' => array(
				'local_cert' => "/var/www/projects/smtplatrine/etc/certs/smtp.srv25.barebone.com.pem",
				'local_pk' => "/var/www/projects/smtplatrine/etc/certs/smtp.srv25.barebone.com.key",
				'disable_compression' => true,
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);
		

		$socket = stream_socket_server('tcp://'.$this->address.':'.$this->port, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
		if ( !$socket ) {
			throw new SocketException( 
				SocketException::CANT_CREATE_SOCKET, $errstr
			);
		}

		stream_context_set_option($socket, $opts);

		stream_set_timeout( $socket, $this->socketRecvTimeout);

		$this->server = $socket;
		$this->isStream = true;
		$this->logger->logDebugMessage('[server] Stream socket created, ready for binding');
	}

	// Create the socket
	private function createSocket() {
		$this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if( $this->server === false ) {
			throw new SocketException( 
				SocketException::CANT_CREATE_SOCKET, socket_strerror( socket_last_error() )
			);
		}

		// Now we setup any options and special things this socket should have
		// Set socket timeouts
		socket_set_option( $this->server, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$this->socketRecvTimeout, 'usec'=>0) );
		socket_set_option( $this->server, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>$this->socketSendTimeout, 'usec'=>0) );

		// Set socket reuse options
		socket_set_option( $this->server, SOL_SOCKET, SO_REUSEADDR, 1);

		$this->logger->logDebugMessage('[server] Socket created, ready for binding');
	}

	// Create SSL/TLS Cert
	public function createSSLCert() {

		// Get SMTP domain name from config or set default
		$domainName = array_key_exists('smtp_domain',$this->config['smtp']) ? $this->config['smtp']['smtp_domain'] : 'smtp.srv25.barebone.com';

		// Check if the certificate already exists
		$certFile = __DIR__ . '/../../etc/certs/' . $domainName . '.pem';
		if ( !file_exists($certFile) ) {

			$dn = array(
				"countryName" => "US",
				"stateOrProvinceName" => "Some State",
				"localityName" => "Some City",
				"organizationName" => "SMTPLatrine Inc.",
				"organizationalUnitName" => "SOC Team",
				"commonName" => $domainName
			);

			$privKey = openssl_pkey_new(array(
				"private_key_bits" => 2048,
				"private_key_type" => OPENSSL_KEYTYPE_RSA,
			));
			
			// Generate a certificate signing request
			$csr = openssl_csr_new($dn, $privKey, array('digest_alg' => 'sha256'));

			// Generate a self-signed cert, valid for 365 days
			$x509 = openssl_csr_sign($csr, null, $privKey, $days=365, array('digest_alg' => 'sha256'));

			// PEM file format
			$pem = array();
			$csrout = '';

			// Save your private key, CSR and self-signed cert for later use
			openssl_csr_export($csr, $csrout);
			openssl_x509_export($x509, $pem[0]);
			openssl_pkey_export($privKey, $pem[1]);
			$pemChain = implode($pem);

			// Save alle certificates individually under etc/certs/
			$pem_file = __DIR__ . '/../../etc/certs/' . $domainName . '.pem';
			$csr_file = __DIR__ . '/../../etc/certs/' . $domainName . '.csr';
			$key_file = __DIR__ . '/../../etc/certs/' . $domainName . '.key';
			$crt_file = __DIR__ . '/../../etc/certs/' . $domainName . '.crt';

			// Save PEM file
			file_put_contents($pem_file, $pemChain);
			chmod($pem_file, 0600);

			// Save CSR file
			//file_put_contents($csr_file, $csrout);
			//chmod($csr_file, 0600);

			// Save KEY file
			file_put_contents($key_file, $pem[1]);
			chmod($key_file, 0600);

			// Save CRT file
			//file_put_contents($crt_file, $pem[0]);
			//chmod($crt_file, 0600);

			// Log about it
			$this->logger->logMessage('[server] Created SSL/TLS certificate for domain: ' . $domainName,'NOTICE');
		}
	}

	// Bind the socket
	private function bindSocket() {
		if ( socket_bind( $this->server, $this->address, $this->port ) === false ) {
			throw new SocketException( 
				SocketException::CANT_BIND, socket_strerror(socket_last_error( $this->server ) ) 
			);
		}
		$this->logger->logDebugMessage('[server] Socket bound to ' . $this->address . ':' . $this->port);
	}

}

?>