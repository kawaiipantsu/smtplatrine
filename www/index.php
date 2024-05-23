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


    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }
        html {
            height: 100%;
            font-family: sans-serif;
            overflow-y: hidden; /* Hide vertical scrollbar */
            overflow-x: hidden; /* Hide horizontal scrollbar */
        }
        
        body { 
            background-image: url("/assets/images/smtplatrine_cover.png"); 
            background-repeat: no-repeat; 
            background-attachment: fixed; 
            background-position: top; 
            background-size: 660px; 

            height: 100%;
            font-family: "Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            background-color: #3c373e;

            overflow-y: hidden; /* Hide vertical scrollbar */
            overflow-x: hidden; /* Hide horizontal scrollbar */
            color: rgba(255, 255, 255, 0.5);
        }
        @media screen and (max-width: 768px) {
            body { 
                background-size: 100%;
                background-position: center; 
            }
            fieldset {
                display: none;
            }
        }
        p {
        color: rgba(255, 255, 255, 0.5);
        font-weight: 300; }

        h1, h2, h3, h4, h5, h6,
        .h1, .h2, .h3, .h4, .h5, .h6 {
        font-family: "Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"; }

        a {
        -webkit-transition: .3s all ease;
        -o-transition: .3s all ease;
        transition: .3s all ease; }
        a, a:hover {
            text-decoration: none !important; }

        .content {
        padding: 7rem 0; }

        h2 {
        font-size: 20px;
        color: #fff; }
        .custom-table {
            min-width: 900px; 
        }
        .custom-table thead tr, .custom-table thead th {
    padding-bottom: 30px;
    border-top: none;
    border-bottom: none !important;
    color: #fff;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .2rem; }

    .custom-table tbody th, .custom-table tbody td {
    color: #777;
    font-weight: 400;
    padding-bottom: 20px;
    padding-top: 20px;
    font-weight: 300;
    border: none;
    -webkit-transition: .3s all ease;
    -o-transition: .3s all ease;
    transition: .3s all ease; }
    .custom-table tbody th small, .custom-table tbody td small {
      color: rgba(255, 255, 255, 0.3);
      font-weight: 300; }
    .custom-table tbody th a, .custom-table tbody td a {
      color: rgba(255, 255, 255, 0.3); }
    .custom-table tbody th .more, .custom-table tbody td .more {
      color: rgba(255, 255, 255, 0.3);
      font-size: 11px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: .2rem; }
      .custom-table tbody tr:hover td, .custom-table tbody tr:focus td {
      color: #fff; }
      .custom-table tbody tr:hover td a, .custom-table tbody tr:focus td a {
        color: #fdd114; }
      .custom-table tbody tr:hover td .more, .custom-table tbody tr:focus td .more {
        color: #fdd114; }
        fieldset {
            font-family: sans-serif;
            border: 5px solid #1b1e20;
            background: #212529;
            border-radius: 5px;
            padding: 15px;
            width: 800px;
            text-align: center;
        }

        fieldset legend {
            border-width: 5px;
            border-radius: 5px;
            border-style: solid;
            color: #bfbfbf;
            border-image: linear-gradient(180deg, #1b1e20, #212529) 1;
            background: rgb(27,30,32);
            background: linear-gradient(180deg, rgba(27,30,32,1) 0%, rgba(38,42,47,1) 70%, rgba(33,37,41,1) 100%);
            padding: 10px;
            font-size: 14pt;
            margin-bottom: 30px;
            margin-top: -30px;
            display: block;
            margin-left: 90px;
            width: 600px;
            text-align: center;
        }
        .parent {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .child {
            padding: 15px;
            font-weight: bold;
            position: absolute;
            top: 40%;
        }
        .footer {
            position: absolute;
            width: 100%;
            text-align: center;
            bottom: 0px;
            background-color: rgba(0,0,0,0.5);
            font-size: 10pt;
            padding: 10px;
            color: #999999;
        }

        input[type="text"], input[type="password"] {
            padding: 4px;
            padding-left: 10px;
            border-radius: 5px;
            border: 1px solid #000;
            background-color: #303639;
            color: #fff;
            margin: 2px;
            width: 300px;
        }
        .form-submit-button:hover {
            text-align: center;
            border-width: 4px;
            border-radius: 5px;
            border-style: solid;
            border-image: linear-gradient(0deg, #1b1e20, #212529) 1;
            color: #bfbfbf;
            background: rgb(33,37,41);
            background: linear-gradient(180deg, rgba(33,37,41,1) 0%, rgba(53,58,61,1) 50%, rgba(33,37,41,1) 100%);
            color: #FFF;
            text-shadow: none;
            height: 50px;
            width: 70%;
        }
        .form-submit-button {
            text-align: center;
            border-width: 4px;
            border-radius: 5px;
            border-style: solid;
            border-image: linear-gradient(0deg, #1b1e20, #212529) 1;
            color: #bfbfbf;
            background: rgb(33,37,41);
            background: linear-gradient(180deg, rgba(33,37,41,1) 0%, rgba(53,58,61,0.2805497198879552) 50%, rgba(33,37,41,1) 100%);
            text-shadow: none;
            height: 50px;
            width: 70%;
        }
        label {
            color: #bfbfbf;
            padding-right: 50px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="parent">
        <div class="child">
        <form action="/index.php?login" method="POST">
        <section style="margin: 10px;">
        <fieldset style="border-radius: 5px; padding: 5px; min-height:140px;">
                <legend>💩 Latrine poop analyst login</legend>
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
