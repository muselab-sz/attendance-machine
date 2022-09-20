<?php
/**
 * @copyright Copyright (c) 2021
 * @license https://opensource.org/licenses/Apache-2.0
 *
 */

declare (strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\User as UserList;
use app\admin\validate\UserCheck;
use dateset\Dateset;
use think\facade\Db;
use think\facade\View;

class User extends BaseController
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            if (!empty($param['keywords'])) {
                $where[] = ['job_no|name', 'like', '%' . $param['keywords'] . '%'];
            }

            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $content = UserList::where($where)
                ->order('id desc')
                ->paginate($rows, false, ['query' => $param])
                ->each(function ($item, $key) {
                    // $item->register_time = empty($item->register_time) ? '-' : date('Y-m-d H:i', $item->register_time);
                });
            return table_assign(0, '', $content);
        } else {
            return view();
        }
    }

    //添加/编辑
    public function edit()
    {
        $param = get_params();
        if (request()->isAjax()) {
            if (!empty($param['id']) && $param['id'] > 0) {
                try {
                    $isUserId = Db::name('User')
                        ->where('job_no', $param['job_no'])
                        ->where('id', '<>', $param['id'])
                        ->find();
                    if ($isUserId) {
                        return to_assign(1, '用户ID已存在');
                    }
                    $param['update_time'] = date('Y-m-d', time());
                    $param['download_status'] = '0';

                    $res = Db::name('User')->where(['id' => $param['id']])->strict(false)->field(true)->update($param);
                    Db::name('DeviceList')
                        ->where('status', '0')
                        ->where('id', '>', 0)
                        ->update([
                            'is_sync' => '1'
                        ]);
                    $devices = Db::name('DeviceList')
                        ->where('status', '0')
                        ->field('dev_id')
                        ->select();
                    $info = Db::name('User')
                        ->where(['id' => $param['id']])
                        ->find();
                    $transId = time() . rand(100, 999);
                    if ($devices) {
                        foreach ($devices as $val) {
                            Db::name('DeviceUserSync')
                                ->insert([
                                    'trans_id' => $transId,
                                    'dev_id' => $val['dev_id'],
                                    'job_no' => $info['job_no'],
                                    'status' => '0',
                                    'create_time' => date('Y-m-d H:i:s', time()),
                                    'update_time' => date('Y-m-d H:i:s', time()),
                                ]);
                        }
                    }
                    if ($res !== false) {
                        add_log('edit', $param['id'], $param);
                        return to_assign();
                    } else {
                        return to_assign(1, '提交失败');
                    }
                } catch (ValidateException $e) {
                    // 验证失败 输出错误信息
                    return to_assign(1, $e->getError());
                }
            } else {
                try {
                    validate(UserCheck::class)->scene('add')->check($param);
                    $isUserId = Db::name('User')->where('job_no', $param['job_no'])
                        ->find();
                    if ($isUserId) {
                        return to_assign(1, '用户ID已存在');
                    }
                    $param['create_time'] = date('Y-m-d H:i:s', time());
                    $param['update_time'] = date('Y-m-d H:i:s', time());
                    $param['download_status'] = '0';
                    $param['photoEnroll'] = '0';
                    $param['vaildStart'] = '20220101';
                    $param['vaildEnd'] = '20990101';

                    $id = Db::name('User')->insertGetId($param);
                    Db::name('DeviceList')
                        ->where('id', '>', 0)
                        ->update([
                            'is_sync' => '1'
                        ]);
                    $devices = Db::name('DeviceList')
                        ->field('dev_id')
                        ->select();
                    $transId = time() . rand(100, 999);
                    if ($devices) {
                        foreach ($devices as $val) {
                            Db::name('DeviceUserSync')
                                ->insert([
                                    'trans_id' => $transId,
                                    'dev_id' => $val['dev_id'],
                                    'job_no' => $param['job_no'],
                                    'status' => '0',
                                    'create_time' => date('Y-m-d H:i:s', time()),
                                    'update_time' => date('Y-m-d H:i:s', time()),
                                ]);
                        }
                    }
                    add_log('add', $id, $param);
                    return to_assign();
                } catch (ValidateException $e) {
                    // 验证失败 输出错误信息
                    return to_assign(1, $e->getError());
                }
            }
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $user = Db::name('User')->where(['id' => $id])->find();
            View::assign('user', $user);
            return view();
        }
    }

    //查看
    public function view()
    {
        $id = empty(get_params('id')) ? 0 : get_params('id');
        $user = Db::name('User')->where(['id' => $id])->find();
        add_log('view', get_params('id'));
        View::assign('user', $user);
        return view();
    }

    //禁用/启用
    public function disable()
    {
        $id = get_params("id");
        $data['id'] = $id;
        Db::name('User')
            ->where('id', $id)
            ->delete(true);
        add_log('disable', $id, $data);
        return to_assign();

    }

    //日志
    public function log()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            if (!empty($param['keywords'])) {
                $where[] = ['nickname|content|param_id', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['action'])) {
                $where[] = ['title', '=', $param['action']];
            }
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $content = DB::name('UserLog')
                ->field("id,uid,nickname,title,content,ip,param_id,param,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') create_time")
                ->order('create_time desc')
                ->where($where)
                ->paginate($rows, false, ['query' => $param]);

            $content->toArray();
            foreach ($content as $k => $v) {
                $data = $v;
                $param_array = json_decode($v['param'], true);
                $param_value = '';
                foreach ($param_array as $key => $value) {
                    if (is_array($value)) {
                        $value = array_to_string($value);
                    }
                    $param_value .= $key . ':' . $value . '&nbsp;&nbsp;|&nbsp;&nbsp;';
                }
                $data['param'] = $param_value;
                $content->offsetSet($k, $data);
            }
            return table_assign(0, '', $content);
        } else {
            $type_action = get_config('log.user_action');
            View::assign('type_action', $type_action);
            return view();
        }
    }

    //记录
    public function record()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            if (!empty($param['keywords'])) {
                $where[] = ['nickname|title', 'like', '%' . $param['keywords'] . '%'];
            }
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $content = Db::name('UserLog')
                ->field("id,uid,nickname,title,content,ip,param,create_time")
                ->order('create_time desc')
                ->where($where)
                ->paginate($rows, false, ['query' => $param]);

            $content->toArray();
            $date_set = new Dateset();
            foreach ($content as $k => $v) {
                $data = $v;
                $param_array = json_decode($v['param'], true);
                $name = '';
                if (!empty($param_array['name'])) {
                    $name = '：' . $param_array['name'];
                }
                if (!empty($param_array['title'])) {
                    $name = '：' . $param_array['title'];
                }
                $data['content'] = $v['content'] . $name;
                $data['times'] = $date_set->time_trans($v['create_time']);
                $content->offsetSet($k, $data);
            }
            return table_assign(0, '', $content);
        } else {
            return view();
        }
    }

}
