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
        $this->logger->logMessage('[server] Stopped listening for connections');
        $this->logger->logMessage('[server] EXIT=0');
        $this->logger->logMessage(">>> SMTPLATRINE - Goodbye!");
        // Clean exit
        exit(0);
    }

    // Handler for SIGHUB
    public function doSIGHUP($signo) {
        // Let's just inform the logs that we are closing down
        $this->logger->logMessage(">>> SMTPLATRINE NOOP - Ignoring SIGHUP");
    }

}

?>