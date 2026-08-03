define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'company_keyword/index',
                    add_url: 'company_keyword/add',
                    edit_url: 'company_keyword/edit',
                    del_url: 'company_keyword/del',
                    multi_url: 'company_keyword/multi',
                    table: 'company_keyword',
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
                        {field: 'user.nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'keyword', title: '主词', operate: 'LIKE'},
                        {field: 'question_count', title: '问题数量', operate: false},

                        // {field: '', title: '问题列表',formatter:function (value,row,index){
                        //     return '<a style="color:#18bc9c;border:1px solid #18bc9c;padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;" href="/user/titles/index/company_keyword_id/'+row.id+'/" target="_self">标题列表</a>';
                        // }, operate: false},
                        {
                            field: 'status',
                            title: '优化状态',
                            searchList: {
                                "1": '开启',
                                "2": '关闭'
                            },
                            formatter: function (value, row, index) {
                                    var table = this.table;
                                    var options = table ? table.bootstrapTable('getOptions') : {};
                                    var pk = options.pk || "id";
                                    var color = typeof this.color !== 'undefined' ? this.color : 'success';
                                    var yes = typeof this.yes !== 'undefined' ? this.yes : 1;
                                    var no = typeof this.no !== 'undefined' ? this.no : 2;
                                    var url = typeof this.url !== 'undefined' ? this.url : '';
                                    var confirm = '';
                                    var disable = false;
                                    if (typeof this.confirm !== "undefined") {
                                        confirm = typeof this.confirm === "function" ? this.confirm.call(this, value, row, index) : this.confirm;
                                    }
                                    if (typeof this.disable !== "undefined") {
                                        disable = typeof this.disable === "function" ? this.disable.call(this, value, row, index) : this.disable;
                                    }
                                    return "<a href='javascript:;' data-toggle='tooltip' title='" + __('Click to toggle') + "' class='btn-change " + (disable ? 'btn disabled no-padding' : '') + "' data-index='" + index + "' data-id='" +
                                        row[pk] + "' " + (url ? "data-url='" + url + "'" : "") + (confirm ? "data-confirm='" + confirm + "'" : "") + " data-params='" + this.field + "=" + (value == yes ? no : yes) + "'><i class='fa fa-toggle-on text-success text-" + color + " " + (value == yes ? '' : 'fa-flip-horizontal text-gray') + " fa-2x'></i></a>";
                                }
                                
                        },
                        {field: 'shoudong_shoulu', title: '手动查收录',formatter:function (value,row,index){
                            if(row.shoudong_shoulu == 0){
                                return '<span style="color:#fff;background:linear-gradient(168deg,#238edc,#75c21c);padding:6px 8px;border-radius: 5px;font-size: 13px;cursor: pointer;width: 70px;text-align: center;margin: 0 auto;">查询中</span>';
                            }else{
                                return '<span style="color:#ccc">已查完/未查询</span>'; 
                            }
                            
                        }, searchList: {
                                "1": '已查完/未查询',
                                "0": '查询中'
                            }},
                      
                        {field: 'addtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'),

                        table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);


            // 重发
            $(document).on('click', '.btn-shoulu', function () {
                pingtai = $(this).attr('data-id');

                layer.confirm('批量查询可以实时查询，同时也会增加token扣费成本，确定批量查询收录吗', {icon: 3, title:'温馨提示',shade: 0.7,closeBtn: 2,  anim: 1}, function(index){
                    var ids = Table.api.selectedids(table);
         
                    Fast.api.ajax({
                        url: "company_keyword/shoulu",
                        type: "post",
                        data: {ids: ids.join(','),pingtai:pingtai},
                    }, function () {
                        table.bootstrapTable('refresh', {});
                        Layer.close(index);
                    });
                });
            });

            // 重发
            $(document).on('click', '.btn-shoulu-stop', function () {

                layer.confirm('是否批量暂停查询？', {icon: 3, title:'温馨提示',shade: 0.7,closeBtn: 2,  anim: 1}, function(index){
                    var ids = Table.api.selectedids(table);
         
                    Fast.api.ajax({
                        url: "company_keyword/shoulu_stop",
                        type: "post",
                        data: {ids: ids.join(',')},
                    }, function () {
                        table.bootstrapTable('refresh', {});
                        Layer.close(index);
                    });
                });
            });
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

