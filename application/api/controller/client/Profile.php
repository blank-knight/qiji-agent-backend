<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use think\Db;

/**
 * 个人中心接口（客户端用户：资料/头像/积分/流水）
 */
class Profile extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->initAuth();
    }

    private function initAuth()
    {
        $token = $this->getBearerToken();
        if ($token) {
            $this->auth->init($token);
        }
    }

    private function getBearerToken()
    {
        $header = $this->request->header('authorization', '');
        if ($header && preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }
        return $this->request->request('token', '');
    }

    private function requireUser()
    {
        if (!$this->auth->isLogin()) {
            $this->error('请先登录', null, 401);
        }
        return $this->auth->getUser();
    }

    /**
     * 个人资料（含积分）
     * @ApiMethod (GET)
     */
    public function index()
    {
        $user = $this->requireUser();

        $agentName = '';
        if ($user->agent_id) {
            $agentName = Db::name('agent')->where('id', $user->agent_id)->value('name') ?: '';
        }

        $this->success('', [
            'id'         => (int)$user->id,
            'username'   => $user->username ?: '',
            'nickname'   => $user->nickname ?: '',
            'avatar'     => $user->avatar ?: '',
            'mobile'     => $user->mobile ? substr_replace($user->mobile, '****', 3, 4) : '',
            'email'      => $user->email ?: '',
            'score'      => (int)$user->score,
            'mode'       => $user->score > 0 ? 'formal' : 'trial',
            'agent_name' => $agentName,
            'createtime' => $user->createtime ? date('Y-m-d', (int)$user->createtime) : '',
        ]);
    }

    /**
     * 更新资料（昵称/邮箱）
     * @ApiMethod (POST)
     */
    public function update()
    {
        $user = $this->requireUser();

        $nickname = trim($this->request->post('nickname', ''));
        $email    = trim($this->request->post('email', ''));

        $data = [];
        if ($nickname !== '') {
            if (mb_strlen($nickname) > 30) {
                $this->error('昵称最长30个字符');
            }
            $data['nickname'] = $nickname;
        }
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('邮箱格式不正确');
            }
            $data['email'] = $email;
        }
        if (!$data) {
            $this->error('没有需要更新的内容');
        }

        Db::name('user')->where('id', $user->id)->update($data);
        $this->success('保存成功');
    }

    /**
     * 设置头像（头像ID或图片URL，客户端本地选择后上传/或直接传头像标识）
     * @ApiMethod (POST)
     */
    public function avatar()
    {
        $user = $this->requireUser();

        $avatar = trim($this->request->post('avatar', ''));
        if ($avatar === '') {
            $this->error('头像不能为空');
        }
        if (mb_strlen($avatar) > 255) {
            $this->error('头像数据过长');
        }
        // 允许的形态：相对/绝对URL 或 avatar:// 协议（客户端本地头像）
        if (!preg_match('#^(https?://|/|avatar://)#i', $avatar)) {
            $this->error('头像地址不合法');
        }

        Db::name('user')->where('id', $user->id)->update(['avatar' => $avatar]);
        $this->success('头像已更新', ['avatar' => $avatar]);
    }

    /**
     * 积分流水
     * @ApiMethod (GET)
     */
    public function scorelogs()
    {
        $user = $this->requireUser();

        $page  = max(1, (int)$this->request->get('page', 1));
        $limit = min(50, max(1, (int)$this->request->get('limit', 10)));

        $total = Db::name('user_score_log')->where('user_id', $user->id)->count();
        $rows  = Db::name('user_score_log')
            ->where('user_id', $user->id)
            ->order('createtime', 'desc')
            ->page($page, $limit)
            ->select();

        foreach ($rows as &$row) {
            $row['createtime'] = $row['createtime'] ? date('Y-m-d H:i', (int)$row['createtime']) : '';
        }

        $this->success('', ['total' => $total, 'page' => $page, 'rows' => $rows]);
    }
}

