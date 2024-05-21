<?PHP

namespace Controller;

// As we want to use GEO information if Geo ACL protection is loaded
// We need to prepare the Maxmind Reader - This does not load anything just make name referring easier
use MaxMind\Db\Reader AS MaxMindReader;

class Meta {

    private $config = false;
    private $logger;
    private $metaAvailable          = array(
        'geoip'     => false,
        'vt'        => false,
        'abuseipdb' => false,
        'otx'       => false
    );

    // Constructor
    public function __construct() {
        // Setup vendor logger
        $this->logger = new \Controller\Logger(basename(__FILE__,'.inc.php'),__NAMESPACE__);

        // Load config if not already loaded
        if ( $this->config === false ) {
            $this->config = $this->loadConfig();
        }

        // First make sure we have meta enabled (default false)
        $meta = trim($this->config['meta']['meta_enable']) == "1" ? true : false;
        if ( $meta ) {

            // Log about it
            $this->logger->logMessage('[meta] Meta services enabled');

            // Check what meta services we have enabled
            $geo       = trim($this->config['geoip']['geoip_enable']) == "1" ? true : false;
            $vt        = trim($this->config['vt']['vt_enable']) == "1" ? true : false;
            $abuseipdb = trim($this->config['abuseipdb']['abuseipdb_enable']) == "1" ? true : false;
            $otx       = trim($this->config['otx']['otx_enable']) == "1" ? true : false;

            // If enabled, then take KEY and assign the metaavaiable array
            if ( $geo ) {
                $this->metaAvailable['geoip'] = array();
                $this->metaAvailable['geoip']['main'] = trim($this->config['geoip']['geoip_main_file']);
                $this->metaAvailable['geoip']['asn']  = trim($this->config['geoip']['geoip_asn_file']);
                // Log about it
                $this->logger->logMessage('[meta] Integration: GeoIP enabled');
            }
            if ( $vt ) {
                $this->metaAvailable['vt'] = "";
                $this->metaAvailable['vt'] = trim($this->config['vt']['vt_key']);
                // Log about it
                $this->logger->logMessage('[meta] Integration: VirusTotal enabled');
            }
            if ( $abuseipdb ) {
                $this->metaAvailable['abuseipdb'] = "";
                $this->metaAvailable['abuseipdb'] = trim($this->config['abuseipdb']['abuseipdb_key']);
                // Log about it
                $this->logger->logMessage('[meta] Integration: AbuseIPDB enabled');
            }
            if ( $otx ) {
                $this->metaAvailable['otx'] = "";
                $this->metaAvailable['otx'] = trim($this->config['otx']['otx_key']);
                // Log about it
                $this->logger->logMessage('[meta] Integration: AlienVault OTX enabled');
            }

        } else {

            // Log about it
            $this->logger->logMessage('[meta] Meta services disabled');
        }

    }

    // Destructor
	public function __destruct() {
		// Nothing to do
	}

	// Load config file from etc
    private function loadConfig() {
        $config = parse_ini_file(__DIR__ . '/../../etc/meta.ini',true);
        return $config;
    }

    // Function to tell if GEOIP is available
    public function isGeoIPavailable() {
        return $this->metaAvailable['geoip'] ? true : false;
    }

    // Maxmind GEO IP (main) Lookup
    public function getGeoIPMain($ip) {
        if ( $this->metaAvailable['geoip'] ) {
            // Validate if IP
            if ( !filter_var($ip, FILTER_VALIDATE_IP) ) {
                return false;
            } else {
                $reader = new MaxMindReader($this->metaAvailable['geoip']['main']);
                $geoip = $reader->get($ip);
                $reader->close();
                return $geoip;
            }
        } else {
            return false;
        }
    }

    // Maxmind GEO IP (ASN) Lookup
    public function getGeoIPASN($ip) {
        if ( $this->metaAvailable['geoip'] ) {
            // Validate if IP
            if ( !filter_var($ip, FILTER_VALIDATE_IP) ) {
                return false;
            } else {
                $reader = new MaxMindReader($this->metaAvailable['geoip']['asn']);
                $geoip = $reader->get($ip);
                $reader->close();
                return $geoip;
            }
        } else {
            return false;
        }
    }

}

?>