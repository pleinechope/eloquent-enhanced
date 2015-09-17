<?php
namespace EloquentEnhanced\Eloquent;

/**
 * Managers classes a linked to Models classes. They have to be named same.
 * All non-static function are intend to do something on an instanciate model
 * All static function are intend to do something on non-instanciate model(s) (like retrieving/creating one or many)
 */
abstract class Manager
{
	public $model = null;

	/**
	 * This is intended to be called only by \EloquentEnhanced\Eloquent\Model::manager()
	 * It is not usefull to instanciate a manager manualy.
	 *
	 * To access a manager of a model use $model->manager
	 * @param [type] &$model [description]
	 */
	public function __construct(&$model)
	{
		$this->model = &$model;
	}
}