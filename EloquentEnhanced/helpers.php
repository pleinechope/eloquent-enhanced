<?php

if ( ! function_exists('data_pluck')){
	/**
	 * Pluck an array of values from an array of object.
	 *
	 * @param  array   $array
	 * @param  string  $value
	 * @param  string  $key
	 * @return array
	 */
	function data_pluck($target, $value, $key = null){
		$results = array();

		foreach ($target as $item){
			$itemValue = data_get($item,$value);

			// If the key is "null", we will just append the value to the array and keep
			// looping. Otherwise we will key the array using the value of the key we
			// received from the developer. Then we'll return the final array form.
			if (is_null($key)){
				$results[] = $itemValue;
			}else{
				$itemKey = data_get($item,$key);
				$results[$itemKey] = $itemValue;
			}
		}
		return $results;
	}
}