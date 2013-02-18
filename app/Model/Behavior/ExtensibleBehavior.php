<?php
/* Todo: corresponding to UPDATE, FIND with conditions */
app::uses('ModelBehavior', 'Model');
app::uses('Extension', 'Model');

class ExtensibleBehavior extends ModelBehavior{
	public $name = 'ExtensibleBehavior';
	public $settings = array();
	
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array('types' => array());
		}

		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);

	/* Todo: This might not be necessary. It could be better to set the extension table within this behavior. */
	 /* Add: Probably it is necessary to distinguish which model should be extended in the case of extending associated models. */
		 if (empty($this->settings[$Model->alias]['types'])) {
			 $this->settings[$Model->alias]['types'] = array('Extension' => $Model->alias);
		 }

		 $this->settings['Extension'] = new Extension();
	 }

	public function getExtensionInstance() {
		return $this->settings['Extension'];
	}

	public function getExtendedField(Model $Model) {
		$Extension = $this->getExtensionInstance();
		$raw = $Extension->find('all', array(
				'conditions' => array(
					'Extension.model LIKE' => $Model->alias,
				),
				'fields' => array('Extension.field'),
				'group' => array('Extension.field'),
			));

		$fields = array();
		foreach ($raw as $model) {
			foreach ($model['Extension'] as $key => $val) {
				$fields[] = $val;
			}
		}
		return $fields;
	}

	public function isExtendedField(Model $Model, $field) {
		$Extension = $this->getExtensionInstance();
		if (is_array($field)) {
			foreach ($field as $val) {
				if ($this->isExtendedField($Model, $val)) {
					return $val;
				}
				return false;
			}
		}

		$extendedFields = $this->getExtendedField($Model);
		if (in_array($field, $extendedFields)) return true;
		return false;
	}

	 public function beforeValidate(Model $Model) {
		 $this->_bindExtensionModel($Model);
		 $this->_buildQuery($Model);
		 return true;
	 }

	 public function beforeSave(Model $Model) {
		 return true;
	 }

	 public function beforeFind(Model $Model, $query) {
		 pr($query);
		 $this->_bindExtensionModel($Model);
	 }

	 public function _makeQueryExtensional(Model $Model, $query) {
		 $parentFields = array_keys($Model->schema());
		 $extendedFields = $this->getExtendedField();

		 $target = array('conditions', 'fields', 'order');
		 foreach ($query as $key => $val) {
		 }
	 }

	 public function _buildQuery(Model $Model) {
		 $data = $Model->data;
		 $parentFields = array_keys($Model->schema());

		 $extensionData = array();
		 foreach ($data as $model => $fields) {
		 	 if ($model != $Model->alias) break;
		 	 foreach ($fields as $field => $val) {
		 		 if (!in_array($field, $parentFields)) {
		 			 $extensionData['Extension'][] = array(
		 				 'model' => $Model->alias,
		 				 'field' => $field,
		 				 'type' => (is_numeric($val))? 'INT' : 'VARCHAR',
		 				 'value' => $val,
		 			 );

					 unset($Model->data[$model][$field]);
		 		 }
		 	 }
		 }

		 $Model->data = array_merge($Model->data, $extensionData);
	 }

	public function _bindExtensionModel(Model $Model) {
		$Model->bindModel(array(
				'hasMany' => array(
					'Extension' => array(
						'className' => 'Extension',
						'foreignKey' => 'model_id',
					)
				)
			), false);
	}
			
}
				
 