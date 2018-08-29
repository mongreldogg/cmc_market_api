<?php

namespace Core;

class Event {
	
	private static $events = [];
	
	public static function add($name, $callback){
		self::$events[$name][] = $callback;
	}
	
	public static function raise($name, $arguments = null){
		$result = null;
		if(is_array(self::$events[$name])){
			foreach(self::$events[$name] as $event){
				if($arguments == null) $result = $event();
				else $result = $event($arguments);
			}
		}
		return $result;
	}
	
	public static function dump(){
		return var_export(self::$events, true);
	}
	
}