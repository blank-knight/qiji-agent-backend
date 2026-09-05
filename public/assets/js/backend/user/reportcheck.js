define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 自定义渲染：不走 Table.api.init 的 url 拉取，直接请求控制器 JSON
            var table = $('#table');
            var load = function () {
                table.bootstrapTable('showLoading');
                $.ajax({
                    url: 'user/reportcheck/index',
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (ret) {
                        table.bootstrapTable('hideLoading');
                        table.bootstrapTable('refreshOptions', { data: ret.rows || [] });
                    },
                    error: function () {
                        table.bootstrapTable('hideLoading');
                        Toastr.error('检测请求失败');
                    }
                });
            };

            table.bootstrapTable({
                data: [],
                toolbar: '#toolbar',
                pagination: false,
                showToggle: false,
                showColumns: false,
                search: false,
                columns: [
                    { field: 'id', title: 'ID', width: 60 },
                    { field: 'username', title: '用户' },
                    { field: 'mobile', title: '手机号' },
                    { field: 'rule', title: '命中规则', formatter: function (v) {
                        return '<span class="label label-' + (v === '活跃零上报' ? 'danger' : 'warning') + '">' + v + '</span>';
                    }},
                    { field: 'risk', title: '风险', formatter: function (v) {
                        return v === '高' ? '<font color="red">高</font>' : '<font color="orange">中</font>';
                    }},
                    { field: 'score', title: '剩余积分' },
                    { field: 'logintime_text', title: '最后登录' },
                    { field: 'last_report_text', title: '最后计费上报' },
                    { field: 'operate', title: '操作', table: table,
                      events: Table.api.events.operate,
                      formatter: Table.api.formatter.operate }
                ]
            });

            $(document).on('click', '.btn-refresh', function () {
                load();
            });
            load();
        }
    };
    return Controller;
});
