<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 独立页面列表
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 独立页面列表组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Contents_Page_List extends Widget_Abstract_Contents
{
    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
    	if(!Cache_Main::exist('page'))
    	{
        	$rawdata = $this->db->fetchAll($this->select()->where('table.contents.type = ?', 'page')
        		->where('table.contents.status = ?', 'publish')
        		->where('table.contents.created < ?', $this->options->gmtTime)
        		->order('table.contents.order', Typecho_Db::SORT_ASC));
        	$data = array();
        	foreach($rawdata as $page_raw)
        	{
        		$data[$page_raw['cid']] = $this->filter($page_raw);
        	}
        	Cache_Main::set('page', $data);
        }
        else $data = Cache_Main::get('page');
        
        //去掉自定义首页
        $frontPage = explode(':', $this->options->frontPage);
        if (2 == count($frontPage) && 'page' == $frontPage[0]) {
        	/*foreach($data as $key => $value) {
        		if($value['cid'] == $frontPage[1]) {
        			unset($data[$key]);
        			break;
        		}
        	}*/
        	if(isset($data[$frontPage[1]])) unset($data[$frontPage[1]]);
        }
        
        //set Stack
        $this->stack = $data;
        $this->length = count($data);
        $this->sequence = 0;
        
        reset($this->stack);
        if(!empty($this->stack)) $this->row = pos($this->stack);
    }
}
