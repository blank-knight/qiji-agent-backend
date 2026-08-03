define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ai_task/index',
                    add_url: 'ai_task/add',
                    // edit_url: 'ai_task/edit',
                    del_url: 'ai_task/del',
                    multi_url: 'ai_task/multi',
                    table: 'ai_task',
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
                        // {field: '', title: '序号', sortable: true, operate: false,formatter: function(value, row, index){
                        //         return ++index;
                        // }},
                        {field: 'user.nickname', title: '企业昵称', operate: 'LIKE'},
                        {field: 'name', title: '任务名', operate: 'LIKE'},
                        {field: 'keywordgroup.keyword', title: '蒸馏词', operate: 'LIKE'},
                        {field: 'max_count', title: '创作上限', operate: false},
                        // {field: '', title: '今日',formatter:function (value,row,index){
                        //     return row.today_count+' / '+row.d_count;
                        // }, operate: false},
                        // {field: '', title: '剩余/总生成',formatter:function (value,row,index){
                        //     return row.shenyu_article_count+' / '+row.success_count;
                        // }, operate: false},
                        {field: 'success_count', title: '已创作', operate: false},
                        {field: 'is_knowledge', title: '知识库', formatter: Table.api.formatter.label ,formatter:function (value,row,index){
                            if(row.is_knowledge > 0 || row.huaxiang_ids){
                                return '<span style="color:#fff;background:linear-gradient(168deg,#238edc,#75c21c);padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;">引用知识库</span>';
                            }else{
                                return '<span >未使用</span>'; 
                            }
                            
                        }, operate: false},
                        // {field: 'is_knowledge', title: '智能知识库', formatter: Table.api.formatter.label, searchList: {0: '未关联', 1:'关联创作'}},
                        // {field: 'shenyu_article_count', title: '文章剩余量', operate: false},
                        // {field: '', title: '详情',formatter:function (value,row,index){
                        //     return '<a style="color:#fff;background:linear-gradient(168deg,#2f48e2,#a63cb6);padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" href="/user/article/index/ai_task_id/'+row.id+'/" target="_self">查看创作内容</a>';
                        // }, operate: false},
                        // {field: 'error_count', title: '错误数', operate: false},
                        {field: 'status', title: '状态', formatter: Table.api.formatter.status, searchList: {0: '创作中', 1:'创作完成',2:'创作失败'}},
                        {field: 'script_time', title: '最新写作', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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

