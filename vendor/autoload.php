<?PHP

// Set include path
set_include_path(".:/usr/lib/php:/usr/local/lib/php:".__DIR__);

// This is the autoloader for the classes in the vendor directory
spl_autoload_register(function ($class) {
    //include __DIR__ . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.inc.php';
	include str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.inc.php';
});

?>