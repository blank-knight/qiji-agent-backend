define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'user/scoreorder/index',
                    table: 'score_order',
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                search: false,
                columns: [
                    [
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'order_no', title: '订单号'},
                        {field: 'user_name', title: '用户', operate: false},
                        {field: 'plan_name', title: '套餐'},
                        {field: 'score', title: 'Token数量', sortable: true},
                        {field: 'price', title: '金额(元)', sortable: true, formatter: function (v) { return '¥' + (parseFloat(v) || 0).toFixed(2); }},
                        {field: 'agent_name', title: '套餐归属', operate: false},
                        {field: 'payee_name', title: '实际收款方', operate: false, formatter: function (v, row) {
                            if (row.payee_agent_id == 0) return '<span class="label label-primary">平台</span>';
                            return '<span class="label label-warning">' + (v || '-') + ' 自收款</span>';
                        }},
                        {field: 'status', title: '状态', searchList: {pending: '待支付', paid: '已支付'}, formatter: function (v) {
                            return v === 'paid' ? '<span class="label label-success">已支付</span>' : '<span class="label label-default">待支付</span>';
                        }},
                        {field: 'paytime', title: '支付时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'createtime', title: '下单时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true}
                    ]
                ]
            });

            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
            }
        }
    };
    return Controller;
});
