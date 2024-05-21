# Certificates for SMTPlatrine

In order to support TLS/SSL via STARTTLS in SMTP. Then you need a full blown encryption setup, meaning you need to be able to negotiate a certificate to the client and and do proper handshaking and establishing a encrypted transport tunnel.

After some much tinkering i finally got it to work, this folder holds the certificate files.

You need a `PEM` and the `KEY` file in here.

AND YES - If you have this thing plopped down on a real domain name you could make a real let's encrypt or similar real deal certificate and it would work much more smoothly!

For now we just use a self signed one.

> **The self signed certificate will be auto generated on the first run**