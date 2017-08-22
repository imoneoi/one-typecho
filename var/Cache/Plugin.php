<?php

@error_reporting(E_ALL);

class Cache_Plugin
{
	public static $_db = NULL;
    public static $_db_prefix = "";
    public static $_db_queries = 0;

    public static $_quotes = array('\'', '"', '`');

    /** For Advanced Options **/
    public static $_options;
	
    public static function on_init()
    {
    	try
    	{
    		self::$_db = Typecho_Db::get();
    		self::$_db_prefix = self::$_db->getPrefix();
    	}
		catch(Exception $e)
    	{
    		//installing!
    		self::$_db = NULL;
    		self::$_db_prefix = '';
    	}
    	//set expire
    	//Cache_Main::$_section_expire['countsql'] = 86400;
    }
	
    /** May called for multiple times ... **/
	public static function on_load()
	{
        if(!Cache_Main::exist('relation')) self::rl_init();
        else Cache_Main::get('relation');
        
        if(!Cache_Main::exist('meta')) self::meta_init();
        else Cache_Main::get('meta');
        
        if(!Cache_Main::exist('postlink')) self::postlink_init();
        else Cache_Main::get('postlink');
	}
	
	public static function wipe()
	{
		Cache_Main::wipe();
		self::on_load();
	}
	
    public static function on_end()
    {
        Cache_Main::finalize();
    }
    
    public static function db_readWhere($where, $key)
    {
        $i = strpos($where, '(');
        while($i !== false)
        {
            $where = substr($where, $i+1);
            $i = strpos($where, ')');
            $keyvalue = @explode('=', substr($where, 0, $i));
            
            $keyvalue[0] = trim($keyvalue[0]);
            if(in_array(substr($keyvalue[0],0,1), self::$_quotes)) $keyvalue[0] = substr($keyvalue[0], 1, -1);
            if($keyvalue[0] == $key)
            {
                $keyvalue[1] = trim($keyvalue[1]);

                if(in_array(substr($keyvalue[1],0,1), self::$_quotes)) $keyvalue[1] = substr($keyvalue[1], 1, -1);
                return $keyvalue[1];
            }
            
            $i = strpos($where, '(');
        }
        return false;
    }

    public static function db_readRow($rows, $target) {
        foreach($rows as $key => $val) {
            $name = trim($key);
            if(in_array($name[0], self::$_quotes)) $name = substr($name, 1, -1);
            if($name == $target) return $val;
        }
        return false;
    }

    public static function on_writedb($query, $insertId)
    {
    	$wipeCount = false;
        if($query->_sqlPreBuild['table'] == (self::$_db_prefix . 'options'))
        {
            //options
            $name = self::db_readWhere($query->_sqlPreBuild['where'], 'name');

            if($name !== false)
            {
                if(!isset(Cache_Main::$_sections['options'])) Cache_Main::get('options');
                if(isset(Cache_Main::$_sections['options']))
                {
                    if($query->_sqlPreBuild['action'] == Typecho_Db::DELETE && isset(Cache_Main::$_sections['options'][$name])) unset(Cache_Main::$_sections['options'][$name]);
                    else Cache_Main::$_sections['options'][$name] = $query->_sqlPreBuild['rowdata']['value'];
                    Cache_Main::set('options');
                }
            }
			elseif($query->_sqlPreBuild['action'] == Typecho_Db::INSERT)
			{
				if(!empty($query->_sqlPreBuild['rowdata']['name']) && ($query->_sqlPreBuild['rowdata']['user'] == 0))
				{
					Cache_Main::$_sections['options'][$query->_sqlPreBuild['rowdata']['name']] = $query->_sqlPreBuild['rowdata']['value'];
					Cache_Main::set('options');
				}
			}
        }
        elseif($query->_sqlPreBuild['table'] == (self::$_db_prefix . 'metas'))
        {
            $widget = Typecho_Widget::widget('Widget_Abstract_Metas');
            if($query->_sqlPreBuild['action'] == Typecho_Db::INSERT)
            {
                $query->_sqlPreBuild['rowdata']['mid'] = $insertId;
                Cache_Main::$_sections['meta'][$insertId] = $widget->filter($query->_sqlPreBuild['rowdata']);
                Cache_Main::set('meta');
            }
            elseif($query->_sqlPreBuild['action'] == Typecho_Db::UPDATE)
            {
                $mid = self::db_readWhere($query->_sqlPreBuild['where'], 'mid');
                if($mid !== false && isset(Cache_Main::$_sections['meta'][$mid]))
                {
                    //count
                    $count = self::db_readRow($query->_sqlPreBuild['rows'], 'count');
                    if($count)
                    {
                        if(strpos($count, '+') !== false)
                            Cache_Main::$_sections['meta'][$mid]['count']++;
                        elseif(strpos($count, '-') !== false)
                            Cache_Main::$_sections['meta'][$mid]['count']--;
                    }
                    Cache_Main::$_sections['meta'][$mid] = $widget->filter(array_merge(Cache_Main::$_sections['meta'][$mid], $query->_sqlPreBuild['rowdata']));
                    Cache_Main::set('meta');
                }
            }
            elseif($query->_sqlPreBuild['action'] == Typecho_Db::DELETE)
            {
                $mid = self::db_readWhere($query->_sqlPreBuild['where'], 'mid');
                if($mid !== false)
                {
                    if(isset(Cache_Main::$_sections['meta'][$mid])) unset(Cache_Main::$_sections['meta'][$mid]);
                    Cache_Main::set('meta');
                }
            }
        }
        elseif($query->_sqlPreBuild['table'] == (self::$_db_prefix . 'relationships'))
        {
        	$wipeCount = true;
        	if($query->_sqlPreBuild['action'] == Typecho_Db::INSERT)
            {
            	$cid = $query->_sqlPreBuild['rowdata']['cid'];
                if(!isset(Cache_Main::$_sections['relation'][$cid])) Cache_Main::$_sections['relation'][$cid] = array();
                if(!@in_array($query->_sqlPreBuild['rowdata']['mid'], Cache_Main::$_sections['relation'][$cid]))
                {
                		Cache_Main::$_sections['relation'][$cid][] = $query->_sqlPreBuild['rowdata']['mid'];
                		Cache_Main::set('relation');
                }
            }
        	elseif($query->_sqlPreBuild['action'] == Typecho_Db::DELETE)
            {
                $cid = self::db_readWhere($query->_sqlPreBuild['where'], 'cid');
                $mid = self::db_readWhere($query->_sqlPreBuild['where'], 'mid');
                if($cid !== false && $mid !== false)
                {
                	if(isset(Cache_Main::$_sections['relation'][$cid]))
                	{
                		foreach(Cache_Main::$_sections['relation'][$cid] as $key => $t_mid)
                		{
                			if($t_mid == $mid)
                			{
                				array_splice(Cache_Main::$_sections['relation'][$cid], $key, 1);
                				Cache_Main::set('relation');
                				break;
                			}
                		}
                	}
                }
            }
        }
        elseif($query->_sqlPreBuild['table'] == (self::$_db_prefix . 'contents'))
        {
        	$wipeCount = true;
        	if($query->_sqlPreBuild['action'] == Typecho_Db::DELETE)
        	{
        		$cid = self::db_readWhere($query->_sqlPreBuild['where'], 'cid');
        		if($cid !== false)
        		{
        			$postlink = Cache_Main::get('postlink');
        			foreach($postlink as $key => $contents)
        			{
        				if($contents[0] == $cid)
        				{
        					array_splice($postlink, $key, 1);
        					Cache_Main::set('postlink', $postlink);
        					break;
        				}
        			}
        		}
        	}
			elseif($query->_sqlPreBuild['action'] == Typecho_Db::UPDATE)
			{
				self::postlink_init();
			}
			Cache_Main::remove("page");
			Cache_Main::wipestatic();
        }
		elseif($query->_sqlPreBuild['table'] == (self::$_db_prefix . 'comments'))
		{
			Cache_Main::wipestatic();
		}
        //wiping count
        if($wipeCount) Cache_Main::set('countsql', array());
    }
    
    //Relationship Cache
    public static function rl_init()
    {
        $data = self::$_db->fetchAll(self::$_db->select()->from('table.relationships'));
        $result = array();
        foreach($data as $rel)
        {
            if(!isset($result[$rel['cid']])) $result[$rel['cid']] = array();
            $result[$rel['cid']][] = $rel['mid'];
        }
        Cache_Main::set('relation', $result);
    }
    
    public static function meta_init()
    {
        $widget = Typecho_Widget::widget('Widget_Abstract_Metas');
        $data = self::$_db->fetchAll(self::$_db->select()->from('table.metas')->order('order', Typecho_Db::SORT_ASC));
        $result = array();
        foreach($data as $meta)
        {
            $result[$meta['mid']] = $widget->filter($meta);
        }
        Cache_Main::set('meta', $result);
        
        $widget = Typecho_Widget::widget('Widget_Metas_Category_List');
        foreach($result as &$meta)
        {
        	if($meta['type'] == 'category') $meta = $widget->filter($meta);
        }
        Cache_Main::set('meta', $result);
    }
    
    public static function meta_get($cid, $type)
    {
        $result = array();
        //$catFilter = $type == 'category';
        //if($catFilter) $widget = Typecho_Widget::widget('Widget_Metas_Category_List');
        if(!empty(Cache_Main::$_sections['relation'][$cid]))
        {
            foreach(Cache_Main::$_sections['relation'][$cid] as $mid)
            {
                if(isset(Cache_Main::$_sections['meta'][$mid]) && Cache_Main::$_sections['meta'][$mid]['type'] == $type)
                {
                    /*if($catFilter) $result[] = $widget->filter(Cache_Main::$_sections['meta'][$mid], false);*/
                    /*else */$result[] = Cache_Main::$_sections['meta'][$mid];
                }
            }
        }
        return $result;
    }
    
    public static function postlink_init()
    {
    	$result = array();
    	$widget = Typecho_Widget::widget('Widget_Abstract_Contents');
    	$data = self::$_db->fetchAll(self::$_db->select('table.contents.cid', 'table.contents.title', 'table.contents.slug', 'table.contents.created', 'table.contents.authorId',
        'table.contents.modified', 'table.contents.type', 'table.contents.status', 'table.contents.commentsNum', 'table.contents.order',
        'table.contents.template', 'table.contents.password', 'table.contents.allowComment', 'table.contents.allowPing', 'table.contents.allowFeed',
        'table.contents.parent')->from('table.contents')->order('created', Typecho_Db::SORT_ASC));
    	foreach($data as $post)
    	{
    		$post = $widget->filter($post);
    		$result[] = array($post['cid'], $post['title'], $post['permalink'], $post['pathinfo'], $post['status'], $post['type'], $post['created']);
    	}
    	Cache_Main::set('postlink', $result);
    }
}