<?php
/**
 * 
 * @authors 刘帅
 * @date    2016-09-07 09:16:30
 */
defined('InShopNC') or exit('Access Invalid!');
class ybk_classModel extends Model {
    
    /**
     * 取分类列表，最多为三级
     *
     * @param int $show_deep 显示深度
     * @param array $condition 检索条件
     * @return array 数组类型的返回结果
     */
    public function getTreeClassList($show_deep='3',$condition=array()){
        $class_list = $this->getGoodsClassList($condition);
        $goods_class = array();//分类数组
        if(is_array($class_list) && !empty($class_list)) {
            $show_deep = intval($show_deep);
            if ($show_deep == 1){//只显示第一级时用循环给分类加上深度deep号码
                foreach ($class_list as $val) {
                    if($val['gc_parent_id'] == 0) {
                        $val['deep'] = 1;
                        $goods_class[] = $val;
                    } else {
                        break;//父类编号不为0时退出循环
                    }
                }
            } else {//显示第二和三级时用递归
                $goods_class = $this->_getTreeClassList($show_deep,$class_list);
            }
        }
        return $goods_class;
    }

    /**
     * 递归 整理分类
     *
     * @param int $show_deep 显示深度
     * @param array $class_list 类别内容集合
     * @param int $deep 深度
     * @param int $parent_id 父类编号
     * @param int $i 上次循环编号
     * @return array $show_class 返回数组形式的查询结果
     */
    private function _getTreeClassList($show_deep,$class_list,$deep=1,$parent_id=0,$i=0){
        static $show_class = array();//树状的平行数组
        if(is_array($class_list) && !empty($class_list)) {
            $size = count($class_list);
            if($i == 0) $show_class = array();//从0开始时清空数组，防止多次调用后出现重复
            for ($i;$i < $size;$i++) {//$i为上次循环到的分类编号，避免重新从第一条开始
                $val = $class_list[$i];
                $gc_id = $val['gc_id'];
                $gc_parent_id	= $val['gc_parent_id'];
                if($gc_parent_id == $parent_id) {
                    $val['deep'] = $deep;
                    $show_class[] = $val;
                    if($deep < $show_deep && $deep < 3) {//本次深度小于显示深度时执行，避免取出的数据无用
		    	//v3-b12
                        $this->_getTreeClassList($show_deep,$class_list,$deep+1,$gc_id,1);
                    }
                }
                if($gc_parent_id > $parent_id) break;//当前分类的父编号大于本次递归的时退出循环
            }
        }
        return $show_class;
    }
}