<?php

namespace Core;

define('CONSOLE_ANY', '__any');

class Console {

	private static $commands = [];
	
	public static function AddCommand($command, $callback){
		self::$commands[$command] = $callback;
	}
	
	public static function Execute($command, $param){
		if(isset(self::$commands[$command])) self::$commands[$command]($param);
		else self::$commands[CONSOLE_ANY]();
	}

}