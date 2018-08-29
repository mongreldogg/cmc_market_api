<?php

namespace Core;

class Request{
	
	private static $request = array();
	
	public function __construct($request){
		self::$request = $request;
	}
	
	public static function Get(){
		return $_GET;
	}
	
	public static function Post(){
		return $_POST;
	}
	
	public static function Path(){
		return $_SERVER['REQUEST_URI'];
	}
	
	public static function Emulate($data){
		if(is_array($data))
			return new Request($data);
		else
			return new Request([]);
	}
	
	public static function Request(){
		@$input = json_decode(file_get_contents('php://input'), true);
		if(is_array($input))
			return array_merge($_REQUEST, $_GET, $_POST, $_COOKIE, self::$request, $input);
		else
			return array_merge($_REQUEST, $_GET, $_POST, $_COOKIE, self::$request);
	}
	
	public static function Input(){
		return file_get_contents('php://input');
	}
	
	public static function JSON(){
		return json_encode(file_get_contents('php://input'),true);
	}
	
	public static function Cookie($key = null){
		if($key == null) return $_COOKIE;
		else return $_COOKIE[$key];
	}
	
	public static function ClientIP(){
		if (isset($_SERVER)) {
			
			if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
				return $_SERVER["HTTP_X_FORWARDED_FOR"];
			
			if (isset($_SERVER["HTTP_CLIENT_IP"]))
				return $_SERVER["HTTP_CLIENT_IP"];
			
			return $_SERVER["REMOTE_ADDR"];
		}
		
		if (getenv('HTTP_X_FORWARDED_FOR'))
			return getenv('HTTP_X_FORWARDED_FOR');
		
		if (getenv('HTTP_CLIENT_IP'))
			return getenv('HTTP_CLIENT_IP');
		
		return getenv('REMOTE_ADDR');
	}
	
	public static function UserAgent(){
		return $_SERVER['HTTP_USER_AGENT'];
	}
	
}
