<?php
namespace EloquentEnhanced;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
	public function logQuery($query, $bindings, $time = null)
	{
		if (isset($this->events))
		{
			$this->events->fire('illuminate.query', array($query, $bindings, $time, $this->getName()));
		}

		if ( ! $this->loggingQueries) return;

		$trace = array_map(function($i){return $i['file'].':'.$i['line'];}, array_slice(debug_backtrace(2), 8));

		$this->queryLog[] = compact('query', 'bindings', 'time', 'trace');
	}
}