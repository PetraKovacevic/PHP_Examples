<?php
// To do: Add check so that if the path does not exist
// it returns False
class Config{
	public static function get($path = null){
		if ($path){
			$config = $GLOBALS['config'];
			$path = explode('/', $path);
			
			foreach($path as $bit){
				if(isset($config[$bit])){
					$config = $config[$bit];
				}
			}
			
			return $config;
		}
		
		return false;
	}
}
?>