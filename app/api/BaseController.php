<?php
/**
 * @copyright Copyright (c) 2021
 * @license https://opensource.org/licenses/Apache-2.0
 *
 */

declare (strict_types=1);

namespace app\api;

use think\App;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\facade\Request;
use think\Response;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 分页数量
     * @var string
     */
    protected $pageSize = '';

    /**
     * jwt配置
     * @var string
     */
    protected $jwt_conf = [
        'secrect' => 'gougucms',
        'iss' => 'www.gougucms.com', //签发者 可选
        'aud' => 'gougucms', //接收该JWT的一方，可选
        'exptime' => 7200, //过期时间,这里设置2个小时
    ];

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        // $this->jwt_conf = get_system_config('token');
        $this->jwt_conf = '';
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        //每页显示数据量
        $this->pageSize = Request::param('page_size', \think\facade\Config::get('app.page_size'));
        $info = Request::header();
        Log::record("\r\n请求头：\r\n" . json_encode($info, JSON_UNESCAPED_UNICODE) . "\r\n请求体：\r\n" . json_encode($this->request->post(), JSON_UNESCAPED_UNICODE) . "\r\n");
    }

    /**
     * Api处理成功结果返回方法
     * @param      $message
     * @param null $redirect
     * @param null $extra
     * @return mixed
     * @throws ReturnException
     */
    protected function apiSuccess($msg = 'success', $data = [])
    {
        return $this->apiReturn($data, 0, $msg);
    }

    /**
     * Api处理结果失败返回方法
     * @param      $error_code
     * @param      $message
     * @param null $redirect
     * @param null $extra
     * @return mixed
     * @throws ReturnException
     */
    protected function apiError($msg = 'fail', $data = [], $code = 1)
    {
        return $this->apiReturn($data, $code, $msg);
    }

    /**
     * 返回封装后的API数据到客户端
     * @param mixed $data 要返回的数据
     * @param integer $code 返回的code
     * @param mixed $msg 提示信息
     * @param string $type 返回数据格式
     * @param array $header 发送的Header信息
     * @return Response
     */
    protected function apiReturn($data, int $code = 0, $msg = '', string $type = '', array $header = []): Response
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'time' => time(),
            'data' => $data,
        ];

        $type = $type ?: 'json';
        $response = Response::create($result, $type)->header($header);

        throw new HttpResponseException($response);
    }

    /**
     * @param array $body 响应体
     * @param array $header 响应头
     * @return Response
     */
    protected function deviceReturn(array $body = [], array $header = []): Response
    {
        Log::record("\r\n" . 'Res====================' . "\r\n响应头：\r\n" . json_encode($header) . "\r\n响应体：\r\n" . json_encode($body) . "\r\n");
        $response = Response::create($body, 'json')->header($header);
        throw new HttpResponseException($response);
    }

}
