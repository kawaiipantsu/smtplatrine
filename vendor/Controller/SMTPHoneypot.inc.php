<?PHP

namespace Controller;

class SMTPHoneypot {
        
    private $logger;
    private $config = false;
    private $smtpAcceptedCommands = array(
        'HELO',         // We accept both HELO and EHLO
        'EHLO',         // -
        'AUTH',         // We accept AUTH LOGIN (multiple line auth process)
        'AUTH PLAIN',   // We accept AUTH PLAIN (one line auth)
        'AUTH PLAIN',   // We accept AUTH LOGIN (multi line auth)
        'MAIL FROM',    // We accept both MAIL FROM and MAIL
        'MAIL',         // - (this however don't do anything)
        'RCPT TO',      // We accept both RCPT TO and RCPT
        'RCPT',         // - (this however don't do anything)
        'DATA',         // We accept both DATA and DATA END
        'RSET',         // We accept RSET - But does nothing!
        'NOOP',         // We accept NOOP
        'QUIT',         // We accept QUIT
        'VRFY',         // We accept VRFY (but does nothing!)
        'EXPN',         // We accept EXPN (but does nothing!)
        'STARTTLS',     // We accept the command but we don't actually support it
        'GET'           // We accept GET (not an accepted command! Turn them way!)
    );
    private $smtpCommands = array();
    private $smtpCommandsSequence = array();
    private $smtpFoundPackedCommand = false;
    public $smtpLastCommand = false;
    protected $smtpDATAmode = false;
    protected $smtpAUTHmode = false;
    private $dataLastLine = false;
    private $weSecure = false;
    private $smtpDATAendHEX = '0d0a2e0d0a'; // HEX for \r\n.\r\n

    // Email components
    private $emailHELO = false;
    private $authCreds = array();
    private $authLOGINuser = false;
    private $authLOGINpass = false;
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

    // Clear all command sequences and last command
    public function clearCommandSequences() {
        $this->smtpCommandsSequence = array();
        $this->smtpCommands = array();
        $this->smtpLastCommand = false;
    }

    // Clear last command
    private function clearLastCommand() {
        $this->smtpLastCommand = false;
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

        // Split emailEML into array
        $emailEMLArray = explode("\r\n",$this->emailEML);
        // This is a hack to find out the line number of some "known" header that we can pre-pend to
        $headerLine = 0;
        foreach($emailEMLArray as $key=>$line) {
            if ( preg_match('/^from:/i',$line) || preg_match('/^subject:/i',$line) || preg_match('/^date:/i',$line) ) {
                $headerLine = $key;
                break;
            }
        }

        // Now add the custom header to the emailEML array before key position and rebuild the complete array
        $top = array_slice($emailEMLArray, 0, $headerLine);
        $bottom = array_slice($emailEMLArray, $headerLine);
        unset($emailEMLArray);

        // Now build emailEML from array as loop
        $this->emailEML = '';
        foreach($top as $line) {
            $this->emailEML .= $line."\r\n";
        }
        $this->emailEML .= trim($header).": ".trim($value)."\r\n";
        foreach($bottom as $line) {
            $this->emailEML .= $line."\r\n";
        }
        unset($top);
        unset($bottom);

        // Old way, just add a line to the end of the emailEML
        //$this->emailEML .= trim($header).": ".trim($value)."\r\n";
    }

    // Create the Received email EML initial header
    private function buildReceivedHeader() {

        $resv = array();

        // Set default values for our Received header
        $domain = array_key_exists("smtp_domain",$this->config['smtp']) ? trim($this->config['smtp']['smtp_domain']) : 'smtp.example.com';
        $banner = array_key_exists("smtp_banner",$this->config['smtp']) ? trim($this->config['smtp']['smtp_banner']) : 'SMTP Honeypot';
        $fullBanner = $domain.' '.$banner;
        //$smtpType = $this->weSecure ? "ESMTP" : "SMTP"; // This IS ALL wrong, ESMTP has nothing to do with encryption :D (kept it here for laughs)
        $smtpType = "ESMTP"; // We always say we are ESMTP (Extended Simple Mail Transfer Protocol)


        // First add the original Received headers
        // TODO: preg_match_all on Received headers

        // Now add our own Received header (top of the eml)
        $resv[] = "Received: from %%CLIENTIPREVERSE%% (%%CLIENTIPREVERSE%% [%%CLIENTIP%%])\r\n";
        $resv[] = "\t%%ENCRYPTION%%\r\n";
        $resv[] = "\tby ".$domain." (Postfix) with ".$smtpType." id ".$this->emailQueueID."\r\n";
        $resv[] = "\tfor <".$this->emailHELO.">; ".date('r')."\r\n";

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

        // We add Return-Path and Delivered-To headers first
        // This is typically for a SMTP server that is the "end destination" for the email

        // First add return path
        $this->emailEML .= "Return-Path: <bounce@".$domain.">\r\n";
        // Add Delivered to
        // This header is "non-standard" but we piggy back on it to show all the recipients
        foreach($this->emailRCPT as $rcpt) {
            $this->emailEML .= "Delivered-To: ".$rcpt."\r\n";
        }

        // SMTP Received headers
        // We prepend ours to the top of the email EML, if it already comes with Received headers
        // they will automatically be added below ours
        $receivedHeaders = $this->buildReceivedHeader();
        foreach($receivedHeaders as $header) {
            $this->emailEML .= $header;
        }

        // Make local copy of email data, to work on without destroying the original
        $emailEML = $this->emailData;
        
        // >>
        // >> IF we need to manipulate the data, we can do it here
        // >>

        // Create the actual EML body from DATA including any sent headers
        $this->emailEML .= $emailEML."\r\n";

        // Add Custom X-Latrine related headers - These are not standard headers
        // Also addCustomHeader will add the header to the emailEML between the Received and the actual email data
        
        $this->addCustomHeader("X-Latrine-Queue-ID",$this->emailQueueID);
        $this->addCustomHeader("X-Latrine-Client-IP","%%CLIENTIP%%");
        $this->addCustomHeader("X-Latrine-Client-Port","%%CLIENTPORT%%");
        $this->addCustomHeader("X-Latrine-Server-Hostname",gethostname());
        $this->addCustomHeader("X-Latrine-Server-Listen",$srvAddress);
        $this->addCustomHeader("X-Latrine-Server-Port",$srvPort);
        $this->addCustomHeader("X-Latrine-Server-System",php_uname());
        
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
                if ( $validateCommands[1] == 'MAIL FROM' || $validateCommands[1] == 'AUTH PLAIN' || $validateCommands[1] == 'AUTH' ) {
                    return true;
                }
            }

            // Third check that we always get RCPT TO
            if ( count($validateCommands) == 3 ) {
                if ( $validateCommands[1] == 'AUTH PLAIN' && $validateCommands[2] == 'MAIL FROM' || $validateCommands[1] == 'AUTH' && $validateCommands[2] == 'MAIL FROM') {
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

            // Log the last 25 bytes of the data (DEBUG)
            //$this->logger->logDebugMessage("[smtp] Last 25 bytes of DATA: ".$rawEndSequence);
            $possibleCommand = false;

            // Regular expression to check if we see <CR><LF>.<CR><LF> followed by ASCII chars and <CR><LF> at the end
            //$regex = '/^.*\r?\n\.\r?\n([ -~]+)\r?\n$/i'; // Not working correctly as it's multiline

            // For now just check if we see the word QUIT<CR><LF> at the end of the data
            $regex = '/(QUIT)\r?\n$/i';

            if (preg_match($regex, $rawEndSequence, $_match)) {
                if ( $_match[0] && $_match[0] != "" ) {
                    $possibleCommand = @trim($_match[0]);
                    // Make sure to strip it away from original data
                    $data = str_replace($possibleCommand,'',$data);
                    // Log it for debugging
                    $this->logger->logDebugMessage("[smtp] Possible Command found: ".$possibleCommand);
                    $this->smtpFoundPackedCommand = $possibleCommand;
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

    // Function to check if packed command was found
    public function checkPackedCommand($command = false) {
        // In the future we might want to check for specific commands
        if ( $command && $command != "" ) {
            return $this->smtpFoundPackedCommand == $command ? true : false;
        } else {
            return $this->smtpFoundPackedCommand ? true : false;
        }
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


        $this->logger->logDebugMessage("[smtp] Received RAW (HEX): ".bin2hex($data));

        // First things first, since we are called that means we are potentially recieving a new command
        // So we clear the last command
        $this->clearLastCommand();

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

        // We are in AUTH mode, so we need to handle the login process
        if ( $this->smtpAUTHmode ) {
            // We can't rely on the command variable, so we need to get the raw data from input
            // We can use input and not data as it's okay it's been trimmed. The content should be base64 encoded.
            $authData = $input;

            // Check if we are in PLAIN process
            if ( $this->smtpAUTHmode == "PLAIN" ) {
                // PLain just awaits the combined username and password base64 encoded
                $creds = preg_replace('/\R/', '', base64_decode($authData));

                // Check if we have a space in the creds, if so we need to split it
                if ( strpos($creds,"\0") !== false ) {
                    $creds = explode("\0",$creds);
                    $user = trim($creds[1]);
                    $pass = $creds[2];
                } else {
                    $user = '';
                    $pass = $creds;
                }

                $authEntry = array(
                    'mechanism' => 'PLAIN',
                    'username' => $user,
                    'password' => $pass
                );
                $this->authCreds = $authEntry;
                // Now stop smtp auth mode
                $this->smtpAUTHmode = false;
                $output = $this->reply(false,235);
            }

            // We are in LOGIN process
            if ( $this->smtpAUTHmode == "LOGIN" ) {
                // First we need to get the username
                if ( $this->authLOGINuser === false ) {
                    $this->authLOGINuser = preg_replace('/\R/', '', base64_decode($authData));
                    $output = $this->reply(" UGFzc3dvcmQ6",334); // Send back Password: prompt (base64 encoded for language support)
                } else if ( $this->authLOGINuser && $this->authLOGINpass === false ) {
                    $this->authLOGINpass = preg_replace('/\R/', '', base64_decode($authData));
                    $output = $this->reply(false,235);
                    $this->smtpAUTHmode = false;
                    $authEntry = array(
                        'mechanism' => 'LOGIN',
                        'username' => $this->authLOGINuser,
                        'password' => $this->authLOGINpass
                    );
                    $this->authCreds = $authEntry;
                    $this->authLOGINuser = false;
                    $this->authLOGINpass = false;
                }
                
            }

            // As we want to talk again let's return now
            return $output;
        }

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
                // Make sure if it contains spaces to split it and only use the first part
                if ( strpos($argument," ") !== false ) {
                    $argument = explode(" ",$argument);
                    $this->emailFrom = trim($argument[0]);
                } else {
                    $this->emailFrom = trim($argument);
                }
                break;
            case 'RCPT TO':
                $output = $this->reply(false,250);
                $this->addCommandSequence($command); // Important command, Add to sequence array
                if ( $argument ) {
                    // Push to emailRCPT array
                    // Make sure if it contains spaces to split it and only use the first part
                    if ( strpos($argument," ") !== false ) {
                        $argument = explode(" ",$argument);
                        $this->emailRCPT[] = trim($argument[0]);
                    } else {
                        $this->emailRCPT[] = trim($argument);
                    }
                }
                break;
            case 'DATA':
                $this->addCommandSequence($command); // Important command, Add to sequence array
                $this->setSMTPDATAmode(true);
                $output = $this->reply(false,354);
                break;
            case 'AUTH':
                if ( $argument ) {
                    // Check if argument is PLAIN or LOGIN
                    if ( strtoupper($argument) == "PLAIN" ) {
                        //$this->smtpAUTHmode = true; // We dont really need to latch into auth mode as it's all done in one line!
                        
                        // Explode input to get the base64 encoded string thta will be the 2 argument
                        $authData = explode(" ",$input);
                        if ( count($authData) > 2 ) {
                            $authData = trim($authData[2]);
                            // Decode base64 string
                            $creds = base64_decode($authData);
                            // Check if we have a space in the creds, if so we need to split it
                            if ( strpos($creds,"\0") !== false ) {
                                $creds = explode("\0",$creds);
                                $user = trim($creds[1]);
                                $pass = $creds[2];
                            } else {
                                $user = '';
                                $pass = $creds;
                            }

                            $authEntry = array(
                                'mechanism' => 'PLAIN',
                                'username' => $user,
                                'password' => $pass
                            );
                            $this->authCreds = $authEntry;
                            $output = $this->reply(false,235);
                        } else {
                            $this->smtpAUTHmode = "PLAIN"; // We need to latch onto auth mode as we will now do the login process
                            $output = $this->reply("",334);
                            return $output;
                        }
                        
                    } else if ( strtoupper($argument) == "LOGIN" ) {
                        $this->smtpAUTHmode = "LOGIN"; // We need to latch onto auth mode as we will now do the login process
                        $output = $this->reply(" VXNlcm5hbWU6",334); // Send back Username: prompt (base64 encoded for language support)
                        return $output;
                    } else {
                        $output = $this->reply(" 5.5.4 Syntax: AUTH mechanism",501);
                    }
                } else {
                    $output = $this->reply(false,432);
                }
                break;
            case 'STARTTLS':
                // STARTTLS IS NOT SUPPORTED YET
                //$output = $this->reply(" STARTTLS command used when not advertised",503);
                $output = $this->reply(" 2.0.0 Ready to start TLS",220);
                $this->weSecure = true;
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
        if ($command != "UNKNOWN" ) $this->addCommand($command);

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

    // Return creentials
    public function getAuthCredentials() {
        return $this->authCreds;
    }

    // function to handle closing connection to early
    public function closeConnection() {
        return $this->reply(false,421);
    }

    // Close connection for idle
    public function closeConnectionIdle() {
        return $this->reply(" 4.4.2 Error: timeout exceeded",421);
    }
    public function closeConnectionIdleWhileInData() {
        return $this->reply(" 4.4.2 Error: timeout exceeded while in DATA",421);
    }

    // Close connection for kill
    public function closeConnectionKill() {
        return $this->reply(" 4.4.2 Error: server went away",421);
    }

    // function to handle closing connection to early
    public function closeConnectionToManyConnections() {
        return $this->reply(" Sorry i'm to busy to handle more connections. Goodbye.",421);
    }

    public function closeConnectionNoTLS() {
        return $this->reply(" STARTTLS command used when not advertised",503);
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
            235 => '235 2.7.0 Authentication succeeded',
            250 => '250 2.0.0 Ok',
            252 => '252 Cannot verify the user, but it will try to deliver the message anyway',
            354 => '354 Start mail input; end with <CRLF>.<CRLF>',
            421 => '421 Service not available, closing transmission channel',
            432 => '432 4.7.12  A password transition is needed',
            450 => '450 Requested mail action not taken: mailbox unavailable',
            451 => '451 4.4.1 Requested action aborted: local error in processing',
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