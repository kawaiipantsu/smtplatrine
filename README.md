# ᵔᴥᵔ SMTPLATRINE - A SMTP Honeypot

[![Twitter Follow](https://img.shields.io/twitter/follow/davidbl.svg?style=social&label=Follow)](https://twitter.com/davidbl) [![GitHub issues](https://img.shields.io/github/issues/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/issues) [![GitHub closed issues](https://img.shields.io/github/issues-closed/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/issues) [![GitHub license](https://img.shields.io/github/license/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/blob/master/LICENSE) [![GitHub forks](https://img.shields.io/github/forks/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/network) [![GitHub stars](https://img.shields.io/github/stars/kawaiipantsu/smtplatrine.svg)](https://github.com/kawaiipantsu/smtplatrine/stargazers)
> SMTP Honeypot written in PHP, with focus on gathering intel on threat actors and for doing spam forensic work

![smtplatrine](www/assets/images/smtplatrine_cover.png)

---

> 🚨 **ALERT**  
> Only run aa honeypot if you know what you are doing!

---

## 🗃️ Table of contents
<!-- TOC -->

- [ᵔᴥᵔ SMTPLATRINE - A SMTP Honeypot](#%E1%B5%94%E1%B4%A5%E1%B5%94-smtplatrine---a-smtp-honeypot)
    - [🗃️ Table of contents](#-table-of-contents)
    - [📧 What is smtplatrine?](#-what-is-smtplatrine)
    - [🪄 How to install](#%F0%9F%AA%84-how-to-install)
    - [💡 How to run](#-how-to-run)
    - [⚙️ Configuaration](#-configuaration)
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

## 📐 RFC-5321 ( Simple Mail Transfer Protocol ) compliance

I tried to stay on top of it as much as possible. But again, when building a honeypot you need to be a little bit "relaxed" when it comes to compliance and following the strict rules of a protocol. Or else you wont be able to catch all the bad guy's out there as they don't always do things by the book :)

But i have tried to add one thing, and that is to mimic the normal "strict" behavior of a SMTP server to always expect EHLO/HELO as the first command and then either MAIL FROM or AUTH. This can of course be disabled if you want to but i think it's nice.

RSET does nothing, NOOP says Ok, but in the end it works out as RSET is not needed to reset the mail. If you just redo the MAIL FROM it will just use what ever you typed in last. And RCPT TO will always just build up an array, so even RSET it will remember old input :) Works out in the end!

## 🎱 Tests performed

When building these kinds of service, especially honeypots - So much can go wrong and you can lose valuable data. I have therefore tried to do a little bit of due diligence and tested the honeypot against a few known things.

### Test of mail clients/software against the honeypot

| Software name | Test(s) performed | Status |
|---|---|:---:|
| **Mozilla Thunderbird**<br>Basic smtp account | - Send mail, as text, html and both<br>- Using To,Cc,Bcc<br>- Adding multiple recipients<br>- Attaching files  | ✔️<br>✔️<br>✔️<br>✔️ |
| **NMAP**<br>Detection | - Showing as Open-Relay<br>- `-sV` Version fingerprinting[^1]<br>- `--script=banner` | ✔️<br>⭕<br>✔️ | 

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

## 📑 References / Links to external sites

- RFC-5321: https://datatracker.ietf.org/doc/html/rfc5321
