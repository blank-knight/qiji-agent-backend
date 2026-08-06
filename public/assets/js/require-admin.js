define(['jquery', 'bootstrap', 'backend', 'toastr'], function ($, undefined, Backend, Toastr) {

    var Controller = {
        login: function () {
            // 拦截表单提交，转为AJAX
            $(document).on("submit", "#login-form", function () {
                var form = $(this);
                var url = form.attr("action") || location.href;
                $.ajax({
                    url: url,
                    type: "POST",
                    data: form.serialize(),
                    dataType: "json",
                    success: function (ret) {
                        if (ret.code === 1) {
                            location.href = ret.url || (ret.data && ret.data.url) || "index/index";
                        } else {
                            if (ret.data && ret.data.token) {
                                $('input[name="__token__"]').val(ret.data.token);
                            }
                            $("#errtips").removeClass("hide").html(ret.msg);
                            Toastr.error(ret.msg);
                        }
                    },
                    error: function () {
                        Toastr.error("Network error");
                    }
                });
                return false;
            });

            // 回车提交
            $(document).on("keypress", "#login-form input", function (e) {
                if (e.which == 13) {
                    e.preventDefault();
                    $("#login-form").submit();
                    return false;
                }
            });
        },
        logout: function () {
            $.ajax({
                url: "index/logout",
                type: "POST",
                dataType: "json",
                success: function (ret) {
                    location.href = ret.url || "index/login";
                }
            });
        }
    };
    return Controller;
});
