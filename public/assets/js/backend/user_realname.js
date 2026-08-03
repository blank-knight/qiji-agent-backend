define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_realname/index',
                    add_url: 'user_realname/add',
                    edit_url: 'user_realname/edit',
                    del_url: 'user_realname/del',
                    multi_url: 'user_realname/multi',
                    table: 'user_realname',
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
                        {field: 'user_type', title: '实名类型', formatter: Table.api.formatter.label, searchList: {1:'个人',2:'企业'}},
                        {field: 'realname', title: '实名', operate: 'LIKE'},
                        // {field: 'number', title: '证件号码', operate: 'LIKE'},
                        {field: 'username', title: '管理员', operate: 'LIKE'},
                        {field: 'yz_image', title: '企业图片', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'user_image', title: '管理员图片', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},

                        {field: 'status', title: '状态', formatter: Table.api.formatter.status, searchList: {0: '待审核', 1:'已认证',2:'认证失败'}},
                        {field: '', title: '状态修改',formatter:function (value,row,index){
                            if(row.status==0){
                                return '<a style="color:#18bc9c;border:1px solid #18bc9c;padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" onclick="change_status('+row.id+',1)" >通过</a>\
                                <a style="color:#ff422f;border:1px solid #ff422f;padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" onclick="change_status('+row.id+',2)" >驳回</a>';
                            }
                        }, operate: false},

                        {field: 'addtime', title: '提交时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        // {field: 'remark', title: '审核消息', operate: false},
                        // {field: 'update_time', title: '审核时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
    
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
                        url:"user_realname/shuaxin",
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
                    //     url: "user_realname/shuaxin",
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
    $.post('user_realname/change_status',{id:id,status:status},function(data){
        layer.msg(data.msg);
        $("table").bootstrapTable('refresh');
    },'json');
    
}
