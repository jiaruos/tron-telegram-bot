define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'tg/bot/index' + location.search,
                    add_url: 'tg/bot/add',
                    edit_url: 'tg/bot/edit',
                    del_url: 'tg/bot/del',
                    multi_url: 'tg/bot/multi',
                    import_url: 'tg/bot/import',
                    table: 'tg_bot',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'bot_token', title: __('Bot_token'), operate: 'LIKE'},
                        {field: 'bot_username', title: __('Bot_username'), operate: 'LIKE'},
                        {field: 'bak', title: __('备注'), operate: 'LIKE'},
                        {field: 'count', title: __('用户人数')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'ajax',
                                    text: __('初始化'),
                                    title: __('初始化'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'tg/bot/init',
                                    confirm: '确认初始化机器人？',
                                    success: function (data, ret) {
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: __('菜单管理'),
                                    title: __('菜单管理'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function(row){
                                        return 'tg/auto_reply?bot_id=' + row.id
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
