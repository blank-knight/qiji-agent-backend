define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/rechargecode/index',
                    add_url: 'user/rechargecode/add',
                    del_url: 'user/rechargecode/del',
                    multi_url: 'user/rechargecode/multi',
                    table: 'recharge_code',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                searchFormVisible: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'Id', sortable: true},
                        {field: 'code', title: '充值码', operate: 'LIKE', formatter: function (value) {
                            return '<span style="font-family:monospace;font-weight:600;">' + value + '</span>';
                        }},
                        {field: 'score', title: '面值(积分)', sortable: true},
                        {field: 'agent_name', title: '归属', operate: false},
                        {field: 'status', title: '状态', searchList: {unused: '未使用', used: '已使用', disabled: '已停用'}, formatter: Table.api.formatter.status},
                        {field: 'used_by_username', title: '兑换人', operate: false, formatter: function (value) {
                            return value || '-';
                        }},
                        {field: 'used_at', title: '兑换时间', operate: false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 生成成功后提示复制（add 弹窗提交后自动刷新列表）
        },
        add: function () {
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
