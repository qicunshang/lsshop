$(document).ready(function(){
	//列表下拉
	$('img[nc_type="flex"]').click(function(){
		var status = $(this).attr('status');
		if(status == 'open'){
			var pr = $(this).parent('td').parent('tr');
			var id = $(this).attr('fieldid');
			var obj = $(this);
			$(this).attr('status','none');
			//ajax
			$.ajax({
				url: 'index.php?act=ybk_class&op=ybk_class&ajax=1&ybk_parent_id='+id,
				dataType: 'json',
				success: function(data){
					var src='';
					for(var i = 0; i < data.length; i++){
						var tmp_vertline = "<img class='preimg' src='templates/images/vertline.gif'/>";
						src += "<tr class='"+pr.attr('class')+" row"+id+"'>";
						src += "<td class='w36'><input type='checkbox' name='check_ac_id[]' value='"+data[i].ac_id+"' class='checkitem' />";
						if(data[i].have_child == 1){
							src += "<img fieldid='"+data[i].ybk_id+"' status='open' nc_type='flex' src='"+ADMIN_TEMPLATES_URL+"/images/tv-expandable.gif' />";
						}else{
							src += "<img fieldid='"+data[i].ybk_id+"' status='none' nc_type='flex' src='"+ADMIN_TEMPLATES_URL+"/images/tv-item.gif' />";
						}
						//图片
						src += "</td><td class='w48 sort'>";
						//排序
						src += "<span title='可编辑' ajax_branch='ybk_class_sort' datatype='number' fieldid='"+data[i].ybk_id+"' fieldname='ybk_sort' nc_type='inline_edit' class='editable'>"+data[i].ybk_sort+"</span>";
						//名称
						src += "<td class='name'>";
						for(var tmp_i=1; tmp_i < (data[i].deep-1); tmp_i++){
							src += tmp_vertline;
						}
						if(data[i].have_child == 1){
							src += " <img fieldid='"+data[i].ybk_id+"' status='open' nc_type='flex' src='"+ADMIN_TEMPLATES_URL+"/images/tv-item1.gif' />";
						}else{
							src += " <img fieldid='"+data[i].ybk_id+"' status='none' nc_type='flex' src='"+ADMIN_TEMPLATES_URL+"/images/tv-expandable1.gif' />";
						}
						src += "<span title='可编辑' required='1' fieldid='"+data[i].ybk_id+"' ajax_branch='ybk_class_name' fieldname='sc_name' nc_type='inline_edit' class='node_name editable'>"+data[i].ybk_name+"</span>";
						//新增下级
						if(data[i].deep < 2){
							src += "<a  class='btn-add-nofloat marginleft' href='index.php?act=ybk_class&op=ybk_class_add&ybk_parent_id="+data[i].ybk_id+"'><span>新增下级<span></a></span>";
						}
						src += "</td>";
						
						//操作
						src += "<td class='w84'>";
						src += "<span><a href='index.php?act=ybk_class&op=ybk_class_edit&ybk_id="+data[i].ybk_id+"'>编辑</a>";
						src += " | <a href=\"javascript:if(confirm('删除该分类将会同时删除该分类的所有下级分类，您确定要删除吗'))window.location = 'index.php?act=ybk_class&op=ybk_class_del&ybk_id="+data[i].ybk_id+"';\">删除</a>";
						src += "</td>";
						src += "</tr>";
					}
					//插入
					pr.after(src);
					obj.attr('status','close');
					obj.attr('src',obj.attr('src').replace("tv-expandable","tv-collapsable"));
					$('img[nc_type="flex"]').unbind('click');
					$('span[nc_type="inline_edit"]').unbind('click');
					//重现初始化页面
                    $.getScript(RESOURCE_SITE_URL+"/js/jquery.edit.js");
					$.getScript(RESOURCE_SITE_URL+"/js/jquery.ybk_class.js");
					$.getScript(RESOURCE_SITE_URL+"/js/admincp.js");
				},
				error: function(){
					alert('获取信息失败');
				}
			});
		}
		if(status == 'close'){
			$(".row"+$(this).attr('fieldid')).remove();
			$(this).attr('src',$(this).attr('src').replace("tv-collapsable","tv-expandable"));
			$(this).attr('status','open');
		}
	})
});