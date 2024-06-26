<?PHP

namespace Controller;

class Database {

    protected $db;
    protected $meta;

    private $dbBackendAvailable = false;
    private $dbConnected = false;
    private $dbReuseConnection = false;
    private $dbHostinfo = '';

    private $dbHost;
    private $dbPort;
    private $dbUser;
    private $dbPass;
    private $dbName = 'smtplatrine';

    private $logger;
    private $mysqlReporting = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
    private $config = false;

    // Constructor
    public function __construct( $reuseConnection = false ) {
        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Enable Meta services if we got em
		$this->meta = new \Controller\Meta;

        // Load config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
            if ( $this->config === false ) {
                $this->dbBackendAvailable = false;
            } else {
                $this->dbBackendAvailable = array_key_exists('engine_backend',$this->config['engine']) ? trim($this->config['engine']['engine_backend']) : 'mysql';
                $this->dbBackendAvailable = strtolower(trim($this->dbBackendAvailable));
            }
        }

        // Only continue if we have a available backend
        if ( $this->dbBackendAvailable ) {
            // Set database credentials
            $this->setDBcredentials(); 

            // Set if we want persistent connection
            if ( $reuseConnection ) $this->dbReuseConnection = true;

            // Switch backend
            switch ( strtolower(trim($this->dbBackendAvailable)) ) {
                case 'mysql':
                    $this->logger->logMessage('[database] Using MySQL backend');
                    $this->dbBackendAvailable = 'mysql';
                    break;
                default:
                    $this->logger->logErrorMessage('[database] Backend not supported: ' . $this->dbBackendAvailable);
                    $this->dbBackendAvailable = false;
                    break;
            }

        } else {
            $this->logger->logErrorMessage('[database] Backend not available');
        }
        
    }

    // Destructor
    public function __destruct() {
        $this->logger->logDebugMessage('[database] Closing connection to ' . $this->dbHost . ' on port ' . $this->dbPort . ' as ' . $this->dbUser . ' to database ' . $this->dbName);
        $this->dbMysqlClose();
    }

    // Check if we could load config
    public function isDBready() {
        if ( $this->dbBackendAvailable === false ) {
            return false;
        } else {
            return true;
        }
    }

    // Load config file from etc
    private function loadConfig() {
        if ( is_file(__DIR__ . '/../../etc/database.ini') === false ) {
            $this->logger->logErrorMessage('Can\'t open etc/database.ini file, not able to handle connections!');
            return false;
        } else {
            $config = parse_ini_file(__DIR__ . '/../../etc/database.ini',true);
            return $config;
            
        }
    }

    // Set host,port,user and password
    private function setDBcredentials( ) {
        if ( $this->config ) {
            $this->dbHost = trim($this->config['mysql']['mysql_host']);
            $this->dbPort = intval($this->config['mysql']['mysql_port']);
            $this->dbUser = trim($this->config['mysql']['mysql_username']);
            $this->dbPass = $this->config['mysql']['mysql_password'];
            $this->dbName = trim($this->config['mysql']['mysql_database']);
        }
    }

    // Connect to the database
    private function dbMysqlConnect() {
        if ( $this->dbConnected === false ) {

            // Prepend p: to hostname to indicate persistent connection
            if ( $this->dbReuseConnection ) {
                $dbHost = 'p:'.trim($this->dbHost);
            } else {
                $dbHost = str_replace("p:","",trim($this->dbHost));
            }

            // Connect to the database
            $db = mysqli_connect($dbHost, $this->dbUser, $this->dbPass, $this->dbName, $this->dbPort);

            if ( mysqli_connect_errno() ) {
                $this->logger->logErrorMessage('[database] Failed connection: ' . trim(mysqli_connect_error()));
                $this->dbConnected = false;
            } else {
                $this->logger->logDebugMessage('[database] Connected to ' . $this->dbHost . ' on port ' . $this->dbPort . ' as ' . $this->dbUser . ' to database ' . $this->dbName);
                $this->db = $db;
                $this->dbConnected = true;
                $this->dbHostinfo = trim(mysqli_get_host_info($db));

                mysqli_set_charset($db, 'utf8mb4');
                if (mysqli_errno($db)) {
                    // Log that setting charset failed
                    $this->logger->logErrorMessage('[database] Failed setting charset: ' . trim(mysqli_error($db)));
                }
            }
        } else {
            // Already connected
        }
    }

    // Close the database connection
    private function dbMysqlClose() {
        if ( $this->dbConnected ) {
            $this->db->close();
            $this->dbConnected = false;
        }
    }

    // Do query ( NOTE THIS HAS NO ESCAPING! PLEASE USE PREPARED STATEMENTS! )
    // This i just for quick and dirty queries
    public function dbMysqlRawQuery( $query, $doReturn = true , $doLogging = true ) {
        // Set MySQLi reporting
        mysqli_report($this->mysqlReporting);
        // Establish a connection to the database
        $this->dbMysqlConnect();

        if ( $this->dbConnected ) {

            if ( $doReturn ) {
                try {
                    $result = mysqli_query($this->db, $query);
                } catch (Exception $e) {
                    $this->logger->logErrorMessage('[database] Exception catch: ' . trim($e->getMessage()));
                    $result = false;
                }
                
                if ( $result === false ) {
                    if ( $doLogging ) $this->logger->logErrorMessage('[database] Query failed: ' . trim(mysql_error()));
                    return false;
                } else {
                    $rowcount = mysqli_num_rows($result);
                    if ( $doLogging )$this->logger->logDebugMessage('[database] Query success: ' . $query . ' returned ' . $rowcount . ' rows');
                    return $result;
                }
            } else {
                if ( mysqli_query($this->db, $query) === false ) {
                    if ( $doLogging ) $this->logger->logErrorMessage('[database] Query failed: ' . trim(mysql_error()));
                    return false;
                } else {
                    if ( $doLogging ) $this->logger->logDebugMessage('[database] Query success: ' . $query);
                    // Get the ID of the freshly inserted row and return that if we see an INSERT
                    if ( preg_match('/^INSERT INTO/i',$query) ) {
                        $rowID = mysqli_insert_id($this->db);
                        return $rowID;
                    } else {
                        return true;
                    }
                    return true;
                }
            }

            // Close the connection
            $this->dbMysqlClose();

        } else {
            return false;
        }
    }

    // Ping the database connection
    // Not really something that has any benefit, but it's here (since we connect, disconnect on each query anyway)
    public function dbMysqlPing() {
        if ( $this->db->ping() && $this->dbConnected ) {
            // Log the result
            $this->logger->logMessage('[database] Ping: Our connection is ok!');
        } else {
            // Log the result and show error
            $this->logger->logErrorMessage('[database] Ping: Failed ('.trim($this->db->error).')');
            $this->dbConnected = false;
        }
    }

    // Generate random password
    public function generateRandomPassword($len = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
        $password = '';
        for ( $i = 0; $i < $len; $i++ ) {
            $password .= $chars[rand(0,strlen($chars)-1)];
        }
        return $password;
    }

    // Get clients
    public function getHoneypotClients( $limit = false ) {
        // Establish a connection to the database
        $this->dbMysqlConnect();

        if ( $this->dbConnected ) {
            // Prepare the query
            if ( $limit) {
                $query = "SELECT *,GREATEST(clients_seen_last, clients_seen_first) AS sortDate FROM honeypot_clients ORDER BY sortDate DESC LIMIT ".intval($limit);
            } else {
                $query = "SELECT *,GREATEST(clients_seen_last, clients_seen_first) AS sortDate FROM honeypot_clients ORDER BY sortDate DESC";
            }

            // Do the query
            $result = $this->dbMysqlRawQuery($query,true,false); // Query, Return sql resource, No logging

            // Check if we got a result
            if ( $result ) {
                $clients = array();
                while ( $row = mysqli_fetch_assoc($result) ) {
                    $clients[] = $row;
                }
                return $clients;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /// Function to set default www admin password if none exists
    public function setDefaultAdminPassword( $password = false ) {
        // Establish a connection to the database
        $this->dbMysqlConnect();

        if ( $this->dbConnected && $password ) {
            // Check if we have any users in the database
            $query = "SELECT id FROM www_users LIMIT 1";
            $result = $this->dbMysqlRawQuery($query,true,false); // Query, Return sql resource, No logging
            $rowcount = mysqli_num_rows($result);

            

            // If we have no users then we can set the default password
            if ( $rowcount == 0 ) {
                $query = "INSERT INTO www_users (users_username,users_password,users_fullname,users_email,users_role) VALUES (";
                $query .= "'admin',";
                $query .= "'".password_hash($password,PASSWORD_DEFAULT)."',";
                $query .= "'Administrator',";
                $query .= "'admin@localhost',";
                $query .= "'Admin'";
                $query .= ")";
                $adminID = $result = $this->dbMysqlRawQuery($query,false,false); // Query, No return sql resource, No logging
                $this->logger->logMessage('[database] Setting WEBUI "admin" password to: "'.$password.'"','WARNING');
                return $adminID;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // Check web login and return role
    public function checkWebLogin( $username = false, $password = false ) {
        // Establish a connection to the database
        $this->dbMysqlConnect();

        if ( $this->dbConnected && $username && $password ) {
            // Prepare the query
            $query = "SELECT * FROM www_users WHERE users_username = '".mysqli_real_escape_string($this->db,$username)."' LIMIT 1";

            // Do the query
            try {
                $result = $this->dbMysqlRawQuery($query,true,false); // Query, Return sql resource, No logging
            } catch (Exception $e) {
                $this->logger->logErrorMessage('[database] Exception catch: ' . trim($e->getMessage()));
                $result = false;
            }

            // Check if we got a result
            if ( $result ) {
                $row = mysqli_fetch_assoc($result);
                if ( $row ) {
                    // Check if password is correct
                    if ( password_verify($password,$row['users_password']) ) {
                        return $row;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // -- UPDATE BLACKLIST ENTRIES IF SEEN AGAIN
    public function blacklistUpdateEntry( $blacklist = false, $value = false ) {

        // Establish a connection to the database
        $this->dbMysqlConnect();

        if ( $this->dbConnected && $blacklist && $value ) {

            $table = false;
            $column = false;

            switch ( $blacklist ) {
                case 'ip':
                    $table   = 'acl_blacklist_ip';
                    $column  = 'ip_addr';
                    $counter = 'ip_conn_tries';
                    break;
                case 'geo':
                    $table   = 'acl_blacklist_geo';
                    $column  = 'geo_code';
                    $counter = 'geo_conn_tries';
                    break;
            }

            // If we have a table (and column) set and not empty then do
            if ( $table && $column ) {
                // Prepare the query
                $query = "UPDATE ".$table." SET ";
                $query .= $counter." = ".$counter." + 1 ";
                $query .= "WHERE ".$column." = '".mysqli_real_escape_string($this->db,$value)."' ";
                $query .= "LIMIT 1";

                // Do the query
                $this->dbMysqlRawQuery($query,false,false); // Query, No return sql resource, No logging
            }

        } 

    }

    // Update internal stats table
    public function updateStats( $table = false, $value = 1 ) {
        // Nothing yet, just preparing
    }

    // -- Save credentials
    public function saveCredentials( $username = '', $password = '', $type = "NONE" ) {
        // Establish a connection to the database
        $this->dbMysqlConnect();

        switch ( strtoupper($type) ) {
            case 'NONE':
                $enumType = 'NONE';
                break;
            case 'PLAIN':
                $enumType = 'PLAIN';
                break;
            case 'LOGIN':
                $enumType = 'LOGIN';
                break;
            default:
                $enumType = 'NONE';
                break;
        }

        // Build an array of credentials
        $credentials = array(
            'username'  => base64_encode($username),
            'password'  => base64_encode($password),
            'mechanism' => $enumType
        );
        $serialized = serialize($credentials);

        // Quick fix for some clients sending username and password in different charset
        // This will replace unknown chars with '?' and make the credential unusable but hey we don't crash!
        $username = mb_convert_encoding($username, 'UTF-8', 'UTF-8');
        $password = mb_convert_encoding($password, 'UTF-8', 'UTF-8');

        if ( $this->dbConnected && $username && $password && $type ) {
            // Prepare the query
            $query = "INSERT INTO honeypot_credentials (credentials_username,credentials_password,credentials_type,credentials_serialized_original) VALUES (";
            $query .= "'".mysqli_real_escape_string($this->db,$username)."',";
            $query .= "'".mysqli_real_escape_string($this->db,$password)."',";
            $query .= "'".mysqli_real_escape_string($this->db,$enumType)."',";
            $query .= "'".mysqli_real_escape_string($this->db,$serialized)."'";
            $query .= ")";

            // Do the query
            $id = $this->dbMysqlRawQuery($query,false,false); // Query, No return sql resource, No logging
        }
    }

    // -- INSERT RECIPIENT EMAIL ADDRESS into database
    private function insertRecipient( $email = false ) {

        // RCPT TO can contain extra SMTP commands after the email separated by space.
        // We only want the email address, so we split it by space and take the first part
        // But we can only explode on space if we have a space in the string check via if
        if ( strpos($email,' ') !== false ) {
            $email = explode(' ',$email);
            $email = $email[0];
            $email = trim($email);
        }
  
        // Try to split up email in username, tags and domain
        $emailParts = explode('@',$email);
        $emailUsername = $emailParts[0];
        $emailTags = explode('+',$emailUsername);
        $emailUsername = $emailTags[0];
        if ( count($emailTags) > 1 ) $emailTags = $emailTags[1];
        else $emailTags = '';
        $emailDomain = $emailParts[1];

        // Establish a connection to the database
        $this->dbMysqlConnect();

        if ( $this->dbConnected && $email ) {
            // Prepare the insert query on duplicate key update
            $query = "INSERT INTO honeypot_recipients (recipients_seen,recipients_address,recipients_username,recipients_tags,recipients_domain) VALUES (";
            $query .= "1,";
            $query .= "'".mysqli_real_escape_string($this->db,$email)."',";
            $query .= "'".mysqli_real_escape_string($this->db,$emailUsername)."',";
            $query .= "'".mysqli_real_escape_string($this->db,$emailTags)."',";
            $query .= "'".mysqli_real_escape_string($this->db,$emailDomain)."'";
            $query .= ")";
            $query .= " ON DUPLICATE KEY UPDATE recipients_seen = recipients_seen + 1";

            // Do the query (INSERT so it will return the ID of the row if it's a new one)
            $rowID = $this->dbMysqlRawQuery($query,false,false); // Query, No return sql resource, No logging
            return $rowID;
        }
    }
    
    // -- INSERT raw EMAIL
    public function insertRawEmail( $email = false ) {
        // Establish a connection to the database
        $this->dbMysqlConnect();

        if ( $this->dbConnected && $email ) {
            // Prepare the insert query on duplicate key update
            $query = "INSERT INTO honeypot_rawemails (rawemails_data) VALUES (";
            $query .= "'".mysqli_real_escape_string($this->db,$email)."'";
            $query .= ")";

            // Do the query (INSERT so it will return the ID of the row if it's a new one)
            $rowID = $this->dbMysqlRawQuery($query,false,false); // Query, No return sql resource, No logging
            return $rowID;
        }
    }

    // -- INSERT CLIENT IP
    // This should be used early on in the connection handler so we can relate to the associated inserted ID for the Client IP
    public function insertClientIP( $clientIP = false ) {
        // Establish a connection to the database
        $this->dbMysqlConnect();

        $clientIP = trim($clientIP);  // Not really needed, but easy way to inject/override for testing

        // Geo information
        if ( $this->meta->isGeoIPavailable() ) {
            $geo = $this->meta->getGeoIPMain($clientIP);
            if ( $geo ) {

                // Country part
                if ( array_key_exists('country',$geo) && array_key_exists('iso_code',$geo['country'] ) ) {
                    $countrycode = $geo['country']['iso_code'];
                    $countryname = $geo['country']['names']['en'];
                    if ( array_key_exists('is_in_european_union',$geo['country']) ) {
                        $euunion = $geo['country']['is_in_european_union'];
                    }
                }

                // City part
                if ( array_key_exists('city',$geo) && array_key_exists('names',$geo['city']) ) {
                    $cityname = $geo['city']['names']['en'];
                }

                // Postal part
                if ( array_key_exists('postal',$geo) && array_key_exists('code',$geo['postal']) ) {
                    $postalcode = $geo['postal']['code'];
                }

                // Subdevision part
                if ( array_key_exists('subdivisions',$geo) && array_key_exists('iso_code',$geo['subdivisions'][0]) ) {
                    $subdivisioncode = $geo['subdivisions'][0]['iso_code'];
                    $subdivisionname = $geo['subdivisions'][0]['names']['en'];
                }

                // Continent part
                if ( array_key_exists('continent',$geo) && array_key_exists('code',$geo['continent'] ) ) {
                    $continentcode = $geo['continent']['code'];
                    $continentname = $geo['continent']['names']['en'];
                }

                // Location part
                if ( array_key_exists('location',$geo) ) {
                    if ( array_key_exists('latitude',$geo['location']) && array_key_exists('longitude',$geo['location']) ) {
                        $latitude = $geo['location']['latitude'];
                        $longitude = $geo['location']['longitude'];
                    }
                    if ( array_key_exists('time_zone',$geo['location']) ) {
                        $timezone = $geo['location']['time_zone'];
                    }
                    if ( array_key_exists('accuracy_radius',$geo['location']) ) {
                        $accuracy = $geo['location']['accuracy_radius'];
                        if ( $accuracy == "" ) $accuracy = NULL;
                    }
                }

            }

            // ASN
            $asn = $this->meta->getGeoIPASN($clientIP);
            if ( $asn ) {
                if ( array_key_exists('autonomous_system_number',$asn) ) {
                    $asnumber = $asn['autonomous_system_number'];
                }
                if ( array_key_exists('autonomous_system_organization',$asn) ) {
                    $asname = $asn['autonomous_system_organization'];
                }
            }

        }

        if ( $this->dbConnected && $clientIP ) {
            // Prepare the insert query on duplicate key update
            $query = "INSERT INTO honeypot_clients (clients_seen,clients_ip,clients_hostname,clients_as_number,clients_as_name,clients_geo_country_code,clients_geo_country_name,clients_geo_continent,clients_geo_eu_union,clients_geo_city_name,clients_geo_city_postalcode,clients_geo_subdivisionname,clients_geo_latitude,clients_geo_longitude,client_location_accuracy_radius,clients_timezone) VALUES (";
            
            $query .= "1,";

            if ( isset($clientIP) ) $query .= "'".mysqli_real_escape_string($this->db,$clientIP)."',";
            else $query .= "NULL,";

            if ( isset($clientIP) ) $query .= "'".mysqli_real_escape_string($this->db,gethostbyaddr($clientIP))."',";
            else $query .= "NULL,";

            if ( isset($asnumber) ) $query .= "".mysqli_real_escape_string($this->db,$asnumber).",";
            else $query .= "NULL,";

            if ( isset($asname) ) $query .= "'".mysqli_real_escape_string($this->db,$asname)."',";
            else $query .= "NULL,";

            if ( isset($countrycode) ) $query .= "'".mysqli_real_escape_string($this->db,$countrycode)."',";
            else $query .= "NULL,";

            if ( isset($countryname) ) $query .= "'".mysqli_real_escape_string($this->db,$countryname)."',";
            else $query .= "NULL,";

            if ( isset($continentname) ) $query .= "'".mysqli_real_escape_string($this->db,$continentname)."',";
            else $query .= "NULL,";

            if ( isset($euunion) && $euunion ) $query .= "'Yes',";
            else $query .= "'No',";

            if ( isset($cityname) ) $query .= "'".mysqli_real_escape_string($this->db,$cityname)."',";
            else $query .= "NULL,";

            if ( isset($postalcode) ) $query .= "'".mysqli_real_escape_string($this->db,$postalcode)."',";
            else $query .= "NULL,";

            if ( isset($subdivisionname) ) $query .= "'".mysqli_real_escape_string($this->db,$subdivisionname)."',";
            else $query .= "NULL,";

            if ( isset($latitude) ) $query .= mysqli_real_escape_string($this->db,$latitude).",";
            else $query .= "NULL,";

            if ( isset($longitude) ) $query .= mysqli_real_escape_string($this->db,$longitude).",";
            else $query .= "NULL,";

            if ( isset($accuracy) ) $query .= "".mysqli_real_escape_string($this->db,$accuracy).",";
            else $query .= "NULL,";

            if ( isset($timezone) ) $query .= "'".mysqli_real_escape_string($this->db,$timezone)."'";
            else $query .= "NULL";

            $query .= ")";
            $query .= " ON DUPLICATE KEY UPDATE clients_seen = clients_seen + 1";

            // Do the query (INSERT so it will return the ID of the row if it's a new one)
            $_notused = $this->dbMysqlRawQuery($query,false,false); // Query, No return sql resource, No logging

            // We saw a client, so we should update the stats
            $this->updateStats('stats_total_clients');
        }
  
    }

    // -- INSERT ATTACHMENT
    public function insertAttachment( $emailID = false, $fields = [] , $doEscape = true ) {
        if ( $emailID ) {
            // Establish a connection to the database
            $this->dbMysqlConnect();


            // Get filename extention
            $ext = pathinfo($fields['filename'], PATHINFO_EXTENSION);

            // Check if filename is longer than 128 characters, if it is then make it shorter and add extention to not break it
            if ( strlen($fields['filename']) > 128 ) {
                $fields['filename'] = substr($fields['filename'],0,127-strlen($ext)).".".$ext;
            }

            // Check if mime type is longer than 50 characters
            if ( strlen($fields['type']) > 50 ) {
                $fields['type'] = substr($fields['type'],0,49);
            }

            if ( $this->dbConnected ) {
                // INSERT INTO honeypot_attachments (attachments_email,attachments_uuid,attachments_filename,attachments_size,attachments_mimetype,attachments_stored_path,attachments_stored,attachments_hash_md5,attachments_hash_sha1,attachments_hash_sha256)
                // VALUES (1,'1234567890','test.txt',1234,'text/plain','/path/to/file',1,'1234567890','1234567890','1234567890')
                $query = "INSERT INTO honeypot_attachments (attachments_email,attachments_uuid,attachments_filename,attachments_size,attachments_mimetype,attachments_stored_path,attachments_stored,attachments_hash_md5,attachments_hash_sha1,attachments_hash_sha256) VALUES (";
                $query .= intval($emailID).",";
                $query .= "'".mysqli_real_escape_string($this->db,$fields['uuid'])."',";
                $query .= "'".mysqli_real_escape_string($this->db,$fields['filename'])."',";
                $query .= intval($fields['size']).",";
                $query .= "'".mysqli_real_escape_string($this->db,$fields['type'])."',";
                $query .= "'".mysqli_real_escape_string($this->db,$fields['stored_path'])."',";
                $query .= "'".mysqli_real_escape_string($this->db,$fields['stored'])."',";
                $query .= "'".mysqli_real_escape_string($this->db,$fields['hashes']['md5'])."',";
                $query .= "'".mysqli_real_escape_string($this->db,$fields['hashes']['sha1'])."',";
                $query .= "'".mysqli_real_escape_string($this->db,$fields['hashes']['sha256'])."'";
                $query .= ")";
                
                // Do the query ( INSERT so it will return the ID of the row if it's a new one )
                $attachmentID = $this->dbMysqlRawQuery($query,false,false); // Query, No SQL resource return, No logging

            }
        }
    }

    // -- INSERT EMAIL PARTS via individual functions for each part
    public function insertEMAIL( $fields = [] , $doEscape = true ) {

        // Establish a connection to the database
        $this->dbMysqlConnect();

        if ( $this->dbConnected ) {

            // This is the finalFields array that will be used in the query
            $finalFields = [];

            // Rebuild smtp_recipients to be a json array from the array it contains
            // But by calling insertRecipient for each email address
            if ( array_key_exists('emails_recipients',$fields) ) {
                $recipients = $fields['emails_recipients']; // Make local copy so we can reset array
                $fields['emails_recipients'] = array();
                if ( $recipients && is_array($recipients) ) {
                    foreach ( $recipients as $recipient ) {
                        // Clean up if email is in < > or other characters
                        $recipient = trim($recipient);
                        $recipient = str_replace('<','',$recipient);
                        $recipient = str_replace('>','',$recipient);
                        $recipient = trim($recipient);
                        // Insert the recipient
                        $recipientID = $this->insertRecipient($recipient);
                        $fields['emails_recipients'][] = $recipientID;
                    }
                } else {
                    // Clean up if email is in < > or other characters
                    $recipient = trim($recipients);
                    $recipient = str_replace('<','',$recipient);
                    $recipient = str_replace('>','',$recipient);
                    $recipient = trim($recipient);
                    // Insert the recipient
                    $recipientID = $this->insertRecipient($recipient);
                    $fields['emails_recipients'][] = $recipientID;
                }
                
                $fields['emails_recipients'] = json_encode($fields['emails_recipients']);
            }

            // Rebuild smtp_attachments to be a json array from UUIDs it contains
            if ( array_key_exists('emails_attachments',$fields) ) {
                $attachments = $fields['emails_attachments']; // Make local copy so we can reset array
                $fields['emails_attachments'] = array();
                foreach ( $attachments as $attachment ) {
                    $fields['emails_attachments'][] = $attachment;
                }
                $fields['emails_attachments'] = json_encode($fields['emails_attachments']);
            }

            // Sanitize values from Fields to be the expected type or set it to something default
            // If all fails try to just use a default value as string
            foreach ( $fields as $key => $value ) {
                switch ( $key ) {
                    case 'emails_client_ip':
                    case 'emails_queue_id':
                    case 'emails_server_hostname':
                    case 'emails_server_listening':
                    case 'emails_server_system':
                    case 'emails_header_to':
                    case 'emails_header_from':
                    case 'emails_header_cc':
                    case 'emails_header_reply_to':
                    case 'emails_header_subject':
                    case 'emails_header_message_id':
                    case 'emails_header_xmailer':
                    case 'emails_header_useragent':
                    case 'emails_header_content_type':
                    case 'emails_header_content_transfer_encoding':
                    case 'emails_body_text':
                    case 'emails_body_html':
                        $fields[$key] = trim($value);
                        break;
                    case 'emails_client_port':
                    case 'emails_server_port':
                        $fields[$key] = intval($value);
                        break;
                    case 'emails_recipients':
                    case 'emails_header_received':
                    case 'emails_attachments':
                        $fields[$key] = $value;
                        break;
                    case 'emails_header_date':
                        if ( $value == "NOW()") $fields[$key] = "current_timestamp()";
                        else $fields[$key] = date('Y-m-d H:i:s',strtotime($value));
                        break;
                    default:
                        $fields[$key] = trim($value);
                        break;
                }
            }

            // Prepare query based on fields by escape if needed
            foreach ( $fields as $key => $value ) {
                // Detect if value is int, float or string
                if ( is_int($value) ) {
                    $finalFields[$key]['type'] = 'int';
                } elseif ( is_float($value) ) {
                    $finalFields[$key]['type'] = 'float';
                } else {
                    $finalFields[$key]['type'] = 'string';
                }
                $finalFields[$key]['value'] = $doEscape ? mysqli_real_escape_string($this->db,$value) : $value;
            }

            // If we have finalFields then do the query
            if ( count($finalFields) > 0 ) {
                $query = "INSERT INTO honeypot_emails (";
                $query .= implode(',',array_keys($finalFields));
                $query .= ") VALUES (";
                foreach ( $finalFields as $key => $value ) {
                    if ( $value['type'] === 'int' ) {
                        $query .= intval($value['value']);
                    } elseif ( $value['type'] === 'float' ) {
                        $query .= floatval($value['value']);
                    } else {
                        // Check if native mysql commands, should not be quoted
                        if ( $value['value'] == "current_timestamp()" ) {
                            $query .= $value['value'];
                        } else {
                            $query .= "'".$value['value']."'";
                        }
                    }
                    $query .= ",";
                }
                // Strip the last comma
                $query = rtrim($query,',');
                $query .= ")";

                // Do the query ( INSERT so it will return the ID of the row if it's a new one )
                $emailID = $this->dbMysqlRawQuery($query,false,false); // Query, No SQL resource return, No logging

                return $emailID;
            }

        }
    }

}
