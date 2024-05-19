<?PHP

$pem_passphrase = "";
$pem_file = "../etc/server.pem"; 

$pem_dn = array(
    "countryName" => "US",                          //Set your country name
    "stateOrProvinceName" => "Shady State",         //Set your state or province name
    "localityName" => "Clobber time",               //Ser your city name
    "organizationName" => "SMTPLatrine",            //Set your company name
    "organizationalUnitName" => "Sysops",           //Set your department name
    "commonName" => "smtp.srv25.barebone.com",      //Set your full hostname.
    "emailAddress" => "hostmaster@localhost"        //Set your email address
);

//create ssl cert for this scripts life.

 //Create private key
 $privkey = openssl_pkey_new();

 //Create and sign CSR
 $cert    = openssl_csr_new($pem_dn, $privkey);
 $cert    = openssl_csr_sign($cert, null, $privkey, 365);

 //Generate PEM file
 $pem = array();
 openssl_x509_export($cert, $pem[0]);
 openssl_pkey_export($privkey, $pem[1], $pem_passphrase);
 $pem = implode($pem);

 //Save PEM file
 file_put_contents($pem_file, $pem);
 chmod($pem_file, 0640);
