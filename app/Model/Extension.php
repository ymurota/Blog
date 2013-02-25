<?php
class Extension extends AppModel
{

	public function afterFind($results) {
		//pr($results);
		return $results;
	}
}