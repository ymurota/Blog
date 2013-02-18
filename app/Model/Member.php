<?php
App::uses('AppModel', 'Model');

class Member extends AppModel {
	public $name = 'Member';
	public $actsAs = array('Extensible');
}