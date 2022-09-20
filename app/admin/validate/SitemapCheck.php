<?php
/**
 * @copyright Copyright (c) 2021  
 * @license https://opensource.org/licenses/Apache-2.0
 *  
 */

namespace app\admin\validate;

use think\Validate;

class SitemapCheck extends Validate
{
    protected $rule = [
        'name' => 'require',
        'id' => 'require',
        'sitemap_cate_id' => 'require',
        'pc_img' => 'require',
        'pc_src' => 'require|url',
        'mobile_img' => 'require',
        'mobile_src' => 'require|url',
    ];

    protected $message = [
        'name.require' => '名称不能为空',
        'pc_img.require' => 'PC端图片不能为空',
        'pc_src.require' => 'PC端链接不能为空',
        'pc_src.url' => 'PC端链接必须以http开头',
        'mobile_img.require' => '移动端图片不能为空',
        'mobile_src.require' => '移动端链接不能为空',
        'mobile_src.url' => '移动端链接必须以http开头',
        'id.require' => '缺少更新条件',
        'sitemap_cate_id.require' => '缺少更新条件',
    ];

    protected $scene = [
        'add' => ['sitemap_cate_id', 'name', 'pc_src', 'mobile_src'],
        'edit' => ['id', 'name', 'pc_src', 'mobile_src'],
    ];
}
