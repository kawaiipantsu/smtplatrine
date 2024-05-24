<?PHP

// Start a session
session_start();

// Check if some session variables already exists if not then set default values
if ( !isset($_SESSION['loggedin']) ) {
    $_SESSION['loggedin'] = false;
    $_SESSION['username'] = NULL;
    $_SESSION['password'] = NULL;
    $_SESSION['role'] = NULL;
}

// Require the autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Setup logging for the main application
$logger = new \Controller\Logger("webpage-index",__NAMESPACE__);

// Load up Database functionality
$db = new \Controller\Database;

// Log debug about index page being loaded
$logger->logDebugMessage("Index page loaded by ".$_SERVER['REMOTE_ADDR']);

// Check for logout
if ( array_key_exists("logout",$_GET ) ) {
    $_SESSION['loggedin'] = false;
    $_SESSION['username'] = NULL;
    $_SESSION['password'] = NULL;
    $_SESSION['role'] = NULL;
    session_destroy();
    header("Location: /");
    exit();
}

// Check if the user is trying to login via the POST global array to see if the key "login" exits
if ( array_key_exists("login",$_GET ) ) {

        // Check via array key exists if the username and password fields are set
        if ( array_key_exists("username",$_POST) && array_key_exists("password",$_POST) ) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $row = $db->checkWebLogin($username,$password);

            // Set session variables if the user is found in the database
            if ( $row ) {
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $row['users_username'];
                $_SESSION['password'] = $row['users_password'];
                $_SESSION['role']     = $row['users_role'];
                $logger->logMessage("User ".$username." logged in from ".$_SERVER['REMOTE_ADDR'],'NOTICE');
                header("Location: /index.php?dashboard");
                exit();
            } else {
                $logger->logMessage("Failed login attempt from ".$_SERVER['REMOTE_ADDR'],'WARNING');
                header("Location: /");
            }

        } else {
            header("Location: /");
        }
}

// Only ever get to the below code if the user is not logged in and the session is not set
if ($_SESSION['loggedin']) {
    switch (true) {

        case array_key_exists("dashboard",$_GET):
        default:
            header("Location: /dashboard.php");
            break;
    }
    exit();
}

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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <link rel="stylesheet" type="text/css" href="/assets/css/index.css">
</head>
<body>
    <div class="parent">
        <div class="child">
        <form action="/index.php?login" method="POST">
        <section style="margin: 10px;">
        <fieldset style="border-radius: 5px; padding: 5px; min-height:140px;">
                <legend>💩 SMTP Latrine poop analyst login</legend>
                <label for="username">Username</label>
                <input type="text" name="username" placeholder="Username" required><br/></br>
                <label for="password">Password</label>
                <input type="password" name="password" placeholder="Password" required><br/></br>
                <input class="form-submit-button" type="submit" value="Start analyzing the poop!">
                </br></br>
            </fieldset>
        </section>
        </form>
        </div>
    </div>
    <div class="footer">A custom SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work</div>
</body>
</html>
