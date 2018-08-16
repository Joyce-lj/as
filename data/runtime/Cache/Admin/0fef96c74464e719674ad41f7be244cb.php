<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<!-- Set render engine for 360 browser -->
	<meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- HTML5 shim for IE8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <![endif]-->

	<link href="/aishe0816-2/public/simpleboot/themes/<?php echo C('SP_ADMIN_STYLE');?>/theme.min.css" rel="stylesheet">
    <link href="/aishe0816-2/public/simpleboot/css/simplebootadmin.css" rel="stylesheet">
    <link href="/aishe0816-2/public/js/artDialog/skins/default.css" rel="stylesheet" />
    <link href="/aishe0816-2/public/simpleboot/font-awesome/4.4.0/css/font-awesome.min.css"  rel="stylesheet" type="text/css">
    <style>
		form .input-order{margin-bottom: 0px;padding:3px;width:40px;}
		.table-actions{margin-top: 5px; margin-bottom: 5px;padding:0px;}
		.table-list{margin-bottom: 0px;}
	</style>
	<!--[if IE 7]>
	<link rel="stylesheet" href="/aishe0816-2/public/simpleboot/font-awesome/4.4.0/css/font-awesome-ie7.min.css">
	<![endif]-->
	<script type="text/javascript">
	//全局变量
	var GV = {
	    ROOT: "/aishe0816-2/",
	    WEB_ROOT: "/aishe0816-2/",
	    JS_ROOT: "public/js/",
	    APP:'<?php echo (MODULE_NAME); ?>'/*当前应用名*/
	};
	</script>
    <script src="/aishe0816-2/public/js/jquery.js"></script>
    <script src="/aishe0816-2/public/js/wind.js"></script>
    <script src="/aishe0816-2/public/simpleboot/bootstrap/js/bootstrap.min.js"></script>
    <script>
    	$(function(){
    		$("[data-toggle='tooltip']").tooltip();
    	});
    </script>
<?php if(APP_DEBUG): ?><style>
		#think_page_trace_open{
			z-index:9999;
		}
	</style><?php endif; ?>
</head>
<body>
	<div class="wrap js-check-wrap">
		<ul class="nav nav-tabs">
			<li class="active"><a href="<?php echo U('housesource/index');?>">房源列表</a></li>
			<!--<li><a href="<?php echo U('housesource/add');?>">新增标签</a></li>-->
			<!--<li><a href="<?php echo U('slideshow/lists');?>"><?php echo L('ADMIN_MENU_LISTS');?></a></li>-->
		</ul>

		<form class="well form-search" method="post" action="<?php echo U('housesource/index');?>">&nbsp;&nbsp;
			<!--时间：-->
			<!--<input type="text" name="start_time" class="js-datetime" value="<?php echo ((isset($formget["start_time"]) && ($formget["start_time"] !== ""))?($formget["start_time"]):''); ?>" style="width: 120px;" autocomplete="off">- -->
			<!--<input type="text" class="js-datetime" name="end_time" value="<?php echo ((isset($formget["end_time"]) && ($formget["end_time"] !== ""))?($formget["end_time"]):''); ?>" style="width: 120px;" autocomplete="off"> &nbsp; &nbsp;-->
			房源类型关键字：
			<input type="text" name="keyword" id="keyword" style="width: 200px;" value="<?php echo ((isset($keyword) && ($keyword !== ""))?($keyword):''); ?>" placeholder="请输入房源类型关键字...">

			<input type="submit" class="btn btn-primary" value="搜索" />
			<!--<a class="btn btn-danger" href="<?php echo U('housesource/index');?>">清空</a>-->
		</form>

		<form class="js-ajax-form" action="<?php echo U('housesource/listorders');?>" method="post">
			<!--<div class="table-actions">-->
				<!--<button class="btn btn-primary btn-small js-ajax-submit" type="submit"><?php echo L('SORT');?></button>-->
			<!--</div>-->
			<table class="table table-hover table-bordered table-list" id="menus-table">
				<thead>
					<tr>
						<th width="80">序号</th>
						<th width="200">类型</th>
						<th width="80">分配数量</th>
						<th width="180"><?php echo L('ACTIONS');?></th>
					</tr>
				</thead>
				<tbody>
				<?php if(is_array($housesource)): foreach($housesource as $key=>$vo): ?><tr>
						<td><?php echo ($vo["id"]); ?></td>
						<td><?php echo ($vo["typename"]); ?></td>
						<td><?php echo ($vo["num"]); ?></td>
						<td>
							<a href="<?php echo U('housesource/edit',array('id'=>$vo['id']));?>" >修改</a>
						</td>
					</tr><?php endforeach; endif; ?>
				</tbody>
			</table>
			<div class="pagination"><?php echo ($page); ?></div>
		</form>
	</div>
	<script src="/aishe0816-2/public/js/common.js"></script>
	<script>
		$(document).ready(function() {
			Wind.css('treeTable');
			Wind.use('treeTable', function() {
				$("#menus-table").treeTable({
					indent : 20
				});
			});
		});

		setInterval(function() {
			var refersh_time = getCookie('refersh_time_admin_menu_index');
			if (refersh_time == 1) {
				reloadPage(window);
			}
		}, 1000);
		setCookie('refersh_time_admin_menu_index', 0);
	</script>

</body>
</html>