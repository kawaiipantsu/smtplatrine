<?PHP

namespace Controller;

class SMTPHoneypot {
        
    private $logger;
    private $config = false;
    private $smtpAcceptedCommands = array(
        'HELO',
        'EHLO',
        'MAIL FROM',
        'RCPT TO',
        'DATA',
        'RSET',
        'NOOP',
        'QUIT',
        'HELP'
    );
    private $smtpCommands = array();
    private $smtpCommandsSequence = array();
    public $smtpLastCommand = false;
    protected $smtpDATAmode = false;
    private $dataLastLine = false;

    // Email components
    private $emailEhlo = false;
    private $emailQueueID = false;
    private $emailFrom = false;
    private $emailTo = false;
    private $emailSubject = false;
    private $emailData = false;
    
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

    // SMTP compliant check order of command sequence
    private function checkCommandSequence() {
        $validateCommands = $this->smtpCommandsSequence;
        $realCommands = $this->smtpCommands;
        
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
                if ( $validateCommands[1] == 'MAIL FROM' ) {
                    return true;
                }
            }

            // Third check that we always get RCPT TO
            if ( count($validateCommands) == 3 ) {
                if ( $validateCommands[2] == 'RCPT TO' ) {
                    return true;
                }
            }

            // Fourth check that we always get DATA
            if ( count($validateCommands) == 4 ) {
                if ( $validateCommands[3] == 'DATA' ) {
                    return true;
                }
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

        // Log SMTP DATA (DEBUG) if not empty
        $dataLog = $data;
        if ( $dataLog && $dataLog != "" ) $this->logger->logDebugMessage("[smtp] Recieved DATA (".strlen($dataLog)." bytes)");

        // Update last line
        $this->dataLastLine = $data;

        // Check if end of data
        if ( preg_match('/^(\r?\n){1}\.(\r?\n){1}$/',$dataQuitSequence) ) {
            $this->setSMTPDATAmode(false);
            $this->logger->logDebugMessage("[smtp] End of DATA (total bytes = ".strlen($this->emailData).")");
            $queue_number = $this->generateQueueID();
            return $this->reply(" Ok: queued as ".$queue_number,250);
        } else {
            // Build email data
            $this->addEmailData($data);
        }
        return false; // We do this to continue the loop
    }

    // Get email DATA
    public function getEmailData() {
        return $this->emailData;
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
        if ( $command && $command != "UNKNOWN" ) $this->logger->logDebugMessage("[smtp] Recieved command: ".trim($command));
        if ($argument) $this->logger->logDebugMessage("[smtp] Recieved argument: ".trim($argument));

        // Create action to return based on SMTP command via switch cases and use reply for anwsers
        switch ( $command ) {
            case 'HELO':
                $this->addCommandSequence($command); // Important command, Add to sequence array
                $output[] = $this->reply('-'.$domain,250);
                $extra = $this->generateSMTPfeatures();
                $output = array_merge($output,$extra);
                $this->emailEhlo = $argument;
                break;
            case 'EHLO':
                $this->addCommandSequence($command); // Important command, Add to sequence array
                $output[] = $this->reply('-'.$domain,250);
                $extra = $this->generateSMTPfeatures();
                $output = array_merge($output,$extra);
                $this->emailEhlo = $argument;
                break;
            case 'MAIL FROM':
                $output = $this->reply(false,250);
                $this->addCommandSequence($command); // Important command, Add to sequence array
                break;
            case 'RCPT TO':
                $output = $this->reply(false,250);
                $this->addCommandSequence($command); // Important command, Add to sequence array
                break;
            case 'DATA':
                $this->addCommandSequence($command); // Important command, Add to sequence array
                $this->setSMTPDATAmode(true);
                $output = $this->reply(false,354);
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
                // Remove command from sequence arary
                $this->delCommandSequence($command);
                // Make sure we are not in DATA mode
                $this->setSMTPDATAmode(false);
                // Set output as either FIRST or BAD sequence
                if ( count($this->smtpCommandsSequence) < 1 ) $output = $this->reply(" Error: send HELO/EHLO first",503);
                else $output = $this->reply(false,503);
            }
        }
        
        // Log how many command we have parsed
        $commandSeen = count($this->smtpCommands);
        if ($command) $this->logger->logDebugMessage("[smtp] ".$commandSeen." command(s) parsed");

        return $output;
    }

    // Handle SMTP Honeypot
    private function reply($message = false, $code = 250) {
        $domain = array_key_exists("smtp_domain",$this->config['smtp']) ? trim($this->config['smtp']['smtp_domain']) : 'smtp.example.com';
        $banner = array_key_exists("smtp_banner",$this->config['smtp']) ? trim($this->config['smtp']['smtp_banner']) : 'SMTP Honeypot';
        $fullBanner = $domain.' '.$banner;
        // List SMTP codes reply
        $replies = array(
            220 => '220 '.trim($fullBanner),
            221 => '221 Goodbye',
            250 => '250 OK',
            354 => '354 Start mail input; end with <CRLF>.<CRLF>',
            421 => '421 Service not available, closing transmission channel',
            450 => '450 Requested mail action not taken: mailbox unavailable',
            451 => '451 Requested action aborted: local error in processing',
            452 => '452 Requested action not taken: insufficient system storage',
            500 => '500 Syntax error, command unrecognised',
            501 => '501 Syntax error in parameters or arguments',
            502 => '502 Command not implemented',
            503 => '503 Bad sequence of commands',
            504 => '504 Command parameter not implemented',
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