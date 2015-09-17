<?php
namespace EloquentEnhanced\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;

/**
 * New kind of one-to-one relation for eloquent. Intended to be very permissive.
 */
class RelatedTo extends \Illuminate\Database\Eloquent\Relations\Relation
{
	protected $matchSQL = null;
	protected $matchPHP = null;

	public function getResults()
	{
		return $this->query->first();
	}

	public function initRelation(array $models, $relation)
	{
		foreach ($models as $model)
		{
			$model->setRelation($relation, null);
		}

		return $models;
	}

	public function match(array $models, Collection $results, $relation)
	{
		$match = $this->matchPHP;

		foreach ($models as $model)
		{
			$value = $results->first(function($result)use($match, $model){
				return $match($model, $result);
			});

			if($value){
				$model->setRelation($relation, $value);
			}
		}

		return $models;
	}

	public function addConstraints(){
		if (static::$constraints)
		{
			$match = $this->matchSQL;
			$match($this->query, $this->parent);
		}
	}

	public function getRelationCountQuery(Builder $query, Builder $parent)
	{
		return $query->select(new Expression('count(*)'));
	}

	public function addEagerConstraints(array $models){}

	public function __construct(Builder $query, Model $parent, Closure $matchSQL, Closure $matchPHP)
	{
		$this->matchSQL = $matchSQL;
		$this->matchPHP = $matchPHP;

		parent::__construct($query, $parent);
	}
}
