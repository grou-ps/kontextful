<?php
/**
 * Factory pattern
 * @author Emre Sokullu
 * @license GROU.PS Inc.
 */


namespace Kontextful\Backend\Workers;

class ImpossibleFetchTypeException extends \Exception {}
class FetchException extends \Exception {}

class Fetch extends Worker {
	public function process() { 
		$service = ucfirst($this->params['service']);
		$class = "Kontextful\\Backend\\Workers\\Fetch\\".$service ;
		if(!class_exists($class)) {
			$filename = __DIR__.'/Fetch/'.$service.'.php';
			if(file_exists($filename)) {
				include($filename);
			}
			else {
				throw new ImpossibleFetchTypeException("No such Fetch Service");
			}
		}
		try {
			$fetcher = new $class();
			$fetcher->bind($this->params);
			$fetcher->process();
		}
		catch(\Exception $e) {
			throw new FetchException("a subclass of Fetch has thrown the following exception: ".$e->getMessage());
		}
	}
}