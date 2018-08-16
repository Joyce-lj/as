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

	<link href="/aishe/public/simpleboot/themes/<?php echo C('SP_ADMIN_STYLE');?>/theme.min.css" rel="stylesheet">
    <link href="/aishe/public/simpleboot/css/simplebootadmin.css" rel="stylesheet">
    <link href="/aishe/public/js/artDialog/skins/default.css" rel="stylesheet" />
    <link href="/aishe/public/simpleboot/font-awesome/4.4.0/css/font-awesome.min.css"  rel="stylesheet" type="text/css">
    <style>
		form .input-order{margin-bottom: 0px;padding:3px;width:40px;}
		.table-actions{margin-top: 5px; margin-bottom: 5px;padding:0px;}
		.table-list{margin-bottom: 0px;}
	</style>
	<!--[if IE 7]>
	<link rel="stylesheet" href="/aishe/public/simpleboot/font-awesome/4.4.0/css/font-awesome-ie7.min.css">
	<![endif]-->
	<script type="text/javascript">
	//全局变量
	var GV = {
	    ROOT: "/aishe/",
	    WEB_ROOT: "/aishe/",
	    JS_ROOT: "public/js/",
	    APP:'<?php echo (MODULE_NAME); ?>'/*当前应用名*/
	};
	</script>
    <script src="/aishe/public/js/jquery.js"></script>
    <script src="/aishe/public/js/wind.js"></script>
    <script src="/aishe/public/simpleboot/bootstrap/js/bootstrap.min.js"></script>
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
	<div class="wrap">
		<ul class="nav nav-tabs">
			<!--<li><a href="<?php echo U('menu/index');?>"><?php echo L('ADMIN_MENU_INDEX');?></a></li>-->
			<!--<li><a href="<?php echo U('menu/add');?>"><?php echo L('ADMIN_MENU_ADD');?></a></li>-->
			<li class="active"><a href="#">修改房源标签</a></li>
		</ul>
		<form method="post" class="form-horizontal js-ajax-form" action="<?php echo U('housesource/edit_post');?>">
			<!--<h3 style="" class="controls">修改房源标签</h3>-->
			<fieldset>
				<div class="control-group">
					<label class="control-label">房源类型名称:</label>
					<div class="controls">
						<input type="text" name="typename" id='typename' value="<?php echo ($data["typename"]); ?>">
						<!--<span class="form-required">*</span>-->
					</div>
				</div>

			</fieldset>
			<div class="form-actions">
				<input type="hidden" name="id" value="<?php echo ($data["id"]); ?>" />
				<button type="submit" class="btn btn-primary js-ajax-submit" onclick="return checkPost()"><?php echo L('SAVE');?></button>
				<a class="btn" href="javascript:history.back(-1);"><?php echo L('BACK');?></a>
				<!-- <button class="btn js-ajax-close-btn" type="submit"><?php echo L('CLOSE');?></button> -->
			</div>
		</form>
	</div>
	<script src="/aishe/public/js/common.js"></script>
	<script>
		$(function() {
			$(".js-ajax-close-btn").on('click', function(e) {
				e.preventDefault();
				Wind.use("artDialog", function() {
					art.dialog({
						id : "question",
						icon : "question",
						fixed : true,
						lock : true,
						background : "#CCCCCC",
						opacity : 0,
						content : "您确定需要关闭当前页面嘛？",
						ok : function() {
							setCookie('refersh_time_admin_menu_index', 1);
							window.close();
							return true;
						}
					});
				});
			});
		});
	</script>
	<script>
        function checkPost(){
            var num = false;
            var name = $("#typename").val();
            if(name == '' || name == null){
                alert('标签名称必须填写!');
                return false;
            }else{
                if(name.length > 6){
                    alert('标签名称最多6个字符!');
                    return false;
                }
                $.ajax({
                    type: 'GET',
                    async:false,
                    url: '<?php echo U("housesource/check_name")?>',
                    data: 'typename='+name,
                    success: function (data) {
                        if(data > 0){
                            num = true;
                            alert('标签名称不能重复!');
                            return false;
                        }
                    },
                    dataType: 'json',
                });
            }
            if(num){
                return false;
            }
        }
	</script>
</body>
</html>