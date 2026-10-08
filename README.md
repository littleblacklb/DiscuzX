![25 年产品历史](https://img.shields.io/badge/Years-25-red?style=plastic)
![MitFrame! 1.0 框架体系](https://img.shields.io/badge/MitFrame-1.0-%23155BD5?style=plastic)
![Discuz! X5 内核](https://img.shields.io/badge/Discuz!-X5-%23F4A62F?style=plastic)
![支持 PHP 8.0](https://img.shields.io/badge/PHP-8-%237A86B8?style=plastic)
![支持 MySQL 8.0](https://img.shields.io/badge/MySQL-8-%233E6E93?style=plastic)

# Discuz! X5 快速上手

本仓库为 Discuz! X5 中文版 Git 仓库，**已内置 `vendor` 与 IP 库**，克隆后即可直接部署，无需额外下载。

- 原版仓库（Gitee）：https://gitee.com/Discuz/DiscuzX
- 国际版镜像（GitHub）：https://github.com/DiscuzTeam/DiscuzX

> 基于 MitFrame® 内核重构的全新框架体系，全面拥抱 PHP 8，前台后台彻底开放。

## 一、环境要求

| 项目 | 要求 |
|---|---|
| PHP | 8.0 及以上（**不兼容 PHP 7**） |
| 数据库 | MySQL 8.0 及以上（或兼容的 MariaDB） |
| 常用扩展 | mysqli、GD、curl、mbstring、json 等 |
| Web 服务器 | Nginx / Apache / IIS 均可 |

## 二、快速安装

1. **上传文件**：将 `upload/` 目录内的**全部内容**上传到网站根目录。
2. **设置权限**：确保 `config/`、`data/` 目录可写（Linux 下可 `chmod -R 777`）。
3. **运行安装程序**：浏览器访问 `https://你的域名/install/`，按向导填写数据库与管理员信息，完成安装。
4. **安装后处理**：确认站点前台、后台可正常访问。`install` 目录同时内置了工具箱程序，如需使用可修改 `install/index.php` 文件名后启用，否则建议删除或重命名该目录以保安全。

## 三、目录说明

| 目录 | 说明 |
|---|---|
| `upload/` | 站点根目录内容，部署时上传此目录内的文件 |
| `upload/config/` | 配置文件目录，安装时写入，需可写 |
| `upload/data/` | 运行数据、缓存与日志目录，需可写 |
| `upload/vendor/` | Discuz! X5 Vendor 库（云 SDK 等），**本仓库已内置** |
| `upload/source/data/ip/` | Discuz! X5 IP 库，**本仓库已内置** |

IP 库如需启用，请在 `config_global.php` 中设置：`$_config['ipdb']['setting']['ipv4'] = 'system'`、`$_config['ipdb']['setting']['ipv6'] = 'v6system'`。

## 四、版本与升级

- 当前版本：**Discuz! X5.0 开源版**。
- 从 X5.0 起默认不再包含 UCenter 服务端，如需站群方式部署请自行下载 [UCenter](https://gitee.com/Discuz/UCenter)。
- 从 X5.0 起安装程序内置升级程序：**升级前请先升级到 X3.5 版本**。
- X5 兼容 X3.5 应用的运行；但是否兼容 PHP 8 环境运行，请咨询对应应用开发者。

## 五、特性与生态

- [插件列表](https://addon.dismall.com/plugins/list-2-210-0-0-1.html)
- [模板列表](https://addon.dismall.com/templates/list-2-212-0-0-1.html)

X5.1 为商业版本，不提供开源版，了解报价请访问 https://www.discuz.vip/buy 。

## 六、声明

您可以 Fork 本仓库代码，但未经许可**禁止**在本产品的整体或任何部分基础上，以发展任何派生版本、修改版本或第三方版本用于**重新分发**。

授权协议详见官方授权网站：https://license.discuz.vip

## 七、交流

参与本项目 PR 的伙伴，可私信 [@zoewho](https://gitee.com/zoewho)、[@DiscuzX](https://gitee.com/3dming) 并提供 QQ 号码进行审核，通过后加入 DxGit Forker QQ 群与开发团队共同交流。

[点击查看如何提交代码到本项目](https://gitee.com/Discuz/DiscuzX/wikis/%E6%8F%90%E4%BA%A4%E4%BB%A3%E7%A0%81%E5%88%B0%E6%9C%AC%E9%A1%B9%E7%9B%AE?sort_id=3466289)
