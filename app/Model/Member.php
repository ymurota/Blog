<?php
App::uses('AppModel', 'Model');

class Member extends AppModel {
	public $name = 'Member';
	//public $actsAs = array('Extensible');
	public $hasMany = array(
		'Extension' => array(
			'className' => 'Extension',
			'foreignKey' => 'model_id',
		)
	);

}