<?php
namespace Kontextful\Backend;

class WorkerFactoryException extends \Exception {}

class WorkerFactory {

	public static function build($worker) {
		$worker = ucfirst($worker);
		$class = "Kontextful\\Backend\\Workers\\".$worker ;
		if(class_exists($class)) {
			return new $class();
		}
		else { 
			$filename = __DIR__.'/Workers/'.$worker.'.php';
			if(file_exists($filename)) {
				include($filename);
				return new $class();
			}
			else {
				throw new WorkerFactoryException('No such worker');
			}
		}
	}
}