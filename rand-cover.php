<?php
/**
 * YBH · 轻量随机封面端点
 *
 * 为什么不走 REST：`/wp-json/sakura/v1/gallery` 每次请求都会**完整启动 WordPress**
 * （内核 + 全部插件 + 主题），再 302 跳转。首页有 10 张封面 ⇒ 每次访问 = 10 次重量级
 * PHP 启动。本文件**不加载 WordPress**，只读索引 JSON 后 302，耗时从数百毫秒降到几毫秒。
 *
 * 用法：rand-cover.php?img=w|l
 *   w = 横图（宽卡用，索引里的 wide）  l = 竖图（长卡用，索引里的 long）  缺省 = 全部
 *
 * 缓存：响应带 Cache-Control: max-age=60 —— 浏览器一分钟内复用这次随机结果，
 * 既明显减少请求，又保持「每次刷新基本都换图」的随机感。
 *
 * 索引由主题后台「随机图 API → 初始化索引」生成：wp-content/uploads/iro_gallery/imglist.json
 */

declare(strict_types=1);

$upload_dir = dirname(__DIR__, 2) . '/uploads/iro_gallery';   // wp-content/uploads/iro_gallery
$list_file  = $upload_dir . '/imglist.json';

// 一分钟缓存：同一访客一分钟内命中同一次随机结果
header('Cache-Control: public, max-age=60');
header('Vary: Accept-Encoding');

if (!is_readable($list_file)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'image index not found';
    exit;
}

$raw = file_get_contents($list_file);
$list = json_decode((string) $raw, true);

if (!is_array($list)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'image index broken';
    exit;
}

$wide = isset($list['wide']) && is_array($list['wide']) ? $list['wide'] : [];
$long = isset($list['long']) && is_array($list['long']) ? $list['long'] : [];

$kind = isset($_GET['img']) ? strtolower((string) $_GET['img']) : '';

if ($kind === 'w' && $wide) {
    $pool = $wide;
} elseif ($kind === 'l' && $long) {
    $pool = $long;
} else {
    $pool = array_merge($long, $wide);
}

if (!$pool) {
    http_response_code(404);
    exit;
}

$pick = $pool[random_int(0, count($pool) - 1)];

// 索引里存的是 /iro_gallery/img/... 这样的相对路径，拼绝对地址
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'www.yibianhui.cn';
$target = $scheme . '://' . $host . '/wp-content/uploads' . $pick;

header('Location: ' . $target, true, 302);
exit;
