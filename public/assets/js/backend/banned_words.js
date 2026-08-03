define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'banned_words/index',
                    add_url: 'banned_words/add',
                    // edit_url: 'banned_words/edit',
                    del_url: 'banned_words/del',
                    multi_url: 'banned_words/multi',
                    table: 'banned_words',
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
                        
                        {field: 'type', title: '类型', formatter: Table.api.formatter.label, searchList: {1:'系统内置',0:'普通'}},
                        {field: 'word', title: '违禁词', operate: 'LIKE'},

        
                  
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



function cancel_order(id){
    layer.confirm('是否取消订单，系统会自动退款？', {icon: 3, title:'谨慎操作',shade: 0.7,closeBtn: 2,  anim: 1}, function(index){
        reload_index = layer.load();
        $.post('/user/banned_words/cancel_order',{id:id},function(data){
            layer.close(reload_index);
            layer.msg(data.msg);
            setTimeout(function(){
                window.location.reload();
            },1500);
            
        },'json');
    })
}
