<?php
// do nothing

namespace Kontextful\Backend\Workers\Structure\Facebook\Groups;

class Groups extends StructureFacebookGroupsWorker {
	public function process() {
		\KSimpleLogger::log("Got into Structure/Facebook/Groups/Groups, and we're doing nothing -- on purpose");
	} // do nothing
	public function structure() {} // just to satisft the abstract class
	protected function set_structure_id() {}
}