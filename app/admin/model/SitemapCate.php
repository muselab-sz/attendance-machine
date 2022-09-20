<?php
/**
 * @copyright Copyright (c) 2021  
 * @license https://opensource.org/licenses/Apache-2.0
 *  
 */

namespace app\admin\model;

use think\Model;

class SitemapCate extends Model
{
    /**
     * 关联网站地图
     * @param [type] $value [description]
     */
    public function sitemap()
    {
        return $this->hasMany('sitemap', 'sitemap_cate_id', 'id')->order('sort desc, id desc');
    }

}
