define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'draft/index?article_type=',
                    add_url: 'draft/add?article_type=',
                    // edit_url: 'draft/edit',
                    del_url: 'draft/del',
                    multi_url: 'draft/multi',
                    table: 'draft',
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
                        {field: 'name', title: '任务名', operate: 'LIKE'},
                        // {field: 'weixinapp.is_api_auth', title: '方式', formatter: Table.api.formatter.label, searchList: {1: 'API授权', 2:'秘钥授权',3:'软件授权'}},
                        // {field: 'weixinapp.name', title: '账号名', operate: 'LIKE'},
                        {field: 'articletype.type_name', title: '文章分类', operate: false},
                        // {field: 'shenyu_article_count', title: '文章剩余量', operate: false},
                        {field: '', title: '总发布',formatter:function (value,row,index){
                            return row.weixin_article_count+' / '+row.max_count;
                        }, operate: false},
                        {field: 'today_count', title: '今日发布', operate: false},
                        {field: 'day_max_count', title: '每日/号发布', operate: false},
             
                        // {field: 'is_send_article', title: '发布类型', formatter: Table.api.formatter.label, searchList: {0: '仅发布草稿箱', 1:'同步发布文章'}},

                        {field: 'message', title: '错误消息',formatter:function (value,row,index){
                            if(row.status==2){
                                return '<span style="cursor:pointer" title="'+row.message+'" onclick="layer.msg(\''+row.message+'\')">'+row.message_str+'</span>';
                            }else{
                                return '';  
                            }
                            
                        }, operate: false},
                        
                        {field: 'status', title: '状态', formatter: Table.api.formatter.status, searchList: {0: '发布中', 1:'发布完成',2:'发布失败',3:'临时暂停'}},
                        {field: '', title: '状态修改',formatter:function (value,row,index){
                            if(row.status!=0){
                                return '<a style="color:#18bc9c;border:1px solid #18bc9c;padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" onclick="change_status('+row.id+',0)" >启动</a>';
                            }else{
                                return '<a style="color:#ff422f;border:1px solid #ff422f;padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" onclick="change_status('+row.id+',3)" >暂停</a>';
                            }
                            
                        }, operate: false},

                        {field: 'script_time', title: '最后执行', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'addtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        
                        // {field: '', title: '发布详情',formatter:function (value,row,index){
                        //     return '<a style="color:#fff;background:linear-gradient(168deg,#2f48e2,#a63cb6);padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" href="/user/weixin_article/index/draft_id/'+row.id+'/?ref=addtabs" target="_blank">查看发布详情</a>';
                        // }, operate: false},
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



function change_status(id,status){
    $.post('/user/draft/change_status',{id:id,status:status},function(data){
        layer.msg(data.msg);
        window.location.reload();
    },'json');
    
}
