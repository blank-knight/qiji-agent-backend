define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'question_rank/index',
                    // add_url: 'question_rank/add',
                    // edit_url: 'question_rank/edit',
                    del_url: 'question_rank/del',
                    multi_url: 'question_rank/multi',
                    table: 'question_rank',
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
                // searchFormVisible:true,
                // commonSearch:true,
                sortName: 'id',
                cardView:cardView,
                exportTypes:['excel'],
                columns: [
                    [
                        {checkbox: true},
                        {field: '', title: '序号', sortable: true, operate: false,formatter: function(value, row, index){
                                return ++index;
                        }}, 
                        {field: 'user.nickname', title: '企业昵称', operate: 'LIKE'},
                        {field: 'keyword.keyword', title: '主词', operate: 'LIKE'},
                        {field: 'question.question', title: '主问题', operate: 'LIKE'},
                        {field: 'tuozhan_question', title: '拓展问题', operate: 'LIKE'},
                        // {field: '', title: '问题列表',formatter:function (value,row,index){
                        //     return '<a style="color:#18bc9c;border:1px solid #18bc9c;padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" href="/user/titles/index/question_rank_id/'+row.id+'/" target="_self">标题列表</a>';
                        // }, operate: false},
                        {field: 'platform', title: '收录平台', searchList: {1: 'DS', 2: '豆包', 3: '元宝', 4: '通义', 5: '文心', 6: '纳米', 7: 'kimi', 8: '智谱'}, formatter: Table.api.formatter.label},


                        {field: 'addtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'),

                        table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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

