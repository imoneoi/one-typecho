<?php

if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

define('__TYPECHO_ADMIN__', true);

/** 载入配置文件 */
if (!defined('__TYPECHO_ROOT_DIR__') && !@include_once __DIR__ . '/../config.inc.php') {
    file_exists(__DIR__ . '/../install.php') ? header('Location: ../install.php') : print('Missing Config File');
    exit;
}

/** 初始化组件 */
Typecho_Widget::widget('Widget_Init');
$user = Typecho_Widget::widget('Widget_User');

$user->pass('administrator');

Typecho_Plugin::factory('TypechoEx')->wipecache();
Cache_Main::wipe();

Typecho_Widget::widget('Widget_Notice')->set(_t("缓存清除成功"), 'success');
$response = new Typecho_Response;
$response->goBack();