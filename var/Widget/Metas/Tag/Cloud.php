<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 标签云
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 标签云组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Metas_Tag_Cloud extends Widget_Abstract_Metas
{
    /**
     * 入口函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $this->parameter->setDefault(array('sort' => 'count', 'ignoreZeroCount' => false, 'desc' => true, 'limit' => 0));
        //$select = $this->select()->where('type = ?', 'tag')->order($this->parameter->sort,
        //$this->parameter->desc ? Typecho_Db::SORT_DESC : Typecho_Db::SORT_ASC);

        /** 忽略零数量 */
        //if ($this->parameter->ignoreZeroCount) {
        //    $select->where('count > 0');
        //}

        /** 总数限制 */
        //if ($this->parameter->limit) {
        //    $select->limit($this->parameter->limit);
        //}
        $data = Cache_Main::$_sections['meta'];
        foreach($data as $meta)
        {
        	if($meta['type'] == 'tag' && (!$this->parameter->ignoreZeroCount || $meta['count'] > 0)) $this->stack[] = $meta;
        }
        
        usort($this->stack, array(__CLASS__, "tagsort"));
        if($this->parameter->limit != 0 && count($this->stack) > $this->parameter->limit) $this->stack = array_slice($this->stack, 0, $this->parameter->limit);
        
        reset($this->stack);
        $this->row = current($this->stack);
        $this->length = count($this->stack);

        //$this->db->fetchAll($select, array($this, 'push'));
    }

    /**
     * 按分割数输出字符串
     *
     * @access public
     * @param string $param 需要输出的值
     * @return void
     */
    public function split()
    {
        $args = func_get_args();
        array_unshift($args, $this->count);
        echo call_user_func_array(array('Typecho_Common', 'splitByCount'), $args);
    }
    
    public function tagsort($a, $b)
    {
    	if($a[$this->parameter->sort] == $b[$this->parameter->sort]) return 0;
    	$r = $a[$this->parameter->sort] > $b[$this->parameter->sort] ? 1 : -1;
    	if($this->parameter->desc) $r = -$r;
    	return $r;
    }
}
