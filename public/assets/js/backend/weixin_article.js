define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'weixin_article/index?article_type=',
                    // add_url: 'weixin_article/add',
                    // edit_url: 'weixin_article/edit',
                    del_url: 'weixin_article/del',
                    multi_url: 'weixin_article/multi',
                    table: 'weixin_article',
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
                        {field: 'user.nickname', title: '企业昵称', operate: 'LIKE'},
                        {field: 'draft_id', title: '草稿id', visible:false},
                        {field: 'draft.name', title: '任务名', operate: 'LIKE'},
                        {field: 'weixinapp.user_type', title: '投喂自媒体', searchList: {1: '网易', 2: '搜狐',3: '百家号',4: '头条号',5: '企鹅号',6: '知乎',7: '公众号',8: '小红书',9: '抖音',10: '哔哩专题',11: 'CSDN',12: '简书'}, formatter: Table.api.formatter.label},

                        {field: 'weixinapp.name', title: '投喂账号', operate: 'LIKE'},
                        {field: 'articletype.type_name', title: '文章分类', operate: 'LIKE'},
                        {field: 'title', title: '文章标题', operate: 'LIKE'},
                        {field: 'status', title: '状态', formatter: Table.api.formatter.status, searchList: {0: '等待发布中', 1:'完成',2:'失败'}},

                        {field: 'draft_send_time', title: '发布时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        // {field: 'article_send_time', title: '正式发布', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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

