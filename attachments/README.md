# ᵔᴥᵔ SMTPLATRINE ATTACHMENTS

> **THIS DIRECTORY CONTAINS ALL FILES SEEN AS ATTACHMENTS TO THE HONEYPOT**

```
📦attachments
 ┣ 📂application_json
 ┃ ┗ 📜ae7f53ad-24ac-44dc-99f6-587044fb505e
 ┣ 📂image_png
 ┃ ┗ 📜b5a72e13-48c5-488d-ade3-f89d704bff79 
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

Under this you will see 1 sub folder, thant then contains the actuall attachments.

- MIME-Type folder

As MIME-Type is open to manipulation by the client, we keep a strict sanity check.  
If anything fails we will just put it under a default folder called `unknown`.

## Files are UUID v4 strings

Uniqe `UUID` strings. This is generated doing the attachment save process.  
If you need to match it up with an actually mail and details, please refere to the database and under **emails**.

## The **unknown** MIME-Types

These should be worth **hunting** in !

I have made sure that most normal/correct written mime-types should be caught.  
So anything under this folder is suspect!

Most likely someone tried to manipulate the mime-type string.  
That also leaeves the concern that the content of the actual file also is suspect :)

Here is the actual code that "sanitizes" our string for use with directories.  
What do you think, eh ? Simple and secure i would say...

```php
$type = trim($part_data['content-type']);

$type = str_replace('/','_',$type);
$type = str_replace(';','_',$type);
$type = str_replace('+','_',$type);
$type = str_replace(' ','_',$type);
$type = str_replace('-','_',$type);
$type = str_replace('.','_',$type);

if ( !preg_match('/^[A-Z0-9_]+$/i',$type) ) $type = "unknown";
```

So going over the wast majority of MIME types out there, they all consisted of 5-6 different chars, so i included those and converted to `_`. So that should actually work with 99% of them all. Those left will just be called `unknown´ - I  feel confident about this. It's risky business to let user-controlled stuff onto your servers filesystem :)
