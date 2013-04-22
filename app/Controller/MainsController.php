<?php
App::uses('AppController', 'Controller');
class MainsController extends AppController {
	public $uses = array('Member');
	//public $scaffold;
	public function index() {
		$data = array(
			'Member' => array(
				'user_id' => 12,
				'password' => 'test',
				'last_name' => 'murota',
				'first_name' => 'yutaka',
				'e_mail' => 'test@test.com',
				'year' => 2002,
				'sex' => 'man',
			),
		);

		//$conditions = array( 'Member.last_name' => 'murota', 'Extension.value' => 'man');
		$conditions = array();
		//$this->Member->saveAll($data);
		pr($this->Member->find('all', array('conditions'=>array('OR'=>array('Member.year' => 2002, 'Member.sex' => 'woman')))));
	}

	public function form($id = null) {
		if ($id != null) {
			$data = $this->Member->findById($id);
			$this->set('data', $data);
		}

		if(!empty($this->data)) {
			pr($this->data);
			$this->Member->saveAll($this->data);
		}
	}

}