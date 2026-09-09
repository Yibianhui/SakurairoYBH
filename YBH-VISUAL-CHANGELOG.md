# SakurairoYBH 视觉改版说明（3.0.11-ybh2 / YBH 层 1.2.0）

> **Fork 归属**：EquinoxX —— 自 YBH 原版（[yiwubianjihui/SakurairoYBH](https://github.com/yiwubianjihui/SakurairoYBH)）fork，
> 上游为 Fuukei Sakurairo。署名见 `style.css` 主题头信息。

> 本次为**纯视觉/排版层**改动，不改动文章数据、设置项与页面逻辑。
> 全部新样式集中在 `css/ybh.css` 末尾新增的 **“SakurairoYBH 2.0 · Modern UI 视觉改版层”**，
> 如想整体回退，删除该段并把 `style.css` 头部 `Version` 与 `inc/ybh/bootstrap.php` 的 `YBH_VERSION` 还原即可。

## 一、针对 Issues 的视觉修复

### Issue #4 动效/遮挡
- 删除文章卡标题 hover 时 `letter-spacing: 2px → 3px` 的**字距突跳**（`style.css` 中 `.post-list-thumb .post-title h3:hover`），
  现在 hover 只平滑变色，不再“拉开字距无动画”；改版层里再做了一重同样约束作双保险。
- 元信息条（发布时间 / 热度 / 分类 / 阅读时长）遮挡修复：
  - `.post-meta` 由“单行 + 截断”改为**可自动换行**、右对齐、收缩到 `calc(100% - 164px)`，
    与左上角 `.post-date` 各占一角，长分类名/多标签不再互相压住；
  - 时间与元信息胶囊统一玻璃拟态（圆角 10px + blur + 描边 + 文字投影），暗色下仍清晰。

### Issue #1 卡片黑边 / 显示不佳
- 文章卡与展台 Bento 卡统一大圆角（20px/18px，暗色 18px）与柔和阴影体系；
- 给卡片加**圆角遮罩**（`mask-image: radial-gradient(white,black)`）+ `overflow`/`isolation`，
  消除部分设备（尤其缩放渲染）在圆角边缘出现的黑边、锯齿与背景溢出色块；
- 封面图加入平滑 hover 微放大，卡内文字浮层间距调整，暗色模式成组适配。

### Issue #3 菜单文字变蓝（视觉项）
- 顶栏 / 移动导航 / 页脚链接补上显式 `:visited { color: inherit }`，
  访问过含中文 URL 的页面后，菜单文字不再回退成浏览器默认蓝色；
- ⚠️ “中文 URL 页面一直加载”本身是**页面跳转脚本/服务端 URL 解析**层面的问题，
  属于前端 JS + 主机环境，需要在你真实站点上抓包定位（建议另开 issue），本次未改运行逻辑。

### Issue #4 “更早的文章”等按钮
- 分页“更早的文章”按钮重做为**现代胶囊按钮**：玻璃底、hover 填充主题强调色并上浮、加载态保持主题加载图。
  （“一次加载更多篇”属于 JS 行为改造，按约定不在本次视觉范围内。）

## 二、现代感排版打磨（大胆项）
- 首页焦点区：垂直居中编排、区块间距 20px、签名玻璃条更大圆角与投影、社交图标 hover 上浮变色。
- 区块标题（展台 / 文章列表）：图标收纳为色块芯片（跟随主题强调色）、标题加粗、留白更统一。
- 统计胶囊：圆角胶囊玻璃化 + hover 微浮起，暗色独立配色。
- 展台紧凑模式：栅格间隙收紧为 14px，悬浮阴影与圆角体系统一。
- 悬浮页脚：圆角顶部收口、更强的 blur 与柔影。
- 卡片悬浮统一为“上浮 + 阴影加深 + 图片微放大”，动效只作用于 transform/box-shadow/filter，不触发重排。

## 三、改动文件清单
| 文件 | 改动 |
| --- | --- |
| `style.css` | 头部 `Version: 3.0.11-ybh2`；删除标题 hover 字距跳变 |
| `css/ybh.css` | 末尾新增 “SakurairoYBH 2.0 · Modern UI 视觉改版层”（约 380 行，含注释） |
| `inc/ybh/bootstrap.php` | `YBH_VERSION` 1.1.5 → 1.2.0（刷新 ybh.css 缓存版本号） |

## 四、部署建议
1. 后台/主机替换主题目录为新的 `SakurairoYBH`（或上传 `SakurairoYBH-3.0.11-ybh2-Modern.zip`）；
2. 如站点开启了额外 CSS/自定样式，个别新样式仍可被其覆盖，属预期行为；
3. 改版层全部用 `body` 前缀抬高优先级、按暗色 `body.dark` 成组，尽量不与你后台“换肤”选项打架；
4. 若某些终端观感仍不理想，先在该段微调 `--ybh-*` 令牌（圆角/阴影/强调色），无需动其它文件。

## 五、已知待办（不在本次范围）
- #2 图片自动转 WebP + 降质（功能项，建议做在 `the_content`/上传钩子层或换图床策略）；
- #3 中文 URL 页面加载卡死（需真实站点联调）；
- #4 “更早的文章”批量加载数量（JS 行为，需按站点首页页大小调优）。
