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
                        {field: 'type', title: '类型', searchList: {tiepai: '贴牌商', agent: '代理'}, formatter: function(value, row){ var map = {tiepai:'<span class="label label-success">贴牌商</span>', agent:'<span class="label label-info">代理</span>'}; return map[value] || '-'; }},
                        {field: 'name', title: '名称', operate: 'LIKE'},
                        {field: 'parent_name', title: '上级', operate: false},
                        {field: 'username', title: '登录账号', operate: 'LIKE'},
                        {field: 'mobile', title: '手机号', operate: 'LIKE'},
                        {field: 'domain', title: '代理网址', operate: 'LIKE', formatter: function(value){ return value ? '<a href="' + value + '" target="_blank" title="' + value + '">' + value + '</a>' : '-'; }},
                        {field: 'user_count', title: '用户数', sortable: true},
                        {field: 'score', title: '积分/点数', operate: 'BETWEEN', sortable: true},
                        {field: 'allow_model_config', title: 'API配置', searchList: {1: '允许自行配置', 0: '使用上级API'}, formatter: Table.api.formatter.label},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'impersonate', title: '进入后台', operate: false, table: table,
                            formatter: function (value, row, index) {
                                if (!row.can_impersonate) return '';
                                return '<a href="javascript:;" class="btn btn-xs btn-primary btn-impersonate" data-id="' + row.id + '" data-name="' + (row.name || '') + '" data-toggle="tooltip" title="以 ' + (row.name || row.username || row.id) + ' 身份进入后台"><i class="fa fa-sign-in"></i> 进入后台</a>';
                            },
                            events: {
                                'click .btn-impersonate': function (e, value, row, index) {
                                    e.stopPropagation(); e.preventDefault();
                                    // 用户手势内同步开新窗口（弹窗拦截器不会拦），票据回来后再定向；失败关窗不留空白页
                                    var win = window.open('about:blank', '_blank');
                                    Fast.api.ajax({url: 'agent/agent/loginas/ids/' + row.id, data: {}}, function (data) {
                                            var url = '/imp.php/index/impersonate?ticket=' + data.ticket;
                                            try {
                                                if (win && !win.closed) {
                                                    win.location.href = url;
                                                    return false;
                                                }
                                            } catch (e) {}
                                            window.open(url, '_blank');
                                            return false;
                                        }, function () {
                                            try { if (win && !win.closed) win.close(); } catch (e) {}
                                        });
                                    return false;
                                }
                            }
                        },
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
