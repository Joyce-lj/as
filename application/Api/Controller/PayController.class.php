<?php
namespace Api\Controller;

use Common\Controller\AppframeController;

class PayController extends AppframeController{

	public function _initialize() {
		parent::_initialize();

		require dirname(dirname(dirname(dirname(__FILE__)))).'/wechatPay/Weixin.class.php';
		require dirname(dirname(dirname(dirname(__FILE__)))).'/wechatPay/WxPayConfig.php';
		require dirname(dirname(dirname(dirname(__FILE__)))).'/wechatPay/lib/log.php';
        //初始化日志
//        $logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
//        $log = Log::Init($logHandler, 15);
	}

    public function test(){
	    $wxpc = new \WxPayConfig();
	    echo $wxpc::MCHID;

    }

    public function payOrder(){
        $wxConfig = new \WxPayConfig();
        $appid = $wxConfig::APPID;
        $mch_id = $wxConfig::MCHID;
        $key = $wxConfig::KEY;
        $openid = I('post.openid');
        $openid = 'oVDb15WC6z5Y1i34w3u2JjDp-FxY';
        $ordernum = rand(10000000,99999999);
//        echo json_encode($ordernum);die;
        $data = array(
//            'ordernum'=> I('post.ordernum'),
            'totalcost'=> I('post.totalcost',0.01),
            'ordernum'=> $ordernum,
            'itemdesc'=> 'test',
        );
//        print_r($data);die;
        $wx = new \WeixinPay($appid,$openid,$mch_id,$key,$data);
        $a = $wx->pay();
        echo json_encode($a);die;
    }

    public function getOpenid(){
        $wxConfig = new \WxPayConfig();
        $appid = $wxConfig::APPID;
        $secret = $wxConfig::APPSECRET;
        $code = I('post.code');
	    $code = '071MApVZ1LTuGZ0ocvXZ15lsVZ1MApVt';
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$code."&grant_type=authorization_code";
        echo $url;die;
    }









}