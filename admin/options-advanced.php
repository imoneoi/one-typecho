<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$advoptions = Cache_Plugin::$_options;

if(!empty($_POST))
{
	$user->pass('administrator');
    $security->protect();

    $advoptions = array(
        'static' => isset($_POST['static']) ? $_POST['static'] : array(),
        'sexpire' => isset($_POST['sexpire']) ? intval($_POST['sexpire']) : 86400
    );

	$db = Typecho_Db::get();
    $db->query($db->update('table.options')->where('name = ?', 'advanced')->rows(array('value' => Json::encode($advoptions) )));

	Typecho_Widget::widget('Widget_Notice')->set(_t("设置已经保存"), 'success');
    $response->goBack();
}

?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="form">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2"><?php
$form = new Typecho_Widget_Helper_Form($security->getAdminUrl('options-advanced.php'), Typecho_Widget_Helper_Form::POST_METHOD);

$edit = new Typecho_Widget_Helper_Form_Element_Checkbox('static', array(
			'index'                     =>  '首页',
            'index_page'                =>  '自定义首页',
            'archive'                   =>  '归档',
            404                         =>  '404',
            'single'                    =>  '单页',
            'page'                      =>  '页面',
            'post'                      =>  '文章',
            'attachment'                =>  '附件',
            'comment_page'              =>  '评论页',
            'category'                  =>  '分类',
            'category_page'             =>  '分类页',
            'tag'                       =>  '标签',
            'tag_page'                  =>  '标签页',
            'author'                    =>  '作者',
            'author_page'               =>  '作者页',
            'archive_year'              =>  '年归档',
            'archive_year_page'         =>  '年归档页',
            'archive_month'             =>  '月归档',
            'archive_month_page'        =>  '月归档页',
            'archive_day'               =>  '日归档',
            'archive_day_page'          =>  '日归档页',
            'search'                    =>  '搜索',
            'search_page'               =>  '搜索页'), $advoptions['static'], _t('静态缓存范围'), _t('不选择表示禁用'));
$form->addInput($edit);

$edit = new Typecho_Widget_Helper_Form_Element_Text('sexpire',NULL,$advoptions['sexpire'],_t('静态缓存过期时间'),_t('0表示永不过期'));
$form->addInput($edit);

$submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('保存设置'));
$submit->input->setAttribute('class', 'btn primary');
$form->addItem($submit);

$form->render();
            ?></div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
include 'footer.php';
?>
