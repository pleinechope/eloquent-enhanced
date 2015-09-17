<?php
namespace EloquentEnhanced\Eloquent;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
	public function isLoaded($relation)
	{
		return $this->first() && $this->first()->isRelationLoaded($relation);
	}

	public function lists($value, $key = null)
	{
		return data_pluck($this->items, $value, $key);
	}

	public function loadOnce($relations)
	{
		if (is_string($relations)) $relations = func_get_args();

		$that = $this;

		$relations = array_filter($relations, function($relation) use ($that){
			return !$that->isLoaded($relation);
		});

		return $this->load($relations);
	}

	/**
	 * fetch the first element by his values
	 *
	 * first element named foo :
	 * $item = $collection->firstMatch('language.name', 'foo');
	 *
	 * first element named foo and id = 2
	 * $item = $collection->firstMatch(array(
	 * 	'id' => 2,
	 * 	'language.name' => 'foo',
	 * ));
	 *
	 * first element with name like foo_id
	 * $item = $collection->firstMatch('language.name', function($item){return 'foo_'.$item->id});
	 *
	 * first element with name like foo_id and id = 2
	 * $item = $collection->firstMatch(array(
	 * 	'id' => 2,
	 * 	'language.name' => function($item){return 'foo_'.$item->id},
	 * ));
	 */
	public function firstMatch($matchs, $value = null)
	{
		if(!is_array($matchs)){
			$matchs = array($matchs => $value);
		}

		return $this->first(function($key,$line)use($matchs)
		{
			foreach($matchs as $collumn => $value){

				$value = $value instanceof Closure ? $value($line) : $value;

				if(data_get($line,$collumn) != $value){
					return false;
				}
			}
			return true;
		});
	}

	public function filterMatch($matchs, $value = null)
	{
		if(!is_array($matchs)){
			$matchs = array($matchs => $value);
		}

		return $this->filter(function($line)use($matchs)
		{
			foreach($matchs as $collumn => $value){

				$value = $value instanceof Closure ? $value($line) : $value;

				if(data_get($line,$collumn) != $value){
					return false;
				}
			}
			return true;
		});
	}
}