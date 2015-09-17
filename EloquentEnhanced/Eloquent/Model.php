<?php
namespace EloquentEnhanced\Eloquent;

/**
 * Extends of \Illuminate\Database\Eloquent\Model allowing us to add behaviors
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
	/**
	 * I'm actually working on a database i didn't choose.
	 * So i can't use this default features of eloquent ><
	 */
	public static $snakeAttributes = false;
	public $timestamps             = false;

	/**
	 * This we contains the instance of the manager.
	 * It ensures us to use the same object each time we use it by calling $model->manager.
	 * @var null
	 */
	protected $managerInstance     = null;

	/**
	 * Return an instance of the manager class corresponding the model
	 * @return \EloquentEnhanced\Eloquent\Manager or null
	 */
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

	/**
	 * allow us to use the accessor system of eloquent to retrieve the manager
	 * $model->manager
	 * @return [type] [description]
	 */
	public function getManagerAttribute(){
		return $this->manager();
	}

	/**
	 * Add an empty clause to the query.
	 * $query->whereEmpty('name');
	 *
	 * @param  string $field
	 * @return \Illuminate\Database\Query\Builder|static
	 */
	public function scopeWhereEmpty($query, $field)
	{
		return $query->where(function($where)use($field){
			$where->whereNull($field)->orWhere(\DB::raw("TRIM($field)"),'');
		});
	}

	/**
	 * Extends of \Illuminate\Database\Eloquent\Model::newCollection
	 * allowing us to extend the \Illuminate\Database\Eloquent\Collection class
	 */
	public function newCollection(array $models = array())
	{
		return new \EloquentEnhanced\Eloquent\Collection($models);
	}

	/**
	 * Extends of \Illuminate\Database\Eloquent\Model::newBaseQueryBuilder
	 * allowing us to extend the \Illuminate\Database\Query\Builder class
	 */
	protected function newBaseQueryBuilder()
	{
		$conn = $this->getConnection();

		$grammar = $conn->getQueryGrammar();

		return new \EloquentEnhanced\Query\Builder($conn, $grammar, $conn->getPostProcessor());
	}

	/**
	 * Determine if the relation is already loaded or not
	 * @param  string  $relation name of the relation
	 * @return boolean
	 */
	public function isRelationLoaded($relation)
	{
		return isset($this->relations[$relation]);
	}

	/**
	 * Eager load relations on the model. Only if its not already loaded
	 *
	 * @param  array|string  $relations
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function loadOnce($relations)
	{
		if (is_string($relations)) $relations = func_get_args();

		$that = $this;

		$relations = array_filter($relations, function($relation) use ($that){
			return !$that->isRelationLoaded($relation);
		});

		return $this->load($relations);
	}

	/**
	 * Add a join clause to the query automatically using the informations of the relation
	 * @param  string  $relations
	 * @param  string  $type
	 * @param  string  $operator
	 * @param  boolean $where
	 * @return \Illuminate\Database\Query\Builder|static
	 */
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

	/**
	 * Define a permissive one-to-one relation
	 * @param  string  $related
	 * @param  Closure $matchSQL
	 * @param  Closure $matchPHP
	 * @return \EloquentEnhanced\Eloquent\Relations\RelatedTo
	 */
	public function relatedTo($related, Closure $matchSQL, Closure $matchPHP)
	{
		$instance = new $related;

		return new \EloquentEnhanced\Eloquent\Relations\RelatedTo($instance->newQuery(), $this, $matchSQL, $matchPHP);
	}

	/**
	 * Define a permissive one-to-many relation
	 * @param  string  $related
	 * @param  Closure $matchSQL
	 * @param  Closure $matchPHP
	 * @return \EloquentEnhanced\Eloquent\Relations\RelatedToMany
	 */
	public function relatedToMany($related, Closure $matchSQL, Closure $matchPHP)
	{
		$instance = new $related;

		return new \EloquentEnhanced\Eloquent\Relations\RelatedToMany($instance->newQuery(), $this, $matchSQL, $matchPHP);
	}
}