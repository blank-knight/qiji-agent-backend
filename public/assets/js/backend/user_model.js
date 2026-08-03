define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_model/index',
                    add_url: 'user_model/add',
                    edit_url: 'user_model/edit',
                    del_url: 'user_model/del',
                    multi_url: 'user_model/multi',
                    table: 'user_model',
                }
            });

            var table = $("#table");
            var cardView = false;
            if($('.is_mobile').val()==1){
                cardView = true;
            }

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                search:false,
                sortName: 'id',
                cardView:cardView,
                exportTypes:['excel'],
                columns: [
                    [
                        {checkbox: true},
                        {field: '', title: '序号', sortable: true, operate: false,formatter: function(value, row, index){
                                return ++index;
                        }},
                        {field: 'name', title: '别名', operate: 'LIKE'},
                        // {field: 'user_id', title: '企业ID'},
                        
                        {field: 'type_id', title: '大模型平台', formatter: Table.api.formatter.label, searchList: {1: 'deepseek', 2:'豆包',3:'元宝',4:'通义千问',5:'文心一言',6:'其他模型'}},
                        {field: 'model', title: '模型名称', operate: 'LIKE'},
                        {field: 'key', title: 'API-KEY', operate: false},
                        {field: 'create_type', title: '创作类型', formatter: Table.api.formatter.status, searchList: {1: '不支持写作', 2:'支持写作'}},
                        {field: 'enable_search', title: '联网搜索', formatter: Table.api.formatter.status, searchList: {0: '不开启', 1:'开启联网', 2:'手动查自动开启'}},
                        {field: 'addtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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

