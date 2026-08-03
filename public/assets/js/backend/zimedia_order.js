define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zimedia_order/index',
                    add_url: 'zimedia_order/add',
                    // edit_url: 'zimedia_order/edit',
                    // del_url: 'zimedia_order/del',
                    multi_url: 'zimedia_order/multi',
                    table: 'zimedia_order',
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
                        
                        {field: 'user.agent_id', title: '代理id', operate: 'LIKE'},
                        {field: 'user.nickname', title: '用户昵称', operate: 'LIKE'},
                        {field: 'media_name', title: '媒体名'},
                        {field: 'title', title: '标题', operate: 'LIKE'},
                        {field: 'price', title: '用户付款(元)', operate: false},
                        {field: 'real_media_money', title: '实际成本(元)', operate: false},
                        {field: 'agent_add_media_money', title: '代理加价(元)', operate: false},
                        {field: 'status', title: '稿件状态', formatter: Table.api.formatter.status, searchList: {0: '待安排', 1:'已安排',2:'已发布',4:'已退稿',9:'售后中'}},
                        {field: 'order_url', title: '发稿链接',formatter:function (value,row,index){
                            if(row.order_url){
                                return '<a style="color:#fff;background:linear-gradient(168deg,#2f48e2,#a63cb6);padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" href="'+row.order_url+'" target="_blank">查看文章</a>';                            
                            }else{
                                return '无';
                            }
                        }, operate: false},
                        {field: 'addtime', title: '提交时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'message', title: '审核消息', operate: false},
                        // {field: 'send_time', title: '审核时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
    
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 重发
            $(document).on('click', '.btn-shuaxin', function () {

                layer.confirm('是否刷新订单？', {icon: 3, title:'温馨提示',shade: 0.7,closeBtn: 2,  anim: 1}, function(index){
                    var ids = Table.api.selectedids(table);
                    load_index = layer.load();
                    Fast.api.ajax({
                        url:"zimedia_order/shuaxin",
                        data:{ids: ids.join(',')},
                        type: "POST",
                        dataType: "json",
                        loading:false,
                        success: function (ret) {
                            layer.close(load_index);
                            table.bootstrapTable('refresh', {});
                            Layer.close(index);
                            // console.log(ret)
                        },
                        error:function(ret){
                            // console.log(ret)
                            layer.msg('网络错误')
                        }
                    });
                    // Fast.api.ajax({
                    //     url: "zimedia_order/shuaxin",
                    //     type: "post",
                    //     data: {ids: ids.join(',')},
                    // }, function () {
                    //     layer.close(load_index)
                    //     table.bootstrapTable('refresh', {});
                    //     Layer.close(index);
                    // });
                });
            });

        },
        tougao: function () {
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



function cancel_order(id){
    layer.confirm('是否取消订单，系统会自动退款？', {icon: 3, title:'谨慎操作',shade: 0.7,closeBtn: 2,  anim: 1}, function(index){
        reload_index = layer.load();
        $.post('/user/zimedia_order/cancel_order',{id:id},function(data){
            layer.close(reload_index);
            layer.msg(data.msg);
            setTimeout(function(){
                window.location.reload();
            },1500);
            
        },'json');
    })
}
