<?php

namespace Core;

class Template {
	
	private static $templates = [];
	private static $globals = [];
	
	public static function Get($templateName){
		$path = BASE_DIR.'/templates/'.$templateName.'.tpl';
		if(isset(self::$templates[$path]))
			return self::$templates[$path];
		elseif(@self::$templates[$path] = file_get_contents($path))
			return self::$templates[$path];
		else
			throw new \Exception("Template $path not found!");
	}
	
	public static function Tag($tag, &$from, $replace_with="", &$prop_val=null){
		
		$tag_pattern = "/\[$tag(=[a-zA-Z0-9\,\.]*)?\](.*)?\[\/$tag\].*/Usx";
		
		$matches = null;
		
		@preg_match($tag_pattern, $from, $matches);
		$from = preg_replace($tag_pattern, $replace_with, $from, 1);
		
		$prop_val = substr($matches[1], 1);
		
		return $matches[2];
		
	}
	
	public static function Replace($in_array, $text) {
		
		$replace_array = array();
		
		foreach ($in_array as $key => $value) {
			
			$replace_array[$key] = "{".$key."}";
			
		}
		
		return str_replace($replace_array, $in_array, $text);
		
	}
	
	public static function Render(&$contents, $variables){
		
		$contents = self::Replace(array_merge(self::$globals, $variables), $contents);
		if(PACK_HTML) $contents = preg_replace('/(\t|\r|\n)+/', '', $contents);
		return $contents;
		
	}
	
	public static function SetGlobal($name, $value) {
		self::$globals[$name] = $value;
	}
	
	public static function GenerateView($content){
		$main = Template::Get('main');
		$main = Template::Replace(['content' => $content], $main);
		Template::SetGlobal('base_href', HOSTNAME.ROOT_DIR);
		$keywords = [];
		while($keyword = Template::Tag('keywords', $main)){
			$keywords[] = $keyword;
		}
		Template::SetGlobal('keywords', implode(',', $keywords));
		$description = '';
		while($descr = Template::Tag('description', $main)) $description = $descr;
		Template::SetGlobal('description', $description);
		$title = [];
		while($t = Template::Tag('title', $main)) $title[] = $t;
		$title = implode(TITLE_DELIMITER, $title);
		Template::SetGlobal('title', $title);
		Response::HTML(Template::Render($main, []));
	}
	
}
