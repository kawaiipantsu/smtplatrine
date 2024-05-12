# ᵔᴥᵔ SMTPLATRINE ATTACHMENTS

> **THIS DIRECTORY CONTAINS ALL FILES SEEN AS ATTACHMENTS TO THE HONEYPOT**

```
📦attachments
 ┣ 📂adf6e1e0ed3670731a33f5e328f85aa1
 ┃ ┗ 📜af6d255c-9382-41bf-8c3b-b0db3b28b551
 ┣ 📂e4397e0eec8e96d6b033eccab4726c7c
 ┃ ┣ 📜9a563701-bb56-40f4-9368-3b6eafb3bf32
 ┃ ┗ 📜e99597cc-ef9e-4027-b1fb-b4e591f4b28b
 ┗ 📜README.md
```

## Focus on attachments ?

Why are you making it so hard to store and look at the actual attachments.  
Well honestly the honeypot is not ment to be a honeypot for saving malware/phishing files or images attached in emails but we can do so. This can take up much space but again there might be forensic value in collecting the binary info. Any attached files in emails will always store a full array of each file as 
```php
$attachments[] = array(
    "filename"     => "example-secret.pdf",
    "content-type" => "application/pdf",
    "size"         => 1230041
);
```
But to save the actual bits and bytes this is optional.

## Directories are MD5 hashes

Uniqe `MD5` strings, all info is stored in the database under each **email** row.  
To be able to find the correct file you have to look it up in the database.

## Files are UUID v4 strings

Uniqe `UUID` strings, all info is stored in the database under each **email** row.  
To be able to find the correct file you have to look it up in the database.
