define(['jquery', 'bootstrap', 'backend', 'toastr'], function ($, undefined, Backend, Toastr) {

    var Controller = {
        login: function () {
            // 登录表单由 backend/index.js 的 Form.api.bindevent 处理，这里不做重复绑定
        },
        logout: function () {
            // logout 由后端 302 重定向处理，无需 JS
        }
    };
    return Controller;
});
