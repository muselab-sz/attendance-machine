<?php
/**
 * @copyright Copyright (c) 2021
 * @license https://opensource.org/licenses/Apache-2.0
 *
 */

declare (strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\validate\PagesValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Pages extends BaseController

{
    /**
     * 构造函数
     */
    public function __construct()
    {

    }

    /**
     * 数据列表
     */
    public function datalist()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            if (!empty($param['keywords'])) {
                $where[] = ['dev_id|dev_model', 'like', '%' . $param['keywords'] . '%'];
            }

            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $content = Db::name('DeviceList')->where($where)
                ->order('id desc')
                ->paginate($rows, false, ['query' => $param]);
            return table_assign(0, '', $content);
        } else {
            return view();
        }
    }

    /**
     * 添加
     */
    public function add()
    {
        /*if (request()->isAjax()) {
			$param = get_params();	
			
            // 检验完整性
            try {
                validate(PagesValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }

            $this->model->addPages($param);
        }else{
			$templates = get_file_list(CMS_ROOT . '/app/home/view/pages/');
			View::assign('templates', $templates);
			return view();
		}*/
    }


    /**
     * 编辑
     */
    public function edit()
    {
        /*$param = get_params();

        if (request()->isAjax()) {
            // 检验完整性
            try {
                validate(PagesValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $this->model->editPages($param);
        }else{
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getPagesById($id);
            if (!empty($detail)) {
                //轮播图
                if(!empty($detail['banner'])) {
                    $detail['banner_array'] = explode(',',$detail['banner']);
                }
                //关键字
                $keyword_array = Db::name('PagesKeywords')
                    ->field('i.aid,i.keywords_id,k.title')
                    ->alias('i')
                    ->join('keywords k', 'k.id = i.keywords_id', 'LEFT')
                    ->order('i.create_time asc')
                    ->where(array('i.aid' => $id, 'k.status' => 1))
                    ->select()->toArray();

                $detail['keyword_ids'] = implode(",", array_column($keyword_array, 'keywords_id'));
                $detail['keyword_names'] = implode(',', array_column($keyword_array, 'title'));
                $detail['keyword_array'] = $keyword_array;

                $templates = get_file_list(CMS_ROOT . '/app/home/view/pages/');
                View::assign('templates', $templates);
                View::assign('detail', $detail);
                return view();
            }
            else{
                throw new \think\exception\HttpException(404, '找不到页面');
            }
        }*/
    }


    /**
     * 查看信息
     */
    public function read()
    {
        /*$param = get_params();
		$id = isset($param['id']) ? $param['id'] : 0;
		$detail = $this->model->getPagesById($id);
		if (!empty($detail)) {
			//轮播图
			if(!empty($detail['banner'])) {
				$detail['banner_array'] = explode(',',$detail['banner']);
			}
			//关键字
			$keyword_array = Db::name('PagesKeywords')
				->field('i.aid,i.keywords_id,k.title')
				->alias('i')
				->join('keywords k', 'k.id = i.keywords_id', 'LEFT')
				->order('i.create_time asc')
				->where(array('i.aid' => $id, 'k.status' => 1))
				->select()->toArray();

			$detail['keyword_ids'] = implode(",", array_column($keyword_array, 'keywords_id'));
			$detail['keyword_names'] = implode(',', array_column($keyword_array, 'title'));		
			$detail['keyword_array'] = $keyword_array;
			View::assign('detail', $detail);
			return view();
		}
		else{
			throw new \think\exception\HttpException(404, '找不到页面');
		}*/
    }

    /**
     * 删除
     */
    public function delete()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $info = Db::name('DeviceList')->where('id', $id)
            ->find();
        if ($info) {
            Db::name('DeviceList')->where('id', $id)
                ->update([
                    'status' => $info['status'] == '0' ? '1' : '0'
                ]);
        }
        return to_assign(0, '操作成功', $info);
    }
}
