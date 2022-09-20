<?php
/**
 * @copyright Copyright (c) 2021  
 * @license https://opensource.org/licenses/Apache-2.0
 *  
 */

namespace app\admin\validate;
use think\Validate;

class GalleryValidate extends Validate
{
    protected $rule = [
    'cate_id' => 'require',
    'title' => 'require',
    'thumb' => 'require',
];

    protected $message = [
    'cate_id.require' => '所属分类不能为空',
    'title.require' => '图集名称不能为空',
    'thumb.require' => '缩略图不能为空',
];
}