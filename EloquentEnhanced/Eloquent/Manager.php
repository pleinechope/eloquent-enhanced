<?php
namespace EloquentEnhanced\Eloquent;

class Manager
{
	public $model = null;

	public function __construct(&$model)
	{
		$this->model = &$model;
	}
}