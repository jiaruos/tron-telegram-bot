# 波场地址监控 Telegram 机器人

这个项目是一个 Telegram 机器人，用于监控波场（TRX/USDT）地址。当有进账或出账时，机器人会通知对应的 Telegram机器人。它支持多个 Telegram 机器人，可以租赁给他人使用。该项目使用 FastAdmin 框架开发。

## 功能特点

- **波场地址监控**：监控指定的波场地址的进账和出账情况。
- **Telegram 通知**：当发生交易时，向 Telegram 机器人发送通知。
- **多机器人支持**：支持多个 Telegram 机器人，适用于不同用户,每个地址可以指定多个机器人通知。
- **租赁服务**：允许将机器人租赁给他人使用。
- **黑名单**：过滤交易所等地址，防止滥用。
- **系统通知**：支持后台统一给机器人所有用户发送通知，适用场景：广告/公告 等类似需告知给客户的通知。

## 安装步骤

1. **克隆仓库**：

    ```bash
    git clone https://github.com/yourusername/tron-telegram-bot.git
    cd tron-telegram-bot
    ```


2. **设置 FastAdmin 框架**：

    请参考 [FastAdmin 安装指南](https://fastadmin.net/docs/installation) 来设置 FastAdmin 框架。

4. **配置机器人**：

    在根目录.env文件中，配置trongrid的token
    
5. **添加守护进程命令**：

    ```
    1 php think getnowblock
    2 php think queue:work --queue GetNowBlock --daemon
    3 php think queue:work --queue TgNotice --daemon
    ```


## 使用说明

1. **配置监听机器人**：
添加正确的机器人密钥，管理员TgId填写自己的管理ID，回调地址为：域名/api/tg/hook，填写正确后点击初始化。
2. **配置机器人菜单**
  自定义底部菜单，回复内容自定义  
  
3. **开始使用**
    在Telegram机器人中，发送/start 开始使用机器人。

## 支持

如果你在搭建或使用过程中遇到任何问题，可以通过 Telegram 联系我：[ @chense688](https://t.me/chense688)。

## 许可证

此项目基于 MIT 许可证，详情请参阅 [LICENSE](LICENSE) 文件。
