define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'menu_banner/index',
                    add_url: 'menu_banner/add',
                    edit_url: 'menu_banner/edit',
                    del_url: 'menu_banner/del',
                    multi_url: 'menu_banner/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                exportTypes:['excel'],
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'name', title: '名称'},
                        {field: 'type', title: '类型', searchList: {1:'轮播图',2: '主页功能推荐'}, formatter: Table.api.formatter.label},

                        {field: 'img_url', title: '图片', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'dsort', title: '排序', sortable: true},
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