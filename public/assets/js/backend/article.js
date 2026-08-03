define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'article/index',
                    add_url: 'article/add',
                    // edit_url: 'article/edit',
                    del_url: 'article/del',
                    multi_url: 'article/multi',
                    table: 'article',
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
                        {field: 'user.nickname', title: '企业昵称', operate: 'LIKE'},
                        {field: 'type_id', title: '分类id',visible:false},
                        {field: '', title: '序号', sortable: true, operate: false,formatter: function(value, row, index){
                                return ++index;
                        }},
                        {field: 'type_name', title: '分类名', operate: false},
                        // {field: 'aitask.name', title: 'AI任务名', operate: 'LIKE'},
                        {field: 'ai_task_id', title: 'AI任务id', visible:false},
                        {field: 'title', title: '标题', operate: 'LIKE'},
                        {field: 'send_count', title: '发送次数'},
                        {field: 'status', title: '状态', formatter: Table.api.formatter.status, searchList: {0: '待审核', 1:'正常',2:'失败'}},

                        {field: 'addtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            UE.getEditor('intro_detail',{  //intro_detail为要编辑的textarea的id
                initialFrameWidth: 1300,  //初始化宽度
                initialFrameHeight: 800,  //初始化高度
                 initialFrameWidth: null,  //自适应大小和滚动条
                autoHeightEnabled: false, //自适应大小和滚动条
            });
            Controller.api.bindevent();
        },
        edit: function () {
            UE.getEditor('intro_detail',{  //intro_detail为要编辑的textarea的id
                initialFrameWidth: 1300,  //初始化宽度
                initialFrameHeight: 800,  //初始化高度
                 initialFrameWidth: null,  //自适应大小和滚动条
                autoHeightEnabled: false, //自适应大小和滚动条
            });
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

