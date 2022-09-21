<?php
/**
 * 考勤机接口
 */
declare (strict_types=1);

namespace app\api\controller;

use app\api\BaseController;
use app\api\middleware\Auth;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;

class Index extends BaseController
{
    /**
     * 控制器中间件 [登录、注册 不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['index']]
    ];

    /**
     * @api {post} /index/index API页面
     * @apiDescription  返回首页信息
     */
    public function index()
    {
        $header = Request::header();

        // 表示机器向服务器询问有没有对自己发送的指令,需求代码
        $requestCode = $header['request-code'];
        // 机器识别号
        $devId = $header['dev-id'];
        // 用于区分设备类型
        $devModel = $header['dev-model'];
        // 任务id
        $transId = $header['trans-id'];

        // 请求体数据
        $req = $this->request->post();

        // 判断设备是否存在，并写入
        $device = Db::name('DeviceList')
            ->where('dev_id', $devId)
            ->find();
        if (!$device) {
            Db::name('DeviceList')->insert([
                'dev_id' => $devId,
                'dev_model' => $devModel,
                'create_time' => date('Y-m-d H:i:s', time()),
                'update_time' => date('Y-m-d H:i:s', time()),
            ]);
        }

        $body = [];
        $status = 'OK';
        $cmdCode = '';

        // 处理上报数据、 // 设备上传用户指纹人脸照片数据
        if ($transId == 'RTEnrollData' && $requestCode == 'realtime_enroll_data') {
            if (!isset($req['userId']) || empty($req['userId'])) {
                Log::record('Req====================' . '上报数据userId为空');
                return $this->deviceReturn($body, ['trans_id' => 100, 'response_code' => $status, 'cmd_code' => $cmdCode, 'Content-Length' => 0]);
            }
            $updateData = [];
            if (isset($req['face']) && !empty($req['face'])) {
                $updateData['face'] = $req['face'];
            }
            if (isset($req['name']) && !empty($req['name'])) {
                $updateData['name'] = $req['name'];
            }
            if (isset($req['photo']) && !empty($req['photo'])) {
                $updateData['photo'] = $req['photo'];
            }
            if (isset($req['privilege']) && !empty($req['privilege'])) {
                $updateData['privilege'] = $req['privilege'];
            }
            if (isset($req['privilege']) && !empty($req['privilege'])) {
                $updateData['password'] = $req['pwd'];
            }
            if (isset($req['vaildEnd']) && !empty($req['vaildEnd'])) {
                $updateData['vaildEnd'] = $req['vaildEnd'];
            }
            if (isset($req['vaildStart']) && !empty($req['vaildStart'])) {
                $updateData['vaildStart'] = $req['vaildStart'];
            }
            if (isset($req['fps']) && !empty($req['fps'])) {
                $updateData['fps'] = json_encode($req['fps']);
            }
            if (isset($req['palm']) && !empty($req['palm'])) {
                $updateData['palm'] = json_encode($req['palm']);
            }
            $updateData['update_time'] = date('Y-m-d H:i:s', time());
            // 更新用户信息并更新设备用户下发状态
            Db::name('User')
                ->where('job_no', $req['userId'])
                ->update($updateData);
            if (isset($devId) && !empty($devId)) {
                Db::name('DeviceList')
                    ->where('dev_id', $devId)
                    ->update([
                        'is_sync' => '0',
                        'update_time' => date('Y-m-d H:i:s', time()),
                    ]);
                $count = Db::name('DeviceList')
                    ->where('is_sync', '1')
                    ->count();
                if ($count == 0) {
                    Db::name('User')
                        ->where('job_no', $req['userId'])
                        ->update([
                            'download_status' => '1',
                            'update_time' => date('Y-m-d H:i:s', time()),
                        ]);
                }
            }
            return $this->deviceReturn($body, ['trans_id' => 100, 'response_code' => 'OK']);
        }

        // 设备上传实时打卡记录
        if ($transId == 'RTLogSend' && $requestCode == 'realtime_glog') {
            Db::name('SignRecord')
                ->insert([
                    'ioMode' => $req['ioMode'],
                    'time' => $req['time'],
                    'userId' => $req['userId'],
                    'verifyMode' => $req['verifyMode'],
                    'ori_data' => json_encode($req),
                    'dev_model' => $devModel,
                    'dev_id' => $devId,
                    'create_time' => date('Y-m-d H:i:s', time()),
                    'update_time' => date('Y-m-d H:i:s', time()),
                ]);
            return $this->deviceReturn($body, ['trans_id' => $transId, 'response_code' => 'OK']);
        }

        // 指定只执行一次
        if (is_numeric($transId) && Cache::get('once_' . $transId)) {
            $transId = '';
            $cmdCode = '';
            $status = 'ERROR_NO_CMD';
            return $this->deviceReturn($body, ['trans_id' => $transId, 'response_code' => $status, 'cmd_code' => $cmdCode, 'Content-Length' => 0]);
        }

        $transId = rand(100, 999);

        // 设备请求接收指令
        if ($requestCode == 'receive_cmd') {

            // 同步时间
            if (!empty($req['time'])) {
                if (abs(strtotime($req['time']) - time()) > 60) {//比对设备与服务器时间差决定是否要同步时间
                    $cmdCode = 'SET_TIME';
                    $body = ['syncTime' => date('YmdHis')];
                    $length = strlen(json_encode($body, JSON_UNESCAPED_UNICODE));
                    return $this->deviceReturn($body, ['trans_id' => $transId, 'response_code' => $status, 'cmd_code' => $cmdCode, 'Content-Length' => $length]);
                }
            }

            Cache::set('once_' . $transId, '1', 10);

            // 如果设备存在未同步状态，先发送用户数据
            $deviceExec = Db::name('DeviceUserSync')
                ->where('status', '0')
                ->where('dev_id', $devId)
                ->find();
            // 没有更新用户返回空指令
            if (!$deviceExec) {
                $transId = '';
                $cmdCode = '';
                $status = 'ERROR_NO_CMD';
                return $this->deviceReturn($body, ['trans_id' => $transId, 'response_code' => $status, 'cmd_code' => $cmdCode, 'Content-Length' => 0]);
            }

            $cmdCode = 'SET_USER_INFO';
            // 获取用户列表
            $userList = Db::name('User')
                ->fieldRaw("job_no as userId,privilege,name,password as pwd, 1 as `update`,vaildStart,vaildEnd")
                ->select();
            if ($userList) {
                $body['users'] = $userList->toArray();
                foreach ($body['users'] as $key => $val) {
                    $body['users'][$key]['userId'] = strval($val['userId']);
                    $body['users'][$key]['privilege'] = intval($val['privilege']);
                }
            }
            /*$body = [
                'users' => [
                    [
                        'userId' => '123456',
                        'privilege' => 1,
                        'name' => 'Admin1',
                        'pwd' => '123456',
                        "update" => 1,
                    ],
                    [
                        'userId' => '123457',
                        'privilege' => 0,
                        'name' => 'zhang1',
                        'pwd' => '123456',
                        "update" => 1,
                    ]
                ]
            ];*/

            $length = 0;
            if ($body) {
                $length = strlen(json_encode($body, JSON_UNESCAPED_UNICODE));
            }
            return $this->deviceReturn($body, ['trans_id' => $deviceExec['trans_id'], 'response_code' => $status, 'cmd_code' => $cmdCode, 'Content-Length' => $length]);


            //$cmdCode = 'GET_DEVICE_INFO';//ok
            //$cmdCode = 'GET_DEVICE_SETTING';//ok
            //$cmdCode = 'GET_LOG_DATA';//ok
            //$cmdCode = 'CLEAR_LOG_DATA';//ok
            //$body = [];
            //$body = ['newLog'=>0,'beginTime'=>'20210101','endTime'=>'20210904','clearMark'=>0];
            //$cmdCode = 'GET_USER_INFO';//ok
            //$body = '{}';json_encode(array('usersId'=>array('100001')));
            //$cmdCode = 'GET_USER_ID_LIST';//ok
            //$cmdCode = 'DELETE_USER';//bug
            //$body = json_encode(array('usersCount'=>2,'usersId'=>array('123','456456')));
            //$cmdCode = 'RESET_FK';//ok
            //$cmdCode = 'CLEAR_MANAGER';//ok
            //$cmdCode = 'SET_DEVICE_SETTING';//ok
            //$body = json_encode(array('devName'=>'BH3','interval'=>'10'));
            //$cmdCode = 'RESET_ENROLL_MARK';//ok
            //$cmdCode = 'RESET_LOG_MARK';//ok
            //$body = json_encode(array('beginTime'=>'20200101','endTime'=>'20211111'));
            //$cmdCode = 'RESET_DEVICE';//ok
            /*if ($userid && $photobase64) {
                $cmdCode = 'SET_USER_INFO';
                $body = json_encode(array('users' => array(array('userId' => $userid, 'name' => $userid, 'photoEnroll' => 1, 'privilege' => 0, 'photo' => $photobase64))));
                //$body = json_encode(array('users'=>array(array('userId'=>$userid,'name'=>$userid,'privilege'=>0,'pwd'=>'123456'))));
            }*/

        } else {
            if ($requestCode == 'send_cmd_result') { // 设备上传指令执行结果
                $transId = $header['trans-id'];
                $cmdReturnCode = $header['cmd-return-code'];
                if ($cmdReturnCode == 'OK') {
                    Db::name('DeviceUserSync')
                        ->where('dev_id', $devId)
                        ->where('trans_id', $transId)
                        ->update([
                            'status' => '1'
                        ]);
                    // 查询当前设备还有没有任务
                    $deviceProcess = Db::name('DeviceUserSync')
                        ->where('status', '0')
                        ->where('dev_id', $devId)
                        ->count();
                    if ($deviceProcess == 0) {
                        // 没有任务时把设备下发标记完成
                        Db::name('DeviceList')
                            ->where('dev_id', $devId)
                            ->update([
                                'is_sync' => '0'
                            ]);
                        // 查询还有没有这个任务的其他设备未同步
                        $transOther = Db::name('DeviceUserSync')
                            ->where('status', '0')
                            ->where('trans_id', $transId)
                            ->count();
                        if ($transOther == 0) {
                            // 没有任务是标记用户已完成全设备下发
                            $transUserId = Db::name('DeviceUserSync')
                                ->where('trans_id', $transId)
                                ->field('job_no')
                                ->find();
                            if ($transUserId) {
                                // 更新用户已下发状态
                                Db::name('User')
                                    ->where('job_no', $transUserId['job_no'])
                                    ->update([
                                        'download_status' => '1'
                                    ]);
                            }
                        }
                    }
                }

                return $this->deviceReturn($body, ['trans_id' => $transId, 'response_code' => 'OK']);
            }
            return $this->deviceReturn($body, ['trans_id' => $transId, 'response_code' => 'OK']);
        }
    }

}
