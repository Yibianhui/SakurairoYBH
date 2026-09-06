<?php
/**
 * SakurairoYBH · 友情链接批量导入工具
 *
 * 后台路径：外观 → 友链批量导入（权限：manage_links）
 *
 * 支持格式：每行一条友链，字段之间用 | 、Tab 或两个以上空格分隔：
 *   名称 | 链接URL | 描述（可选） | 头像图片URL（可选）
 *
 * - 以 # 或 ; 开头的行视为注释，自动跳过
 * - 链接未带协议时自动补全 https://
 * - 支持 IP 形式链接（如 http://192.168.1.10/ 、[2001:db8::1]）
 * - 可选按 URL 去重（对比已有书签与本次已导入条目）
 *
 * @package SakurairoYBH
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'ybh_friend_import_menu');
function ybh_friend_import_menu()
{
    add_theme_page(
        '友链批量导入',
        '友链批量导入',
        'manage_links',
        'ybh-friend-import',
        'ybh_friend_import_page'
    );
}

/**
 * 解析单行文本 → 数组(name, url, description, image)；无效行返回 null。
 */
function ybh_friend_import_parse_line($line)
{
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || $line[0] === ';') {
        return null;
    }
    $parts = preg_split('/\s*(?:\||\t|\s{2,})\s*/', $line);
    if (!is_array($parts)) {
        return null;
    }
    $parts = array_map('trim', $parts);
    $name = isset($parts[0]) ? $parts[0] : '';
    $url = isset($parts[1]) ? $parts[1] : '';
    if ($name === '' || $url === '') {
        return null;
    }
    return array(
        'name' => $name,
        'url' => $url,
        'description' => isset($parts[2]) ? $parts[2] : '',
        'image' => isset($parts[3]) ? $parts[3] : '',
    );
}

/**
 * 规范化 URL：补协议 + 校验（FILTER_VALIDATE_URL 接受 IP 主机形式）。
 */
function ybh_friend_import_normalize_url($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    return esc_url_raw($url);
}

/**
 * 处理导入请求，返回统计结果。
 */
function ybh_friend_import_handle()
{
    $raw = isset($_POST['ybh_import_data']) ? wp_unslash($_POST['ybh_import_data']) : '';
    $skip_dup = !empty($_POST['ybh_import_skip_dup']);
    $visible = (isset($_POST['ybh_import_visible']) && $_POST['ybh_import_visible'] === 'pending') ? 'N' : 'Y';
    $category = isset($_POST['ybh_import_category']) ? intval($_POST['ybh_import_category']) : 0;

    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $existing = array();
    foreach (get_bookmarks(array('hide_invisible' => false)) as $bm) {
        $existing[trailingslashit(strtolower($bm->link_url))] = true;
    }

    $ok = array();
    $skipped = array();
    $failed = array();
    $seen = array();

    foreach ($lines as $index => $line) {
        $no = $index + 1;
        $item = ybh_friend_import_parse_line($line);
        if ($item === null) {
            if (trim($line) !== '') {
                $failed[] = sprintf('第 %d 行：格式无效（需要至少「名称 链接」两个字段）', $no);
            }
            continue;
        }

        $url = ybh_friend_import_normalize_url($item['url']);
        if ($url === '') {
            $failed[] = sprintf('第 %d 行：链接无效（%s）', $no, $item['url']);
            continue;
        }

        $image = ybh_friend_import_normalize_url($item['image']);

        $key = trailingslashit(strtolower($url));
        if ($skip_dup && (isset($existing[$key]) || isset($seen[$key]))) {
            $skipped[] = sprintf('第 %d 行：%s（%s）已存在，跳过', $no, $item['name'], $url);
            continue;
        }
        $seen[$key] = true;

        $link_id = wp_insert_link(array(
            'link_name' => sanitize_text_field($item['name']),
            'link_url' => $url,
            'link_description' => sanitize_text_field($item['description']),
            'link_image' => $image,
            'link_target' => '_blank',
            'link_visible' => $visible,
            'link_rel' => 'friend',
            'link_rating' => 0,
        ));

        if (is_wp_error($link_id) || !$link_id) {
            $failed[] = sprintf('第 %d 行：%s 写入失败', $no, $item['name']);
            continue;
        }

        if ($category > 0) {
            wp_set_object_terms($link_id, array($category), 'link_category');
        }
        $ok[] = sprintf('第 %d 行：%s（%s）', $no, $item['name'], $url);
    }

    return array('ok' => $ok, 'skipped' => $skipped, 'failed' => $failed);
}

/**
 * 管理页面渲染。
 */
function ybh_friend_import_page()
{
    if (!current_user_can('manage_links')) {
        wp_die('权限不足：需要「manage_links」权限。');
    }

    $result = null;
    if (isset($_POST['ybh_import_nonce']) && wp_verify_nonce($_POST['ybh_import_nonce'], 'ybh_friend_import')) {
        $result = ybh_friend_import_handle();
    }

    $categories = get_terms(array(
        'taxonomy' => 'link_category',
        'hide_empty' => false,
    ));
    ?>
    <div class="wrap">
        <h1>友链批量导入 <span style="font-weight:normal;font-size:13px;color:#666;">SakurairoYBH</span></h1>

        <?php if (is_array($result)) : ?>
            <div class="notice notice-success"><p>
                导入完成：成功 <strong><?php echo count($result['ok']); ?></strong> 条，
                跳过 <?php echo count($result['skipped']); ?> 条，
                失败 <?php echo count($result['failed']); ?> 条。
                <?php if (count($result['ok']) > 0) : ?>
                    <a href="<?php echo esc_url(admin_url('link-manager.php')); ?>">前往链接管理器查看 →</a>
                <?php endif; ?>
            </p></div>
            <?php if (!empty($result['failed'])) : ?>
                <div class="notice notice-warning"><p><strong>未成功明细：</strong><br>
                    <?php echo esc_html(implode("\n", $result['failed'])); ?></p></div>
            <?php endif; ?>
        <?php endif; ?>

        <p>每行一条友链，字段用 <code>|</code>、Tab 或两个以上空格分隔；<code>#</code> 或 <code>;</code> 开头的行视为注释：</p>
        <p><code>站点名称 | https://example.com/ | 站点描述 | https://example.com/avatar.png</code></p>
        <p>支持 IP 形式链接（如 <code>http://192.168.1.10/</code>）；未带协议的链接自动补全 <code>https://</code>。</p>

        <form method="post">
            <?php wp_nonce_field('ybh_friend_import', 'ybh_import_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ybh_import_data">友链列表</label></th>
                    <td>
                        <textarea name="ybh_import_data" id="ybh_import_data" rows="12" class="large-text code"
                            placeholder="示例：&#10;张三的博客 | https://zhangsan.example.com/ | 前端开发 | https://zhangsan.example.com/avatar.png&#10;内网NAS | http://192.168.1.10/ | 家庭服务器"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">导入选项</th>
                    <td>
                        <p>
                            <label><input type="checkbox" name="ybh_import_skip_dup" value="1" checked> 按 URL 去重（已存在的链接自动跳过）</label>
                        </p>
                        <p>
                            <label><input type="radio" name="ybh_import_visible" value="visible" checked> 立即可见</label>
                            &nbsp;&nbsp;
                            <label><input type="radio" name="ybh_import_visible" value="pending"> 待审核</label>
                        </p>
                        <p>
                            <label>归属链接分类：
                                <select name="ybh_import_category">
                                    <option value="0">不分类</option>
                                    <?php if (!is_wp_error($categories)) : ?>
                                        <?php foreach ($categories as $cat) : ?>
                                            <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </label>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('开始导入', 'primary', 'ybh_import_submit'); ?>
        </form>
    </div>
    <?php
}
