<?php
/**
 * @copyright Copyright (c) 2021  
 * @license https://opensource.org/licenses/Apache-2.0
 *  
 */
// 应用公共文件
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;

//设置缓存
function set_cache($key, $value, $date = 86400)
{
    Cache::set($key, $value, $date);
}

//读取缓存
function get_cache($key)
{
   return Cache::get($key);
}

//清空缓存
function clear_cache($key)
{
    Cache::clear($key);
}


//读取文件配置
function get_config($key)
{
    return Config::get($key);
}

//读取系统配置
function get_system_config($name,$key='')
{
    $config=[];
    if (get_cache('system_config' . $name)) {
        $config = get_cache('system_config' . $name);
    } else {
        $conf = Db::name('config')->where('name',$name)->find();
        if($conf['content']){
            $config = unserialize($conf['content']);
        }
        set_cache('system_config' . $name, $config);
    }
    if($key==''){
        return $config;
    }
	else{
		if($config[$key]){
			return $config[$key];
		}				
	}
}

//系统信息
function get_system_info($key)
{
    $system = [
        'os' => PHP_OS,
        'php' => PHP_VERSION,
        'upload_max_filesize' => get_cfg_var("upload_max_filesize") ? get_cfg_var("upload_max_filesize") : "不允许上传附件",
        'max_execution_time' => get_cfg_var("max_execution_time") . "秒 ",
    ];
    if (empty($key)) {
        return $system;
    } else {
        return $system[$key];
    }
}

//获取url参数
function get_params($key = "")
{
    return Request::instance()->param($key);
}

//生成一个不会重复的字符串
function make_token()
{
    $str = md5(uniqid(md5(microtime(true)), true));
    $str = sha1($str); //加密
    return $str;
}

//随机字符串，默认长度10
function set_salt($num = 10)
{
    $str = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
    $salt = substr(str_shuffle($str), 10, $num);
    return $salt;
}
//密码加密
function set_password($pwd, $salt)
{
    return md5(md5($pwd . $salt) . $salt);
}

//判断cms是否完成安装
function is_installed()
{
    static $isInstalled;
    if (empty($isInstalled)) {
        $isInstalled = file_exists(CMS_ROOT . 'config/install.lock');
    }
    return $isInstalled;
}

//判断cms是否存在模板
function isTemplate($url='')
{
    static $isTemplate;
    if (empty($isTemplate)) {
        $isTemplate = file_exists(CMS_ROOT . 'app/'.$url);
    }
    return $isTemplate;
}

/**
 * 返回json数据，用于接口
 * @param    integer    $code
 * @param    string     $msg
 * @param    array      $data
 * @param    string     $url
 * @param    integer    $httpCode
 * @param    array      $header
 * @param    array      $options
 * @return   json
 */
function to_assign($code = 0, $msg = "操作成功", $data = [], $url = '', $httpCode = 200, $header = [], $options = [])
{
    $res = ['code' => $code];
    $res['msg'] = $msg;
    $res['url'] = $url;
    if (is_object($data)) {
        $data = $data->toArray();
    }
    $res['data'] = $data;
    $response = \think\Response::create($res, "json", $httpCode, $header, $options);
    throw new \think\exception\HttpResponseException($response);
}

/**
 * 适配layui数据列表的返回数据方法，用于接口
 * @param    integer    $code
 * @param    string     $msg
 * @param    array      $data
 * @param    integer    $httpCode
 * @param    array      $header
 * @param    array      $options
 * @return   json
 */
function table_assign($code = 0, $msg = '请求成功', $data = [], $httpCode = 200, $header = [], $options = [])
{
    $res['code'] = $code;
    $res['msg'] = $msg;
    if (is_object($data)) {
        $data = $data->toArray();
    }
    if (!empty($data['total'])) {
        $res['count'] = $data['total'];
    } else {
        $res['count'] = 0;
    }
    $res['data'] = $data['data'];
    $response = \think\Response::create($res, "json", $httpCode, $header, $options);
    throw new \think\exception\HttpResponseException($response);
}

//菜单转为父子菜单
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'list', $root = 0)
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[$data[$pk]] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][$data[$pk]] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}
/**
 * 时间戳格式化
 * @param int    $time
 * @param string $format 默认'Y-m-d H:i'，x代表毫秒
 * @return string 完整的时间显示
 */
function time_format($time = NULL, $format = 'Y-m-d H:i:s')
{
    $usec = $time = $time === null ? '' : $time;
    if (strpos($time, '.')!==false) {
        list($usec, $sec) = explode(".", $time);
    } else {
        $sec = 0;
    }

    return $time != '' ? str_replace('x', $sec, date($format, intval($usec))) : '';
}

/**
 * 根据附件表的id返回url地址
 * @param  [type] $id [description]
 */
function get_file($id)
{
    if ($id) {
        $geturl = \think\facade\Db::name("file")->where(['id' => $id])->find();
        if ($geturl['status'] == 1) {
            //审核通过
            //获取签名的URL
            $url = $geturl['filepath'];
            return $url;
        } elseif ($geturl['status'] == 0) {
            //待审核
            return '/static/admin/images/nonepic360x360.jpg';
        } else {
            //不通过
            return '/static/admin/images/nonepic360x360.jpg';
        }
    }
    return false;
}

function get_file_list($dir)
{
	$list=[];
    if(is_dir($dir)){
        $info = opendir($dir);
        while (($file = readdir($info)) !== false) {
            //echo $file.'<br>';
			$pathinfo=pathinfo($file);
			if($pathinfo['extension']=='html'){   //只获取符合后缀的文件
				array_push($list, $pathinfo);
			}
        }
        closedir($info);
    }
	return $list;
}

//获取当前登录用户的信息
function get_login_user($key = "")
{
    $session_user = get_config('app.session_user');
    if (\think\facade\Session::has($session_user)) {
        $gougu_user = \think\facade\Session::get($session_user);
        if (!empty($key)) {
            if (isset($gougu_user[$key])) {
                return $gougu_user[$key];
            } else {
                return '';
            }
        } else {
            return $gougu_user;
        }
    } else {
        return '';
    }
}

/**
 * 判断访客是否是蜘蛛
 */
function isRobot($except = '') {
    $ua = strtolower ( $_SERVER ['HTTP_USER_AGENT'] );
    $botchar = "/(baidu|google|spider|soso|yahoo|sohu-search|yodao|robozilla|AhrefsBot)/i";
    $except ? $botchar = str_replace ( $except . '|', '', $botchar ) : '';
    if (preg_match ( $botchar, $ua )) {
       return true;
    }
    return false;
 }

/**
 * 客户操作日志
 * @param string $type 操作类型 login reg add edit view delete down join sign play order pay
 * @param string    $param_str 操作内容
 * @param int    $param_id 操作内容id
 * @param array  $param 提交的参数
 */
function add_user_log($type, $param_str = '', $param_id = 0, $param = [])
{
	$request = request();
	$title = '未知操作';
	$type_action = get_config('log.user_action');
	if($type_action[$type]){
		$title = $type_action[$type];
	}
    if ($type == 'login') {
        $login_user = \think\facade\Db::name('User')->where(array('id' => $param_id))->find();
        if ($login_user['nickname'] == '') {
            $login_user['nickname'] = $login_user['name'];
        }
        if ($login_user['nickname'] == '') {
            $login_user['nickname'] = $login_user['username'];
        }
    } else {
        $login_user = get_login_user();
        if (empty($login_user)) {
            $login_user = [];
            $login_user['id'] = 0;
            $login_user['nickname'] = '游客';
            if(isRobot()){
                $login_user['nickname'] = '蜘蛛';
                $type = 'spider';
                $title = '爬行';
            }
        } else {
            if ($login_user['nickname'] == '') {
                $login_user['nickname'] = $login_user['username'];
            }
        }
    }
    $content = $login_user['nickname'] . '在' . date('Y-m-d H:i:s') . '执行了' . $title . '操作';
    if ($param_str != '') {
        $content = $login_user['nickname'] . '在' . date('Y-m-d H:i:s') . $title . '了' . $param_str;
    }
    $data = [];
    $data['uid'] = $login_user['id'];
    $data['nickname'] = $login_user['nickname'];
    $data['type'] = $type;
    $data['title'] = $title;
    $data['content'] = $content;
    $data['param_id'] = $param_id;
    $data['param'] = json_encode($param);
	$data['module'] = strtolower(app('http')->getName());
    $data['controller'] = strtolower(app('request')->controller());
    $data['function'] = strtolower(app('request')->action());
    $data['ip'] = app('request')->ip();
    $data['create_time'] = time();
    \think\facade\Db::name('UserLog')->strict(false)->field(true)->insert($data);
}

/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject 邮件标题
 * @param string $content 邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to, $subject = '', $content = '')
{
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $email_config = \think\facade\Db::name('config')
        ->where('name', 'email')
        ->find();
    $config = unserialize($email_config['content']);

    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->isSMTP();
    $mail->SMTPDebug = 0;

    //调试输出格式
    //$mail->Debugoutput = 'html';
    //smtp服务器
    $mail->Host = $config['smtp'];
    //端口 - likely to be 25, 465 or 587
    $mail->Port = $config['smtp_port'];
    if($mail->Port == '465'){
        $mail->SMTPSecure = 'ssl';// 使用安全协议
    }    
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //发送邮箱
    $mail->Username = $config['smtp_user'];
    //密码
    $mail->Password = $config['smtp_pwd'];
    //Set who the message is to be sent from
    $mail->setFrom($config['email'], $config['from']);
    //回复地址
    //$mail->addReplyTo('replyto@example.com', 'First Last');
    //接收邮件方
    if (is_array($to)) {
        foreach ($to as $v) {
            $mail->addAddress($v);
        }
    } else {
        $mail->addAddress($to);
    }

    $mail->isHTML(true);// send as HTML
    //标题
    $mail->Subject = $subject;
    //HTML内容转换
    $mail->msgHTML($content);
    $status = $mail->send();
    if ($status) {
        return true;
    } else {
      //  echo "Mailer Error: ".$mail->ErrorInfo;// 输出错误信息
      //  die;
        return false;
    }
}

/*
* 下划线转驼峰
* 思路:
* step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
* step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
*/
function camelize($uncamelized_words,$separator='_')
{
	$uncamelized_words = $separator. str_replace($separator, " ", strtolower($uncamelized_words));
	return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator );
}

/**
* 驼峰命名转下划线命名
* 思路:
* 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
*/
function uncamelize($camelCaps,$separator='_')
{
	return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}