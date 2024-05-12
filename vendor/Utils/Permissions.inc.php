<?PHP

namespace Utils;

class Permissions {

    // Constructor
    public function __construct() {
        
    }

    // Destructor
    public function __destruct() {
        
    }

    // Check directory is writeable by UID / GID
    public function isDirWriteable($dir, $uid = 0, $gid = 0) {
        // Placeholder
        return true;
    }

    // Check file is writeable by UID / GID
    public function isFileWriteable($file, $uid = 0, $gid = 0) {
        // Placeholder
        return true;
    }

    // Set chmod on file/path
    public function setChmod($path, $mode) {
        // Placeholder
        return true;
    }

    // Set chown on file/path
    public function setChown($path, $uid, $gid) {
        // Placeholder
        return true;
    }

}

?>