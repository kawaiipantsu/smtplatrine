<?PHP
// Let's utilize that autoloader is called early on to check we have the reuirements to run the application
if( ! extension_loaded('sockets' ) ) {
	echo "This application requires sockets extension (http://www.php.net/manual/en/sockets.installation.php)\n";
	exit(-1);
}

if( ! extension_loaded('pcntl' ) ) {
	echo "This application requires PCNTL extension (http://www.php.net/manual/en/pcntl.installation.php)\n";
	exit(-1);
}

if( ! extension_loaded('mailparse' ) ) {
	echo "This application requires mailparse extension (https://www.php.net/manual/en/mailparse.installation.php)\n";
	exit(-1);
}

// Set include path
set_include_path(".:/usr/lib/php:/usr/local/lib/php:".__DIR__);

// This is the autoloader for the classes in the vendor directory
spl_autoload_register(function ($class) {
    //include __DIR__ . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.inc.php';
	include str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.inc.php';
});

?>