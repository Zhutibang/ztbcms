<?php

// +----------------------------------------------------------------------
// |  本地模块管理
// +----------------------------------------------------------------------

namespace Admin\Controller;

use Common\Controller\AdminBase;
use Libs\System\Module;

class ModuleController extends AdminBase
{

    //模块所处目录路径
    protected $appPath = APP_PATH;
    protected $tpAppPath = SITE_PATH . "tp6/app/";
    //已安装模块列表
    protected $moduleList = array();
    //系统模块，隐藏
    protected $systemModuleList = array('Admin', 'Api', 'Install', 'Attachment', 'Template', 'Content');

    //初始化
    protected function _initialize()
    {
        parent::_initialize();
        $this->moduleList = M('Module')->select();
    }

    //远程模块仓库
    public function remote()
    {
        $this->display('remote');
    }


    //本地模块列表
    public function local()
    {
        $this->redirect('index');
    }

    //本地模块列表
    public function index()
    {
        //取得模块目录名称
        $dirs = glob($this->appPath . '*');
        $dirs_arr = [];

        //tp6下的模块
        $tpDisrs = glob($this->tpAppPath . "*");
        foreach ($tpDisrs as $path) {
            if (is_dir($path) && file_exists($path . '/Config.inc.php')) {
                $pathName = basename($path);
                $dirs_arr[ucwords($pathName)] = $path . '/Config.inc.php';
            }
        }

        foreach ($dirs as $path) {
            if (is_dir($path)) {
                //目录名称
                $pathName = basename($path);
                //系统模块隐藏
                if (in_array($pathName, $this->systemModuleList)) {
                    continue;
                }
                $dirs_arr[$pathName] = $path . '/Config.inc.php';
            }
        }
        //取得已安装模块列表
        $moduleList = array();
        foreach ($this->moduleList as $v) {
            $moduleList[$v['module']] = $v;
            //检查是否系统模块，如果是，直接不显示
            if ($v['iscore']) {
                $key = array_keys($dirs_arr, $v['module']);
                unset($dirs_arr[$key[0]]);
            }
        }


        //数量
        $count = count($dirs_arr);
        //把一个数组分割为新的数组块
        $dirs_arr = array_chunk($dirs_arr, 10, true);
        //当前分页
        $page = max(I('get.' . C('VAR_PAGE'), 0, 'intval'), 1);
        //根据分页取到对应的模块列表数据
        $directory = $dirs_arr[intval($page - 1)];

        foreach ($directory as $moduleName => $moduleConfig) {
            $moduleList[$moduleName] = $this->Module->config($moduleName, $moduleConfig);
        }
        //进行分页
        $Page = $this->page($count, 10, I('get.page', 1));

        $this->assign("Page", $Page->show());
        $this->assign("data", $moduleList);
        $this->assign("modules", $this->moduleList);
        $this->display();
    }

    //安装
    public function install()
    {
        if (IS_POST) {
            $post = I('post.');
            $module = $post['module'];
            $isTp6 = $post['is_tp6'];
            if (empty($module)) {
                $this->error('请选择需要安装的模块！');
            }
            $configPath = '';
            if ($isTp6 == 1) {
                $this->Module->isTp6 = true;
                //如果是tp6框架的模块，指向模块的config
                $configPath = $this->tpAppPath . strtolower($module) . '/Config.inc.php';
            }
            if ($this->Module->install($module, $configPath)) {
                $this->success('模块安装成功！', U('Admin/Module/index'));
            } else {
                $error = $this->Module->error;
                $this->error($error ? $error : '模块安装失败！');
            }
        } else {
            $module = I('get.module', '', 'trim,ucwords');
            $isTp6 = I('get.is_tp6', 0);
            if (empty($module)) {
                $this->error('请选择需要安装的模块！');
            }
            //检查是否已经安装过
            if ($this->Module->isInstall($module) !== false) {
                $this->error('该模块已经安装！');
            }
            if ($isTp6 == 1) {
                $this->Module->isTp6 = true;
            }
            $config = $this->Module->config($module);
            //版本检查
            if ($config['adaptation']) {
                $version = version_compare(CMS_VERSION, $config['adaptation'], '>=');
                $this->assign('version', $version);
            }
            $this->assign('config', $config);
            $this->display();
        }
    }

    //模块卸载 
    public function uninstall()
    {
        $module = I('get.module', '', 'trim,ucwords');
        $isTp6 = I('get.is_tp6', 0);
        if (empty($module)) {
            $this->error('请选择需要安装的模块！');
        }
        if ($isTp6 == 1) {
            $this->Module->isTp6 = true;
        }
        if ($this->Module->uninstall($module)) {
            $this->success("模块卸载成功，请及时更新缓存！", U("Module/index"));
        } else {
            $error = $this->Module->error;
            $this->error($error ? $error : "模块卸载失败！", U("Module/index"));
        }
    }

    //模块状态转换
    public function disabled()
    {
        $module = I('get.module', '', 'trim,ucwords');
        if (empty($module)) {
            $this->error('请选择模块！');
        }
        if (D('Common/Module')->disabled($module)) {
            $this->success("状态转换成功，请及时更新缓存！", U("Module/index"));
        } else {
            $this->error("状态转换成功失败！", U("Module/index"));
        }
    }

}
