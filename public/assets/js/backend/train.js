define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form,Layui) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'train/index',
                    add_url: 'train/add',
                    edit_url: 'train/edit',
                    del_url: 'train/del',
                    multi_url: 'train/multi',
                    table: 'train',
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
                // searchFormVisible:true,
                // commonSearch:true,
                sortName: 'id',
                cardView:cardView,
                exportTypes:['excel'],
                columns: [
                    [
                        // {checkbox: true},
                        {field: '', title: '序号', sortable: true, operate: false,formatter: function(value, row, index){
                                return ++index;
                        },width:80}, 
                        {field: 'user.nickname', title: '企业昵称', operate: 'LIKE'},
                        {field: 'keyword.keyword', title: '训练词', operate: 'LIKE'},
                        {field: 'question_count', title: '训练问题', operate: 'LIKE'},

                        // 1网易、2搜狐、3百家号、4头条号、5企鹅号、6知乎、7微信公众号、8小红书图文、9抖音图文、10哔哩哔哩、12 csdn、13简书
                        // {field: 'platform', title: '训练平台', searchList: {1: '网易', 2: '搜狐',3: '百家号',4: '头条号',5: '企鹅号',6: '知乎',7: '公众号',8: '小红书',9: '抖音',10: '哔哩专题',11: 'CSDN',12: '简书'}, formatter: Table.api.formatter.label},
                        // 平台 1ds 2豆包 3元宝 4千问 5文心/
                        {field: 'platform', title: '训练平台', searchList: {1: 'deepseek', 2: '豆包',3: '腾讯元宝',4: '通义千问',5: '文心一言'}, formatter: function(value, row, index){
                            if(row.platform==1){
                                return '<img src="/assets/img/deepseek.png" style="width:30px;"><span style="color:#666;font-size:12px;margin-left:5px;vertical-align: middle;">deepseek</span>';
                            }else if(row.platform==2){
                                return '<img src="/assets/img/doubao.png" style="width:30px;"><span style="color:#666;font-size:12px;margin-left:5px;vertical-align: middle;">豆包</span>';
                            }else if(row.platform==3){
                                return '<img src="/assets/img/yuanbao.png" style="width:30px;"><span style="color:#666;font-size:12px;margin-left:5px;vertical-align: middle;">腾讯元宝</span>';
                            }else if(row.platform==4){
                                return '<img src="/assets/img/qianwen.png" style="width:30px;"><span style="color:#666;font-size:12px;margin-left:5px;vertical-align: middle;">通义千问</span>';
                            }else{
                                return '<img src="/assets/img/wenxin.png" style="width:30px;"><span style="color:#666;font-size:12px;margin-left:5px;vertical-align: middle;">文心一言</span>';
                            }
                        }},


                        {field: 'train_date', title: '训练时间', formatter: Table.api.formatter.date, operate: 'RANGE', addclass: 'datetimerange', sortable: true},

                        {field: '', title: '训练状态',  operate: false,formatter: function(value, row, index){
                            if(row.status==1){
                                return '<svg t="1755307013447" style="vertical-align: middle;margin-left: 12px;" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="5860" width="30" height="30"><path d="M512 1024A512 512 0 1 1 512 0a512 512 0 0 1 0 1024zM397.824 713.045333a53.333333 53.333333 0 0 0 75.434667 0l323.328-323.328a53.333333 53.333333 0 0 0-75.434667-75.434666l-287.914667 283.306666-128.853333-128.853333a53.333333 53.333333 0 0 0-75.434667 75.434667l168.874667 168.874666z" fill="#23DA72" p-id="5861"></path></svg>\
                                    <span style="font-size: 14px;margin-left: 5px;color: #23da72;font-weight: bold;vertical-align: middle;">训练完成</span>';
                            }else{
                                return '<img src="/user_assets/img/leida1.gif" style="width: 30px;border-radius: 50%;"><span style="font-size: 14px;margin-left: 10px;color: #4609a0;font-weight: bold;vertical-align: middle;">训练中</span>';
                            }
                        },width:150}, 
                        // {field: 'operate', title: __('Operate'),

                        // table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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

