<?php 
namespace Bobby\Component\Purifier;

class Purifier
{
	public static function filterByCallBacks($var, array $callbacks)
	{
		if(is_array($value)) foreach ($value as $k => $v) $value[$k] = static::filterByCallBacks($v, $callbacks);

		else return array_reduce($callbacks, function($value, $callback) {
		        return call_user_func($callback, $value);
		    }, $value);

		return $value;
	} 
}