<?PHP

namespace Controller;

class EmailParser {
    
    private $logger;
    private $config = false;
    private $canParse = false;
    
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
    public function __construct( $rawmail, $format = "eml" ) {
        
        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Read config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }

        // Check that we have a valid format
        // use a switch statement to allow for future formats
        switch ( strtolower(trim($format)) ) {
            case "eml":
                $this->canParse = true;
                $this->logger->logDebugMessage('[EmailParser] Parsing email in EML format');
                break;
            default:
                $this->canParse = false;
                $this->logger->logErrorMessage('[EmailParser] Invalid format specified! Cannot parse email!');
                break;
        }
        
        // Continue if we have a valid format
        if ( $this->canParse ) {

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
                        $fileUUIDv4 = $this->generateUUID4();
                        $this->attachments[] = array(
                            "uuid" => $fileUUIDv4,
                            "filename" => $filename,
                            "size" => $part_data['content-length']
                        );
                        // If we oped in to save the actual data, do so!
                        /*
                        $saveAttachment = trim($this->config['smtp']['smtp_attachments_store']) == "1" ? true : false;
                        if ( $saveAttachment ) {
                            $attachmentData = mailparse_msg_extract_part($part, $rawmail, null);
                            $this->saveAttachment($fileUUIDv4,$attachmentData);
                        }
                        */
                    }

                }
            } else {
                $this->logger->logErrorMessage('[EmailParser] Could not parse email! Something went wrong ...');
            }

        }

    }

    // Destructor
    public function __destruct() {
        mailparse_msg_free($this->mimeObject);
        $this->logger->logDebugMessage('[EmailParser] Email object freed!');
    }
    
    // Load config file from etc
    private function loadConfig() {
        $config = parse_ini_file(__DIR__ . '/../../etc/server.ini',true);
        return $config;
    }

    // Private function to save the attachment to disk
    private function saveAttachment($filename,$data) {
        $attachmentPath = $this->config['smtp']['smtp_attachments_path'];
        // Check if path is absolute
        if ( substr($attachmentPath, 0, 1) != '/' ) $attachmentPath = __DIR__ . '/../../' . $attachmentPath;
        // check if path ends with backslash
        if ( substr($attachmentPath, -1) != '/' ) $attachmentPath .= '/';
        // Create log directory if not exists
        if ( !is_dir($attachmentPath) ) mkdir($paattachmentPathth, 0775, true);

        // Remember that if we run in non-privileged mode, we need to make sure to always
        // pay attention to the user/group permissions for the files we create

        // Check if we can write to the directory
        if ( !is_writable($attachmentPath) ) {
            $this->logger->logErrorMessage('[EmailParser] Attachment path is not writable! Cannot save attachment!');
        } else {
            $attachmentFile = $attachmentPath.$filename;
            $attachmentFileHandle = fopen($attachmentFile, "w");
            fwrite($attachmentFileHandle, $data);
            fclose($attachmentFileHandle);
            $this->logger->logMessage('[EmailParser] Attachment saved to disk: '.$attachmentFile);
        }

    }

    // Private function to generate uuid version 4
    private function generateUUID4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Function that return array of details on all what we know so far
    public function getParsedResult() {
        $result = array();
        $result["mime"] = array(
            "mimeData" => $this->mimeData,
            "mimeStructure" => $this->mimeStructure
        );
        $result["headers"] = $this->headers;
        $result["attachments"] = $this->attachments;
        $result["body"]["html"] = $this->bodyHTML;
        $result["body"]["text"] = $this->bodyText;

        return $details;
    }


}

?>