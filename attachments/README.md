# ᵔᴥᵔ SMTPLATRINE ATTACHMENTS

> **THIS DIRECTORY CONTAINS ALL FILES SEEN AS ATTACHMENTS TO THE HONEYPOT**

```
📦attachments
 ┣ 📂application_json
 ┃ ┗ 📜a647b5eb209e95ad91200324ae4fa5aa8e26a74800d122bb66abc760f330650b
 ┣ 📂application_vnd_ms_excel
 ┃ ┗ 📜e6baca06ef0a7fd2fbb58b68ce364490fd58441ed5e039173a4802866351b685
 ┣ 📂application_x_msdownload
 ┃ ┗ 📜1a72da70694b3e00a8511c5035934461fc17ec4bfe9e04ee95860ecf926fa08d
 ┣ 📂image_png
 ┃ ┗ 📜5259d6e413242c63afe88027122eed783612ff9a9e48b9a9c51313f6bf66fb94 
 ┗ 📂unknown
   ┣ 📜1b4f0e9851971998e732078544c96b36c3d01cedf7caa332359d6f1d83567014
   ┗ 📜60303ae22b998861bce3b28f33eec1be758a213c86c93c076dbe9f558c11c752

```

## Focus on attachments ?

Why are you making it so hard to store and look at the actual attachments.  
Well honestly the honeypot is not build to be a honeypot for saving malware/phishing files or images attached in emails but we can do so. This can take up much space but again there might be forensic value in collecting the binary info. Any attached files in emails will always store a full array of each file as the following:

```ini
[attachments] => Array
    (
        [0] => Array
            (
                [uuid] => fcd9fe61-8a68-4557-9f52-1a4598792b32
                [filename] => giftcard-verifyer(2).exe
                [type] => application/x-msdownload
                [size] => 110312
                [stored] => Yes
                [stored_path] => application_x_msdownload/1a72da70694b3e00a8511c5035934461fc17ec4bfe9e04ee95860ecf926fa08d
                [hashes] => Array
                    (
                        [md5] => 29493ac35d0cbdecec05073482f9ac8d
                        [sha1] => afca6d2128f0eca07103611d62c5d30578d0d1c9
                        [sha256] => 1a72da70694b3e00a8511c5035934461fc17ec4bfe9e04ee95860ecf926fa08d
                    )

            )

    )
```
But to save the actual bits and bytes this is optional.

## Directory structure

All files are stored under the main attachment's folder defined in the `server.ini` file. But default it's `attachments/`.

Under this you will see 1 sub folder, thant then contains the actual attachments.

- MIME-Type folder

As MIME-Type is open to manipulation by the client, we keep a strict sanity check.  
If anything fails we will just put it under a default folder called `unknown`.

## Files are SHA256 hashes based on file data

Uniq `SHA256` strings are based on the binary/content of the file.  
This is generated doing the attachment save process. The smart thing about using the content sha256 is that we will only keep *one* version of the same file, no matter how much they spam us. Before we stored them using a uuid v4 string but that would just result in many copies of the same file.  

If you need to match it up with an actually mail and details, please refer to the database and under **emails** and use the uuid.

## The **unknown** MIME-Types

These should be worth **hunting** in !

I have made sure that most normal/correct written mime-types should be caught.  
So anything under this folder is suspect!

Most likely someone tried to manipulate the mime-type string.  
That also leaves the concern that the content of the actual file also is suspect :)

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
