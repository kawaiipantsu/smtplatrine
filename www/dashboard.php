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

// Check if loggedin session variable is not set then redirect to index.php
if ( !isset($_SESSION['loggedin']) ) {
    header("Location: /");
    exit();
}

// Monitor mode dashboard
$monitorMode = false;
if ( array_key_exists("monitor",$_GET ) ) {
    $monitorMode = true;
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
            background-color: #021B2A;
            filter: brightness(1.2);
            <?PHP if ( $monitorMode) { ?>
            overflow-y: hidden; /* Hide vertical scrollbar */
            overflow-x: hidden; /* Hide horizontal scrollbar */
            <?PHP } else { ?>
            overflow-y:scroll;
            overflow-x:hidden;
            <?PHP } ?>
        }

        body {
            <?PHP if ( !$monitorMode) { ?>
            background-image: url("/assets/images/smtplatrine_cover.png"); 
            background-origin: border-box;
            background-repeat: no-repeat;
            background-attachment: fixed; 
            background-position: top;
            background-blend-mode: difference;
            background-size: 360px;
            padding-top: 80px;
            <?PHP } else { ?>

            <?PHP } ?>
            color: #301934;
            font-size: 11pt;
            font-family: 'Verdana', sans-serif;
        }
        .parent {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .child {
            width: 1600px;
            text-align: left;
            padding: 15px;
            font-weight: bold;
        }
        .footer {
            font-size: 8pt;
            color: #999999;
            text-align: center;
            <?PHP if ( $monitorMode) echo "display: none;\n"; ?>
        }
        fieldset {
            font-family: sans-serif;
            border: 5px solid #1F497D;
            background: #ddd;
            border-radius: 5px;
            padding: 15px;
            width: 100%;
        }

        fieldset legend { 
            background-color: #1F497D;
            color: #fff;
            padding: 10px 50px;
            font-size: 14pt;
            border-radius: 5px;
            box-shadow: 0 0 0 5px #ddd;
            margin-left: 10px;
            margin-bottom: 10px;
            width: 300px;
            <?PHP if ( $monitorMode) echo "display: none;\n"; ?>
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
        .form-menu-button:hover {
            background: #016;
            color: #fff;
            border: 1px solid #eee;
            border-radius: 5px;
            box-shadow: 5px 5px 5px #eee;
            text-shadow: none;
            height: 35px;
            width: 100%;
        }
        .form-menu-button {
            background: #016ABC;
            color: #fff;
            border: 1px solid #eee;
            border-radius: 5px;
            box-shadow: 5px 5px 5px #eee;
            text-shadow: none;
            height: 35px;
            width: 100%;
        }
        .menu {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            width: 100%;
        }
        .content {
            border: 2px solid #1F497D;
        }
        .main {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 600px;
            width: 100%;
        }
        .tablecontainer {
            width: 1520px;
            height: 600px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 10px;
            padding-right: 10px;
            overflow-y: scroll;
        }

        h2 {
            font-size: 26px;
            margin: 20px 0;
            text-align: center;
        }
        h2 small {
            font-size: 0.5em;
        }
        .responsive-table li {
            border-radius: 5px;
            padding: 5px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .responsive-table .table-header {
            color: #fff;
            background-color: #1F497D;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            position: absolute;
            width: 1495px;
        }
        .responsive-table .table-row {
            background-color: #ffffff;
            -webkit-box-shadow: 3px 3px 8px 1px rgba(0,0,0,0.14); 
            box-shadow: 3px 3px 8px 1px rgba(0,0,0,0.14);
        }
        .col {

        }
        .responsive-table .col-1 {
            flex-basis: 200px;
            text-align: center;
        }
        .responsive-table .col-2 {
            flex-basis: 150px;
        }
        .responsive-table .col-3 {
            flex-basis: 350px;
        }
        .responsive-table .col-4 {
            flex-basis: 415px;
        }
        .responsive-table .col-5 {
            flex-basis: 50px;
            text-align: center;
        }
        .responsive-table .col-6 {
            flex-basis: 200px;
        }
        .responsive-table .col-7 {
            flex-basis: 130px;
            text-align: center;
        }
        
        @media all and (max-width: 767px) {
            .responsive-table .table-header {
                display: none;
            }
            .responsive-table li {
                display: block;
            }
            .responsive-table .col {
                flex-basis: 100%;
            }
            .responsive-table .col {
                display: flex;
                padding: 10px 0;
            }
            .responsive-table .col:before {
                color: #6C7A89;
                padding-right: 10px;
                content: attr(data-label);
                flex-basis: 50%;
                text-align: right;
            }
        }
        .flag {
            -webkit-box-shadow: 5px 5px 8px 1px rgba(0,0,0,0.14); 
            box-shadow: 5px 5px 8px 1px rgba(0,0,0,0.14);
            border-radius: 0px;
            width: 25px;
            height: 15px;
        }
        .faded {
            color: #808080;
        }
    </style>
</head>
<body>
    <div class="parent">
        <div class="child">
            <fieldset>
                <legend>📊 Dashboard</legend>
                <p>👤 Welcome back, <?PHP echo $_SESSION['username']; ?>!</p>
                <div class="menu">
                    
                        <input type="button" value="📊 Dashboard" onclick="window.location.href='/dashboard.php'" class="form-menu-button" />
                        <input type="button" value="📬 E-Mails overview" onclick="window.location.href='/overview.php?emails'" class="form-menu-button" />
                        <input type="button" value="📧 Recipients overview" onclick="window.location.href='/overview.php?recipients'" class="form-menu-button" />
                        <input type="button" value="🔐 Credentials overview" onclick="window.location.href='/overview.php?credentials'" class="form-menu-button" />
                        <input type="button" value="📂 Attachments overview" onclick="window.location.href='/overview.php?attachments'" class="form-menu-button" />
                        <input type="button" value="🌐 Clients overview" onclick="window.location.href='/overview.php?clients'" class="form-menu-button" />
                        <input type="button" value="✉️ Raw emails" onclick="window.location.href='/'" class="form-menu-button" />
                        <input type="button" value="🛡️ Blacklist control" onclick="window.location.href='/'" class="form-menu-button" />
                        <input type="button" value="🔑 Logout" onclick="window.location.href='/?logout'" class="form-menu-button" />
                    </div>
                <div class="content">
                    
                    <br/>
                    <div class="main">
                    This is in it's very early stages of development, please check back later.
                    </div>
                </div>
            </fieldset>
            <div class="footer">
                <br/>
                <p>A custom SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work</p>
            </div>
        </div>
    </div>
</body>
</html>