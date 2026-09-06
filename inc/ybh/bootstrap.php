<?php
/**
 * SakurairoYBH · YBH 增量层（fork 魔改入口）
 *
 * - 摘除上游"主题目录必须叫 Sakurairo"的强制检查（保住 fork 目录名）
 * - 默认字体注入更纱黑体（仅当选项仍为旧默认/空值时，用户显式自定义优先）
 * - 加载 YBH 样式层 css/ybh.css（字体系统/标题签名/展台/默认样式烘焙）
 * - 字体 CDN 预连接 + 首屏关键字重预加载
 * - 裁剪前端 Emoji 脚本（后台保留，dashboard-emoji-fix 不受影响）
 * - 注册友链批量导入工具
 *
 * @package SakurairoYBH
 */

if (!defined('ABSPATH')) {
    exit;
}

define('YBH_FONT_CDN', 'https://download.yibianhui.cn/fonts');
define('YBH_VERSION', '1.0.0');

/**
 * 1) 上游在 admin_init 会把非 Sakurairo 目录强制改名回 Sakurairo，
 *    对 fork 而言这是破坏性行为，必须解除。
 *    （functions.php 中的钩子注册先于本文件加载，此处摘除即可生效）
 */
remove_action('admin_init', 'theme_folder_check_on_admin_init');

/**
 * 2) 默认字体：更纱黑体。
 *    仅当主题选项仍为旧默认（Noto Serif SC）或为空时替换；
 *    用户在主题设置里显式填写的字体一律尊重（保留原格式选项）。
 */
add_filter('option_iro_options', 'ybh_font_option_defaults');
function ybh_font_option_defaults($value)
{
    if (!is_array($value)) {
        return $value;
    }
    $sarasa = "'Sarasa UI SC','PingFang SC','Microsoft YaHei','TH-Tshyn',sans-serif";
    $legacy = array('', 'Noto Serif SC');
    foreach (array('global_default_font', 'global_font_2') as $key) {
        $current = isset($value[$key]) ? trim((string) $value[$key]) : '';
        if ($current === '' || in_array($current, $legacy, true)) {
            $value[$key] = $sarasa;
        }
    }
    return $value;
}

/**
 * 3) YBH 样式层：排在 iro-dark / iro-responsive 之后，保证覆盖顺序。
 */
add_action('wp_enqueue_scripts', 'ybh_enqueue_layer', 20);
function ybh_enqueue_layer()
{
    wp_enqueue_style(
        'ybh-layer',
        get_template_directory_uri() . '/css/ybh.css',
        array('iro-dark', 'iro-responsive'),
        IRO_VERSION . '-ybh' . YBH_VERSION
    );
}

/**
 * 4) 字体 CDN 预连接 + 首屏关键字重预加载（Preload 只给确定会用到的字重）。
 */
add_action('wp_head', 'ybh_resource_hints', 2);
function ybh_resource_hints()
{
    echo '<link rel="preconnect" href="https://download.yibianhui.cn" crossorigin>' . "\n";
    $preloads = array(
        'sarasa/SarasaUiSC-Regular.woff2',
        'sarasa/SarasaUiSC-SemiBold.woff2',
        'lxgw/LXGWWenKai-Regular-subset.woff2',
    );
    foreach ($preloads as $file) {
        printf(
            '<link rel="preload" href="%s/%s" as="font" type="font/woff2" crossorigin>' . "\n",
            YBH_FONT_CDN,
            $file
        );
    }
}

/**
 * 5) 前端 Emoji 脚本裁剪（后台不动，dashboard-emoji-fix.css 依旧有效）。
 */
add_action('init', 'ybh_trim_front_emoji');
function ybh_trim_front_emoji()
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('embed_head', 'print_emoji_detection_script');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
}

/**
 * 6) 友链批量导入工具（外观 → 友链批量导入）。
 */
require_once get_template_directory() . '/inc/ybh/friend-importer.php';
