<?PHP

// Start a session
session_start();

// Require the autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Setup logging for the main application
$logger = new \Controller\Logger("webpage-index",__NAMESPACE__);

// Load up Database functionality
$db = new \Controller\Database;

// Log debug about index page being loaded
$logger->logDebugMessage("Dashboard page loaded by ".$_SERVER['REMOTE_ADDR']);

// Check if loggedin session variable is not set - Boot em out
if ( isset($_SESSION['loggedin']) === false ) {
    header("Location: /");
    exit();
}

// Check if loggedin session variable IS false - Boot em out
if ( $_SESSION['loggedin'] === false ) {
    header("Location: /");
    exit();
}

if ( array_key_exists("clientData",$_GET ) ) {

    $clients = $db->getHoneypotClients();
    $dataArray = array();
    foreach ($clients as $row) {

        if ( $row["clients_seen_last"] != "0000-00-00 00:00:00" ) $seen = $row["clients_seen_last"];
        else $seen = $row["clients_seen_first"];
        $clientip = $row["clients_ip"];
        if ( $clientip == "" ) $clientip = "-";
        $clienthost = $row["clients_hostname"];
        if ( $clienthost == "" ) $clienthost = "-";
        $clientas = $row["clients_as_name"];
        if ( $clientas == "" ) $clientas = "-";
        $clientflag = $row["clients_geo_country_code"];
        $clientcountry = $row["clients_geo_country_name"];
        if ( $clientcountry == "" ) $clientcountry = "-";
        if ( $clientflag != "" ) $flagimg = "<img class=\"flag\" src='/assets/images/flags/".strtolower($clientflag).".png' alt='".$clientflag."' />";
        else $flagimg = "<img class=\"flag\" src='/assets/images/flags/unknown.png' alt='Unknown' />";
        $numseen = $row["clients_seen"];

        $dataArray[] = array(
            "date" => $seen,
            "client-ip" => $clientip,
            "client-host" => $clienthost,
            "client-as-name" => $clientas,
            "country-flag" => $flagimg,
            "country-name" => $clientcountry,
            "client-seen" => $numseen
        );
    }

    // JSON header
    header('Content-Type: application/json');
    echo json_encode($dataArray);

    exit();
}




?>