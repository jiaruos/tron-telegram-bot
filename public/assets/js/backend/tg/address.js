define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'tg/address/index' + location.search,
                    add_url: 'tg/address/add',
                    edit_url: 'tg/address/edit',
                    del_url: 'tg/address/del',
                    multi_url: 'tg/address/multi',
                    import_url: 'tg/address/import',
                    table: 'tg_address',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                dblClickToEdit:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'bot_id', title: __('机器人ID'), sortable: true},
                        {field: 'address', title: __('Address'), operate: 'LIKE'},
                        {field: 'bak', title: __('Bak'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'tg_id', title: __('tg_id'), operate: 'LIKE'},
                        {field: 'user.username', title: __('User.username'), operate: 'LIKE'},
                        {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table); 

            $(document).on('click', '.btn_import_data', function () {
                layer.open({
                    title:"导入监听地址",
                    type: 2,
                    area: ['60%', '60%'],
                    fixed: false, //不固定
                    maxmin: true,
                    content: Config.import_url
                });
            });
            
             // 全部地址重新加入Redis监听
            $(document).on("click", ".btn-start", function () {
                Fast.api.ajax({
                    url: 'tg/address/addredis',
                }, function (res) {
                    //Toastr.success(res.msg)
                });

            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        
         importaddress: function () {
            $(document).on('click', '.bt-savemy', function () {
                var user_id = $("#c-user_id").val();
                var address = $("#c-address").val();

                if(!user_id) {
                    Toastr.error("请选择绑定用户")
                    return false;
                }
                if(!address) {
                    Toastr.error("请输入地址")
                    return false;
                }

                Fast.api.ajax({
                    url: 'tg/address/importaddress',
                    data: {
                        'user_id': user_id,
                        'address': address
                    }
                }, function (res) {
                    if(res.code === 0) {
                        Toastr.error(res.msg)
                    } else {
                        Fast.api.close("操作成功")
                    }
                });

                return false;
            });
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                 $("#select-c-goods_id").on('click', function () {
                    parent.Fast.api.open("/EBsgaoIFQD.php/user/user/select", "选择绑定用户", {
                        callback: function (data) {
                            $('#c-user_id').val(data.id);

                            var html =  "<div style='font-weight:600;'>已选择: </div>";
                            html +=  "<div>用户ID:" + data.id + "</div>";
                            html +=  "<div>用户名:" + data.username + "</div>";
                            html +=  "<div>用户昵称:" + data.nickname + "</div>";

                            $('#goods_box').html(html);
                        },
                        area: ['90%', '80%'],
                    });
                });


                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
