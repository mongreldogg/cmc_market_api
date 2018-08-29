<?php

namespace Core;

use Core\Console;
use Core\Event;

class Cache {
	
	
	public static function Clean(){
		$files = glob('{core,bundles,routes,events}/{{*/}*,*}*.{php}', GLOB_BRACE);
		foreach($files as $file) {
			if(opcache_is_script_cached($file)) opcache_invalidate($file);
		}
		opcache_reset();
		Event::raise('onCache');
	}
	
	public static function Warmup(){
		$files = glob('{core,bundles,routes,events}/{{*/}*,*}*.{php}', GLOB_BRACE);
		foreach($files as $file) {
			if(!opcache_is_script_cached($file)) opcache_compile_file($file);
		}
		Event::raise('onCache');
	}
	
}

Console::AddCommand("cache", function($command){
	switch(true){
		case $command == "clean":
			Cache::Clean();
			print_r("Cache cleaned up!");
			break;
		case $command == "warmup":
			Cache::Warmup();
			print_r("Successful cache warmup");
			break;
		case substr($command, 0, 7) == "reload=":
			$file = BASE_DIR."/".substr($command, 7);
			Cache::ReloadFile($file);
			print_r("Reloaded");
			break;
	}
});