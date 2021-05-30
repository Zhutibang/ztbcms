<?php

namespace app\admin\model;

use app\admin\service\AdminConfigService;
use app\admin\service\AdminUserService;
use think\Model;
use think\facade\Request;

class OperationlogModel extends Model
{

    protected $name = 'operation_log';

    /**
     * 删除登录日志(X天前的数据)
     *
     * @param  int  $day  N
     *
     * @return bool
     */
    public function deleteOperationLog($day = 30)
    {
        $limit_time = time() - $day * 24 * 60 * 60;
        $this->where('time', '<=', $limit_time)->delete();
        return true;
    }

    /**
     * 记录日志
     * @param  \think\Response  $request
     * @return bool
     */
    public function record(\think\Response $request) {
        if (Request::instance()->isPost()) {
            //未开启的状态下不进行记录
            $admin_operation_switch = AdminConfigService::getInstance()->getConfig('admin_operation_switch')['data'];
            if (!is_numeric($admin_operation_switch) || $admin_operation_switch != 1) {
                return false;
            }
            $content = json_decode($request->getContent(), true);
            $url = request()->url();
            $status = $content['status'] ?? 0;
            $logData['uid'] = AdminUserService::getInstance()->getInfo()['id'] ?? 0;
            $logData['status'] = $status ? 1 : 0;
            $logData['info'] = $request->getContent();
            $logData['get'] = 'POST '.$url;
            $logData['time'] = time();
            $logData['ip'] = request()->ip();
            $this->insert($logData);
            return true;
        } else {
            return  false;
        }
    }

}