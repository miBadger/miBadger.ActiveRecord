<?php

namespace miBadger\ActiveRecord\Traits;

use miBadger\Query\Query;
use miBadger\ActiveRecord\ColumnProperty;
use miBadger\ActiveRecord\AbstractActiveRecord;

Trait ManyToManyRelation
{
	// These variables are relevant for internal bookkeeping (constraint generation etc)
	private $_leftColumnName;

	private $_rightColumnName;

	private $_leftEntityTable;

	private $_rightEntityTable;

	/**
	 * Initializes the the ManyToManyRelation trait on the included object
	 * 
	 * @param AbstractActiveRecord $leftEntity The left entity of the relation
	 * @param &variable $leftVariable The variable where the id for the left entity will be stored
	 * @param AbstractActiveRecord $rightEntity The left entity of the relation
	 * @param &variable $leftVariable The variable where the id for the right entity will be stored
	 */
	protected function initManyToManyRelation(AbstractActiveRecord $leftEntity, &$leftVariable, AbstractActiveRecord $rightEntity, &$rightVariable)
	{
		$this->_leftEntityTable = $leftEntity->getActiveRecordTable();
		$this->_rightEntityTable = $rightEntity->getActiveRecordTable();

		if (get_class($leftEntity) === get_class($rightEntity)) {
			$this->_leftColumnName = sprintf("id_%s_left", $leftEntity->getActiveRecordTable());
			$this->_rightColumnName = sprintf("id_%s_right", $rightEntity->getActiveRecordTable());
		} else {
			$this->_leftColumnName = sprintf("id_%s", $leftEntity->getActiveRecordTable());
			$this->_rightColumnName = sprintf("id_%s", $rightEntity->getActiveRecordTable());
		}

		$this->extendTableDefinition($this->_leftColumnName, [
			'value' => &$leftVariable,
			'validate' => null,
			'type' => AbstractActiveRecord::COLUMN_TYPE_ID,
			'properties' => ColumnProperty::NOT_NULL
		]);

		$this->extendTableDefinition($this->_rightColumnName, [
			'value' => &$rightVariable,
			'validate' => null,
			'type' => AbstractActiveRecord::COLUMN_TYPE_ID,
			'properties' => ColumnProperty::NOT_NULL
		]);
	}

	/**
	 * Build the constraints for the many-to-many relation table
	 */
	public function createTableConstraints()
	{
		$childTable = $this->getActiveRecordTable();

		$leftParentTable = $this->_leftEntityTable;
		$rightParentTable = $this->_rightEntityTable;

		$leftConstraint = $this->buildConstraint($leftParentTable, 'id', $childTable, $this->_leftColumnName);
		$rightConstraint = $this->buildConstraint($rightParentTable, 'id', $childTable, $this->_rightColumnName);

		$this->pdo->query($leftConstraint);
		$this->pdo->query($rightConstraint);
	}
}