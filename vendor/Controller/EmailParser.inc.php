<?PHP

namespace Controller;

class EmailParser {
    
    private $logger;
    private $config = false;

    protected $mimeObject = false;

    private $mimeStructure = array();
    private $mimeData = array();
    private $mimeHeaders = array();
    private $headers = array();
    private $attachments = array();
    private $sections = array();
    private $bodyHTML = "";
    private $bodyText = "";
    
    public $subject = "";
    public $from = "";
    
    // Constructor
    public function __construct( $rawmail ) {
        
        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Read config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }

        // Using PHP extentions Mailparse
        $this->mimeObject = mailparse_msg_create();
        if ( mailparse_msg_parse($this->mimeObject,$rawmail) ) {
            $this->logger->logMessage('[EmailParser] Email parsed successfully!');
            $this->mimeStructure = mailparse_msg_get_structure($this->mimeObject);
            $this->mimeData = mailparse_msg_get_part_data($this->mimeObject);
            $this->headers = $this->mimeData['headers'];
            unset($this->mimeData['headers']);

            // Run though all mime Sections besides 1
            foreach ($this->mimeStructure as $structurePart) {

                // Build the individual mime part
                $part = mailparse_msg_get_part($this->mimeObject, $structurePart);
                $part_data = mailparse_msg_get_part_data($part);

                // Get TEXT body
                if ( isset($part_data['content-type']) && strpos($part_data['content-type'],'text/plain') !== false ) {
                    $this->bodyText = $contents = mailparse_msg_extract_part($part, $rawmail, null);
                }

                // Get HTML body
                if ( isset($part_data['content-type']) && strpos($part_data['content-type'],'text/html') !== false ) {
                    $this->bodyHTML = $contents = mailparse_msg_extract_part($part, $rawmail, null);
                }

                // If Attachment
                if ( isset($part_data['content-disposition']) && $part_data['content-disposition'] == 'attachment' ) {
                    // Extract filename from content-disponition or content-name, and store in attachments array
                    $filename = "";
                    if ( isset($part_data['disposition-filename']) ) {
                        $filename = $part_data['disposition-filename'];
                    } else if ( isset($part_data['content-name']) ) {
                        $filename = $part_data['content-name'];
                    }
                    $this->attachments[] = array(
                        "filename" => $filename,
                        "data" => "n/a"
                        //"data" => mailparse_msg_extract_part($part, $rawmail, null)
                    );
                }

            }
        } else {
            $this->logger->logErrorMessage('[EmailParser] Could not parse email! Something went wrong ...');
        }

    }

    // Destructor
    public function __destruct() {
        mailparse_msg_free($this->mimeObject);
        $this->logger->logMessage('[EmailParser] Email object freed!');
    }
    
    // Load config file from etc
    private function loadConfig() {
        $config = parse_ini_file(__DIR__ . '/../../etc/server.ini',true);
        return $config;
    }

    // Function that return array of details on all what we know so far
    public function getMailDetails() {
        $details = array();
        $details["mime"] = array(
            "mimeData" => $this->mimeData,
            "mimeStructure" => $this->mimeStructure
        );
        $details["headers"] = $this->headers;
        $details["attachments"] = $this->attachments;
        $details["body"]["html"] = $this->bodyHTML;
        $details["body"]["text"] = $this->bodyText;

        return $details;
    }


}

?>