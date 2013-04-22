<?php
/* Todo: corresponding to UPDATE, FIND with conditions */
/* 用意するExtensionモデルのテーブルは以下の通り　*/
/* テーブル名：extensions */
/* フィールド：id(int, primary), model(varchar), model_id(int), field(varchar), type(varchar), value(varchar) */

app::uses('ModelBehavior', 'Model');
app::uses('Extension', 'Model');

class ExtensibleBehavior extends ModelBehavior{
	public $name = 'ExtensibleBehavior';
	public $settings = array();

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
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

	public function getFields(Model $Model) {
		$parentFields = array_keys($this->getSchema($Model));
		$extendedFields = $this->getExtendedFields($Model);
		return array_merge($parentFields, $extendedFields);
	}

	public function getExtendedFields(Model $Model) {
		$Extension = $this->getExtensionInstance();
		$raw = $Extension->find('all', array(
				'conditions' => array(
					"$Extension->alias.model LIKE" => $Model->alias,
				),
				'fields' => array("$Extension->alias.field"),
				'group' => array("$Extension->alias.field"),
			));

		$fields = array();
		foreach ($raw as $model) {
			foreach ($model[$Extension->alias] as $key => $val) {
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

		$extendedFields = $this->getExtendedFields($Model);
		if (in_array($field, $extendedFields)) return true;
		return false;
	}

	public function beforeValidate(Model $Model) {
		$Model->data = $this->_buildQuery($Model);
		if (!empty($Model->data['Extension'])) {
			$this->_bindExtensionModel($Model);
			//Update
			if (isset($Model->data[$Model->alias]['id'])) {
				$Extension = $this->getExtensionInstance();
				$data = $Extension->find('all', array(
						'conditions' => array('model_id' => $Model->data[$Model->alias]['id'])
					));

				if (!empty($data)) {
					$ex_data = array();
					foreach ($data as $val) {
						foreach ($Model->data['Extension'] as $key2 => $val2) {
							$val1 = $val['Extension'];
							if ($val1['model'] == $val2['model'] && $val1['field'] == $val2['field']) {
								$ex_data[] = array('Extension' => array_merge($val1, $val2));
								unset($Model->data['Extension'][$key2]);
								break;
							}
						}	
					}
					if(!$Extension->saveAll($ex_data)) return false;
				} else {
				}
			}

		}
		return true;
	}

	public function beforeSave(Model $Model) {
		return true;
	}

	public function beforeFind(Model $Model, $query) {
		$this->_bindExtensionModel($Model);
		$params = $this->_parseQueryR($Model, $query);
		$Extension = $this->getExtensionInstance();

		if (isset($params['Extension']) && !empty($params['Extension']['conditions'])) {
			$a = $Extension->find('list', array('conditions' => $params['Extension']['conditions'], 'fields' => 'model_id'));
			$query = $params[$Model->alias];
			$query['conditions'][] = array(
				"{$Model->alias}.id" => $a
			);
			if (empty($query['conditions']['OR'])) unset($query['conditions']['OR']);
		}

		return $query;
	}

	public function afterFind(Model $Model, $results, $primary) {

		$extended = $this->getExtendedFields($Model);
		foreach ($results as $k => $v) {
			//すべてのデータにExtensionalなフィールドを追加
			foreach ($extended as $field) {
				$results[$k][$Model->alias][$field] = "";
			}
			//それぞれのデータが持っているExtensionデータを親モデルに偽装して、Extensionデータを削除
			if (isset($results[$k]['Extension']) && is_array($results[$k]['Extension'])) {
				foreach ($results[$k]['Extension'] as $n => $v) {
					$value = ($v['type'] == 'INT')?intval($v['value']):$v['value'];
					$results[$k][$Model->alias][$v['field']] = $value;
				}
				unset($results[$k]['Extension']);
			}
		}

		return $results;
	}

	// Extensionな要素を含んだクエリを再帰的に解析して元のクエリとExtension用のクエリとを分けて一つの配列に格納し返す。
	// Example 
	// --before-- 
	// array(
	// 	'conditions' => array(
	// 		'Member.userid' => 'aaa',
	// 		'Member.graduation_date' => '2012-1-10', <--Extended field
	// 		'OR' => array(
	// 			'Member.id' => 12,
	// 			'Member.test' => 'aaa' <--Extended field
	// 		)
	// 	),
	// 	'field' => array(
	// 		'Member.test' <--Extended field
	// 	)
	// );
	/* --after-- */
	// array(
	// 	'Member' => array(
	// 		'conditions' => array(
	// 			'Member.userid' => 'aaa',
	// 			'OR' => array(
	// 				'Member.id' => 12
	// 			)
	// 		)
	// 	),
	// 	'Extension' => array(
	// 		'conditions' => array(
	// 			'Member.graduation_date' => '2012-1-10',
	// 			'OR' => array(
	// 				'Member.test' => 'aaa'
	// 			)
	// 		),
	// 		'field' => array(
	// 			'Member.test'
	// 		)
	// 	)
	// );
	public function _parseQueryR(Model $Model, $query, $extension = array(), $path = null) {
		if (!is_array($query)) return $query;
		if (empty($extension)) {
			//呼び出しもとのモデルとExtension用のクエリを格納する場所を確保
			$extension[$Model->alias] = $query;
			$extension['Extension']	= array();
		}

		foreach ($query as $key => $val) {
			if (is_array($val)) {
				//現在のvalまでのキーのパスを追加
				$path = ($path)?"$path/$key":"$key";

				$extension = $this->_parseQueryR($Model, $val, $extension, $path);

				//再帰呼び出しの後はパスをひとつ戻る。
				$tmp = explode('/', $path);
				array_pop($tmp);
				$path = (!empty($tmp))?join('/',$tmp):null;
			} else {
				$e = explode('.', $key);//モデル名とフィールド名を切り離す。
				$f = isset($e[1]) ? $e[1] : $e[0];//フィールド名を格納
				$ope = 'LIKE';//デフォルト値
				if (count($f = explode(' ', $f)) > 1) $ope = $f[1];
				$f = $f[0];

				if ($e[0] == $Model->alias && !in_array($f, array_keys($this->getSchema($Model)))) {
					$Extension = $this->getExtensionInstance();
					//元モデルのクエリからExtensionな要素を削除
					$extension = $this->_remove($extension, "$Model->alias/$path/$key");
					//Extensionクエリに削除した要素を追加
					$tmp = array(
						"{$Extension->alias}.model LIKE" => $Model->alias,
						"{$Extension->alias}.field LIKE" => $e[1],
						"{$Extension->alias}.value $ope" => $val
					);
					$extension = $this->_push($extension, $tmp, "Extension/$path");
				}
			}
		}

		return $extension;
	}

	public function _buildQuery(Model $Model) {
		$data = $Model->data;
		$parentFields = array_keys($Model->schema());
		$virtualFields = (!empty($Model->virtualFields))? array_keys($Model->virtualFields):array();
		$parentFields = array_merge($parentFields, $virtualFields);

		$Extension = $this->getExtensionInstance();

		$extensionData = array();
		foreach ($data as $model => $fields) {
			if ($model != $Model->alias) continue;
			foreach ($fields as $field => $val) {
				if (!in_array($field, $parentFields)) {
					$extensionData[$Extension->alias][] = array(
						'model' => $Model->alias,
						'field' => $field,
						'type' => (is_numeric($val))? 'INT' : 'VARCHAR',
						'value' => $val,
					);

					unset($Model->data[$model][$field]);
				}
			}
		}

		return array_merge($Model->data, $extensionData);
	}

	public function _bindExtensionModel(Model $Model) {
		$Extension = $this->getExtensionInstance();
		$Model->bindModel(array(
				'hasMany' => array(
					$Extension->alias => array(
						'className' => $Extension->alias,
						'foreignKey' => 'model_id',
					)
				)
			), false);
	}

	/* Utilities for hash */
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

	public function _push($arr, $val, $path, $d='/') {
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

		$cpy[] = $val;
		return $arr;
	}


}