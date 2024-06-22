<?php

namespace app\admin\controller\tg;

use app\common\controller\Backend;

use think\Cache;
use think\Db;

/**
 * 监听地址
 *
 * @icon fa fa-circle-o
 */
class Address extends Backend
{

    /**
     * Address模型对象
     * @var \app\admin\model\Tg\Address
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Tg\Address;


        $this->assignconfig("import_url", url("tg/address/importaddress"));
    }



    public function addredis($ids = '')
    {
        $allAddress = db('tg_address')->select();
        foreach ($allAddress as $kk=>$vv) {
            Cache::store('redis')->handler()->SADD('listens',$vv['address']);
        }

        $this->success("成功");
    }
    

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    ->with(['user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','tg_id','bot_id','user_id','address','bak','createtime']);
                $row->visible(['user']);
				$row->getRelation('user')->visible(['chat_id','username','nickname']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }



    public function importaddress()
    {
        if ($this->request->isAjax()) {
            $user_id = $this->request->param("user_id");
            $address = $this->request->param("address");

            if(empty($user_id) || empty($address)) {
                $this->error("参数错误");
            }

            $address = explode("\n", $address);
            foreach ($address as $kk=>$vv) {
                $str_first = ucfirst(msubstr_local2($vv, 0, 1));
                if($str_first != "T" && $str_first != "0") {
                    continue;
                } else {
                    $len = strlen($vv);
                    if($str_first == "T") {
                        if($len != 34 )
                        {
                            continue;
                        }
                    }

                    if ($str_first == "0") {
                        if($len != 42 ) {
                            continue;
                        }
                    }
                }

                $userInfo = Db::name("user")
                    ->where("id", $user_id)
                    ->find();

                if(empty($userInfo['tg_id'])) {
                    continue;
                }


                $hasIf = Db::name("tg_address")
                    ->where("user_id", $user_id)
                    ->where("tg_id", $userInfo['tg_id'])
                    ->where("address", trim($vv))
                    ->find();

                if(!empty($hasIf['id'])) {
                    continue;
                }

                Db::name("tg_address")->insertGetId([
                    "user_id" => $user_id,
                    "tg_id" => $userInfo['tg_id'],
                    "bot_id" => $userInfo['bot_id'],
                    "address" => trim($vv),
                    "createtime" => time(),
                    "bak" => "批量导入"
                ]);
                
                
                Cache::store('redis')->handler()->SADD('listens',trim($vv));
            }

            $this->success("操作成功");
        }


        return $this->view->fetch();
    }
}
