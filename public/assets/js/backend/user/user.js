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
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'agent_id', title: '代理id'},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'company_name', title: '公司名', operate: 'LIKE'},
                        {field: 'score', title: '点数', operate: false},
                        {field: 'media_money', title: '余额', operate: false},
                        // {field: 'memory', title: '存储(M)', operate: false},
                        {field: '', title: '发布', operate: false,formatter: function(value, row, index){
                            var send_count = row.send_count;
                            if(row.send_count > 999999){
                                send_count = '不限';
                            }
                            return row.use_send_count+ '/'+send_count;
                        }},
                        {field: '', title: '写作', operate: false,formatter: function(value, row, index){
                            var article_count = row.article_count;
                            if(row.article_count > 999999){
                                article_count = '不限';
                            }
                            return row.use_article_count+ '/'+article_count;  
                        }},
                        // {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'remark', title: '备注', operate: 'LIKE'},
                        // {field: 'cid', title:'公司标识', operate: 'LIKE'},
                        {field: 'out_time', title: '到期时间', formatter: Table.api.formatter.date, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        // {field: 'score', title: __('Score'), operate: 'BETWEEN', sortable: true},
                        {field: 'jointime', title: '注册时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: '', title: '管理用户',formatter:function (value,row,index){
                            return '<a style="color:#fff;background:linear-gradient(168deg,#2f48e2,#a63cb6);padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" href="../index/user_manage/?id='+row.id+'" target="_blank">进入后台</a>';
                        }, operate: false},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
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