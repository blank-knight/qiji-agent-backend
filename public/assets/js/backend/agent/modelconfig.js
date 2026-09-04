define(['jquery', 'bootstrap', 'backend', 'form'], function ($, undefined, Backend, Form) {

    var Controller = {
        index: function () {
            // 普通表单提交（非 AJAX 弹窗），走 Form.api.bindevent 的 AJAX 提交
            Form.api.bindevent($("#model-config-form"));
        },
    };

    return Controller;
});
