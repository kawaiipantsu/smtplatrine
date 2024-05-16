# ᵔᴥᵔ SMTPLATRINE - A SMTP Honeypot

[![Twitter Follow](https://img.shields.io/twitter/follow/davidbl.svg?style=social&label=Follow)](https://twitter.com/davidbl) [![GitHub issues](https://img.shields.io/github/issues/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/issues) [![GitHub closed issues](https://img.shields.io/github/issues-closed/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/issues) [![GitHub license](https://img.shields.io/github/license/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/blob/master/LICENSE) [![GitHub forks](https://img.shields.io/github/forks/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/network) [![GitHub stars](https://img.shields.io/github/stars/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/stargazers)
> SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work

![smtplatrine](www/assets/images/smtplatrine_cover.png)

---

> 🚨 **ALERT**  
> Only run a honeypot if you know what you are doing!

---

## 🗃️ Table of contents

<!-- TOC updateonsave:false -->

- [ᵔᴥᵔ SMTPLATRINE - A SMTP Honeypot](#%E1%B5%94%E1%B4%A5%E1%B5%94-smtplatrine---a-smtp-honeypot)
    - [🗃️ Table of contents](#-table-of-contents)
    - [📧 What is smtplatrine?](#-what-is-smtplatrine)
        - [Features](#features)
        - [Why?](#why)
    - [☑️ Requirements](#-requirements)
    - [🪄 How to install](#-how-to-install)
        - [Quick and dirty Debian 12](#quick-and-dirty-debian-12)
        - [Manual install](#manual-install)
    - [💡 How to run](#-how-to-run)
    - [⚙️ Configuration](#-configuration)
        - [Config file: database.ini](#config-file-databaseini)
        - [Config file: logger.ini](#config-file-loggerini)
        - [Config file: meta.ini](#config-file-metaini)
        - [Config file: server.ini](#config-file-serverini)
    - [🫶 META osint Integrations](#-meta-osint-integrations)
        - [VirusTotal](#virustotal)
        - [AbuseIPDB](#abuseipdb)
        - [AlienVault OTX](#alienvault-otx)
        - [Ideas for other osint services to query](#ideas-for-other-osint-services-to-query)
    - [📐 RFC-5321  Simple Mail Transfer Protocol  compliance](#-rfc-5321--simple-mail-transfer-protocol--compliance)
    - [🎱 Tests performed](#-tests-performed)
        - [Test of mail clients/software against the honeypot](#test-of-mail-clientssoftware-against-the-honeypot)
    - [💣 Security concerns and safety issues!](#-security-concerns-and-safety-issues)
    - [😬 Running a "open-relay" SMTP server honeypot or not](#-running-a-open-relay-smtp-server-honeypot-or-not)
    - [📑 References / Links to external sites](#-references--links-to-external-sites)

<!-- /TOC -->

---

## 📧 What is smtplatrine?

It's a **HONEYPOT** !

> In computer terminology, a honeypot is a computer security mechanism set to detect, deflect, or, in some manner, counteract attempts at unauthorized use of information systems

When it comes to SMTP honeypots it's also a cool way to actually see what data is flowing out there in terms of "leaked" mail lists, data breaches, sold darkweb emails etc. But also to see credentials or to collect attachment that might contain malware or other new exploits you can analyze!

I always do a bit of testing via emil clients, smtp sending scripts and other scanning tools to see if things work as expected. Take a look under [Test of mail clients/software against the honeypot](#test-of-mail-clientssoftware-against-the-honeypot)

### Features

I have tied to make this a very feature rich smtp honeypot - Or at least that's what i tell myself. But i do really believe that it's cool. But that might just be me. It's also mostly for my own sake as i love to hunt for bad guys and ne exploits and that requires lots of data and ways to index/search it :)

**The following things is supported by smtplatrine as of today!**

- Collecting RAW email (Stored in DB)
- Collecting all recipients (Stored in DB)
  - Full email
  - Email username
  - Email Tag(s)
  - Email Domain
  - How many times seen, first and last timestamp
- Collecting all attachments
  - Attachment meta info stored in DB
    - UUID, Filename, Size, MimeType, Hashes
  - Grabbing MD5,SHA1,SHA256 hashes of data
  - Attachment data stored on disk (opt-out)
- Collecting Received headers (Stored in DB)
- Collecting most important headers (Stored in DB)
  - Header(s) Date, To, From, Cc, Reply-To, Subject, Message-ID, X-Mailer,User-Agent, Organization, Content-Type
  - Rest can be found in raw record (if kept)
- Collecting Body
  - TEXT
  - HTML
- Collecting client connection info
  - IP address, Communications ports
  - Reverse DNS
  - Enriching IP with GEO location details
  - Enriching IP with ASN details

### Why?

I made smtplatrine as i wanted to keep myself entertained. Both hunting for threatactors but also with developing something while learning/refreshing protocol and networking. Building a honeypot is just the thing. And i love to collect and analyze data. So this is the best thing to do :D

Why PHP ? Sorry, it's simply just my goto lang over 10+ years ...

## ☑️ Requirements

- **PHP CLI** ( version 8+ ) with mysqli pdo, sockets, pcntl, mailparse
- **Database** (MySQL) MariaDB, Percona or MySQL Community Edition
- **Integrations**
  - To enable GEO
    - Maxmind City DB (mmdb)
    - Maxmind ASN DB (mmdb)
- **Optional:**
  - Maxmind geoipupdate tooling + api access
  - Webserver with PHP support and mysqli pdo
  - PHPMailer library for test script

## 🪄 How to install

This will require some Linux knowledge, i'm not going to hold your hand - Sorry  
Besides, if you have the urge to run a SMTP honeypot i would expect you already know how to work in Linux!

### Quick and dirty (Debian 12)

This should work on a fresh installed Debian-
Make sure you run Bookworm and with `main contrib non-free-firmware`

```shell
apt-get install mariadb-server apache2 nano
apt-get install geoipupdate multitail
apt-get install php8.2 php8.2-mysqli
apt-get install libphp-phpmailer php-maxminddb php-mailparse

cat <<_EOF >> /etc/GeoIP.conf
AccountID <CHANGEME BEFORE PASTING>
LicenseKey <CHANGEME BEFORE PASTING>
EditionIDs GeoLite2-ASN GeoLite2-City GeoLite2-Country
_EOF
geoipupdate

cd /opt
git clone https://github.com/kawaiipantsu/smtplatrine

cd smtplatrine

cp etc/database.example.ini etc/database.ini
cp etc/meta.example.ini etc/meta.ini

mysql -u root -p < contrib/smtplatrine_database_scratch.sql
nano etc/database.ini
chmod 755 smtplatrine

chown -R www-data www 
cp contrib/apache2_vhost.conf /etc/apache2/sites-available/smtplatrine.conf
a2ensite smtplatrine
apachectl restart

cp contrib/smtplatrine.service /etc/systemd/system
systemctl daemon-reload

systemctl start smtplatrine.service
systemctl status smtplatrine.service
```

### Manual install

Well ... That is up to you. You will need the following.

1) PHP-CLI (ver 8+)
2) PHP Extensions: Sockets, PCNTL, Mailparse (opt. phpmailer in path)
3) A Mysql Database (MariaDB or Mysql or Percona)
4) A Web server with PHP support and a vhost/root pointing to www
5) Use the goodies under `contrib` etc
6) Clone it and run it!

## 💡 How to run

The preferred way is via `systemd`  
Check -> [How to guide for setting up systemd smtplatrine service](contrib/README.md#systemd---setup-smtplatrine-step-by-step)

Alternative you can run i directly from the terminal/shell.  
This is also a good way to debug or just run it quickly and be able to kill it fast.

```shell
cd /opt/smtplatrine
./smtplatrine
```

## ⚙️ Configuration

All configuration happens under the path: `etc/`  
I have opt-in on using the good old `INI` config file format.

Should be straight forward here is an detailed overview of each config file with explanation. That's all you get. If you have questions or problems please refer to the Github Discussion community or our Discord server.

### Config file: `database.ini`

| SECTION | IDENTIFIER | DEFAULT | OPTIONS | DESCRIPTION |
|:-------:|:-----------|--------:|:--------|:------------|
|**engine**|engine_backend|"mysql"|"mysql"<br>~~"sqlite"~~<br>~~"elastic"~~|Choose what database backend you want to use. Right now only mysql is supported.
|**mysql**|mysql_auto_create_db|true|(bool)|Not implemented, use the `sql` files under `contrib` to create the database.
|-|mysql_persistent_connection|false|(bool)|Use persistent Mysql connection?
|-|mysql_host|"localhost"|(string)| IP or Hostname of your Mysql server
|-|mysql_port|3306|(int)| MySQL port number
|-|mysql_database|"smtplatrine"|(string)|Mysql database name
|-|mysql_username|"root"|(string)|Mysql credentials - Username
|-|mysql_password|""|(string)| Mysql credentials - Password

### Config file: `logger.ini`

| SECTION | IDENTIFIER | DEFAULT | OPTIONS | DESCRIPTION |
|:-------:|:-----------|--------:|:--------|:------------|
|**logger**|logger_enable|true|(bool)|Enable overall logging?
|-|logger_debug|false|(bool)|Show debug(verbose)) logging?
|-|logger_destination|"file"|"syslog"<br>"file"<br>"both"|Log destination
|**output_syslog**|output_syslog_facility|"local0"|(string)|SYSlOG Facility
|**output_file**|output_file_path|"logs/"|(string)|Path to save log files
|-|output_file_main|"smtplatrine.log"|(string)|Main log file
|-|output_file_error|"smtplatrine_error.log"|(string)|Log file with only ERROR
|-|output_file_debug|"smtplatrine_debug.log"|(string)|Log file with only DEBUG
|**vendor_log_level**|vendor_log_level_default|"E_ALL"|(string)|PHP log level internally 
|-|vendor_log_level_controller|"E_ERROR \| E_WARNING \| E_PARSE"|(string)|-
|-|vendor_log_level_socket|"E_ERROR \| E_WARNING \| E_PARSE"|(string)|-

### Config file: `meta.ini`

| SECTION | IDENTIFIER | DEFAULT | OPTIONS | DESCRIPTION |
|:-------:|:-----------|--------:|:--------|:------------|
|**meta**|meta_enable|true|(bool)|Enable META enrichment integrations
|**geoip**|geoip_enable|true|(bool)|Use GEO location (Maxmind)?
|-|geoip_main_file|"/var/lib/GeoIP/GeoLite2-City.mmdb"|(string)|Maxmind City DB
|-|geoip_asn_file|"/var/lib/GeoIP/GeoLite2-ASN.mmdb"|(string)|Maxmind ASN DB
|**abuseipdb**|abuseipdb_enable|false|(bool)|Enrich using remote api AbuseIPDB?
|-|abuseipdb_key|""|(string)|Requires API key!
|**vt**|vt_enable|false|(bool)|Enrich using remote api VirusTotal
|-|vt_key|""|(string)|Requires API key!
|**otx**|otx_enable|false|(bool)|Enrich using remote api AlienVault OTX
|-|otx_key|""|(string)|Requires API key!

### Config file: `server.ini`

| SECTION | IDENTIFIER | DEFAULT | OPTIONS | DESCRIPTION |
|:-------:|:-----------|--------:|:--------|:------------|
|**server**|server_port|25|(int)|Honeypot SMTP port
|-|server_listen|"0.0.0.0"|(string)|Honeypot listen to interface/all
|-|server_protection|true|(bool)|Enable ACL protection (blacklisting)?
|-|server_idle_timeout|60|(int)|Not implemented (kick idle clients)
|-|server_spawn_clients_as_non_privileged|false|(bool)|Spawn client threads as other user/group?
|**non_privileged**|non_privileged_user|"nobody"|(string)|Spawn as - user
|-|non_privileged_group|"nogroup"|(string)|Spawn as - group
|**protection**|protection_max_connections|100|(int)|Max concurrent connections (soft kick))
|-|protection_acl_blacklist_ip|true|(bool)|Enable blacklisting table IP
|-|protection_acl_blacklist_geo|true|(bool)|Enable blacklisting table Geo-CC
|-|protection_smart_acl|false|(bool)|Not implemented (Catch sinners)
|-|protection_smart_acl_action|"protection_acl_blacklist_ip"|(string)|Not implemented (Put sinners into)
|**smart_acl**|smart_acl_detect_flood|true|(bool)|Not implemented (Detect sinners flooding)
|-|smart_acl_detect_protocol_mismatch|true|(bool)|Not implemented (Detect sinners playing bad)
|**smtp**|smtp_domain|"smtp.srv25.barebone.com"|(string)|Honeypot SMTP FQDN
|-|smtp_banner|"SMTP Postfix (Debian/GNU)"|(string)|Honeypot SMTP Banner
|-|smtp_max_size|10485760|(int)|Not implemented (Honeypot message size cut-off)
|-|smtp_auth_accept|false|(bool)|Not implemented (Honeypot accept AUTH)
|-|smtp_compliant|true|(bool)|Honeypot require commands in order?
|-|smtp_attachments_store|true|(bool)|Honeypot save attachments to disk?
|-|smtp_attachments_path|"attachments/"|(string)|Honeypot attachments disk path
|**smtp_features**|smtp_featuresX|"EXAMPLE"|(string)|Iterate through all SMTP features you need to simulate. Default should be fine. Don't change unless you know what you are doing!

## 🫶 META (osint) Integrations

> 🚩 **Please note, sadly this is all still on the "todo" list**

META as in Metadata. It's enabled by default as we want to do GEO enrichment if the Maxmind files are available. But you can complexly disable it if you want to in the `meta.ini` file.

As I want to make this a full blown "threat hunting" honeypot for SMTP in terms of looking at data. Not only for a forensic perspective on SPAM but also on Malware or other types of malicious things you might see. To do this I thought it would be cool to integrate with public (osint) services out there to enrich the data on the fly.

**Please be-aware that many of these "free" services have a daily API limit!** 
If you have a API subscription with limits then smtplatrine will most likely use it all if it's a busy honeypot. I might incorporate in the future some "limit" checks.

### VirusTotal

> ⚠️ REQUIRES API KEY

We use VirusTotal to query on attachment filehashes and store the result in the DB. It will include things like scanning results and known names. To enable this make sure that you look in the `meta.ini` file under section `[vt]`

```ini
[vt]
vt_enable                                        = false
vt_key                                           = "YOUR KEY HERE"
```

### AbuseIPDB

> ⚠️ REQUIRES API KEY

To enable this make sure that you look in the `meta.ini` file under section `[abuseipdb]`

```ini
[abuseipdb]
abuseipdb_enable                                 = true
abuseipdb_key                                    = "YOUR KEY HERE"
```

### AlienVault OTX

> ⚠️ REQUIRES API KEY

To enable this make sure that you look in the `meta.ini` file under section `[otx]`

```ini
[otx]
otx_enable                                       = false
otx_key                                          = "YOUR KEY HERE"
```
### Ideas for other osint services to query

If you want to see other osint services used in this honeypot, head on over to the discussion community and chat about it there. I think there would be some cool integrations out there! 

Go to: https://github.com/kawaiipantsu/smtplatrine/discussions

## 📐 RFC-5321 ( Simple Mail Transfer Protocol ) compliance

I tried to stay on top of it as much as possible. But again, when building a honeypot you need to be a little bit "relaxed" when it comes to compliance and following the strict rules of a protocol. Or else you wont be able to catch all the bad guy's out there as they don't always do things by the book :)

But i have tried to add one thing, and that is to mimic the normal "strict" behavior of a SMTP server to always expect EHLO/HELO as the first command and then either MAIL FROM or AUTH. This can of course be disabled if you want to but i think it's nice.

RSET does nothing, NOOP says Ok, but in the end it works out as RSET is not needed to reset the mail. If you just redo the MAIL FROM it will just use what ever you typed in last. And RCPT TO will always just build up an array, so even RSET it will remember old input :) Works out in the end!

## 🎱 Tests performed

When building these kinds of service, especially honeypots - So much can go wrong and you can lose valuable data. I have therefore tried to do a little bit of due diligence and tested the honeypot against a few known things.

### Test of mail clients/software against the honeypot

| -- Software name ------------------------------ | -- Test(s) performed ---------------------- | -- Status -- |
|---|---|:---:|
| **Mozilla Thunderbird**<br>Basic smtp account | - Send mail, as text, html and both<br>- Using To,Cc,Bcc<br>- Adding multiple recipients<br>- Attaching files  | ✔️<br>✔️<br>✔️<br>✔️ |
| **NMAP**<br>Detection | - Showing as Open-Relay<br>- `-sV` Version fingerprinting[^1]<br>- `--script=banner` | ✔️<br>⭕<br>✔️ |
| **PHPMailer**<br>Perfect for testing and debugging | - Send regular email<br>- Send regular email (HTML+TEXT)<br>- Send regular email (With Attachment)<br>- Send regular email (SMTPS)<br>- Send regular email (STARTTLS)<br>- Adding `AUTH` ( via LOGIN )<br>- Adding `AUTH` ( via PLAIN ) | ✔️<br>✔️<br>✔️<br>❌<br>❌<br>❌<br>❌ | 

[^1]: We can choose to simulate a known fingerprint in the future. But for now we are our own SMTP server/honeypot.

<!--- 
Status icons
❌ = Not working
⭕ = Problems but not critical
❔ = Not fully tested
✔️ = Working!

Others
💯 🚩 🙈 🙉 🙊
💕 👺 👹 ☠️ 😈
🫶 🙏
--->

## 💣 Security concerns and safety issues!

When running a honeypot please note that is comes with risks!  
You are literally asking for threat actors and other bad guys our there to connect to your server and "do your worst". Obviously this has it's challenges :) Something to be very aware of!

Risks running a SMTP honeypot or honeypots in general.

- Don't run it on a PRODUCTION server
- Make sure you can delete/restore the server fast (image snapshot etc)
- Don't keep any secret or sensitive data/information on the server
- Don't use the same SSH credentials/keys as anything else
- Running as root is required, bu sub-thread can be spawned as non-root user if needed
- Native PHP can have vulnerabilities leading to RCE
- PHP Extensions,PECL,Composer components can have vulnerabilities leading to RCE
- Use blacklisting to keep worst of worst at bay
- Expect DDOS/Connection issues/Resource hogging
- Expect bad-standing with Cloud providers / ISP / Hosting providers
- IP address needs to be "cleaned" after long usage (RBL, reputation lists etc)

It's not to scare you, it's just the facts when dabble with honeypots!

## 😬 Running a "open-relay" SMTP server (honeypot or not)

When running a SMTP server there are some very important rules to remember.  
Something a honeypot is complexly ignoring and is actively trying to do all of that!

1) Never run a "OPEN RELAY" on the public internet.
2) Never run a "OPEN RELAY" on the public internet.
3) Never run a "OPEN RELAY" on the public internet.

I think you get the idea now :D

But why this emphasis on not running a open relay smtp on the public internet ?  
Well it's very bad manner and any Hosting provider, ISP or other kind of service provider will almost always either forcefully close your account/service down instantly or slap you with warnings, security tickets or straight up yell at you!

So PLEASE refer to the [💣 Security concerns and safety issues!](#-security-concerns-and-safety-issues) section that explains a bit about safety and security when running a smtp honeypot but also in general to secure the surrounding OS/system.

## 📑 References / Links to external sites

- RFC-5321: https://datatracker.ietf.org/doc/html/rfc5321
