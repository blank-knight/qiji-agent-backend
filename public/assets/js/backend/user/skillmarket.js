/* 技能市场管理页（原生JS，无requirejs依赖） */
(function () {
    var table = document.getElementById('table');
    var modal = document.getElementById('skill-modal');
    if (!table) return;

    function getToken() {
        var el = document.querySelector('#skill-form input[name="__token__"]');
        return el ? el.value : '';
    }

    function refreshToken(html) {
        // 服务器每次渲染新token；从返回页面抓一次太重——FastAdmin token 支持一次性，
        // 这里用最简做法：保存成功后重新加载页面刷新token
    }

    function load() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', location.pathname, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            var ret;
            try { ret = JSON.parse(xhr.responseText); } catch (e) { return; }
            var rows = (ret && ret.rows) || [];
            var html = '<thead><tr><th>ID</th><th>标识</th><th>名称</th><th>分类</th><th>版本</th><th>大小</th><th>下载次数</th><th>状态</th><th>更新时间</th><th>操作</th></tr></thead><tbody>';
            rows.forEach(function (r) {
                html += '<tr>'
                    + '<td>' + r.id + '</td>'
                    + '<td>' + r.name + '</td>'
                    + '<td>' + escapeHtml(r.title) + '</td>'
                    + '<td>' + escapeHtml(r.category) + '</td>'
                    + '<td>' + r.version + '</td>'
                    + '<td>' + (r.size_text || '-') + '</td>'
                    + '<td>' + r.download_count + '</td>'
                    + '<td>' + (r.status === 'normal' ? '<span class="label label-success">上架</span>' : '<span class="label label-default">下架</span>') + '</td>'
                    + '<td>' + (r.updatetime ? new Date(r.updatetime * 1000).toLocaleDateString() : '-') + '</td>'
                    + '<td>'
                    + '<button class="btn btn-xs btn-default" data-act="edit" data-id="' + r.id + '">编辑</button> '
                    + '<button class="btn btn-xs btn-warning" data-act="toggle" data-id="' + r.id + '">' + (r.status === 'normal' ? '下架' : '上架') + '</button> '
                    + '<button class="btn btn-xs btn-danger" data-act="del" data-id="' + r.id + '">删除</button>'
                    + '</td></tr>';
            });
            html += '</tbody>';
            table.innerHTML = html;
        };
        xhr.send();
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }

    function openModal(row) {
        document.getElementById('f-id').value = row ? row.id : 0;
        document.getElementById('f-name').value = row ? row.name : '';
        document.getElementById('f-name').disabled = !!row;
        document.getElementById('f-title').value = row ? row.title : '';
        document.getElementById('f-desc').value = row ? (row.description || '') : '';
        document.getElementById('f-cat').value = row ? row.category : 'general';
        document.getElementById('f-ver').value = row ? row.version : '1.0.0';
        document.getElementById('f-weigh').value = row ? (row.weigh || 0) : 0;
        document.getElementById('f-status').value = row ? row.status : 'normal';
        document.getElementById('f-file').value = '';
        document.getElementById('modal-title').textContent = row ? '编辑技能：' + row.title : '上架技能';
        modal.style.display = 'block';
    }

    function save() {
        var form = document.getElementById('skill-form');
        var fd = new FormData(form);
        fd.append('__token__', getToken());
        var xhr = new XMLHttpRequest();
        xhr.open('POST', location.pathname.replace(/\/index$/, '') + '/save', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            var ret;
            try { ret = JSON.parse(xhr.responseText); } catch (e) { alert('响应异常'); return; }
            if (ret.code === 1) {
                modal.style.display = 'none';
                location.reload(); // 刷新token + 列表
            } else {
                alert(ret.msg || '保存失败');
                if ((ret.msg || '').indexOf('token') >= 0 || ret.code === 0) location.reload();
            }
        };
        xhr.send(fd);
    }

    function act(action, id) {
        if (action === 'del' && !confirm('确认删除该技能记录？')) return;
        var url = location.pathname.replace(/\/index$/, '') + (action === 'del' ? '/remove' : '/' + action);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            var ret;
            try { ret = JSON.parse(xhr.responseText); } catch (e) { alert('响应异常'); return; }
            if (ret.code === 1) { load(); } else { alert(ret.msg || '操作失败'); }
        };
        xhr.send('id=' + id + '&__token__=' + encodeURIComponent(getToken()));
    }

    document.getElementById('btn-add').addEventListener('click', function () { openModal(null); });
    document.getElementById('btn-refresh').addEventListener('click', load);
    document.getElementById('btn-cancel').addEventListener('click', function () { modal.style.display = 'none'; });
    document.getElementById('btn-save').addEventListener('click', save);
    document.getElementById('f-name').disabled = false;

    table.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-act]');
        if (!btn) return;
        var action = btn.getAttribute('data-act');
        var id = btn.getAttribute('data-id');
        if (action === 'edit') {
            // 从当前表格数据找行
            var xhr = new XMLHttpRequest();
            xhr.open('GET', location.pathname, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                try {
                    var rows = JSON.parse(xhr.responseText).rows || [];
                    var row = rows.filter(function (r) { return String(r.id) === String(id); })[0];
                    if (row) openModal(row);
                } catch (e) { }
            };
            xhr.send();
        } else {
            act(action, id);
        }
    });

    load();
})();
