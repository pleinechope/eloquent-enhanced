<?php
namespace EloquentEnhanced\Eloquent;

/**
 * Extends of \Illuminate\Database\Eloquent\Collection
 * allowing us to add some behavoirs
 */
class Collection extends \Illuminate\Database\Eloquent\Collection
{
	/**
	 * Determine if the relation is already loaded or not for the first ellement of the collection
	 * @param  string  $relation name of the relation
	 * @return boolean
	 */
	public function isLoaded($relation)
	{
		return $this->first() && $this->first()->isRelationLoaded($relation);
	}

	/**
	 * Eager load relations on the models of the collection. Only if they are not already loaded.
	 *
	 * @param  array|string  $relations
	 * @return \EloquentEnhanced\Eloquent\Collection
	 */
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
	 * Extend of the lists function using data_pluck instead of array_pluck
	 * make this function work for models et models' relations
	 * @param  string  $value
	 * @param  string  $key
	 * @return array
	 */
	public function lists($value, $key = null)
	{
		return data_pluck($this->items, $value, $key);
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

	/**
	 * filter all element that don't match conditions :
	 *
	 * elements named foo :
	 * $item = $collection->firstMatch('language.name', 'foo');
	 *
	 * elements named foo and id = 2
	 * $item = $collection->firstMatch(array(
	 * 	'id' => 2,
	 * 	'language.name' => 'foo',
	 * ));
	 *
	 * elements with name like foo_id
	 * $item = $collection->firstMatch('language.name', function($item){return 'foo_'.$item->id});
	 *
	 * elements with name like foo_id and id = 2
	 * $item = $collection->firstMatch(array(
	 * 	'id' => 2,
	 * 	'language.name' => function($item){return 'foo_'.$item->id},
	 * ));
	 *
	 * @param  [type] $matchs [description]
	 * @param  [type] $value  [description]
	 * @return [type]         [description]
	 */
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