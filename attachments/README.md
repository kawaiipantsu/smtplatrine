# ᵔᴥᵔ SMTPLATRINE ATTACHMENTS

> **THIS DIRECTORY CONTAINS ALL FILES SEEN AS ATTACHMENTS TO THE HONEYPOT**

```
📦attachments
 ┣ 📂application_json
 ┃ ┗ 📂172.30.30.90
 ┃ ┃ ┗ 📜ae7f53ad-24ac-44dc-99f6-587044fb505e
 ┣ 📂image_png
 ┃ ┗ 📂172.30.30.90
 ┃ ┃ ┗ 📜b5a72e13-48c5-488d-ade3-f89d704bff79
 ┗ 📂unknown
   ┣ 📜2549d0e7-a1ae-44f3-b2ae-460ed35d3978
   ┗ 📜f6c006e4-dc3c-4e23-bc90-a8084366a66c

```

## Focus on attachments ?

Why are you making it so hard to store and look at the actual attachments.  
Well honestly the honeypot is not ment to be a honeypot for saving malware/phishing files or images attached in emails but we can do so. This can take up much space but again there might be forensic value in collecting the binary info. Any attached files in emails will always store a full array of each file as 
```ini
[attachments] => Array
        (
            [0] => Array
                (
                    [uuid] => b5a72e13-48c5-488d-ade3-f89d704bff79
                    [filename] => download.png
                    [type] => image/png
                    [size] => 28838
                )
        )
```
But to save the actual bits and bytes this is optional.

## Directory structure

All files are stored under the main attachment's folder defined in the `server.ini` file. But default it's `attachments/`.

Under this you will see 2 sub folder divisions.

- First it will be categorized into what MIME type it is.
- Second it will be kept under the Remote IP address that sent it.

As MIME-Type is open to mnipulation by the client, we keep a strict sanity check.  
If anything fails we will just put it under a default folder called `unknown`

## Files are UUID v4 strings

Uniqe `UUID` strings. This is generated doing the attachment save process.  
If you need to match it up with an actually mail and details, please refere to the database and under **emails**.

## The **unknown** MIME-Types

These should be worth **hunting** in !

I have made sure that most normal/correct written mime-types should be caught.  
So anything under this folder is suspect!

Most likely someone tried to manipulate the mime-type string.  
That also leaeves the concern that the content of the actual file also is suspect :)