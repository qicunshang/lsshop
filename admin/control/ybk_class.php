<?php
/**
 * 邮币卡分类
 * @authors 刘帅
 * @date    2016-09-01 13:48:16
 */

class ybk_classControl extends SystemControl {
    
    public function __construct(){
		parent::__construct();
		Language::read('ybk_class');
	}

	//显示邮币分类
    public function ybk_classOp(){
    	$lang	= Language::getLangContent();
		$model_class = Model('ybk_class');

		//删除
		if (chksubmit()){
			if (!empty($_POST['check_ybk_id']) && is_array($_POST['check_ybk_id']) ){
			    $result = $model_class -> where(array('ybk_id'=>array('in',$_POST['check_ybk_id']))) -> delete();
				if ($result) {
			        $this->log(L('nc_del,ybk_class').'[ID:'.implode(',',$_POST['check_ybk_id']).']',1);
				    showMessage($lang['nc_common_del_succ']);
				}
			}
		    showMessage($lang['nc_common_del_fail']);
		}

		$ybk_class = $model_class -> order('ybk_sort asc,ybk_id asc') ->page(20) -> select();
		Tpl::output('class_list',$ybk_class);
		Tpl::output('page',$model_class->showpage());
		Tpl::showpage('ybk_class.index');
    }

    //邮币添加
    public function ybk_class_addOp(){
		$lang	= Language::getLangContent();
		$model_class = Model('ybk_class');
		if (chksubmit()){
			//验证
			$obj_validate = new Validate();
			$obj_validate->validateparam = array(
			array("input"=>$_POST["ybk_name"], "require"=>"true", "message"=>$lang['store_class_name_no_null']),
			);
			$error = $obj_validate->validate();
			if ($error != ''){
				showMessage($error);
			}else {
				$insert_array = array();
				$insert_array['ybk_name'] = $_POST['ybk_name'];
				$insert_array['ybk_bail'] = intval($_POST['ybk_bail']);
				$insert_array['ybk_sort'] = intval($_POST['ybk_sort']);
				$result = $model_class -> insert($insert_array);
				if ($result){
					$url = array(
					array(
					'url'=>'index.php?act=ybk_class&op=ybk_class_add',
					'msg'=>$lang['continue_add_store_class'],
					),
					array(
					'url'=>'index.php?act=ybk_class&op=ybk_class',
					'msg'=>$lang['back_store_class_list'],
					)
					);
					$this->log(L('nc_add,ybk_class').'['.$_POST['ybk_name'].']',1);
					showMessage($lang['nc_common_save_succ'],$url,'html','succ',1,5000);
				}else {
					showMessage($lang['nc_common_save_fail']);
				}
			}
		}
		Tpl::showpage('ybk_class.add');
	}

	//编辑
	public function ybk_class_editOp(){
		$lang	= Language::getLangContent();

		$model_class = Model('ybk_class');

		if (chksubmit()){
			//验证
			$obj_validate = new Validate();
			$obj_validate->validateparam = array(
			array("input"=>$_POST["ybk_name"], "require"=>"true", "message"=>$lang['store_class_name_no_null']),
			);
			$error = $obj_validate->validate();
			if ($error != ''){
				showMessage($error);
			}else {
				$update_array = array();
				$update_array['ybk_name'] = $_POST['ybk_name'];
				$update_array['ybk_bail'] = intval($_POST['ybk_bail']);
				$update_array['ybk_sort'] = intval($_POST['ybk_sort']);
				$result = $model_class-> where (array('ybk_id'=>intval($_POST['ybk_id']))) -> update($update_array);
				if ($result){
					$this->log(L('nc_edit,ybk_class').'['.$_POST['ybk_name'].']',1);
					showMessage($lang['nc_common_save_succ'],'index.php?act=ybk_class&op=ybk_class');
				}else {
					showMessage($lang['nc_common_save_fail']);
				}
			}
		}

		$class_array = $model_class->where(array('ybk_id'=>intval($_GET['ybk_id'])))->find();
		if (empty($class_array)){
			showMessage($lang['illegal_parameter']);
		}

		Tpl::output('class_array',$class_array);
		Tpl::showpage('ybk_class.edit');
	}

	//删除
	public function ybk_class_delOp(){
		$lang	= Language::getLangContent();
		$model_class = Model('ybk_class');
		if (intval($_GET['ybk_id']) > 0){
			$array = array(intval($_GET['ybk_id']));
			$result = $model_class->where(array('ybk_id'=>intval($_GET['ybk_id'])))->delete();
			if ($result) {
			     $this->log(L('nc_del,ybk_class').'[ID:'.$_GET['ybk_id'].']',1);
			     showMessage($lang['nc_common_del_succ'],getReferer());
			}
		}
		showMessage($lang['nc_common_del_fail'],'index.php?act=ybk_class&op=ybk_class');
	}

	//ajax操作
	public function ajaxOp(){
	    $model_class = Model('ybk_class');
	    $update_array = array();
		switch ($_GET['branch']){
			//分类：验证是否有重复的名称
			case 'ybk_class_name':
			    $condition = array();
				$condition['ybk_name'] = $_GET['value'];
				$condition['ybk_id'] = array('ybk_id'=>array('neq',intval($_GET['ybk_id'])));
				$class_list = $model_class->where($condition)->select();
				if (empty($class_list)){
					$update_array['ybk_name'] = $_GET['value'];
					$update = $model_class->where(array('ybk_id'=>intval($_GET['id'])))->update($update_array);
					$return = $update ? 'true' : 'false';
				} else {
					$return = 'false';
				}
				break;
			//分类： 排序 显示 设置
			case 'ybk_class_sort':
				$model_class = Model('ybk_class');
				$update_array['ybk_sort'] = intval($_GET['value']);
				$result = $model_class->where(array('ybk_id'=>intval($_GET['id'])))->update($update_array);
				$return = $result ? 'true' : 'false';
				break;
			//分类：添加、修改操作中 检测类别名称是否有重复
			case 'check_class_name':
				$condition['ybk_name'] = $_GET['ybk_name'];
				$condition['ybk_id'] = array('ybk_id'=>array('neq',intval($_GET['ybk_id'])));
				$class_list = $model_class->where($condition)->select();
				$return = empty($class_list) ? 'true' : 'false';
				break;
		}
		exit($return);
	}
}