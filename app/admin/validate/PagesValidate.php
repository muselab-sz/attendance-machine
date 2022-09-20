<?php
/**
 * @copyright Copyright (c) 2021  
 * @license https://opensource.org/licenses/Apache-2.0
 *  
 */

namespace app\admin\validate;
use think\Validate;

class PagesValidate extends Validate
{
    protected $rule = [
		'title' => 'require',
		'content' => 'require',
		'name' => 'lower|min:3|unique:pages',
	];

    protected $message = [
		'title.require' => '页面名称不能为空',
		'content.require' => '页面内容不能为空',
		'name.lower' => 'URL文件名称只能是小写字符',
        'name.min' => 'URL文件名称至少需要3个小写字符',
        'name.unique' => '同样的URL文件名称已经存在',
	];
}