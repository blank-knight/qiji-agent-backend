define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'agent/agent/index',
                    add_url: 'agent/agent/add',
                    edit_url: 'agent/agent/edit',
                    del_url: 'agent/agent/del',
                    multi_url: 'agent/agent/multi',
                    table: 'agent',
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search: false,
                exportTypes: ['excel'],
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'name', title: '代理名称', operate: 'LIKE'},
                        {field: 'mobile', title: '手机号', operate: 'LIKE'},
                        {field: 'domain', title: '代理网址', operate: 'LIKE', formatter: function(value){ return value ? '<a href="' + value + '" target="_blank" title="' + value + '">' + value + '</a>' : '-'; }},
                        {field: 'agent_id', title: '上级代理ID', sortable: true},
                        {field: 'score', title: '积分/点数', operate: 'BETWEEN', sortable: true},
                        {field: 'is_custom_key', title: 'API配置', searchList: {1: '自定义API', 0: '使用上级API'}, formatter: Table.api.formatter.label},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

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
