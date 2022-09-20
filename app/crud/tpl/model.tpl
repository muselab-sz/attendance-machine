<?php
/**
 * @copyright Copyright (c) 2021
 * @license https://opensource.org/licenses/Apache-2.0
 *
 */
namespace app\admin\model;
use think\model;
class <model> extends Model
{
    /**
    * 获取分页列表
    * @param $where
    * @param $param
    */
    public function get<model>List($where, $param)
    {
		$rows = empty($param['limit']) ? get_config('app . page_size') : $param['limit'];
		$order = empty($param['order']) ? '<pk> desc' : $param['order'];
        $list = $this->where($where)->field('<fieldlist>')->order($order)->paginate($rows, false, ['query' => $param]);
		return $list;
    }

    /**
    * 添加数据
    * @param $param
    */
    public function add<model>($param)
    {
		$insertId = 0;
        try {
			$param['create_time'] = time();
			$insertId = $this->strict(false)->field(true)->insertGetId($param);
			add_log('add', $insertId, $param);
        } catch(\Exception $e) {
			return to_assign(1, '操作失败，原因：'.$e->getMessage());
        }
		return to_assign(0,'操作成功',['aid'=>$insertId]);
    }

    /**
    * 编辑信息
    * @param $param
    */
    public function edit<model>($param)
    {
        try {
            $param['update_time'] = time();
            $this->where('<pk>', $param['<pk>'])->strict(false)->field(true)->update($param);
			add_log('edit', $param['id'], $param);
        } catch(\Exception $e) {
			return to_assign(1, '操作失败，原因：'.$e->getMessage());
        }
		return to_assign();
    }
	

    /**
    * 根据id获取信息
    * @param $id
    */
    public function get<model>ById($id)
    {
        $info = $this->where('<pk>', $id)->find();
		return $info;
    }

    /**
    * 删除信息
    * @param $id
    * @return array
    */
    public function del<model>ById($id,$type=0)
    {
		if($type==0){
			//逻辑删除
			try {
				$param['delete_time'] = time();
				$this->where('<pk>', $id)->update(['delete_time'=>time()]);
				add_log('delete', $id);
			} catch(\Exception $e) {
				return to_assign(1, '操作失败，原因：'.$e->getMessage());
			}
		}
		else{
			//物理删除
			try {
				$this->where('<pk>', $id)->delete();
				add_log('delete', $id);
			} catch(\Exception $e) {
				return to_assign(1, '操作失败，原因：'.$e->getMessage());
			}
		}
		return to_assign();
    }
}

