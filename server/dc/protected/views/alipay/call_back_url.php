<?php
/* * 
 * 功能：支付宝页面跳转同步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

 *************************页面功能说明*************************
 * 该页面可在本机电脑测试
 * 可放入HTML等美化页面的代码、商户业务逻辑程序代码
 * 该页面可以使用PHP开发工具调试，也可以使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyReturn
 */

require_once("alipay.config.php");
require_once("lib/alipay_notify.class.php");
?>
<!DOCTYPE HTML>
<html>
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyReturn();
if($verify_result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代码
	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

	//商户订单号
	$out_trade_no = $_GET['out_trade_no'];

	//支付宝交易号
	$trade_no = $_GET['trade_no'];

	//交易状态
	$result = $_GET['result'];


	//判断该笔订单是否在商户网站中已经做过处理
		//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
		//如果有做过处理，不执行商户的业务程序
    $order = Alipay::model()->findByPk($out_trade_no);
    if (isset($order)) {
        if ($order->status==0) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $user = User::model()->findByPk($order->usr_id);
                switch ($order->fee) {
                    case 1:
                    $gold=100;
                    $user->gold+=100;
                    break;
                    case 5:
                    $gold=500;
                    $user->gold+=500;
                    break;
                    case 10:
                    $gold=1050;
                    $user->gold+=1050;
                    break;
                    case 100:
                    $gold=11000;
                    $user->gold+=11000;
                    default:
                            # code...
                    break;
                }
                $user->saveAttributes(array('gold'));
                $order->status = 1;
                $order->saveAttributes(array('status'));
                Talk::model()->sendMsg(NPC_SYSTEM_USRID, $order->usr_id, "Hello，您的充值".$gold."金币已经入账咯～");
                $easemob = Yii::app()->easemob;
                $npc = User::model()->findByPk(NPC_SYSTEM_USRID);
                $easemob->sendToUsers($order->usr_id, NPC_SYSTEM_USRID, array(
                    'mixed'=>TRUE,
                    'msg'=>"Hello，您的充值".$gold."金币已经入账咯～",
                    'ext'=>array(
                        'nickname'=>$npc->name,
                        'tx'=>$npc->tx,
                    ),
                ));
                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                echo 'fail';
                return ;
            }
        }
    } else {
        echo 'fail';
        return ;
    }
		
	//echo "验证成功<br />";
    $this->redirect(array('alipay/success'));

	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
    //验证失败
    //如要调试，请看alipay_notify.php页面的verifyReturn函数
    echo "验证失败";
}
?>
        <title>支付宝即时到账交易接口</title>
	</head>
    <body>
    </body>
</html>
