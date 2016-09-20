<?php 
/**
 * @see http://jeremycook.ca/2012/10/02/turbocharging-your-logs/
 * @todo revisit this.
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class KSimpleLogger {
	public static function log($msg) {
		switch(LOG_OUTPUT) {
			case "none":
				// do nothing
				break;
			case "stdout":
				echo $msg.PHP_EOL;
				break;
			default:
				file_put_contents(LOG_OUTPUT, $msg.PHP_EOL, FILE_APPEND | LOCK_EX);
				break;
		}
	}
}

class KLogger {
	private $log;
	private static $instance = null;
	
	private function __construct() {
		// create a log channel
		$log = new Logger('kontextfuld');
		$log->pushHandler(new StreamHandler(KONTEXTFULD_LOG_DIR, Logger::DEBUG));
	}
	
	private static function getInstance() {
        if(!is_object(self::$instance))
            self::$instance = new KLogger();
        return self::$instance;
    }
    
    public static function log($msg, $level="debug") {
    	// here's the error message we get:
    	// PHP Fatal error:  Call to undefined method KLogger::addDebug() in /Users/esokullu/Code/Kontextful/libs/KLogger.php on line 31
    	return; // don't do anything yet.
    	
    	$logger = self::getInstance();
    	if($level=="error") {
    		$logger->addError($msg);
    		die($msg.PHP_EOL);
    	}
    	else {
    		$logger->addDebug($msg);
    	}
    }
}