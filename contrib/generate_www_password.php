<?PHP

// This will simply use the build in PHP password generate hash to generate a password
// We use the DEFAULT algorithm which is currently bcrypt

echo "SMTPLATRINE - WWW Password Hash Generator\n";
echo "-----------------------------------------\n";

// We use getopt to grab the password from the command line and/or help
$options = getopt("hp:", array("help","password:"));

// Switch case to handle the help
switch (true) {

    case (isset($options['p'])):
    case (isset($options['password'])):
        echo password_hash($options['p'], PASSWORD_DEFAULT)."\n";
        echo "\n";
        echo "You can then use this password hash in the www_users table for the admin user already there.\n";
        echo "By running the following SQL query in your mysql client:\n";
        echo "UPDATE smtplatrine.www_users SET users_password='".password_hash($options['p'], PASSWORD_DEFAULT)."' WHERE users_username='admin' LIMIT 1;\n";
        break;

    case (isset($options['h'])):
    case (isset($options['help'])):
        echo "Usage: php generate_www_password.php -p <password>\n";
        break;
    default:
        echo "Error: No password provided\n";
        exit;

}

?>