<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 最新文章
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 最新评论组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Contents_Post_Recent extends Widget_Abstract_Contents
{
    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $this->parameter->setDefault(array('pageSize' => $this->options->postsListSize));

        /*$this->db->fetchAll($this->select()
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.created < ?', $this->options->gmtTime)
        ->where('table.contents.type = ?', 'post')
        ->order('table.contents.created', Typecho_Db::SORT_DESC)
        ->limit($this->parameter->pageSize), array($this, 'push'));*/

		$counter = 0;
        $gmTime = $this->options->gmtTime;
        for($i = count(Cache_Main::$_sections['postlink'])-1; $i >= 0; $i--)
        {
        	$content = Cache_Main::$_sections['postlink'][$i];
        	if($content[4] == 'publish' && $content[5] == 'post' && $content[6] <= $gmTime)
        	{
        		$this->stack[] = array('cid' => $content[0], 'title' => $content[1], 'permalink' => $content[2], 'date' => new Typecho_Date($content[6]));
        		if(++$counter >= $this->parameter->pageSize) break;
        	}
        }
        
        reset($this->stack);
        $this->row = &$this->stack[0];
        $this->length = count($this->stack);
    }
}
