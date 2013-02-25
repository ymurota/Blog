<?php
/* Todo: corresponding to UPDATE, FIND with conditions */
/* Todo: Model::schema() should be called once in this class, so that it should be saved in the $this->settings */
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

		 $this->settings[$Model->alias]['schema'] = $Model->schema();
		 $this->settings['Extension'] = new Extension();
	 }

	public function getExtensionInstance() {
		return $this->settings['Extension'];
	}

	public function getSchema(Model $Model) {
		return $this->settings[$Model->alias]['schema'];
	}

	public function listField(Model $Model) {
		$parentFields = array_keys($this->getSchema($Model));
		$extendedFields = $this->getExtendedField($Model);
		return array_merge($parentFields, $extendedFields);
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
		 $this->_buildQuery($Model);//Todo: _buildQuery should return data
		 return true;
	 }

	 public function beforeSave(Model $Model) {
		 return true;
	 }

	 public function beforeFind(Model $Model, $query) {
		 $this->_bindExtensionModel($Model);
		 $params = $this->_parseQueryR($Model, $query);
		 $Extension = $this->getExtensionInstance();
		 $a = $Extension->find('list', array('conditions' => $params['Extension']['conditions'], 'fields' => 'model_id'));

		 $query = $params[$Model->alias];
		 $query['conditions'][] = array(
			 "{$Model->alias}.id" => $a
		 );

		 return $query;
	 }

	 public function afterFind(Model $Model, $results, $primary) {
		 foreach ($results as $k => $v) {
			 foreach ($v as $model => $data) {
				 if ($model == 'Extension') {
					 foreach ($data as $n => $f) {
						 $value = ($f['type'] == 'INT')?intval($f['value']):$f['value'];
						 $results[$k][$Model->alias][$f['field']] = $value;
						 unset($results[$k][$model][$n]);
					 }
					 unset($results[$k][$model]);
				 }
			 }
		 }

		 return $results;
	 }

	 public function _remove($arr = array(), $path, $d = '/') {
		 $path = explode($d, $path);
		 $cpy =& $arr;

		 foreach ($path as $v) {
			 if (isset($cpy[$v]) && is_array($cpy[$v])) $cpy =& $cpy[$v];
			 else if (isset($cpy[$v]) && !is_array($cpy[$v])) break;
			 else break;
		 }

		 unset($cpy[$v]);
		 return $arr;
	 }

	 public function _insert($arr, $val, $path, $d='/') {
		 $path = explode($d, $path);
		 $cpy =& $arr;

		 foreach ($path as $k) {
			 if (isset($cpy[$k])) {
				 $cpy =& $cpy[$k];
			 } else {
				 $cpy[$k] = array();
				 $cpy =& $cpy[$k];
			 }
		 }

		 $cpy = $val;
		 return $arr;
	 }

	 public function _parseQueryR(Model $Model, $query, $extension = array(), $path = null) {
		 if (!is_array($query)) return $query;
		 if (empty($extension)) {
			 /* initial settings */
			 /* providing two params for the parent and Extension models */
			 $extension[$Model->alias] = $query;
			 $extension['Extension']	= array();
		 }

		 foreach ($query as $key => $val) {
			 if (is_array($val)) {
				 /* setting current path */
				 $path = ($path)?"$path/$key":"$key";
				
				 $extension = $this->_parseQueryR($Model, $val, $extension, $path);

				 /* returning just before */
				 $tmp = explode('/', $path);
				 array_pop($tmp);
				 $path = (!empty($tmp))?join('/',$tmp):null;
			 } else {
				 $e = explode('.', $key);
				 if ($e[0] == $Model->alias && !in_array($e[1], array_keys($this->getSchema($Model)))) {
					 /* removing the extensional field from the parent params */
					 $extension = $this->_remove($extension, "$Model->alias/$path/$key");
					 /* instead, insert the removed value into the extensional params */
					 $tmp = array(
						 'Extension.model LIKE' => $Model->alias,
						 'Extension.field LIKE' => $e[1],
						 'Extension.value LIKE' => $val
					 );
					 $extension = $this->_insert($extension, $tmp, "Extension/$path");
				 }
			 }
		 }

		 return $extension;
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
				
 