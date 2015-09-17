<?php
namespace EloquentEnhanced\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

class RelatedToMany extends RelatedTo
{
	public function getResults()
	{
		return $this->query->get();
	}

	public function initRelation(array $models, $relation)
	{
		foreach ($models as $model)
		{
			$model->setRelation($relation, $this->related->newCollection());
		}

		return $models;
	}

	public function match(array $models, Collection $results, $relation)
	{
		$match = $this->matchPHP;

		foreach ($models as $model)
		{
			$value = $results->filter(function($result)use($match, $model){
				return $match($model, $result);
			});

			if($value->count()){
				$model->setRelation($relation, $value);
			}
		}

		return $models;
	}
}
