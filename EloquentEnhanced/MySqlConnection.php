<?php
namespace EloquentEnhanced;

/**
 * Extends of \Illuminate\Database\MySqlConnection
 * alowing us to add trace information on the queryLog
 */
class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
	/**
	 * Log a query in the connection's query log.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @param  $time
	 * @return void
	 */
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