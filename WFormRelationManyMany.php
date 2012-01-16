<?php
/**
 * @author Weavora Team <hello@weavora.com>
 * @link http://weavora.com
 * @copyright Copyright (c) 2011 Weavora LLC
 */

class WFormRelationManyMany extends WFormRelationHasMany {

	public $type = CActiveRecord::HAS_MANY;

	public function save() {
		// remove all links
		if (!$this->_unlink())
			return false;

		$relatedModels = $this->getRelatedModels();
		if (count($relatedModels) == 0 && $this->required)
			return false;

		$isSuccess = true;
		foreach ($relatedModels as $index => $relationModel) {
			if ($relationModel->save()) {
				$isSuccess = $this->_linkTo($relationModel) && $isSuccess;
			} else {
				$isSuccess = false;
			}
		}

		return $isSuccess;
	}

	/**
	 * Insert link between parent and relation models into database
	 *
	 * @todo maybe we should execute bulk insert of links ? It faster a lot
	 * @param $relatedModel
	 * @return bool
	 */
	protected function _linkTo($relatedModel) {
		$foreignKey = $this->_parseForeignKey($this->info[WFormRelation::RELATION_FOREIGN_KEY]);

		try {
			$sql = "INSERT INTO {$foreignKey['table']} ({$foreignKey['model_fk']}, {$foreignKey['relation_fk']}) VALUES (:model_fk,:relation_fk)";

			$command = Yii::app()->db->createCommand($sql);
			$command->bindValues(array(
				":model_fk" => $this->model->primaryKey,
				":relation_fk" => $relatedModel->primaryKey,
			));
			$command->execute();
		} catch (Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Remove all links between parent and relation models into database
	 *
	 * @return bool
	 */
	protected function _unlink() {
		$foreignKey = $this->_parseForeignKey($this->info[WFormRelation::RELATION_FOREIGN_KEY]);

		try {
			$sql = "DELETE FROM {$foreignKey['table']} WHERE {$foreignKey['model_fk']} = :model_fk";

			$command = Yii::app()->db->createCommand($sql);
			$command->bindValues(array(
				":model_fk" => $this->model->primaryKey,
			));
			$command->execute();

		} catch (Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * Parse foreign key into table name, model FK and relation FK
	 *
	 * @param $key
	 * @return array
	 */
	protected function _parseForeignKey($key) {
		if (preg_match('/(?P<table>.*?)\((?P<model_fk>.*?),(?P<relation_fk>.*?)\)/is', $key, $matches))
		{
			return array(
				'table' => Yii::app()->db->quoteTableName(trim($matches['table'])),
				'model_fk' => Yii::app()->db->quoteColumnName(trim($matches['model_fk'])),
				'relation_fk' => Yii::app()->db->quoteColumnName(trim($matches['relation_fk'])),
			);
		}

		return null;
	}
}
