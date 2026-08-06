define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'agent/agent/index',
                    add_url: 'agent/agent/add',
                    edit_url: 'agent/agent/edit',
                    del_url: 'agent/agent/del',
                    multi_url: 'agent/agent/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                exportTypes:['excel'],
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},

                        // {field: 'group.name', title: __('Group')},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        // {field: 'email', title: __('Email'), operate: 'LIKE'},
                        {field: 'agent_id', title: '上级代理id', sortable: true},
                        // {field: 'level', title: '代理级别', operate: 'BETWEEN', sortable: true},
                        // {field: 'agent_count', title: '可开代理', operate: false},
                        // {field: 'open_agent_count', title: '下级代理', operate: false},
                        // {field: 'memory', title: '存储空间', operate: false},
                        {field: 'avatar', title: '头像', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'score', title: '点数', operate: 'BETWEEN', sortable: true},
                        {field: 'media_money', title: '余额', operate: 'BETWEEN', sortable: true},
                        {field: 'is_custom_key', title: '账号配置', searchList: {1: '自定义API', 0: '使用上级API'}, formatter: Table.api.formatter.label},
                        {field: 'is_custom_model', title: '大模型配置', searchList: {1: '自定义大模型', 0: '使用上级大模型'}, formatter: Table.api.formatter.label},
                        {field: 'out_time', title:'到期时间', formatter: Table.api.formatter.date, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'remark', title: '备注', operate: 'LIKE'},
                        {field: 'jointime', title:'创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
                        {field: '', title: '管理代理',formatter:function (value,row,index){
                            return '<a style="color:#18bc9c;border:1px solid #18bc9c;padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" href="index/agent_manage/?id='+row.id+'" target="_blank">进入后台</a>';
                        }, operate: false},
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


function agent_vip(agent_id){
    $.post('./agent/agent/get_agent_vip',{agent_id:agent_id},function(data){
        if(data.code==0){
            layer.msg(data.msg);return;
        }
        content = '<div style="agent">';
        data = data.data;
        for (var i = 0 ; i < data.length ; i++) {
            content += '<p style="margin-bottom:15px;"><span style="width:50%;text-align:right;display:inline-block">【'+data[i]['name']+'】限制数：</span> <input type="text" style="width: 20%;margin-left: 5%;border: 1px solid #888;border-radius: 5px;padding-left: 5px;line-height: 30px;" class="vip'+data[i]['level']+'" data-level="'+data[i]['level']+'" name="agent_vip[]" value="'+data[i]['value']+'"></p>';
        }
        content += '<p style="text-align:center;margin-top:20px;color:#f77;font-size:14px;">该限制数量为总数，不会因为代理开vip后而递减，如代理授权数量已达上限，请自行按总数叠加。 <br> 例如：第一次设置vip套餐A为10个，代理使用完10个后需要加量，这时应该把套餐A的数量设置成20个限制数，代理才可接着开VIP的套餐A</p></div>';
        
        Layer.open({
            title: '套餐开户限制',
            content:content,
            shadeClose: true, // 点击遮罩关闭
            shade: 0.8, // 遮罩层的透明度
            area:["50%","50%"],
            btn: [__('OK')],
            yes: function (index, layero) {
                var agent_vip  = {};
                $('input[name="agent_vip[]"]').map(function() {
                    agent_vip[$(this).attr('data-level')] = $(this).val(); // 返回每个input的值
                });
                 
                $.post('./agent/agent/set_agent_vip',{agent_vip:agent_vip,agent_id:agent_id},function(data){
                    layer.msg(data.msg);
                    if(data.code==1){
                        Layer.close(index);
                    }
                },"json");
                
            },
            success: function (layero, index) {
            }
        });
    },'json');
    
}