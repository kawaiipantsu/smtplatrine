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
if ( !isset($_SESSION['loggedin'])$_SESSION['loggedin'] === false ) {
    header("Location: /");
    exit();
}

// Check if loggedin session variable IS false - Boot em out
if ( $_SESSION['loggedin'] === false ) {
    header("Location: /");
    exit();
}

// Monitor mode dashboard
$monitorMode = false;
if ( array_key_exists("monitor",$_GET ) ) {
    $monitorMode = true;
}

// Set active array based on GET parameters
switch (true) {
    case array_key_exists("emails",$_GET):
        $active = array("emails" => "active");
        break;
    case array_key_exists("recipients",$_GET):
        $active = array("recipients" => "active");
        break;
    case array_key_exists("credentials",$_GET):
        $active = array("credentials" => "active");
        break;
    case array_key_exists("attachments",$_GET):
        $active = array("attachments" => "active");
        break;
    case array_key_exists("clients",$_GET):
        $active = array("clients" => "active");
        break;
    case array_key_exists("raw",$_GET):
        $active = array("raw" => "active");
        break;
    case array_key_exists("blacklist",$_GET):
        $active = array("blacklist" => "active");
        break;
    case array_key_exists("logout",$_GET):
        $active = array("logout" => "active");
        break;
    default:
        $active = array("dashboard" => "active");
        break;
}

echo "<pre>";
print_r($_SESSION);
echo "</pre>";

?><!doctype html>
<html lang="en" />
<head>
    <meta charset="utf-8" />
    <title>SMTPLATRINE</title>
    <meta name="description" content="A custom SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work." />
    <meta name="robots" content="index, follow" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="author" content="THUGS(red)" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="THUGS(red)" />
    <meta property="og:title" content="SMTPLATRINE" />
    <meta property="og:url" content="https://smtplatrine.thugs.red/" />
    <meta property="og:description" content="A custom SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work." />
    <meta property="og:image" content="/assets/images/smtplatrine_logo.png" />
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <link rel="stylesheet" href="/assets/css/main.css">


    <script src="/assets/js/main.js"></script>
</head>
<body>
    <div class="parent">
        <div class="content">
            <div id="menu">
                <div class="logo"></div>
                <div class="topnav" id="myTopnav">
                <a href="/dashboard.php" class="<?PHP echo $active["dashboard"]; ?>">📊 Dashboard</a>
                <a href="/dashboard.php?emails" class="<?PHP echo $active["emails"]; ?>">📬 E-Mails</a>
                <a href="/dashboard.php?recipients" class="<?PHP echo $active["recipients"]; ?>">📧 Recipients</a>
                <a href="/dashboard.php?credentials" class="<?PHP echo $active["credentials"]; ?>">🔐 Credentials</a>
                <a href="/dashboard.php?attachments" class="<?PHP echo $active["attachments"]; ?>">📂 Attachments</a>
                <a href="/dashboard.php?clients" class="<?PHP echo $active["clients"]; ?>">🌐 Clients</a>
                <a href="/dashboard.php?raw" class="<?PHP echo $active["raw"]; ?>">✉️ Raw</a>
                <a href="/dashboard.php?blacklist" class="<?PHP echo $active["blacklist"]; ?>">🛡️ Blacklist</a>
                <a href="/?logout" class="<?PHP echo $active["logout"]; ?>">🔑 Logout</a>
                <a href="javascript:void(0);" class="icon" onclick="showMenu()">
                    <i class="fa fa-bars"></i>
                </a>
                </div>
            </div>
            <div class="main">

<?PHP
 if ( array_key_exists("clients",$_GET) ) {
?>
<div class="table100 ver3 m-b-110">
<div class="table100-head">
<table id="table">
<thead>
<tr class="row100 head">
<th class="cell100 column1 showsort">Last seen <i class="fa-solid fa-arrow-down-short-wide"></i></th>
<th class="cell100 column2 hidesort">IP address <i class="fa-solid fa-arrow-down-short-wide"></i></th>
<th class="cell100 column3 hidesort">Reverse DNS <i class="fa-solid fa-arrow-down-short-wide"></i></th>
<th class="cell100 column4 hidesort">Autonomous System (AS) name <i class="fa-solid fa-arrow-down-short-wide"></i></th>
<th class="cell100 column5 hidesort">Flag <i class="fa-solid fa-arrow-down-short-wide"></i></th>
<th class="cell100 column6 hidesort">Country <i class="fa-solid fa-arrow-down-short-wide"></i></th>
<th class="cell100 column7 hidesort">Conn seen <i class="fa-solid fa-arrow-down-short-wide"></i></th>
</tr>
</thead>
</table>
</div>
<div class="table100-body js-pscroll ps ps--active-y">
<table id="datatable">
<tbody>

<?PHP
$clients = $db->getHoneypotClients();
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

?>

<tr class="row100 body">
    <td class="cell100 column1"><?=htmlentities($seen)?></td>
    <td id="<?=htmlentities($clientip)?>" class="clientclick cell100 column2 more"><?=htmlentities($clientip)?></td>
    <td id="<?=htmlentities($clientip)?>" class="clientclick cell100 column3"><?=htmlentities($clienthost)?></td>
    <td class="cell100 column4"><?=htmlentities($clientas)?></td>
    <td class="cell100 column5"><?=$flagimg?></td>
    <td class="cell100 column6"><?=htmlentities($clientcountry)?></td>
    <td class="cell100 column7"><?=htmlentities($numseen)?></td>
    <div  class="clientdiv">
        <div id="client_<?=htmlentities($clientip)?>" class="clientinfo">
        test 123
        </div>
    </div>
</tr>

<?PHP
}
?>
</tbody>
</table>
</div>


<?PHP
 }
?>
            </div>
        </div>
    </div>
    <div class="footer">A custom SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work</div>
</body>
</html>