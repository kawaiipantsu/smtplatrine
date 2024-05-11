<?PHP

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
    </style>
</head>
<body>
    <div class="parent">
        <div class="child">A custom SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work</div>
    </div>
</body>
</html>
