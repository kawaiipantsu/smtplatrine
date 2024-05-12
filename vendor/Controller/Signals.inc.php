<?PHP

namespace Controller;

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
        // Clean exit
        exit(0);
    }

    // Handler for SIGTERM
    public function doSIGTERM($signo) {
        // LEt's just inform the logs that we are closing down
        $logger->logMessage("SMTPLATRINE Shutdown - Goodbye!");
        // Clean exit
        exit(0);
    }

    // Handler for SIGHUB
    public function doSIGHUP($signo) {
        // LEt's just inform the logs that we are closing down
        $logger->logMessage("SMTPLATRINE NOOP - Ignoring SIGHUP");
    }

}

?>