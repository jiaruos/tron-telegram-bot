define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'bot_id', title: __('机器人ID'), sortable: true},
                        // {field: 'group.name', title: __('Group')},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'tg_id', title: __('TG-ID')},
                        {field: 'listeners_counts', title: __('额外监听地址数')},
                        {field: 'notice_count', title: __('每日额外通知次数')},


                        // {field: 'email', title: __('Email'), operate: 'LIKE'},
                        // {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        // {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        // {field: 'level', title: __('Level'), operate: 'BETWEEN', sortable: true},
                        // {field: 'gender', title: __('Gender'), visible: false, searchList: {1: __('Male'), 0: __('Female')}},
                        // {field: 'score', title: __('Score'), operate: 'BETWEEN', sortable: true},
                        // {field: 'successions', title: __('Successions'), visible: false, operate: 'BETWEEN', sortable: true},
                        // {field: 'maxsuccessions', title: __('Maxsuccessions'), visible: false, operate: 'BETWEEN', sortable: true},
                        // {field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        // {field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search},
                        {field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'updatetime', title: __('Updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},

                        // {field: 'joinip', title: __('Joinip'), formatter: Table.api.formatter.search},
                        {field: 'activation', title: __('Status'), formatter: Table.api.formatter.status, searchList: {0: __('未激活'), 1: __('激活')}},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('监听地址'),
                                    title: __('监听地址'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function(row){
                                        return 'tg/address?ids='+row.id;
                                    },
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
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
        
        
        select: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    select_url: 'user/user/select' + location.search,
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.select_url,
                pk: 'id',
                sortName: 'id',
                //commonSearch: false,
                visible: false,
                showToggle: false,
                showColumns: false,
                search:false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'tg_id', title: __('TG-ID')},
                        {field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'activation', title: __('Status'), formatter: Table.api.formatter.status, searchList: {0: __('未激活'), 1: __('激活')}},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'select',
                                    title: __('Select'),
                                    text: __('Select'),
                                    classname: 'btn btn-xs btn-warning btn_select_goods'
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                $('.btn_select_goods', this).on('click', function(e) {
                    var row = table.bootstrapTable('getData')[$(this).data('row-index')]
                    Controller.api.selectFunc(row, $(this).data('row-index'))
                })
            });
        },
        
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            
            
            selectFunc: function(row, indexrow) {
                Fast.api.close(row);
            }
        }
    };
    return Controller;
});