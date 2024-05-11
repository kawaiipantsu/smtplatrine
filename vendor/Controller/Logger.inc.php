<?PHP
namespace Controller;

class Logger {
    
    public $loggerEnabled = false;
    public $lastError;
    private $config = false;
    private $logName = 'logger';
    private $namespace = __NAMESPACE__;
    private $fullName = false;
    private $pidTitle = false;
    
    // Contructor
    public function __construct($logName='logger', $namespace=__NAMESPACE__) {

        // Set namespace
        if ( $namespace === false || $namespace == "" ) $namespace = $logName;
        $this->namespace = $namespace;

        // Get process title before trying to just setting a static name
        // I'm still not sure what is best here, it's all visual and debug that depends on it
        //$this->logName = isset(cli_get_process_title()) ? trim(cli_get_process_title()) : $logName;
        $this->logName = trim($logName);
        $this->pidTitle = trim(cli_get_process_title());
        
        // Set full name
        if ( $this->logName != $this->namespace) {
            $this->fullName = $this->namespace."/".$this->logName;
        } else {
            $this->fullName = $this->logName;
        }
        
        // Load config if not loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }

        // If logger is disabled set state
        $loggerEnabled = trim($this->config['logger']['logger_enable']) == "1" ? true : false;
        if ( $loggerEnabled === false ) {
            $this->loggerEnabled = false;
        } else {
            $this->loggerEnabled = true;
        }

        // Log that logger is initialized
        $this->logDebugMessage('[logger] New logger initialized for '.$this->fullName);

        // Get default log level for this vendor or use default
        $vendor_log_level_specific = "vendor_log_level_".strtolower($this->namespace);
        if ( array_key_exists($vendor_log_level_specific, $this->config['vendor_log_level']) ) {
            $vendor_log_level = trim($this->config['vendor_log_level'][$vendor_log_level_specific]);
        } else {
            $vendor_log_level_specific = "vendor_log_level_default";
            $vendor_log_level = trim($this->config['vendor_log_level'][$vendor_log_level_specific]);
        }
        $this->logDebugMessage("[logger] Vendor ".$this->namespace." log level loaded: ".$vendor_log_level_specific);
        $this->logDebugMessage("[logger] Vendor ".$this->namespace." log level set: ".$vendor_log_level);

        // Set the new log level
        $this->disableDisplay();

        // Build int from constants
        $level_constants = explode('|', $vendor_log_level);
        $level_constants_int = 0;
        foreach ( $level_constants as $level_constant ) {
            $level_constants_int = $level_constants_int | constant(trim($level_constant));
        }

        // Set the new default log level
        $this->enableReporting($level_constants_int);
    }

    // Destructor
    public function __destruct() {
        // Nothing to do
    }

    // Main log entry function that calls the appropriate log function based on the config
    public function logMessage($entry, $level='INFO') {
        if ( $this->loggerEnabled ) {
            if ( strtolower(trim($this->config['logger']['logger_destination'])) == 'both' ) {
                $this->logToFile($entry, $level);
                $this->logToSyslog($entry, $level);
            } else {
                if ( strtolower(trim($this->config['logger']['logger_destination'])) == 'file' ) {
                    $this->logToFile($entry, $level);
                } else if ( strtolower(trim($this->config['logger']['logger_destination'])) == 'syslog' ) {
                    $this->logToSyslog($entry, $level);
                }
            }
        }
    }

    // logEntry for Errors
    public function logErrorMessage($entry) {
        if ( $this->loggerEnabled ) {
            $this->logMessage($entry, 'ERROR');
            // Set last error message
            $this->lastError = $entry;
        }
    }

    // logEntry for Debug
    public function logDebugMessage($entry) {
        if ( $this->loggerEnabled ) {
            // Check if debug enabled
            if ( strtolower(trim($this->config['logger']['logger_debug'])) == true ) {
                $this->logMessage($entry, 'DEBUG');
            }
        }
    }

    // Log to file
    private function logToFile($entry, $level='INFO') {
        
        // Switch case to set level
        switch ( strtolower(trim($level)) ) {
            case 'info':
                $level = 'INFO';
                break;
            case 'warning':
                $level = 'WARNING';
                break;
            case 'error':
                $level = 'ERROR';
                break;
            case 'critical':
                $level = 'CRITICAL';
                break;
            case 'alert':
                $level = 'ALERT';
                break;
            case 'emergency':
                $level = 'EMERGENCY';
                break;
            case 'debug':
                $level = 'DEBUG';
                break;
            default:
                $level = 'INFO';
        }

        // Setup file locations
        $path = $this->config['output_file']['output_file_path'];
        $file = $this->config['output_file']['output_file_main'];
        $file_error = $this->config['output_file']['output_file_error'];
        $file_debug = $this->config['output_file']['output_file_debug'];

        // Check if path is absolute
        if ( substr($path, 0, 1) != '/' ) $path = __DIR__ . '/../../' . $path;
        // check if path ends with backslash
        if ( substr($path, -1) != '/' ) $path .= '/';
        // Create log directory if not exists
        if ( !is_dir($path) ) mkdir($path, 0755, true);

        // Open log file and write entry (main)
        $fh = fopen($path.$file, 'a');
        $logEntry = date("Y-m-d H:i:s")." ".$this->pidTitle."[".getmypid()."]: ($level) $entry\n";
        fwrite($fh, $logEntry);
        fclose($fh);

        // Open log file and write entry (error)
        if ( $level == 'ERROR' || $level == 'WARNING' ) {
            $fh = fopen($path.$file_error, 'a');
            $logEntry = date("Y-m-d H:i:s")." ".$this->pidTitle."[".getmypid()."]: $entry\n";
            fwrite($fh, $logEntry);
            fclose($fh);
        }
        // Open log file and write entry (debug)
        if ( $level == 'DEBUG' ) {
            $fh = fopen($path.$file_debug, 'a');
            $logEntry = date("Y-m-d H:i:s")." ".$this->pidTitle."[".getmypid()."]: $entry\n";
            fwrite($fh, $logEntry);
            fclose($fh);
        }
    }

    // Log to syslog
    private function logToSyslog($entry, $level='INFO') {
        // Switch case to set level for syslog
        switch ( strtolower(trim($level)) ) {
            case 'info':
                $level = LOG_INFO;
                break;
            case 'warning':
                $level = LOG_WARNING;
                break;
            case 'error':
                $level = LOG_ERR;
                break;
            case 'critical':
                $level = LOG_CRIT;
                break;
            case 'alert':
                $level = LOG_ALERT;
                break;
            case 'emergency':
                $level = LOG_EMERG;
                break;
            case 'debug':
                $level = LOG_DEBUG;
                break;
            default:
                $level = LOG_INFO;
        }
        // switch case to select facility
        switch ( strtolower(trim($this->config['output_syslog']['output_syslog_facility'])) ) {
            case 'auth':
                $facility = LOG_AUTH;
                break;
            case 'authpriv':
                $facility = LOG_AUTHPRIV;
                break;
            case 'cron':
                $facility = LOG_CRON;
                break;
            case 'daemon':
                $facility = LOG_DAEMON;
                break;
            case 'ftp':
                $facility = LOG_FTP;
                break;
            case 'kern':
                $facility = LOG_KERN;
                break;
            case 'local0':
                $facility = LOG_LOCAL0;
                break;
            case 'local1':
                $facility = LOG_LOCAL1;
                break;
            case 'local2':
                $facility = LOG_LOCAL2;
                break;
            case 'local3':
                $facility = LOG_LOCAL3;
                break;
            case 'local4':
                $facility = LOG_LOCAL4;
                break;
            case 'local5':
                $facility = LOG_LOCAL5;
                break;
            case 'local6':
                $facility = LOG_LOCAL6;
                break;
            case 'local7':
                $facility = LOG_LOCAL7;
                break;
            case 'lpr':
                $facility = LOG_LPR;
                break;
            case 'mail':
                $facility = LOG_MAIL;
                break;
            case 'news':
                $facility = LOG_NEWS;
                break;
            case 'syslog':
                $facility = LOG_SYSLOG;
                break;
            case 'user':
                $facility = LOG_USER;
                break;
            case 'uucp':
                $facility = LOG_UUCP;
                break;
            default:
                $facility = LOG_USER;
        }
        openlog($this->pidTitle, LOG_NDELAY | LOG_PID, LOG_USER);
        syslog($level, $entry);
        closelog();
    }

    // Load config file from etc
    private function loadConfig() {
        $config = parse_ini_file(__DIR__ . '/../../etc/logger.ini',true);
        return $config;
    }

    // Enable PHP error logging
    public function enableReporting($level=E_ERROR) {
        error_reporting($level);
        $this->logDebugMessage("[".$this->fullName."] Error reporting level set to: ".$level);
    }

    // Enable showing errors
    public function enableDisplay() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        $this->logDebugMessage("[".$this->fullName."] Display errors enabled");
    }

    // Disable showing errors
    public function disableDisplay() {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        $this->logDebugMessage("[".$this->fullName."] Display errors disabled");
    }
}

?>