<?php
namespace EloquentEnhanced\Eloquent;

class Model extends \Illuminate\Database\Eloquent\Model
{
	public static $snakeAttributes = false;
	public $timestamps             = false;
	protected $managerInstance     = null;

	public function manager()
	{
		if($this->managerInstance){
			return $this->managerInstance;
		}
		$className = '\\'.str_replace('\Models\\','\Managers\\',get_class($this));
		if(class_exists($className)){
			return $this->managerInstance = new $className($this);
		}
		return null;
	}

	public function getManagerAttribute(){
		return $this->manager();
	}

	public function scopeWhereEmpty($query, $field)
	{
		return $query->where(function($where)use($field){
			$where->whereNull($field)->orWhere(db_raw("TRIM($field)"),'');
		});
	}

	public function newCollection(array $models = array())
	{
		return new \EloquentEnhanced\Eloquent\Collection($models);
	}

	protected function newBaseQueryBuilder()
	{
		$conn = $this->getConnection();

		$grammar = $conn->getQueryGrammar();

		return new \EloquentEnhanced\Query\Builder($conn, $grammar, $conn->getPostProcessor());
	}

	public function isRelationLoaded($relation)
	{
		return isset($this->relations[$relation]);
	}

	public function loadOnce($relations)
	{
		if (is_string($relations)) $relations = func_get_args();

		$that = $this;

		$relations = array_filter($relations, function($relation) use ($that){
			return !$that->isRelationLoaded($relation);
		});

		return $this->load($relations);
	}

	public function scopeJoinRelation($query, $relations, $type = 'inner', $operator = '=', $where = false)
	{
		$me = $this;
		$relations = (array) $relations;
		$joined = array();

		$joinRelation = function($model, $names, $constraints) use ($query,&$joinRelation,&$joined, $type, $operator, $where){
			$name = array_shift($names);

			$relation = \Illuminate\Database\Eloquent\Relations\Relation::noConstraints(function() use ($model, $name){return $model->$name();});

			$related = $relation->getRelated();

			if(!in_array($name, $joined)){
				$joined[] = $name;

				if(is_a($relation, '\Illuminate\Database\Eloquent\Relations\HasOneOrMany')){
					$one = $relation->getQualifiedParentKeyName();
					$two = $relation->getForeignKey();
				}

				if(is_a($relation, '\Illuminate\Database\Eloquent\Relations\BelongsTo')){
					$one = $relation->getQualifiedForeignKey();
					$two = $relation->getQualifiedOtherKeyName();
				}

				if(is_a($relation, '\Illuminate\Database\Eloquent\Relations\BelongsToMany')){
					$query->join($relation->getTable(), $relation->getForeignKey(), '=', $relation->getParent()->getQualifiedKeyName());
					$one = $relation->getRelated()->getQualifiedKeyName();
					$two = $relation->getOtherKey();
				}

				$query->join($related->getTable(), $one, $operator, $two, $type, $where);

				$relationQuery = $relation->getBaseQuery();

				call_user_func($constraints, $query);

				$query->getQuery()->mergeWheres($relationQuery->wheres, $relationQuery->getBindings());
			}

			if($names){
				$joinRelation($related, $names, $constraints);
			}
		};

		foreach($relations as $name => $constraints){
			if (is_numeric($name)){
				list($name, $constraints) = array($constraints, function(){});
			}

			$joinRelation($me, explode('.', $name), $constraints);
		}

		return $query;
	}

	public function relatedTo($related, Closure $matchSQL, Closure $matchPHP)
	{
		$instance = new $related;

		return new \EloquentEnhanced\Eloquent\Relations\RelatedTo($instance->newQuery(), $this, $matchSQL, $matchPHP);
	}

	public function relatedToMany($related, Closure $matchSQL, Closure $matchPHP)
	{
		$instance = new $related;

		return new \EloquentEnhanced\Eloquent\Relations\RelatedToMany($instance->newQuery(), $this, $matchSQL, $matchPHP);
	}
}