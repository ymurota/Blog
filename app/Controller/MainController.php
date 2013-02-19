<?php
App::uses('AppController', 'Controller');
class MainController extends AppController {
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
		pr($this->Member->Extension->find('first', array('conditions' => array('field' => 'sex'))));
	}

}