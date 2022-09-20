<?php
/**
 * @copyright Copyright (c) 2021  
 * @license https://opensource.org/licenses/Apache-2.0
 *  
 */
//读取文章分类列表
function get_article_cate()
{
    $cate = \think\facade\Db::name('ArticleCate')->order('create_time asc')->select()->toArray();
    return $cate;
}