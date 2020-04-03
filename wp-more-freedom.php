<?php

/**
 * Plugin Name:       WP More Freedom
 * Plugin URI:        https://github.com/kuanghy/wp-more-freedom
 * Description:       定制部分功能，让网站有更多的自由。包括禁止自动更新，移除仪表盘，CDN 替换等功能。
 * Version:           0.0.1
 * Author:            Huoty
 * Author URI:        http://konghy.cn/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

/** 移除后台管理页面的部分菜单项 */
function remove_admin_menus(){
    remove_menu_page( 'index.php' );                  // Dashboard
    // remove_menu_page( 'jetpack' );                    // Jetpack*
    // remove_menu_page( 'edit.php' );                   // Posts
    // remove_menu_page( 'upload.php' );                 // Media
    // remove_menu_page( 'edit.php?post_type=page' );    // Pages
    remove_menu_page( 'edit-comments.php' );          // Comments
    // remove_menu_page( 'themes.php' );                 // Appearance
    // remove_menu_page( 'plugins.php' );                // Plugins
    // remove_menu_page( 'users.php' );                  // Users
    // remove_menu_page( 'tools.php' );                  // Tools
    // remove_menu_page( 'options-general.php' );        // Settings
}

/** 后台登录页跳转，即将仪表盘重定向到其他页面 */
function dashboard_redirect(){
    wp_redirect(admin_url('edit.php'));
}

function login_redirect( $redirect_to, $request, $user ){
    return admin_url('edit.php');
}

/** 定制后台工具栏 */
function custom_admin_bar() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('updates');
    $wp_admin_bar->remove_menu('comments');
}

/** 定制后台页面文本 */
function custom_footer_text () {
     return '欢迎使用 <a href="#">xxx</a> 后台管理系统';
}

/** 轻量级可选链接 CDN 替换 simple selective CDN */
function ssc_cdn_run($html) {
	$settings = array(
		'local_hosts' => [],                    // 本地需要替换的域名
		'cdn_host' => '',                       // CDN 域名
		'cdn_exts' => 'png|jpg|jpeg|gif|ico',   // 扩展名（使用|分隔）
		'cdn_dirs' => 'wp-content|wp-includes'  // 目录（使用|分隔）
	);
	foreach(array_keys($settings) as $key) {
		$constant = sprintf('WP_SSC_%s', strtoupper($key));
		if (defined("$constant")) {
			$settings["$key"] = constant($constant);
		}
	}

	if (empty($settings["local_hosts"]) || empty($settings["cdn_host"])) {
		return $html;
	}

	$local_hosts = str_replace(['/', '.'], ['\/', '\.'], implode("|", $settings['local_hosts']));
	$cdn_host = $settings['cdn_host'];
	$cdn_exts = $settings['cdn_exts'];
	$cdn_dirs = str_replace(['-', '/'],['\-', '\/'], $settings['cdn_dirs']);

	if ($cdn_dirs) {
		$regex = '/(' . $local_hosts . ')\/((' . $cdn_dirs . ')\/[^\s\?\\\'\"\;\>\<]{1,}\.(' . $cdn_exts . '))/';
		$html = preg_replace($regex, $cdn_host . '/$2', $html);
	} else {
		$regex = '/(' . $local_hosts . ')\/([^\s\?\\\'\"\;\>\<]{1,}\.(' . $cdn_exts . '))/';
		$html = preg_replace($regex, $cdn_host . '/$2', $html);
	}
	return $html;
}

/** 关闭自动更新 */
add_filter('automatic_updater_disabled', '__return_true');

remove_action('init', 'wp_schedule_update_checks');  // 关闭更新检查定时作业
wp_clear_scheduled_hook('wp_version_check');         // 移除已有的版本检查定时作业
wp_clear_scheduled_hook('wp_update_plugins');        // 移除已有的插件更新定时作业
wp_clear_scheduled_hook('wp_update_themes');         // 移除已有的主题更新定时作业
wp_clear_scheduled_hook('wp_maybe_auto_update');     // 移除已有的自动更新定时作业


if ( is_admin() ) {
    // 移除仪表盘等菜单
    add_action('admin_menu', 'remove_admin_menus');
    add_action('load-index.php','dashboard_redirect');
    add_filter('login_redirect','login_redirect', 10, 3);

    // 定制后台页面页脚文本
    add_filter('admin_footer_text', 'custom_footer_text', 9999);

    // 定制后台工具栏
    add_action('wp_before_admin_bar_render', 'custom_admin_bar');

    // 禁止 WordPress 检查更新
    remove_action('admin_init', '_maybe_update_core');

    // 移除后台插件更新检查
    remove_action('admin_init', '_maybe_update_plugins');  // 禁止 WordPress 更新插件
    remove_action('load-plugins.php', 'wp_update_plugins');
    remove_action('load-update.php', 'wp_update_plugins');
    remove_action('load-update-core.php', 'wp_update_plugins');

    // 移除后台主题更新检查
    remove_action('admin_init', '_maybe_update_themes');  // 禁止 WordPress 更新主题
    remove_action('load-themes.php', 'wp_update_themes');
    remove_action('load-update.php', 'wp_update_themes');
    remove_action('load-update-core.php', 'wp_update_themes');
} else {
    function ssc_ob_start() {
        ob_start('ssc_cdn_run');
    }
    add_action('wp_loaded', 'ssc_ob_start');
}
