<?PHP

//Import Maxmind classes into the global namespace
//These must be at the top of your script, not inside a function
use MaxMind\Db\Reader AS MaxMindReader;

// DONT FORGET TO INSTALL MAXMIND PHP Library
// (DEBIAN) apt-get install php-maxminddb php8.2-maxminddb

//require_once 'vendor/autoload.php';

$ipAddress        = '178.162.203.202';                           // IP to test
$databaseFileCity = '/var/lib/GeoIP/GeoLite2-City.mmdb';        // To your Maxmind mmdb v2 file
$databaseFileASN  = '/var/lib/GeoIP/GeoLite2-ASN.mmdb';         // To your Maxmind mmdb v2 file

$reader1 = new MaxMindReader($databaseFileCity);
$reader2 = new MaxMindReader($databaseFileASN);

// Get returns just the record for the IP address
print_r($reader1->get($ipAddress));
print_r($reader2->get($ipAddress));

// Free up the resources
$reader1->close();
$reader2->close();

?>