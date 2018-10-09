<?php
namespace Api\Controller;

use Common\Controller\AppframeController;
use Couchbase\Document;

class HouseorderController extends AppframeController{
	
	protected $house_model;
	protected $housetype_model;
	protected $housephoto_model;
	protected $housedetail_model;
	protected $houseorder_model;
    protected $city_model;
    protected $member_model;
    protected $memberCoupon_model;
    protected $coupon_model;
	protected $perpage = 20;
	
	public function _initialize() {
		parent::_initialize();
        require dirname(dirname(dirname(dirname(__FILE__)))).'/wechatPay/Weixin.class.php';
        require dirname(dirname(dirname(dirname(__FILE__)))).'/wechatPay/WxPayConfig.php';
		$this->house_model=D("Common/House");
		$this->housetype_model=D("Common/Housetype");
		$this->housephoto_model=D("Common/Housephoto");
		$this->houseorder_model=D("Common/Houseorder");
		$this->housedetail_model=D("Common/Housedetail");
		$this->member_model=D("Common/Member");
		$this->city_model=D("Common/City");
		$this->memberCoupon_model=D("Common/MemberCoupon");
		$this->coupon_model=D("Common/Coupon");
	}

    public function createOrder(){
	    //1未支付,2已支付未确认,3已支付已确认,4已入住,5已退房,6已失效,7已退款
	    //考虑并发,两人同时下单
	    //isorder=1预定 0未预定
        $ordertime = time();
        $houseid = I('request.houseid');
        $uid = I('request.uid');
        $cmid = I('request.cmid');
        $checkin_time = strtotime(I('request.checkin_time'));
        $checkout_time = strtotime(I('request.checkout_time'));
        $checkin_members = I('request.checkin_members');
        $staydays = I('request.staydays');
        $discount_cost = I('request.cost');
        //存储于stayInfos
        $startweek = I('request.startweek');
        $endweek = I('request.endweek');
        $discount = I('request.discount');

        if(empty($uid)){
            $data['code'] = -2;
            $data['msg'] = 'uid参数有误!';
            $this->ajaxData($data);
        }
        if(empty($checkin_time)){
            $data['code'] = -2;
            $data['msg'] = 'checkin_time参数有误!';
            $this->ajaxData($data);
        }
        if(empty($checkin_members)){
            $data['code'] = -2;
            $data['msg'] = 'checkin_members参数有误!';
            $this->ajaxData($data);
        }

        if(empty($houseid)){
            $data['code'] = -2;
            $data['msg'] = 'houseid参数有误!';
            $this->ajaxData($data);
        }

        //下单
        $this->houseorder_model->startTrans();
        $alreadyDate = $this->houseorder_model->getOrderedHouseTime($houseid,$lock=true);
        $pickDate =  dateList($checkin_time,$checkout_time);
        $interDate = array_intersect($alreadyDate,$pickDate);
        if($interDate){//有交集
            $data['code'] = -1;
            $data['msg'] = 'fail';//下单日期与已租日期有交集
            //写入log
            $failReason = '用户'.$uid.'房源'.$houseid.'下单日期'.$checkin_time.'至'.$checkout_time.'与已租日期有交集';
            $logfile = dirname(dirname(dirname(__DIR__))).'\/data\/errorlog\/order\/';
            if(!is_dir($logfile)){
                mkdir($logfile, 0777,true);
            }

            $filename = 'order.txt';
            addLog($failReason,$logfile,$filename);
            //写入log end
            $data['code'] = -2;
            $data['msg'] = '所选日期有误';
            $this->ajaxData($data);

        }else{
            //房屋名称
            $isOrder = 1;
            $insdata['mid'] = $uid;
            $insdata['houseid'] = $houseid;
            $housename =  $this->getHousenameById($houseid);
            //所使用的优惠券
            $cid = $this->memberCoupon_model->where(array('cmid'=>$cmid))->field('cid')->find();
            $cup = $this->coupon_model->CouponList('conditions',array('cid'=>$cid['cid']));
            if(!empty($cup)){
                $cond = json_decode($cup[0]['conditions'],true);
                $coupon = $cond['discount'];
            }else{
                $coupon = 0;
            }

            $lastorder = $this->houseorder_model->limit(1)->order('createtime DESC')->field('orderid')->find();

            $insdata['ordernum'] = $this->getOrderNum($lastorder['orderid']);//唯一标识的订单号
            $insdata['housename'] = !empty($housename['housename']) ? $housename['housename'] : '房屋名称未知';
            $insdata['checkin_time'] = $checkin_time;
            $insdata['checkout_time'] = $checkout_time;
            $insdata['checkin_members'] = $checkin_members;
            $insdata['createtime'] = $ordertime;
            $insdata['staydays'] = $staydays;
            $insdata['orderstate'] = 2;   //已支付未确认
            $insdata['sum_cost'] = $discount_cost;
            $insdata['discount_cost'] = $discount_cost;
            $insdata['stayinfo'] = json_encode(array(
                'startweek'=>$startweek,
                'endweek'=>$endweek,
                'discount'=>$discount,
                'coupon'=>$coupon,
            ));

            $userinfo = $this->member_model->getUserByUid('memberphone',array('mid'=>$uid));
            $insdata['orderphone'] = $userinfo[0]['memberphone'];

            $where = array('houseid'=>$houseid);
            $updata = array('isorder'=>$isOrder);

        }
//print_r($where);die;

        if(!empty($where)){
            $paystate = 0;//1支付成功,0支付失败
            //事务处理
                //如果未支付成功,则回滚插入的数据(即订单未入库=未生成)
                //如果支付成功则,生成一条订单数据,并且房源被标记已预订
            //开启事务
//            $this->houseorder_model->startTrans();
            //1.入订单库
            $insId = $this->houseorder_model->add($insdata);
            //修改用户优惠券状态=已过期
            $upstate['state'] = 2;
            $cstate = $this->memberCoupon_model->where(array('cmid'=>$cmid,'mid'=>$uid))->save($upstate);
//            echo $this->memberCoupon_model->getLastSql();die;
            ////////////////////////////////////////////////////////////////////////////////////////////
            //2.调用支付接口
//            $paystate = 1;
            $totalcost = $discount_cost;
            $ordernum = $this->getOrderNum($lastorder['orderid']);
            $paydatas = array('ordernum'=>$ordernum,'itemdesc'=>'test','totalcost'=>$totalcost,'uid'=>$uid);
            $paystate = $this->wxpay($paydatas);
//            print_r($paystate);die;
/////////////////////////////////////////////////////////////////////////////////
            if($paystate){//成功
                $this->houseorder_model->commit();
                $this->memberCoupon_model->commit();

                $this->ajaxData();
            }else{
                $this->houseorder_model->rollback();
                $this->memberCoupon_model->rollback();

                $data['code'] = -1;
                $data['msg'] = 'fail';
                $this->ajaxData($data);
            }
        }

//        if($paystate && $insId && $upId){
//        if($paystate){
//            $this->ajaxData();
//        }else{
//            $data['code'] = -1;
//            $data['msg'] = 'fail';
//            $this->ajaxData($data);
//        }
    }

    //我的订单列表
    public function orderList(){
        //1未支付,2已支付未确认,3已支付已确认,4已入住,5已退房,6已失效,7已退款8未入住
        $uid = I('get.uid',0,'intval');
        $state = I('get.orderstate',0,'intval');
        $page = I('get.page',1,'intval');
        $perpage = I('get.perpage',20,'intval');
        $limit = ($page - 1) * $perpage;
        if($state == 4){//已入住
            $where['where'] = array(
                'mid'=> $uid,
                'orderstate'=> $state
            );
        }
        if($state == 8){//未入住
            $where['where'] = array(
                'mid'=> $uid,
                'orderstate'=> array('exp', 'IN (2,3)'),
            );
        }
        if(empty($state)){
            $where['where'] = array(
                'mid'=> $uid,
                //'orderstate'=> $state
            );
        }
        $order = $this->houseorder_model->getOrderByUid($where,'*',$limit,$perpage);

        foreach ($order as $or => $v){
            $order[$or]['checkin_time'] = date('n',$order[$or]['checkin_time']).'月'.date('j',$order[$or]['checkin_time']).'日';
            $order[$or]['checkout_time'] = date('n',$order[$or]['checkout_time']).'月'.date('j',$order[$or]['checkout_time']).'日';
            $order[$or]['stayinfo'] = json_decode($order[$or]['stayinfo'],true);
        }
        $data['code'] = 0;
        $data['msg'] = 'success';
        $data['data'] = $order;
        $this->ajaxData($data);
    }

    public function orderDetail(){
        $houseid = I('request.houseid',0,'intval');
        $orderid = I('request.orderid',0,'intval');
        $uid = I('request.uid',0,'intval');
//        $where['houseid'] = $houseid;
        $where['orderid'] = $orderid;
        $field = 'orderid,housename,checkin_time,checkout_time,staydays,discount_cost,stayinfo';
        $this->houseorder_model->where($where);
        $this->houseorder_model->field($field);
        $order = $this->houseorder_model->select();


        //缓存
        $cache = S(array('type'=>'file','prefix'=>'','expire'=>300));//'expire'=>60
        $key = 'orderdetail';
        $orderDetail = $cache->$key;

        if(!empty($orderDetail)){
            $house = $orderDetail;
        }else{
            foreach ($order as $or=>$v){
                $order[$or]['checkin_time'] = date('n',$order[$or]['checkin_time']).'月'.date('j',$order[$or]['checkin_time']).'日';
                $order[$or]['checkout_time'] = date('n',$order[$or]['checkout_time']).'月'.date('j',$order[$or]['checkout_time']).'日';
                $order[$or]['stayinfo'] = json_decode($order[$or]['stayinfo'],true);
            }

            $field = 'bathroom,mindays,cash,price,maxmembers,housearea,bedtype,starttime,endtime';
            $where['houseid'] = $houseid;
            $housedetail = $this->housedetail_model->where($where)->field($field)->select();
            foreach($housedetail as $hd=>$v){
                $housedetail[$hd]['starttime'] = date('H:i',$housedetail[$hd]['starttime']);
                $housedetail[$hd]['endtime'] = date('H:i',$housedetail[$hd]['endtime']);
            }

            $field = 'houseaddress,houseposition,typeid,house_x,house_y';
            $where['houseid'] = $houseid;
            $house = $this->house_model->where($where)->field($field)->select();
            foreach ($house  as $h=>$v){
                $type = $this->housetype_model->where($v['typeid'])->field('housetype')->find();
                $house[$h]['housetype'] = $type['housetype'];
                //合并
                if($orderid){
                    $house = array_merge($house[$h],$housedetail[$h],$order[$h]);
                }
            }
            //设置缓存
            $cache->$key = $house;
        }
        $data['data']['orderdetail'] = $house;
        $this->ajaxData($data);
    }

    public function getHousenameById($houseid=0){
	    $name = $this->house_model->where(array('houseid'=>$houseid))->field('housename')->find();
	    return $name;
    }

    public function getOrderNum($orderid=0){
	    if($orderid){
	        $num = $this->houseorder_model->where(array('orderid'=>$orderid))->field('ordernum')->find();
	        if(!empty($num['ordernum'])){
                $st = substr($num['ordernum'], -1);
                $or = intval($st +1);
                $orderNum = date('Ymd').'00000'.$or;
            }else{
                $orderNum = date('Ymd').'000001';
            }
	        return $orderNum;
        }else{
	        $orderNum = date('Ymd').'000001';
	        return $orderNum;
        }
    }

    /**
     * 调用微信支付
    */
    public function wxpay($paydatas){
        $wxConfig = new \WxPayConfig();
        $appid = $wxConfig::APPID;
        $mch_id = $wxConfig::MCHID;
        $key = $wxConfig::KEY;

        $uid = $paydatas['uid'];
        $openid = $this->member_model->where('mid',$uid)->getField('openid');

        $data = array(
            'ordernum'=> $paydatas['ordernum'],
            'itemdesc'=> $paydatas['itemdesc'],
            'totalcost'=> $paydatas['totalcost'],
        );
        $wx = new \WeixinPay($appid,$openid,$mch_id,$key,$data);
        $res = $wx->pay();
        echo json_encode($res);die;
    }


    //获取openid
    public function getopenid(){
        $js_code = I('post.code');
        $uid = I('post.uid');
        if(empty($js_code)) return array('status'=>0,'info'=>'缺少js_code');

        $wxConfig = new \WxPayConfig();
        $appid = $wxConfig::APPID;
        $appsecret = $wxConfig::APPSECRET;

        $params = array(
            'appid' => $appid,
            'secret' => $appsecret,
            'js_code' => $js_code, // 前端传来的
            'grant_type' => 'authorization_code',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.weixin.qq.com/sns/jscode2session');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $output = curl_exec($ch);

        if (false === $output) {
            echo 'CURL Error:' . curl_error($ch);
        }

        $op = json_decode($output,true);
        print_r($op);die;
        $openid = $op['openid'];
        $openid = 23322;
        if($uid){
            //根据uid修改openid的值
            $where['mid'] = $uid;
            $data['openid'] = $openid;
            $upmid = $this->member_model->where($where)->save($data);
        }
        return $upmid;
//        echo $output;
    }

    //微信支付回调验证
    public function wxnotify(){
        $wxConfig = new \WxPayConfig();
        $key = $wxConfig::KEY;
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];

        // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
        //file_put_contents(APP_ROOT.'/Statics/log2.txt',$res,FILE_APPEND);

        //将服务器返回的XML数据转化为数组
        $data = self::xml2array($xml);
        // 保存微信服务器返回的签名sign
        $data_sign = $data['sign'];
        // sign不参与签名算法
        unset($data['sign']);
        $sign = self::makeSign($data,$key);

        // 判断签名是否正确  判断支付状态
        if ( ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
            $result = $data;
            //获取服务器返回的数据
            $order_sn = $data['out_trade_no'];            //订单单号
            $openid = $data['openid'];                    //付款人openID
            $total_fee = $data['total_fee'];            //付款金额
            $transaction_id = $data['transaction_id'];     //微信支付流水号

            //更新数据库
            //$this->updateDB($order_sn,$openid,$total_fee,$transaction_id);

            $result = true;
        }else{
            $result = false;
        }
        // 返回状态给微信服务器
        if ($result) {
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;
        return $result;
    }

    /**
     * 生成签名, $KEY就是支付key
     * @return 签名
     */
    private function makeSign( $params,$KEY){
        //签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = $this->ToUrlParams($params);  //参数进行拼接key=value&k=v
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }



    /**
     * 将参数拼接为url: key=value&key=value
     * @param $params
     * @return string
     */
    public function ToUrlParams( $params ){
        $string = '';
        if( !empty($params) ){
            $array = array();
            foreach( $params as $key => $value ){
                $array[] = $key.'='.$value;
            }
            $string = implode("&",$array);
        }
        return $string;
    }

    //xml转换成数组
    private function xml2array($xml) {

        //禁止引用外部xml实体

        libxml_disable_entity_loader(true);


        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);


        $val = json_decode(json_encode($xmlstring), true);

        return $val;
    }
}


