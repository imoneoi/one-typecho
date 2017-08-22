<?php

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

define("__APCU__", function_exists("apcu_cache_info"));
define("__APC__", __APCU__ || function_exists("apc_cache_info"));

class Cache_Main
{
    const SAFETY_HEAD = '<? exit; ?>';
    
    public static $_cacheDir = '';
    public static $_sections = array();
    public static $_section_mod = array();
    public static $_section_expire = array();

	public static $_apcPrefix;
    
    public static function init()
    {
    	if(__APC__) self::$_apcPrefix = Typecho_Db::get()->getPrefix();
    	else {
        	self::$_cacheDir = __TYPECHO_ROOT_DIR__ . '/usr/cache_' . self::$_apcPrefix . '/';
        	if(!@is_dir(self::$_cacheDir)) mkdir(self::$_cacheDir);
        }
    }
    
    public static function finalize()
    {
    	if(__APC__) {
    		foreach(self::$_section_mod as $mod) {
    			$name = self::$_apcPrefix . $mod;
    			$data = Json::encode(self::$_sections[$mod]);
    			$exp = isset(self::$_section_expire[$mod]) ? self::$_section_expire[$mod] : 0;

    			__APCU__ ? apcu_store($name, $data, $exp) : apc_store ($name, $data, $exp);
    		}
    	}
    	else {
	        foreach(self::$_section_mod as $mod)
	            @file_put_contents(self::$_cacheDir . $mod . ".data.php", self::SAFETY_HEAD . @Json::encode(self::$_sections[$mod]));
	    }
        self::$_section_mod = array();
    }
    
    public static function get($section)
    {
        if(!isset(self::$_sections[$section]))
        {
        	if(__APC__) {
        		$name = self::$_apcPrefix . $section;
        		$data = @Json::decode(__APCU__ ? apcu_fetch($name) : apc_fetch($name), true);
        		if(!$data) $data = array();

        		self::$_sections[$section] = $data;
        	}
        	else {
	            $file = self::$_cacheDir . $section . ".data.php";
	            if(@file_exists($file))
	            {
	                if(isset(self::$_section_expire[$section]) && (time() - @filemtime($file)) > self::$_section_expire[$section]) 
	                {
	                    self::$_sections[$section] = array();
	                    @unlink($file);
	                }
	                else
	                {
	                    self::$_sections[$section] = @Json::decode(@substr(@file_get_contents($file), strlen(self::SAFETY_HEAD)), true);
	                    if(!self::$_sections[$section]) self::$_sections[$section] = array();
	                }
	            }
	            else self::$_sections[$section] = array();
	        }
        }
        return self::$_sections[$section];
    }
    
    public static function exist($section)
    {
    	if(__APC__) {
    		$name = self::$_apcPrefix . $section;
    		return __APCU__ ? apcu_exists($name) : apc_exists($name);
    	} else {
	        $file = self::$_cacheDir . $section . ".data.php";
	        if(@file_exists($file))
	        {
	            if(isset(self::$_section_expire[$section]) && (time() - @filemtime($file)) > self::$_section_expire[$section]) 
	            {
	                self::$_sections[$section] = array();
	                @unlink($file);
	            }
	            else return true;
	        }
	        return false;
	    }
    }
    
    public static function set($section, $value = NULL)
    {
        if(!@in_array($section, self::$_section_mod)) array_push(self::$_section_mod, $section);
        
        if($value != NULL) self::$_sections[$section] = $value;
    }

	public static function remove($cache)
	{
		if(__APC__) {
			$name = self::$_apcPrefix . $section;
    		__APCU__ ? apcu_delete($name) : apc_delete($name);
		}
		else {
			$fn = self::$_cacheDir . $cache . ".data.php";
			if(@file_exists($fn)) @unlink($fn);
		}
	}

	public static function wipestatic()
	{
		if(!__APC__) {
			$files = glob(self::$_cacheDir . "static/*.htm");
			if($files)
			{
				foreach($files as $file) @unlink($file);
			}
		}
	}

	public static function wipe()
	{
		if(__APC__) __APCU__ ? apcu_clear_cache() : apc_clear_cache();
		else {
			$files = glob(self::$_cacheDir . "*.data.php");
			if($files)
			{
				foreach($files as $file)
				{
					if(strtolower(basename($file)) != 'advoptions.data.php') @unlink($file);
				}

				self::$_sections = array();
				self::$_section_mod = array();
			}
			self::wipestatic();
		}
	}
}

Cache_Main::init();