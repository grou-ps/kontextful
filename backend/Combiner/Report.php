<?php 
namespace Kontextful\Backend\Combiner;

/**
 * Element ID
 * Element Type
 * Element Status
 * Subelements
 * 	Type
 * 	List
 * @author esokullu
 *
 */

class Report {
	private $report = array();
	/*public function __construct($raw_report="") {
		if(!empty($raw_report))
			$this->report = json_decode($report, true);
		else
			$this->report = array();
	}*/
	public function __construct($id, $type) {
		$this->set_id($id);
		$this->set_type($type);
		$this->report['Element Status']='Processing';
	}
	
	private function set_id($id) {
		$this->report['Element ID'] =  $id;	
	}
	public function is_complete() {
		return isset($this->report['Element Status']) && $this->report['Element Status']=='Complete';
	}
	public function complete() {
		$this->report['Element Status']='Complete';
	}
	private function set_type($type) {
		$this->report['Element Type'] = ucfirst($type);
	}
	public function make_subelements($type) {
		$this->report['Subelements'] = array();
		$this->report['Subelements']['Type'] = ucfirst($type);
	}
	public function add_subelement($element) {
		if(!isset($this->report['Subelements']['List'])) {
			$this->report['Subelements']['List'] = array();
		}
		$this->report['Subelements']['List'][] = $element;
	}
	/*
	public function add_subelements($type, $list) {
		$this->report['Subelements'] = array();
		$this->report['Subelements']['Type'] = ucfirst($type);
		$this->report['Subelements']['List'] = $list;
	}*/
	/*public function generate_report() {
		return json_encode($this->report);	
	}*/
}