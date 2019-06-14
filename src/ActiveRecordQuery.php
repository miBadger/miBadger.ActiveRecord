<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\ActiveRecord;

use miBadger\Query\Query;
use miBadger\Query\QueryInterface;
use miBadger\Query\QueryExpression;

/**
 * The active record exception class.
 *
 * @since 2.0.0
 */
class ActiveRecordQuery
{
	private $table;

	private $hooks;

	private $results;

	private $query;

	private $whereExpression = null;

	private $clauses = [];

	public function __construct(AbstractActiveRecord $instance, $table, Array $additionalWhereClauses)
	{
		$this->table = $table;
		$this->query = new Query($instance->getPdo(), $table);
		$this->type = $instance;
		$this->clauses = $additionalWhereClauses;
	}

	private function execute()
	{
		$clauses = $this->clauses;

		// Optionally add user concatenated where expression
		if ($this->whereExpression !== null)
		{
			$clauses[] = $this->whereExpression;
		}

		// Construct where clause
		if (count($clauses) == 1)
		{
			$this->query->where($clauses[0]);
		} else if (count($clauses) >= 2)
		{
			$rest = array_slice($clauses, 1);
			$this->query->where(Query::And($clauses[0], ...$rest));
		}

		$this->query->select();

		$this->results = $this->query->execute();

		return $this;
	}

	public function fetchAll()
	{
		try {
			// TODO: Should execute call be explicit?
			$this->execute();

			$typedResults = [];

			$entries = $this->results->fetchAll();
			if ($entries === false) {
				throw new ActiveRecordException(sprintf('Can not search one non-existent entry from the `%s` table.', $this->table));
			}

			foreach ($entries as $entry) {
				$typedEntry = clone $this->type;
				$typedEntry->fill($entry);
				$typedResults[] = $typedEntry;
			}

			return $typedResults;
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}
	}

	public function fetch()
	{
		try {
			$this->execute();

			$typedResult = clone $this->type;

			$entry = $this->results->fetch();
			if ($entry === false) {
				throw new ActiveRecordException(sprintf('Can not search one non-existent entry from the `%s` table.', $this->table));
			}

			$typedResult->fill($entry);

			return $typedResult;
		} catch (\PDOException $e) {
			throw new ActiveRecordException($e->getMessage(), 0, $e);
		}
	}


	/**
	 * Set the where condition
	 *
	 * @param QueryExpression $expression the query expression
	 * @return $this
	 * @see https://en.wikipedia.org/wiki/SQL#Operators
	 * @see https://en.wikipedia.org/wiki/Where_(SQL)
	 */
	public function where(QueryExpression $expression)
	{
		$this->whereExpression = $expression;
		return $this;
	}

	/**
	 * Set an additional group by.
	 *
	 * @param string $column
	 * @return $this
	 * @see https://en.wikipedia.org/wiki/SQL#Queries
	 */
	public function groupBy($column)
	{
		$this->query->groupBy($column);
		return $this;
	}

	/**
	 * Set an additional order condition.
	 *
	 * @param string $column
	 * @param string|null $order
	 * @return $this
	 * @see https://en.wikipedia.org/wiki/SQL#Queries
	 * @see https://en.wikipedia.org/wiki/Order_by
	 */
	public function orderBy($column, $order = null)
	{
		$this->query->orderBy($column, $order);	
		return $this;
	}

	/**
	 * Set the limit.
	 *
	 * @param mixed $limit
	 * @return $this
	 */
	public function limit($limit)
	{
		$this->query->limit($limit);
		return $this;
	}

	/**
	 * Set the offset.
	 *
	 * @param mixed $offset
 	 * @return $this
	 */
	public function offset($offset)
	{
		$this->query->offset($offset);
		return $this;
	}
}
