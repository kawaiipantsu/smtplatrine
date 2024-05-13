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
    - [🪄 How to install](#-how-to-install)
    - [💡 How to run](#-how-to-run)
    - [⚙️ Configuaration](#-configuaration)
    - [🫶 META osint Integrations](#-meta-osint-integrations)
        - [VirusTotal](#virustotal)
        - [AbuseIPDB](#abuseipdb)
        - [AlienVault OTX](#alienvault-otx)
        - [Ideas for other osint services to query](#ideas-for-other-osint-services-to-query)
    - [📐 RFC-5321  Simple Mail Transfer Protocol  compliance](#-rfc-5321--simple-mail-transfer-protocol--compliance)
    - [🎱 Tests performed](#-tests-performed)
        - [Test of mail clients/software against the honeypot](#test-of-mail-clientssoftware-against-the-honeypot)
    - [💣 Security concerns and safty issues!](#-security-concerns-and-safty-issues)
    - [😬 Running a "open-relay" SMTP server honypot or not](#-running-a-open-relay-smtp-server-honypot-or-not)
    - [📑 References / Links to external sites](#-references--links-to-external-sites)

<!-- /TOC -->

---

> ⚠️ **WARNING**  
> The appliction as-is now, will not preform as intented!  
> If you have no interrest in the development, then please come back when it's done!
> 
> This README/documentation is not done, lot of what you see is placeholders

---

## 📧 What is smtplatrine?

## 🪄 How to install

## 💡 How to run

## ⚙️ Configuaration

## 🫶 META (osint) Integrations

META as in Metadata. It's enabled by default as we want to do GEO enrichment if the Maxmind files are avaiable. But you can completly disable it if you want to in the `meta.ini` file.

As I want to make this a full blown "threat hunting" honeypot for SMTP in terms of looking at data. Not only for a forensic perspective on SPAM but also on Malware or other types of malicous things you might see. To do this I tought it would be cool to integrate with public (osint) services out there to enrich the data on the fly.


> 🚩 **Please note, sadly this is all still on the "todo" list**

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

## 💣 Security concerns and safty issues!

## 😬 Running a "open-relay" SMTP server (honypot or not)

When running a SMTP server there are some very important rules to remember.  
Something a honeypot is completly ignoring and is actively trying to do all of that!

1) Never run a "OPEN RELAY" on the public internet.
2) Never run a "OPEN RELAY" on the public internet.
3) Never run a "OPEN RELAY" on the public internet.

I think you get the idea now :D

But why this emphasis on not running a open relay smtp on the public internet ?  
Well it's very bad manner and any Hosting provider, ISP or other kind of service provider will almost always either forcefully close your account/service down instantly or slap you with warnings, security tickets or straight up yell at you!

So PLEASE refere to the [💣 Security concerns and safty issues!](#-security-concerns-and-safty-issues) section that explains a bit about safety and security when running a smtp honeypot but also in general to secure the sourrounding OS/system.

## 📑 References / Links to external sites

- RFC-5321: https://datatracker.ietf.org/doc/html/rfc5321
