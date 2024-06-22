define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            function HTMLEncode ( input ) 
            { 
            var converter = document.createElement("DIV"); 
            converter.innerText = input; 
            var output = converter.innerHTML; 
            converter = null; 
            return output; 
            }
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'tg/notice/index' + location.search,
                    add_url: 'tg/notice/add',
                    // edit_url: 'tg/notice/edit',
                    del_url: 'tg/notice/del',
                    multi_url: 'tg/notice/multi',
                    import_url: 'tg/notice/import',
                    table: 'tg_notice',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                escape:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'tg_id', title: __('Tg_id')},
                        {field: 'user.username', title: __('User.username'), operate: 'LIKE'},
                        {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        {field: 'content', title: __('Content')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
