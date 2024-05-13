<?PHP

namespace Utils;

class Signals {

    private $logger;

    // Constructor
    public function __construct() {
        // Setup logging for the main application
        $this->logger = new \Controller\Logger(basename(__FILE__,'.php'),__NAMESPACE__);
    }

    // Destructor
    public function __destruct() {
        // Nothing to do
    }

    // Handler for SIGINT (^C)
    public function doSIGINT($signo) {
        // LEt's just inform the logs that we are closing down
        $this->logger->logMessage('Caught SIGINT, shutting down now!', 'WARNING');
        $this->logger->logMessage('[server] Stopped listening for connections');
        $this->logger->logMessage('[server] EXIT=0');
        $this->logger->logMessage(">>> SMTPLATRINE - Goodbye!");
        // Clean exit
        exit(0);
    }

    // Handler for SIGTERM
    public function doSIGTERM($signo) {
        // Let's just inform the logs that we are closing down
        $this->logger->logMessage(">>> Posix SIGNAL recieved '".trim($this->signalToString($signo))."' - Goodbye","WARNING");
        $this->logger->logMessage('[server] Stopped listening for connections');
        $this->logger->logMessage('[server] EXIT=0');
        $this->logger->logMessage(">>> SMTPLATRINE - Goodbye!");
        // Clean exit
        exit(0);
    }

    // Handler for SIGHUB
    public function doSIGHUP($signo) {
        // Let's just inform the logs that we are closing down
        $this->logger->logMessage(">>> SMTPLATRINE RELOAD '".trim($this->signalToString($signo))."' but we did nothing!","WARNING");
    }

    // Handler for SIGNAL placeholder
    public function doNOTHING($signo) {
        // Let's just inform the logs about a signal we are ignoring
        $this->logger->logMessage(">>> Posix SIGNAL recieved '".trim($this->signalToString($signo))."' we did nothing!","WARNING");
    }

    // Make a function that converts the integer of a signal into a meaningfull string.
    // These are the signals: SIGINT,SIGTERM,SIGQUIT,SIGKILL,SIGHUP,SIGCHLD,SIG_IGN,SIGUSR1,SIGUSR2,SIGALRM,SIGSEGV,SIGCONT, SIGSTOP
    private function signalToString($signo) {
        switch($signo) {
            case SIGINT:
                return "SIGINT";
            case SIGTERM:
                return "SIGTERM";
            case SIGQUIT:
                return "SIGQUIT";
            case SIGHUP:
                return "SIGHUP";
            case SIGCHLD:
                return "SIGCHLD";
            case SIG_IGN:
                return "SIG_IGN";
            case SIGUSR1:
                return "SIGUSR1";
            case SIGUSR2:
                return "SIGUSR2";
            case SIGALRM:
                return "SIGALRM";
            case SIGSEGV:
                return "SIGSEGV";
            case SIGCONT:
                return "SIGCONT";
            case SIGSTOP:
                return "SIGSTOP";
            default:
                return "($signo)UNKNOWN";
        }
    }








}

?>