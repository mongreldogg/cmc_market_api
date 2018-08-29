<?php

namespace Core;

class Route implements IRoute {
	
	public function __construct()
	{
	}
	
	protected static $routes = array();
	
	public static function on($pattern, $callback){
		self::$routes[$pattern] = $callback;
	}
	
	public static function own(){
		return self::$routes;
	}
	
	public static function call($pattern){
		self::$routes[$pattern]();
	}
	
	public static function delegate($pattern, $route){
		@$child = new $route;
		if(($child instanceof IRoute) && !($child instanceof IReverseRoute))
			self::$routes[$pattern] = array(
				'__delegate' => $child
			);
	}
	
}

interface IRoute {
	
	public function __construct();
	public static function on($pattern, $callback);
	public static function call($pattern);
	public static function own();
	
}

define('REQUEST_FLAGS_ALL', 0);

interface IReverseRoute extends IRoute {
	
	public static function request($method, $url, $data, $flags = REQUEST_FLAGS_ALL);
	
}
