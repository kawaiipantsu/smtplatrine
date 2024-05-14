<?PHP
namespace Socket;

class connectionHandler {
    
    protected $socket = false;
    protected $pid;
    protected $run;

    private $logger;
    private $db;
    private $smtp;
    private $config = false;
    private $srvPort = false;
    private $srvAddress = false;

    private $otherProcesses = array();
    private $maxClients = 0;
    
    // Constructor
    public function __construct( $socket, $otherProcesses ) {

        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Load up Database functionality
        $this->db = new \Controller\Database;

        // Load up SMTP Honeypot functionaility
        $this->smtp = new \Controller\SMTPHoneypot;

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

            // If we get here that means a client connected
            // So let's insert the client into the database
            $this->db->insertClientIP($this->socket->getPeerAddress());

            // Handle connection socket data
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
        // Get the user info, running this script
        $handleUserInfo = posix_getpwuid(posix_geteuid());

        $client = $this->socket;
        $read = '';

        $this->logger->logMessage('[client] Client connected '.$client->getPeerAddress().':'.$client->getPeerPort().'->'.$client->getAddress().':'.$client->getPort());

        // If we have more than 2 processes, close connection with log message
        if ( count($this->otherProcesses) >= $this->maxClients ) {
            $client->send($this->smtp->sendBanner());
            $client->send($this->smtp->closeConnectionToManyConnections());
            $this->logger->logErrorMessage('[server] Too many connections, closing connection');
            $client->close();
            $this->logger->logMessage("[".$client->getPeerAddress()."] Disconnected");
            return false;
        }



        // If db return false, close connection with log message
        if ( $this->db->isDBready() === false) {
            $client->send($this->smtp->sendBanner());
            $client->send($this->smtp->closeConnection());
            $this->logger->logErrorMessage('['.$client->getPeerAddress().'] Database backend not found, closing connection');
            $client->close();
            $this->logger->logMessage("[".$client->getPeerAddress()."] Disconnected");
            return false;
        }
        
        // Send SMTP Banner
        $client->send($this->smtp->sendBanner());

        while( true ) {
            // Read buffer from client
            // As we want to make it more reliable, we change the buffer read size depending on the SMTP transaction
            // Commands = 1024 bytes
            // Data = 4096 bytes
            if ( $this->smtp->getSMTPDATAmode() ) {
                $read = $client->read(4096);
            } else {
                $read = $client->read(); // Default buffer size is 1024 bytes
            }

            // Check if buffer is empty else parse data
            //if ( empty($read) === false ) {
            if ( $read != '' ) {
                
                // THIS IS THE MAIN LOOP TO PROESS INCOMING DATA
                // As this is a SMTP Honeypot, we shift to a mixture of SMTP and DATA mode
                // Also remember the SMTP protocol, even if we are "done" and recieve a mail
                // to emulate sending, the connetion will still be alive until the client 
                // sends a QUIT command!

                // Controle - Check if we are inside SMTP DATA mode
                if ( $this->smtp->getSMTPDATAmode() ) {

                    // Since this should be DATA, parse it as such!
                    // As long as we are in DATA mode, we will keep parsing the data by returning "false"
                    $dataStatus = $this->smtp->parseData($read);

                    // We recieved output from the DATA parser, meaning we are either done or have an error
                    if ( $dataStatus ) {

                        // Send the reply back to the client connected
                        $client->send($dataStatus);

                        // Detect if the holy grail happened - That someone actually tried to relay mail and we got a 250 OK
                        // This is a honeypot gratest moment, any security hunter would be proud of this moment :D
                        if ( preg_match('/^250 Ok: queued as /i',$dataStatus) ) {

                            // Log that things went successful
                            $this->logger->logMessage("[".$client->getPeerAddress()."] Successfully queued mail for relaying");

                            // Get the complete/finished email in a raw known EML data format
                            // EML = Electronic Mail Message
                            $mailData = $this->smtp->getEmailEML();

                            // It's happend !
                            // Now let's parse the data and store it in the database for further analysis
                            // Time to reep the rewards of our honeypot!
                            $result = $this->handleResult($client, $mailData);

                            // HONEYPOT SESSION IS ACTUALLY OVER AT THIS POINT!
                            // But we are still technically waiting for the client to send a QUIT command

                        }

                    }
                } else {

                    // Since we are NOT in the DATA mode, we are in regular SMTP command mode
                    // So let's parse what ever the client connected inputs and simulate SMTP commands responses
                    // The command line interpeter will always return a reply to the client!
                    $response = $this->smtp->parseCommand($read);

                    // Based on what the client sent, we can now send a reply back to the client
                    if ( $response ) {

                        // Little trick to handle multiple responses, ie. if the SMTP protocol
                        // reuiqres us to send multiple consecutive responses
                        if ( is_array($response) ) {

                            // Send multiple responses to client
                            foreach( $response as $r ) {
                                $client->send($r);
                            }

                        } else {

                            // Send single reponse to client
                            $client->send($response);
                        
                        }

                        // Handle SMTP QUIT command
                        // We have this very nice public variable to check what ever the last
                        // SMTP command we processed was, so we can act on it
                        if ( $this->smtp->smtpLastCommand == 'QUIT' ) {
                            // Log what is going to happen
                            $this->logger->logMessage("[".$client->getPeerAddress()."] Client sent QUIT command, closing connection");

                            // Handle end of connection
                            $this->handleEnd($client);

                            // End the connectionHandler handle() function
                            $this->logger->logMessage("[".$client->getPeerAddress()."] Closed connection (QUIT)");
                            return false;
                        }

                        // Handle SMTP command that break rules
                        // Be aware that response can be an array, so we need to check for that
                        if ( !is_array($response) && preg_match('/^503 Error: I can break rules, too/i',$response) ) {
                                // Log what is going to happen
                                $this->logger->logMessage("[".$client->getPeerAddress()."] Client sent a UNWANTED command at this time, closing connection", 'WARNING');

                                // Handle end of connection
                                $this->handleEnd($client);
    
                                // End the connectionHandler handle() function
                                $this->logger->logMessage("[".$client->getPeerAddress()."] Closed connection (FORCED CLOSE)");
                                return false;
                        }

                    }
                }
            } else {
                break;
            }
            
            // Check if read is null
            if ( $read === null ) {
                $this->logger->logMessage("[".$client->getPeerAddress()."] Disconnected");
                return false;
            }
        }

        // Close connection
        $client->close();

        // Log disconnect
        $this->logger->logMessage("[".$client->getPeerAddress()."] Lost connection");
    }

    // Private function to handle end of connection
    private function handleEnd( $client, $data=false) {
        
        // This could be done via the handle() function, but we want to keep it clean
        // so anything else needed to gracefully end a client connection is done here
        if ( $data ) $client->send( $data );
        $client->close();
                            
    }

    // Private function to handle data end result
    private function handleResult( $client, $data=false ) {
        
        if ( $data === false ) return false;
        else {

            // Replace placeholders that need to be updated with client connection details
            $data = str_replace('%%CLIENTIP%%',$client->getPeerAddress(),$data);
            $data = str_replace('%%CLIENTIPREVERSE%%',gethostbyaddr($client->getPeerAddress()),$data);
            $data = str_replace('%%CLIENTPORT%%',$client->getPeerPort(),$data);

            // Prepare our Email parser for EML data
            // This will parse the email and make it ready for storage in the database
            // This will also handle attachments (blob) data directly and store to disk if needed
            // The parsed result will only ever contain the actual file names for the attachments
            // To actually save the attachments, please opt-in to save the actual data in server.ini:
            // smtp_attachments_store = true

            $parser = new \Controller\EmailParser($data,"eml");
            $result = $parser->getParsedResult();

            // We are all set !!
            // Now we can store the data in the database
            if ($result) {

                // Rebuild smtp_attachments to be a json array from the array it contains
                $attachments_uuids = array();

                // Prepare attachments array
                foreach ( $result['attachments'] as $attachment ) {
                    $attachments_uuids[] = $attachment['uuid'];
                }

                $this->logger->logDebugMessage('['.$client->getPeerAddress().'] Storing email data in database');
                
                // Special switch case to catch multiways of useragents key name in headers
                $useragent = '';
                if ( array_key_exists('user-agent',$result['headers']) ) {
                    $useragent = $result['headers']['user-agent'];
                } else if ( array_key_exists('x-user-agent',$result['headers']) ) {
                    $useragent = $result['headers']['x-user-agent'];
                } else if ( array_key_exists('useragent',$result['headers']) ) {
                    $useragent = $result['headers']['useragent'];
                } else if ( array_key_exists('x-useragent',$result['headers']) ) {
                    $useragent = $result['headers']['x-useragent'];
                }

                // Prepare the fields array
                $fields = array(
                    'emails_client_ip'                        => array_key_exists('x-latrine-client-ip',$result['headers']) ? $result['headers']['x-latrine-client-ip'] : $client->getPeerAddress(),
                    'emails_client_port'                      => array_key_exists('x-latrine-client-port',$result['headers']) ? $result['headers']['x-latrine-client-port'] : $client->getPeerPort(),
                    'emails_server_port'                      => array_key_exists('x-latrine-server-port',$result['headers']) ? $result['headers']['x-latrine-server-port'] : $client->getPort(),
                    'emails_server_hostname'                  => array_key_exists('x-latrine-server-hostname',$result['headers']) ? $result['headers']['x-latrine-server-hostname'] : gethostname(),
                    'emails_server_listning'                  => array_key_exists('x-latrine-server-listen',$result['headers']) ? $result['headers']['x-latrine-server-listen'] : $client->getPeerAddress(),
                    'emails_server_system'                    => array_key_exists('x-latrine-server-system',$result['headers']) ? $result['headers']['x-latrine-server-system'] : php_uname(),
                    'emails_queue_id'                         => array_key_exists('x-latrine-queue-id',$result['headers']) ? $result['headers']['x-latrine-queue-id'] : '',
                    'emails_header_to'                        => array_key_exists('to',$result['headers']) ? $result['headers']['to'] : '',
                    'emails_header_from'                      => array_key_exists('from',$result['headers']) ? $result['headers']['from'] : '',
                    'emails_header_cc'                        => array_key_exists('cc',$result['headers']) ? $result['headers']['cc'] : '',
                    'emails_header_reply_to'                  => array_key_exists('reply-to',$result['headers']) ? $result['headers']['reply-to'] : '',
                    'emails_header_organization'              => array_key_exists('organization',$result['headers']) ? $result['headers']['organization'] : '',
                    'emails_header_subject'                   => array_key_exists('subject',$result['headers']) ? $result['headers']['subject'] : '',
                    'emails_header_message_id'                => array_key_exists('message-id',$result['headers']) ? $result['headers']['message-id'] : '',
                    'emails_header_xmailer'                   => array_key_exists('x-mailer',$result['headers']) ? $result['headers']['x-mailer'] : '',
                    'emails_header_useragent'                 => $useragent,
                    'emails_header_content_type'              => array_key_exists('content-type',$result['headers']) ? $result['headers']['content-type'] : '',
                    'emails_header_content_transfer_encoding' => array_key_exists('content-transfer-encoding',$result['headers']) ? $result['headers']['content-transfer-encoding'] : '',

                    'emails_body_text' => empty($result['body']['text']) ? '' : $result['body']['text'],
                    'emails_body_html' => empty($result['body']['html']) ? '' : $result['body']['html'],

                    'emails_recipients' => $result['headers']['delivered-to'],                  // This will be handled inside storeEmail function
                    'emails_header_received' => json_encode($result['headers']['received']),    // This can just be sent directly as JSON
                    'emails_attachments' => $attachments_uuids                                  // This will be handled inside insertEMAIL function
                );

                $emailID = $this->db->insertEMAIL($fields);
                
                // Now handle the attachments
                foreach ( $result['attachments'] as $attachment ) {
                    $this->db->insertATTACHMENT($emailID, $attachment);
                }

            } else {
                $this->logger->logErrorMessage('Something went wrong ???');
            }

            // We could return data back to the handler() function,
            // but we want to keep it clean and only handle the result here
            return true;

        }
        
    }

}