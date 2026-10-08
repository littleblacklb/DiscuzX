![MitFrame! 1.0 框架体系](https://img.shields.io/badge/MitFrame-1.0-%23155BD5?style=plastic)
![Discuz! X5 内核](https://img.shields.io/badge/Discuz!-X5-%23F4A62F?style=plastic)

## Discuz! X5 IP 库

Discuz! X5 的 IP 库位于 /source/data/ip 目录下，请把本仓库的文件上传到该目录下。文件较大，请酌情使用

| 文件名            | 含义              |
|----------------|-----------------|
| ipdb.dat       | Discuz! 官方 IP 库，使用时请把 config_global.php 中的 $_config['ipdb']['setting']['ipv4'] 设置为 'system' |
| ipv6db.dat     | Discuz! 官方 IPv6 库，使用时请把 config_global.php 中的 $_config['ipdb']['setting']['ipv6'] 设置为 'v6system' |
| ipv6wry.dat    | wry IPV6 库 |
| tinyipdata.dat | 旧版简易库 |
