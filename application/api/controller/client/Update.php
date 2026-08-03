<?php

namespace app\api\controller\client;

use app\common\controller\Api;
use app\common\model\Version;

/**
 * 客户端更新检查接口
 */
class Update extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 检查更新
     * @ApiMethod (GET)
     */
    public function check()
    {
        $version = $this->request->get('version', '');
        if (!$version) {
            $this->error('请传入 version 参数');
        }

        $result = Version::check($version);

        if ($result) {
            $this->success('', [
                'has_update'  => true,
                'enforce'     => (int)$result['enforce'],
                'newversion'  => $result['newversion'],
                'downloadurl' => $result['downloadurl'],
                'packagesize' => $result['packagesize'],
                'upgradetext' => $result['upgradetext'],
            ]);
        } else {
            $this->success('', [
                'has_update' => false,
            ]);
        }
    }
}
