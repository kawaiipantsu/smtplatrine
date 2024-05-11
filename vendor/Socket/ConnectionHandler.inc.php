<?PHP
namespace Socket;

class connectionHandler {
    
    protected $socket = false;
    protected $pid;
    private $logger;
    private $config = false;
    
    // Constructor
    public function __construct( $socket ) {
        
        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Read config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }

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
            cli_set_process_title("smtplatrine-connectionhandler");
            $this->handle();
            exit(0);
        }
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

    // Handle connection
    public function handle() {
        $client = $this->socket;
        $read = '';
        $this->logger->logMessage('['.$client->getAddress().'] Connected at port ' . $client->getPort());

        // Load up SMTP Honeypot functionaility
        $smtp = new \Controller\SMTPHoneypot;

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