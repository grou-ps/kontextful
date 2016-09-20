<?php
/**
 * @author Emre Sokullu
 * @license GROU.PS Inc.
 */


namespace Kontextful\Backend\Workers;

class ImpossibleStructureTypeException extends \Exception {}
class StructureException extends \Exception {}

class Structure extends Worker {
	
	public function process() {
		\KSimpleLogger::log("Inside the Structure factory");
		$service = ucfirst($this->params['service']);
		$file_type = ucfirst(basename($this->params['raw_file'], '.json'));
		$class = "Kontextful\\Backend\\Workers\\Structure\\".$service."\\Groups\\".$file_type;
		\KSimpleLogger::log("Structure Factory class name will be: ".$class);
		if(!class_exists($class)) {
			\KSimpleLogger::log("Structure Factory class ".$class." doesn't exist. So will create one.");
			$filename = __DIR__.'/Structure/'.$service.'/Groups/'.$file_type.'.php';
			\KSimpleLogger::log("Will look for file: ".$filename);
			if(file_exists($filename)) {
				\KSimpleLogger::log("Found: ".$filename);
				include($filename);
			}
			else {
				throw new ImpossibleStructureTypeException("No such Structure Service");
			}
		}
		try {
			\KSimpleLogger::log("Will be calling Structure class {$class}");
			$structure = new $class();
			$structure->bind($this->params);
			$structure->process();
		}
		catch(\Exception $e) {
			throw new StructureException("a subclass of Structure has thrown the following exception: ".$e->getMessage());
		}
	}
	
}