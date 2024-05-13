<?PHP

namespace Utils;

class Timing {

    // Constructor
    public function __construct() {
        // Nothing to do
    }

    // Destructor
    public function __destruct() {
        // Nothing to do
    }

    // Get the current time in seconds
    public static function getMicroTime() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}

?>