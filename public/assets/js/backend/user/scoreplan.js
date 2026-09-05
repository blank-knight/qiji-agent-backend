define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/scoreplan/index',
                    add_url: 'user/scoreplan/add',
                    edit_url: 'user/scoreplan/edit',
                    del_url: 'user/scoreplan/del',
                    multi_url: 'user/scoreplan/del',
                    table: 'score_plan',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'name', title: '套餐名称'},
                        {field: 'score', title: 'Token数量', sortable: true},
                        {field: 'price', title: '价格(元)', sortable: true, formatter: function (v) { return '¥' + (parseFloat(v) || 0).toFixed(2); }},
                        {field: 'agent_name', title: '归属', operate: false},
                        {field: 'weigh', title: '排序', sortable: true},
                        {field: 'remark', title: '描述', operate: 'LIKE'},
                        {field: 'status', title: '状态', searchList: {normal: '上架', hidden: '下架'}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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
