<?php

namespace app\admin\validate;

use think\Validate;

class UserCheck extends Validate
{
    protected $rule = [
        'job_no' => 'require',
        'privilege' => 'require',
        'name' => 'require',
        'pwd' => 'require',
        'username' => 'require',
        'password' => 'require',
        'captcha' => 'require',
    ];

    protected $message = [
        'userId.require' => '用户id不能为空',
        'privilege.require' => '账号类型不能为空',
        'name.require' => '用户名不能为空',
        'pwd.require' => '密码不能为空',
        'username.require' => '用户名不能为空',
        'password.require' => '密码不能为空',
        'captcha.require' => '验证码不能为空',
    ];

    protected $scene = [
        'add' => ['job_no', 'privilege', 'name', 'password'],
        'edit' => ['id', 'userId', 'privilege', 'name', 'pwd'],
        'login' => ['username', 'password', 'captcha']
    ];
}
