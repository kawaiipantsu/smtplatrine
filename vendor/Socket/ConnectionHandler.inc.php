<?PHP
namespace Socket;

class connectionHandler {
    
    protected $socket = false;
    protected $pid;
    protected $run;

    private $logger;
    private $config = false;
    private $srvPort = false;
    private $srvAddress = false;

    private $otherProcesses = array();
    private $maxClients = 0;
    
    // Constructor
    public function __construct( $socket, $otherProcesses ) {

        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Read config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }

        // Get max connections allowed from protection
		$this->maxClients = array_key_Exists("protection_max_connections",$this->config['protection']) ? intval($this->config['protection']['protection_max_connections']) : 10;

        // Set socket
        $this->socket = $socket;

        // Get PID by forking
        $pid = pcntl_fork();
        if ( $pid == -1 ) {
            $this->logger->logErrorMessage('Could not fork');
        } else if ( $pid ) {
            // Parent process
            $this->pid = $pid;
            
            
            
        } else {
            // Child process
            
            // Check if we need to use non-previleged UID/GID
            $non_privileged = trim($this->config['server']['server_spawn_clients_as_non_privileged']) == "1" ? true : false;
            if ( $non_privileged ) {
                $_uid = posix_getpwnam($this->config['non_privileged']['non_privileged_user']);
                $_gid = posix_getgrnam($this->config['non_privileged']['non_privileged_group']);
                if ( $_uid && $_gid ) {
                    posix_setgid($_gid['gid']);
                    posix_setuid($_uid['uid']);
                    $this->logger->logMessage('[client] Non-privileged mode (UID:'.$_uid['uid'].' GID:'.$_gid['gid'].')','INFO');
                } else {
                    $this->logger->logMessage('Could not set UID/GID for non-privileged user/group for connecting clients','ERROR');
                    $this->logger->logMessage('Please note that we are still running but as root!','WARNING');
                }
            }

            $this->otherProcesses = $otherProcesses;
            cli_set_process_title("smtplatrine-connectionhandler");
            $this->handle();
            exit(0);
        }

        // We can use these for logs
        $this->srvAddress = strtolower(trim($this->config['server']['server_listen']));
		$this->srvPort = strtolower(trim($this->config['server']['server_port']));

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

    // Get PID of child process
    public function getPID() {
        return $this->pid;
    }

    // Handle connection
    public function handle() {
        $client = $this->socket;
        $read = '';

        $this->logger->logMessage('[client] Client connected '.$client->getPeerAddress().':'.$client->getPeerPort().'->'.$client->getAddress().':'.$client->getPort());

        // Load up Database functionality
        $db = new \Controller\Database;

        // Load up SMTP Honeypot functionaility
        $smtp = new \Controller\SMTPHoneypot;

        // If we have more than 2 processes, close connection with log message
        if ( count($this->otherProcesses) >= $this->maxClients ) {
            $client->send($smtp->sendBanner());
            $client->send($smtp->closeConnectionToManyConnections());
            $this->logger->logErrorMessage('[server] Too many connections, closing connection');
            $client->close();
            $this->logger->logMessage("[".$client->getPeerAddress()."] Disconnected");
            return false;
        }



        // If db return false, close connection with log message
        if ( !$db->isConfigLoaded() ) {
            $client->send($smtp->sendBanner());
            $client->send($smtp->closeConnection());
            $this->logger->logErrorMessage('['.$client->getPeerAddress().'] Database connection failed, closing connection');
            $client->close();
            $this->logger->logMessage("[".$client->getPeerAddress()."] Disconnected");
            return false;
        }
        
        // Send SMTP Banner
        $client->send($smtp->sendBanner());

        while( true ) {
            // Read buffer
            $read = $client->read();

            // Check if buffer is empty else parse data
            if ( $read != '' ) {
                // SMTP DATA mode
                if ( $smtp->getSMTPDATAmode() ) {
                    $dataStatus = $smtp->parseData($read);
                    if ( $dataStatus ) {
                        $client->send($dataStatus);
                        // Detect if mail was "simulated" and close connection
                        if ( preg_match('/^250 Ok: queued as /i',$dataStatus) ) {
                            $this->logger->logMessage("[".$client->getAddress()."] Successfully queued mail for relaying");
                            $eml = trim($smtp->getEmailEML());
                            $eml = str_replace('%%CLIENTIP%%',$client->getAddress(),$eml);
                            $eml = str_replace('%%CLIENTIPREVERSE%%',gethostbyaddr($client->getAddress()),$eml);
                            $eml = str_replace('%%CLIENTPORT%%',$client->getPeerPort(),$eml);
                            $eml .= "\n\r";
                            // Send raw email to EmailParser
                            $email = new \Controller\EmailParser($eml);
                            print_r($email->getMailDetails());
                        }
                    }
                } else {
                    $response = $smtp->parseCommand($read);
                    if ( $response ) {
                        if ( is_array($response) ) {
                            foreach( $response as $r ) {
                                $client->send($r);
                            }
                        } else {
                            $client->send($response);
                        }

                        // Handle SMTP QUIT command
                        if ( $smtp->smtpLastCommand == 'QUIT' ) {
                            $client->close();
                            $this->logger->logMessage("[".$client->getAddress()."] Closed connection (QUIT)");
                            return false;
                        }

                    }
                }
            } else {
                break;
            }
            
            // Check if read is null
            if ( $read === null ) {
                $this->logger->logMessage("[".$client->getAddress()."] Disconnected");
                return false;
            }
        }

        // Close connection
        $client->close();

        // Log disconnect
        $this->logger->logMessage("[".$client->getAddress()."] Lost connection");
    }
}