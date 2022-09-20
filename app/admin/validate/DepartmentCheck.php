<?php
/**
 * @copyright Copyright (c) 2021  
 * @license https://opensource.org/licenses/GPL-2.0
 *  
 */

namespace app\admin\validate;

use think\Validate;

class DepartmentCheck extends Validate
{
    protected $rule = [
        'title' => 'require|unique:department',
        'id' => 'require',
    ];

    protected $message = [
        'title.require' => '部门名称不能为空',
        'title.unique' => '同样的部门名称已经存在',
        'id.require' => '缺少更新条件',
    ];

    protected $scene = [
        'add' => ['title'],
        'edit' => ['id', 'title'],
    ];
}