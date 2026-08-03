define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'weixinapp2/index',
                    add_url: 'weixinapp2/add',
                    add_ck_url: 'weixinapp2/add_ck',
                    // edit_url: 'weixinapp2/edit',
                    edit_ck_url: 'weixinapp2/edit_ck',
                    del_url: 'weixinapp2/del',
                    multi_url: 'weixinapp2/multi',
                    copy_url: 'user_ims_ice/copy',
                    qrcode_url: 'user_ims_ice/qrcode',
                    table: 'weixinapp2',
                    import_url: 'weixinapp2/import',
                }
            });

            var table = $("#table");
            var cardView = false;
            if($('.is_mobile').val()==1){
                cardView = true;
            }

            var http_host = $('.http_host').val();
            var server_host = $('.server_host').val();
            

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
                        {field: 'nickname', title: '账号名称', operate: 'LIKE'},
                        {field: 'avatar', title: '头像',formatter:function (value,row,index){
                            avatar = row.avatar;
                            if(avatar==''){
                                avatar = '/user_assets/img/avatar.png';
                            }
                            return '<img src="'+avatar+'" style="width:35px;border-radius: 50%;">';
                        }, operate: false},

                        {field: 'user_type', title: '自媒体', searchList: {1: '网易', 2: '搜狐',3: '百家号',4: '头条号',5: '企鹅号',6: '知乎',7: '公众号',8: '小红书',9: '抖音',10: '哔哩专题',11: 'CSDN',12: '简书'}, formatter: Table.api.formatter.label},


                        {field: 'agent_ip_url', title: '代理IP', operate: 'LIKE'},
                        {field: 'today_send_count', title: '今日发布', operate: false},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {0: '已掉线', 1:'授权成功',2:'授权异常'}},
                        // {field: '', title: '近期错误',formatter:function (value,row,index){
                        //     if(row.ip_message!='' && row.ip_message!="null"){
                        //         return '<span title="'+row.ip_message+'" onclick="alert(\''+row.ip_message+'\')">查看('+row.limit_error_count+')</span>';
                        //     }
                            
                        // }, operate: false},

                        // {field: 'limit_status', title: 'API状态', formatter: Table.api.formatter.status, searchList: {0: '正常', 1:'今日API上限',2:'IP未授权白名单',3:'代理IP异常'}},

                        {field: 'auth_time', title: '授权时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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
        add_ck: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        edit_ck: function () {
            Controller.api.bindevent();
        },
        copy: function () {
            Controller.api.bindevent();
        },
        qrcode: function () {
            // Controller.api.bindevent();
        },
        import: function () {
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

function shouquan(id){
    var http_host = $('.http_host').val();
    var server_host = $('.server_host').val();
    layer.confirm('该账号已经授权过了，是否重新授权，旧授权信息会丢失', {icon: 3, title:'温馨提示',shade: 0.7,closeBtn: 2,  anim: 1}, function(index){
        window.open('https://'+server_host+'/user/weixinapp2/shouquan?id='+id+'&http_host='+http_host, '_blank');
        
    });
}
