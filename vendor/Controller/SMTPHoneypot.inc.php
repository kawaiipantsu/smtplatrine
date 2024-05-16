<?PHP

namespace Controller;

class SMTPHoneypot {
        
    private $logger;
    private $config = false;
    private $smtpAcceptedCommands = array(
        'HELO',
        'EHLO',
        'AUTH',
        'MAIL FROM',
        'MAIL',
        'RCPT',
        'RCPT TO',
        'DATA',
        'RSET',
        'NOOP',
        'QUIT',
        'VRFY',
        'EXPN',
        'GET'
    );
    private $smtpCommands = array();
    private $smtpCommandsSequence = array();
    public $smtpLastCommand = false;
    protected $smtpDATAmode = false;
    private $dataLastLine = false;
    private $weSecure = false;
    private $smtpDATAendHEX = '0d0a2e0d0a'; // HEX for \r\n.\r\n

    // Email components
    private $emailHELO = false;
    private $authCreds = array();
    private $emailQueueID = false;
    private $emailData = false;
    private $emailFrom = false;
    private $emailRCPT = array();

    private $emailEML = '';
    
    // Constructor
    public function __construct() {
        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Load config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }

        // Reset smtpCommands array
        $this->smtpCommands = array();
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

    // Get and Set for smtpDATAmode
    public function setSMTPDATAmode($mode) {
        $this->smtpDATAmode = $mode;
    }
    public function getSMTPDATAmode() {
        return $this->smtpDATAmode;
    }

    // Add smtp command to array
    private function addCommand($command) {
        $this->smtpCommands[] = $command;
        $this->smtpLastCommand = $command;
    }

    // Delete smtp command from array
    private function delCommand($command) {
        if (($key = array_search($command, $this->smtpCommands)) !== false) {
            unset($this->smtpCommands[$key]);
            $this->smtpCommands = array_values($this->smtpCommands);
        }
    }

    // Add smtp command to sequence array
    private function addCommandSequence($command) {
        $this->smtpCommandsSequence[] = $command;
    }

    // Delete smtp command from sequence array
    private function delCommandSequence($command) {
        if (($key = array_search($command, $this->smtpCommandsSequence)) !== false) {
            unset($this->smtpCommandsSequence[$key]);
            $this->smtpCommandsSequence = array_values($this->smtpCommandsSequence);
        }
    }

    // Add DATA to emailData
    private function addEmailData($data) {
        $this->emailData .= $data;
    }

    // Function to add custom X-header to email EML
    private function addCustomHeader($header,$value) {
        $this->emailEML .= trim($header).": ".trim($value)."\r\n";
    }

    // Create the Received email EML initial header
    private function buildReceivedHeader( $oldData = "" ) {

        // oldData will be the original mail DATA as delivered to the SMTP server (honeypot)
        // We will use this to add the original Received headers to the new email EML below our own

        // Set default values for our Received header
        $domain = array_key_exists("smtp_domain",$this->config['smtp']) ? trim($this->config['smtp']['smtp_domain']) : 'smtp.example.com';
        $banner = array_key_exists("smtp_banner",$this->config['smtp']) ? trim($this->config['smtp']['smtp_banner']) : 'SMTP Honeypot';
        $fullBanner = $domain.' '.$banner;
        $smtpType = $this->weSecure ? "ESMTP" : "SMTP";

        // First add the original Received headers
        // TODO: preg_match_all on Received headers

        // Now add our own Received header (top of the eml)
        $resv = "Received: from %%CLIENTIP%% ( %%CLIENTIP%% [%%CLIENTIPREVERSE%%])\r\n";
        $resv .= "\tby ".$domain." (Postfix) with ".$smtpType." id ".$this->emailQueueID."\r\n";
        $resv .= "\for <".$this->emailHELO.">; ".date('r')."\r\n";

        // Return the new build Received header(s)
        return $resv;
    }

    // Build rawEML from email components
    private function buildEmailEML() {

        // Just some default config values we might need
        $domain = array_key_exists("smtp_domain",$this->config['smtp']) ? trim($this->config['smtp']['smtp_domain']) : 'smtp.example.com';
        $banner = array_key_exists("smtp_banner",$this->config['smtp']) ? trim($this->config['smtp']['smtp_banner']) : 'SMTP Honeypot';
        $fullBanner = $domain.' '.$banner;
        $srvAddress = strtolower(trim($this->config['server']['server_listen']));
		$srvPort = strtolower(trim($this->config['server']['server_port']));
        $smtpType = $this->weSecure ? "ESMTP" : "SMTP";

        // First add return path
        $this->emailEML .= "Return-Path: <bounce@".$domain.">\r\n";
        // Add Delivered to
        foreach($this->emailRCPT as $rcpt) {
            $this->emailEML .= "Delivered-To: ".$rcpt."\r\n";
        }

        // SMTP Received headers
        // TODO: Check if there already is a Received header or multiple, in either case
        //       we should add ours as the last one in the chain (top of the eml)
        $this->emailEML .= $this->buildReceivedHeader($this->emailData);

        // Custom X-Latrine related headers
        $this->addCustomHeader("X-Latrine-Queue-ID",$this->emailQueueID);
        $this->addCustomHeader("X-Latrine-Client-IP","%%CLIENTIP%%");
        $this->addCustomHeader("X-Latrine-Client-Port","%%CLIENTPORT%%");
        $this->addCustomHeader("X-Latrine-Server-Hostname",gethostname());
        $this->addCustomHeader("X-Latrine-Server-Listen",$srvAddress);
        $this->addCustomHeader("X-Latrine-Server-Port",$srvPort);
        $this->addCustomHeader("X-Latrine-Server-System",php_uname());

        // Make local copy of email data, to work on without destroying the original
        $emailEML = $this->emailData;

        // TODO: Check if there already is a Return-Path header, if so note it down and remove it from the data
        // TODO: Check if there already is a Delivered-To header, if so note it down and remove it from the data

        // TODO: Check if there already is a Message-ID header, if so we should not add it again
        // Add Message-ID

        // Create the actual EML body from DATA
        $this->emailEML .= $emailEML."\r\n";
    }

    // SMTP compliant check order of command sequence
    private function checkCommandSequence() {
        $validateCommands = $this->smtpCommandsSequence;
        $realCommands = $this->smtpCommands;
        
        // Quickly just check if this is even a SMTP accepted command
        // If not, just return true and let the command parser handle it.
        if ( $this->validateCommand(end($realCommands)) === false ) {
            return true;
        }

        // Check for the commands that can always come out of order
        if ( count($realCommands) > 0 ) {
            if ( end($realCommands) == 'QUIT' || end($realCommands) == 'RSET' || end($realCommands) == 'NOOP' || end($realCommands) == 'HELP' ) {
                return true;
            }
        }

        // We only want to actually validate sequence if we have at least 1 command
        if ( count($validateCommands) > 0 ) {

            // First check that we always get HELO/EHLO first
            if ( count($validateCommands) == 1 ) {
                if ( $validateCommands[0] == 'HELO' || $validateCommands[0] == 'EHLO' ) {
                    return true;
                }
            }

            // Second check that we always get MAIL FROM
            if ( count($validateCommands) == 2 ) {
                // Next up is MAIL FROM or AUTH (if received!)
                if ( $validateCommands[1] == 'MAIL FROM' || $validateCommands[1] == 'AUTH' ) {
                    return true;
                }
            }

            // Third check that we always get RCPT TO
            if ( count($validateCommands) == 3 ) {
                if ( $validateCommands[1] == 'AUTH' && $validateCommands[2] == 'MAIL FROM' ) {
                    return true;
                } elseif ( $validateCommands[2] == 'RCPT TO' ) {
                    return true;
                }
            }

            // Fourth check that we always get DATA
            if ( count($validateCommands) == 4 ) {
                if ( $validateCommands[3] == 'DATA' || $validateCommands[3] == 'RCPT TO' ) {
                    return true;
                }
            }

            // This should be enoght, from here on out all commands should be in order
            if ( count($validateCommands) > 4 ) {
                return true;
            }

        }

        // If we get here then we have failed the sequence check
        return false;
    }

    // Handle SMTP Banner
    public function sendBanner() {
        $domain = array_key_exists("smtp_domain",$this->config['smtp']) ? trim($this->config['smtp']['smtp_domain']) : 'smtp.example.com';
        $banner = array_key_exists("smtp_banner",$this->config['smtp']) ? trim($this->config['smtp']['smtp_banner']) : 'SMTP Honeypot';
        $fullBanner = $domain.' '.$banner;
        $this->logger->logDebugMessage('[smtp] -> '.trim($fullBanner));
        return $this->reply(false,220);
    }

    // Get features after EHLO/HELO
    private function generateSMTPfeatures() {
        $features = $this->config['smtp_features'];
        $dataArray = array();
        $numItems = count($features);
        $i = 0;
        foreach($features as $key=>$value) {
            if(++$i === $numItems) {
                $dataArray[] = $this->reply(" ".$value,250);
            } else {
                $dataArray[] = $this->reply("-".$value,250);
            }
        }
        return $dataArray;
    }

    // Validate SMTP Command is allowed
    private function validateCommand($command) {
        if ( in_array($command,$this->smtpAcceptedCommands) ) {
            return true;
        } else {
            return false;
        }
    }

    // Generate random Queue ID
    private function generateQueueID($num_bytes=6) {
        return bin2hex(openssl_random_pseudo_bytes($num_bytes));
    }

    // Parse SMTP DATA
    public function parseData($data) {
        // Build DATA end sequence
        $dataQuitSequence = $this->dataLastLine.$data;

        // Output debug log for data in ascii and hex (This is only a temporary debug log, remove when done testing)
        //$this->logger->logDebugMessage("[smtp] Received DATA: ".$data);
        //$this->logger->logDebugMessage("[smtp] Received DATA (HEX): ".bin2hex($data));

        // Log SMTP DATA (DEBUG) if not empty
        $dataLog = $data;
        if ( $dataLog && $dataLog != "" ) {
            $this->logger->logDebugMessage("[smtp] Received DATA (".strlen($dataLog)." bytes)");
            //$this->logger->logDebugMessage("[smtp] : ".$dataLog);
        }
        // Update last line
        $_testLines = explode("\n",$data);
        if ( count($_testLines) > 1 ) {
            $this->dataLastLine = substr($data, -2);
        } else $this->dataLastLine = $data;

        // Grab the last 25 bytes of the data and convert to HEX for comparison
        // We take this extra part as they might choose to wrap a command at the end of the data
        $rawEndSequence = substr($data,-25);
        $hexEndSequence = bin2hex($rawEndSequence);
        
        // Check if we see the smtpDATAendHEX is present in the hexEndSequence
        if ( strpos($hexEndSequence,$this->smtpDATAendHEX) !== false ) {

            // Check if we end in ASCII characters in hexEndSequence
            $regex = '/^.*([ -~]+)$/';
            $possibleCommand = false;
            if (preg_match($regex, $rawEndSequence, $_match)) {
                if ( $_match[0] && $_match[0] != "" ) {
                    $possibleCommand = @trim($_match[0]);
                    // Make sure to strip it away from original data
                    $data = str_replace($possibleCommand,'',$data);
                    // Log it for debugging
                    $this->logger->logDebugMessage("[smtp] Possible Command found: ".$possibleCommand);
                }
            }

            // Add the last line to the email data
            $this->addEmailData(rtrim(trim($data),'.'));

            $this->setSMTPDATAmode(false);
            $this->logger->logDebugMessage("[smtp] End of DATA (total bytes = ".strlen($this->emailData).")");
            $queue_number = $this->generateQueueID();
            // Set queue number
            $this->emailQueueID = $queue_number;
            // Build email EML
            $this->buildEmailEML();
            return $this->reply(" Ok: queued as ".$queue_number,250);
        } else if ( preg_match('/^(\r?\n){1}\.(\r?\n){1}$/',$dataQuitSequence) || preg_match('/^(\r?\n){1}\.(\r?\n){1}$/',$data) ) {
            // Add the last line to the email data
            $this->addEmailData(rtrim(trim($data),'.'));

            $this->setSMTPDATAmode(false);
            $this->logger->logDebugMessage("[smtp] End of DATA (total bytes = ".strlen($this->emailData).")");
            $queue_number = $this->generateQueueID();
            // Set queue number
            $this->emailQueueID = $queue_number;
            // Build email EML
            $this->buildEmailEML();
            return $this->reply(" Ok: queued as ".$queue_number,250);
        } else {
            // Build email data
            $this->addEmailData($data);
        }

        return false; // We do this to continue the loop and continue to accept DATA
    }

    // Get email DATA (EML format)
    public function getEmailEML() {
        // Note:
        // The final chunk of data is supposed to end with a newline (CRLF);
        // otherwise the last line of the message will not be parsed by many EML/MIME parsers.

        // First trim the email data, to make sure we don't have any trailing whitespace
        $this->emailEML = trim($this->emailEML);

        // And then finally add a newline to the end of the email data as required
        $this->emailEML .= "\r\n";

        // Return email data
        return $this->emailEML;
    }

    // Parse SMTP Command
    public function parseCommand($data) {

        // Get SMTP command
        $input = trim($data);
        $command = false;
        $argument = false;

        if ( preg_match('/:/', $input) ) {
            $_smtpCommand = explode(":",trim($input));
            $command = trim($_smtpCommand[0]);
            $argument = array_key_exists(1,$_smtpCommand) ? trim($_smtpCommand[1]) : false;
        } else {
            $_smtpCommand = explode(" ",trim($input));
            $command = trim($_smtpCommand[0]);
            $argument = array_key_exists(1,$_smtpCommand) ? trim($_smtpCommand[1]) : false;
        }

        $domain = array_key_exists("smtp_domain",$this->config['smtp']) ? trim($this->config['smtp']['smtp_domain']) : 'smtp.example.com';
        $banner = array_key_exists("smtp_banner",$this->config['smtp']) ? trim($this->config['smtp']['smtp_banner']) : 'SMTP Honeypot';
        $fullBanner = $domain.' '.$banner;

        // Sanity check on command
        if ( !$command || $command == "" || !isset($command) ) {
            return false;
        }

        // Uppercase command
        $command = strtoupper(trim($command));

        // Validate command
        if ( !$this->validateCommand($command) ) {
            $this->logger->logDebugMessage("[smtp] Invalid command: ".trim($command));
            $command = "UNKNOWN";
        }

        // Log SMTP command and argument (DEBUG)
        if ( $command && $command != "UNKNOWN" ) {
            $this->logger->logDebugMessage("[smtp] Received command: ".trim($command));
            // Output same log line but in HEX
            $this->logger->logDebugMessage("[smtp] Received command (HEX): ".bin2hex(trim($command)));
        }
        if ($argument) $this->logger->logDebugMessage("[smtp] Received argument: ".trim($argument));
        // Output same log line but in HEX
        if ($argument) $this->logger->logDebugMessage("[smtp] Received argument (HEX): ".bin2hex(trim($argument)));

        // Create action to return based on SMTP command via switch cases and use reply for answers
        switch ( $command ) {
            case 'HELO':
                $this->addCommandSequence($command); // Important command, Add to sequence array
                $output = $this->reply(' '.$domain,250);
                $this->emailHELO = $argument;
                break;
            case 'EHLO':
                $this->addCommandSequence($command); // Important command, Add to sequence array
                $output[] = $this->reply('-'.$domain,250);
                $extra = $this->generateSMTPfeatures();
                $output = array_merge($output,$extra);
                $this->emailHELO = $argument;
                break;
            case 'MAIL FROM':
                $output = $this->reply(false,250);
                $this->addCommandSequence($command); // Important command, Add to sequence array
                if ( $argument ) {
                    $this->emailFrom = $argument;
                }
                break;
            case 'RCPT TO':
                $output = $this->reply(false,250);
                $this->addCommandSequence($command); // Important command, Add to sequence array
                if ( $argument ) {
                    // Push to emailRCPT array
                    $this->emailRCPT[] = trim($argument);
                }
                break;
            case 'DATA':
                $this->addCommandSequence($command); // Important command, Add to sequence array
                $this->setSMTPDATAmode(true);
                $output = $this->reply(false,354);
                break;
            case 'AUTH':
                if ( $argument ) {
                    $this->authCreds[] = $argument;
                    $output = $this->reply(false,250);
                } else {
                    $output = $this->reply(false,501);
                }
                break;
            case 'RSET':
                $output = $this->reply(false,250);
                break;
            case 'NOOP':
                $output = $this->reply(false,250);
                break;
            case 'QUIT':
                $output = $this->reply(false,221);
                break;
            case 'HELP':
                $output = $this->reply(false,250);
                break;
            default:
                $output = $this->reply(false,502);
                break;
        }

        // Add command to smtp command array if known
        $this->addCommand($command);

        // Check if we are SMTP compliant
        $compliance = trim($this->config['smtp']['smtp_compliant']) == "1" ? true : false;
        if ( $compliance ) {
            // Command sequence check
            $status = $this->checkCommandSequence();
            if ( !$status ) {
                // Remove command from sequence array
                $this->delCommandSequence($command);
                // Make sure we are not in DATA mode
                $this->setSMTPDATAmode(false);
                // Set output as either FIRST or BAD sequence
                // We want to emulate Bad faith on some commands if they are first
                if ( count($this->smtpCommandsSequence) < 1  && $command == "GET" ) $output = $this->reply(" Error: I can break rules, too. Goodbye.",503);
                else if ( count($this->smtpCommandsSequence) < 1  && $command == "MAIL" ) $output = $this->reply(" Error: I can break rules, too. Goodbye.",503);
                else if ( count($this->smtpCommandsSequence) < 1  && $command == "RCPT" ) $output = $this->reply(" Error: I can break rules, too. Goodbye.",503);
                else if ( count($this->smtpCommandsSequence) < 1 ) $output = $this->reply(" Error: Send HELO/EHLO first.",503);
                else $output = $this->reply(false,503);
            }
        }
        
        // Log how many command we have parsed
        $commandSeen = count($this->smtpCommands);
        if ($command) $this->logger->logDebugMessage("[smtp] ".$commandSeen." command(s) parsed");

        // Log debug on what we are returning
        if ( is_array($output) ) {
            foreach($output as $line) {
                $this->logger->logDebugMessage("[smtp] Sending reply: ".trim($line));
            }
        } else {
            $this->logger->logDebugMessage("[smtp] Sending reply: ".trim($output));
        }

        return $output;
    }

    // function to handle closing connection to early
    public function closeConnection() {
        return $this->reply(false,421);
    }

    // function to handle closing connection to early
    public function closeConnectionToManyConnections() {
        return $this->reply(" Sorry i'm to busy to handle more connections. Goodbye.",421);
    }

    // Handle SMTP Honeypot
    private function reply($message = false, $code = 250) {
        $domain = array_key_exists("smtp_domain",$this->config['smtp']) ? trim($this->config['smtp']['smtp_domain']) : 'smtp.example.com';
        $banner = array_key_exists("smtp_banner",$this->config['smtp']) ? trim($this->config['smtp']['smtp_banner']) : 'SMTP Honeypot';
        $fullBanner = $domain.' '.$banner;
        // List SMTP codes reply
        $replies = array(
            220 => '220 '.trim($fullBanner),
            221 => '221 2.0.0 Bye',
            250 => '250 2.0.0 Ok',
            354 => '354 Start mail input; end with <CRLF>.<CRLF>',
            421 => '421 Service not available, closing transmission channel',
            450 => '450 Requested mail action not taken: mailbox unavailable',
            451 => '451 Requested action aborted: local error in processing',
            452 => '452 Requested action not taken: insufficient system storage',
            500 => '500 Syntax error, command unrecognized',
            501 => '501 5.5.4 Syntax: Error in parameters or arguments',
            502 => '502 5.5.2 Error: command not recognized',
            503 => '503 5.5.1 Error: Bad sequence of commands',
            504 => '504 5.5.1 Command parameter not implemented',
            521 => '521 5.5.1 Protocol error',
            550 => '550 Requested action not taken: mailbox unavailable',
            551 => '551 User not local; please try <forward-path>',
            552 => '552 Requested mail action aborted: exceeded storage allocation',
            553 => '553 Requested action not taken: mailbox name not allowed',
            554 => '554 Transaction failed',
        );
        if ( $message === false ) {
            $message = $replies[$code]."\r\n";
        } else {
            $message = $code.$message."\r\n";
        }
        return $message;
    }
}

?>