<?php
namespace EloquentEnhanced\Query;

class Builder extends \Illuminate\Database\Query\Builder
{
	/**
	 * Extend of the eloquent whereIn function without error when empty array is given
	 */
	public function whereIn($column, $values, $boolean = 'and', $not = false)
	{
		if(empty($values)){
			$this->whereRaw(0);
		}else{
			parent::whereIn($column, $values, $boolean, $not);
		}

		return $this;
	}

	/**
	 * Extends of the offset function,
	 * avoid setting an offset of 0 that cause error on some SQL engine
	 */
	public function offset($value)
	{
		$value = max(0, $value);

		if ($value > 0) $this->offset = $value;

		return $this;
	}

	/**
	 * remove everything but the name of the collunm name for the lists function
	 * @param  string $column
	 * @param  string $key
	 * @return array
	 */
	protected function getListSelect($column, $key)
	{
		$parseCol = function($value){
			if(preg_match('/^\s*(?<table>\w+)\.(?<col>\w+)\s*$/', $value, $match)){
				return $match['col'];
			}
			if(preg_match('/^\s*(?<table>.*)\s+as\s+(?<col>\w+)\s*$/', $value, $match)){
				return $match['col'];
			}
			return (string) $value;
		};

		$select = array($parseCol($column));

		if(!is_null($key)){
			$select[] = $parseCol($key);
		}

		return $select;
	}

	/**
	 * Extends of the lists function.
	 * Allowing use table name or a subquery for each column and key parameter
	 * @param  string $column
	 * @param  string $key
	 * @return array
	 */
	public function lists($column, $key = null)
	{
		$select = is_null($key) ? array($column) : array($column, $key);

		$columns = $this->getListSelect($column, $key);

		$results = new \Illuminate\Support\Collection($this->get($select));

		$values = $results->fetch($columns[0])->all();

		if ( ! is_null($key) && count($results) > 0)
		{
			$keys = $results->fetch($columns[1])->all();

			return array_combine($keys, $values);
		}

		return $values;
	}

	/**
	 * Insert (replace) a new record into the database.
	 *
	 * @param  array  $values
	 * @return bool
	 */
	public function replace(array $values)
	{
		if ( ! is_array(reset($values)))
		{
			$values = array($values);
		}

		else
		{
			foreach ($values as $key => $value)
			{
				ksort($value); $values[$key] = $value;
			}
		}

		$bindings = array();

		foreach ($values as $record)
		{
			$bindings = array_merge($bindings, array_values($record));
		}

		$sql = $this->grammar->compileInsert($this, $values);

		$sql = preg_replace('/^insert/','replace',$sql);

		$bindings = $this->cleanBindings($bindings);

		return $this->connection->insert($sql, $bindings);
	}
}