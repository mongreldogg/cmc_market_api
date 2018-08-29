<?php

namespace Core;

include BASE_DIR."/core/request.php";
include BASE_DIR."/core/response.php";
include BASE_DIR."/core/console.php";
include BASE_DIR."/core/cache.php";
include BASE_DIR."/core/route.php";
include BASE_DIR."/core/event.php";
include BASE_DIR."/core/template.php";


class Core extends Route{
	
	public function __construct(){
		
		@session_start();
		
		$command = $_SERVER["REQUEST_URI"];
		if(strpos($command, ROOT_DIR) == 0) $command = substr($command, strlen(ROOT_DIR));
		
		//Security bypass prevention for reserved route patterns
		if(substr($command, 0, strlen(SECURE_ROUTE)) == SECURE_ROUTE) exit;
		
		$route = null;
		if(isset(parent::$routes[$command])) {
			$route = parent::$routes[$command];
		}
		foreach(parent::$routes as $key => $_route) {
			if (@preg_match("/^".str_replace("/","\\/",$key)."(\?.*)?$/",$command))
			{
				if(is_array($_route)){
					if(!(@$_route['__delegate'] instanceOf IReverseRoute))
						if(@$_route['__delegate'] instanceOf IRoute){
							$childRoutes = $_route['__delegate']::own();
							@$straight = $childRoutes[$command];
							if($straight) {
								$_route['__delegate']::call($command);
								exit;
							}
							foreach($childRoutes as $pattern=>$callback){
								if (@preg_match("/^".str_replace("/","\\/",$pattern)."(\?.*)?$/",$command)) {
									$_route['__delegate']::call($pattern);
									exit;
								}
							}
						}
				} else $route = $_route;
			}
		}
		if($route == null && isset(parent::$routes[NOTFOUND])){
			$route = parent::$routes[NOTFOUND];
		}
		if(is_callable($route)) $route();
		exit;
		
	}
	
}

