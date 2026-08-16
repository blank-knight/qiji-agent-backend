define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'version/index',
                    add_url: 'version/add',
                    edit_url: 'version/edit',
                    del_url: 'version/del',
                    multi_url: 'version/multi',
                    table: 'version',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'newversion', title: '新版本号'},
                        {field: 'downloadurl', title: '下载地址', formatter: Controller.api.formatter.url},
                        {field: 'packagesize', title: '包大小'},
                        {field: 'enforce', title: '强制更新', formatter: Controller.api.formatter.enforce},
                        {field: 'upgradetext', title: '更新说明', class: 'autocontent', formatter: Controller.api.formatter.content},
                        {field: 'createtime', title: __('Createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'status', title: __('Status'), searchList: {"normal": '正常', "hidden": '隐藏'}, formatter: Table.api.formatter.status},
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
            },
            formatter: {
                url: function (value, row, index) {
                    if (!value) return '-';
                    return '<a href="' + value + '" target="_blank" class="text-muted" title="' + value + '"><i class="fa fa-download"></i> 下载</a>';
                },
                enforce: function (value, row, index) {
                    return value == 1
                        ? '<span class="label label-danger">强制</span>'
                        : '<span class="label label-default">可选</span>';
                },
                content: function (value, row, index) {
                    if (!value) return '-';
                    return '<span title="' + value.replace(/"/g, '&quot;') + '">' + value.substr(0, 30) + (value.length > 30 ? '...' : '') + '</span>';
                }
            }
        }
    };
    return Controller;
});
