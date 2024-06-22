define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'tg/auto_reply/index' + location.search,
                    add_url: 'tg/auto_reply/add',
                    edit_url: 'tg/auto_reply/edit',
                    del_url: 'tg/auto_reply/del',
                    multi_url: 'tg/auto_reply/multi',
                    import_url: 'tg/auto_reply/import',
                    table: 'tg_auto_reply',
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
                        {field: 'bot_id', title: __('Bot_id')},
                        {field: 'is_menu', title: __('Is_menu'), searchList: {"0":__('Is_menu 0'),"1":__('Is_menu 1')}, formatter: Table.api.formatter.normal},
                        {field: 'keyword', title: __('Keyword'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
