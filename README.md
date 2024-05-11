# ᵔᴥᵔ SMTPLATRINE

[![Twitter Follow](https://img.shields.io/twitter/follow/davidbl.svg?style=social&label=Follow)](https://twitter.com/davidbl) [![GitHub issues](https://img.shields.io/github/issues/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/issues) [![GitHub closed issues](https://img.shields.io/github/issues-closed/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/issues) [![GitHub license](https://img.shields.io/github/license/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/blob/master/LICENSE) [![GitHub forks](https://img.shields.io/github/forks/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/network) [![GitHub stars](https://img.shields.io/github/stars/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/stargazers)
> SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work

![smtplatrine](www/assets/images/smtplatrine_cover.png)

---

## Join the community

Join the community of Kawaiipantsu / THUGS(red) and participate in the dev talk around Redjoust or simply just come visit us and chat about anything security related :) We love playing around with security. Also we have ctf events and small howto events for new players.

**THUGS(red) Discord**: <https://discord.gg/Xg2jMdvss9>

> This is still in a very early stage of development!

## Current state

Following things work as of now:

- Sockets / Server Listner
- Threaded SocketClient handler
- SMTP Honeypot functionality
  - Tested via nmap openrelay script - Says open relay :)
  - Toggle compliance (ie. EHLO/HELO first) and so on
  - Handle DATA and End

The following is up next:

- Database connection
- Storing data

## What is smtplatrine?

When you work in security and specially with doing forensic work or hunting threat actors. One of the big things that you need is "data" and one of the best ways to get new or unknown malicious data is via honeypots. A honeypot will emulate a real world service (in this case SMTP) and then the user (threat actor) will believe they are using a real service and try to exploit or use it for criminal shenanigans :)

All while they are exploring the service trying to do their thing - All we do is to log all and everything about them and what they try.

That is roughly what a honeypot is!

In this case i'm aiming to collect the following data:

- Threat Actors IP Address
  - Meta via: VT, AbuseIPDB, OTX
  - GeoIP
- Threat Actors SMTP Transactions
  - HELO/EHLO hostname
  - AUTH credentials
  - Identities
    - Return-Path email
    - Delivered-To email(s)
    - Reply-To email(s)
    - From email
    - To email(s)
    - Cc email(s)
    - Bcc email(s)
  - Received flow headers
  - Authentication headers
  - Abuse headers
  - Custom Headers
  - Message-ID
  - Subject
  - Body
    - HTML version
    - Text version
  - Attachments

Everything will then be stored in a database (mysql) for further indexing and analysis/forensic work - But also to use for showing simple stats on the webpage etc.