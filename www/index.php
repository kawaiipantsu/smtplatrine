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

    <style>
        html, body, div, span, applet, object, iframe,
        h1, h2, h3, h4, h5, h6, p, blockquote, pre,
        a, abbr, acronym, address, big, cite, code,
        del, dfn, em, img, ins, kbd, q, s, samp,
        small, strike, strong, sub, sup, tt, var,
        b, u, i, center,
        dl, dt, dd, ol, ul, li,
        fieldset, form, label, legend,
        table, caption, tbody, tfoot, thead, tr, th, td,
        article, aside, canvas, details, embed, 
        figure, figcaption, footer, header, hgroup, 
        menu, nav, output, ruby, section, summary,
        time, mark, audio, video {
        margin: 0;
        padding: 0;
        border: 0;
        font-size: 100%;
        font: inherit;
        vertical-align: baseline;
        }

        html { 
            background-image: url("/assets/images/smtplatrine_cover.png"); 
            background-repeat: no-repeat; 
            background-attachment: fixed; 
            background-position: center; 
            background-size: 60%; 
            background-color: #021B2A;
        }
        @media screen and (max-width: 768px) {
            html { 
                background-size: 100%;
                background-position: center; 
            }
        }

        body {
            overflow-y: hidden; /* Hide vertical scrollbar */
            overflow-x: hidden; /* Hide horizontal scrollbar */
            color: #999999;
            font-family: 'Verdana', sans-serif;
        }
        fieldset {
            font-family: sans-serif;
            border: 5px solid #1F497D;
            background: #ddd;
            border-radius: 5px;
            padding: 15px;
        }

        fieldset legend {
            background: #1F497D;
            color: #fff;
            padding: 5px 15px ;
            font-size: 12pt;
            border-radius: 5px;
            box-shadow: 0 0 0 5px #ddd;
            margin-left: 10px;
            margin-bottom: 10px;
        }
        .parent {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .child {
            text-align: center;
            padding: 15px;
            font-weight: bold;
            position: absolute;
            bottom: 5px;
        }
        .footer {
            font-size: 8pt;
            color: #999999;
        }

        input[type="text"], input[type="password"] {
            padding: 4px;
            border-radius: 5px;
            border: 1px solid #1F497D;
            margin: 2px;
        }
        .form-submit-button:hover {
            background: #016;
            color: #fff;
            border: 1px solid #eee;
            border-radius: 5px;
            box-shadow: 5px 5px 5px #eee;
            text-shadow: none;
            height: 35px;
            width: 70%;
        }
        .form-submit-button {
            background: #016ABC;
            color: #fff;
            border: 1px solid #eee;
            border-radius: 5px;
            box-shadow: 5px 5px 5px #eee;
            text-shadow: none;
            height: 35px;
            width: 70%;
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
                <label for="username">Username:</label>
                <input type="text" name="username" placeholder="Username" required><br/></br>
                <label for="password">Password:</label>
                <input type="password" name="password" placeholder="Password" required><br/></br>
                <input class="form-submit-button" type="submit" value="Start analyzing the poop!">
            </fieldset>
        </section>
        </form>
    
        <br/>
        <p class="footer">A custom SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work</p></div>
    </div>
</body>
</html>
