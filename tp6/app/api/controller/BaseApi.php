<?php
/**
 * Author: Jayin Taung <tonjayin@gmail.com>
 */

namespace app\api\controller;

use app\api\middleware\ApiAuth;
use app\BaseController;
use think\App;
use think\middleware\AllowCrossDomain;

/**
 * API基类
 */
class BaseApi extends BaseController
{

    /**
     * 跳过登录验证的Action
     * 注：
     * 1、若跳过全部，可以填写*
     * 2、跳过指定action，可填写 ['actionA', 'actionB']
     */
    protected $skillAuthActions = [];

    protected $middleware = [
        AllowCrossDomain::class,
        ApiAuth::class,
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        // 往请求注入
        $app->request->skillAuthActions = $this->skillAuthActions ?? [];
    }
}