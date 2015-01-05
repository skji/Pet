<?php

class SocialController extends Controller
{
    public function filters() 
    {
        return array(
        );
    }

    public function actionFoodShareApi($img_id, $to='wechat')
    {
        $this->layout = FALSE;
        $r = Yii::app()->db->createCommand('SELECT i.img_id, i.url, i.aid, i.cmt, i.food, i.create_time, a.name, a.tx, a.type, a.gender, u.usr_id, u.tx AS u_tx, u.name AS u_name  FROM dc_image i LEFT JOIN dc_animal a ON i.aid=a.aid LEFT JOIN dc_user u ON a.master_id=u.usr_id WHERE i.img_id=:img_id')->bindValue(':img_id', $img_id)->queryRow();

        $this->render('food', array('r'=>$r, 'to'=>$to));
    }

    public function actionActivity()
    {
        $this->layout = FALSE;

        $this->render('activity_index');
    }

    public function actionActivityview()
    {
        $this->layout = FALSE;

        $this->render('activity_view');
    }
    
    /*
    public function actionOAuth2CallbackApi($code, $state)
    {
        if (isset($code)) {
            $rtn = Yii::app()->curl->get('https://api.weixin.qq.com/sns/oauth2/access_token', array(
                'appid'=>'',
                'secret'=>'',
                'code'=>$code,
                'grant_type'=>'authorization_code',
            )); 
            $t = json_decode($rtn);
            if (isset($t->errcode)) {
                $this->redirect($this->createUrl('social/foodShareApi',array('img_id'=>$state)));
            } else {
                if (isset($t->openid)) {
                    $usrinfo = Yii::app()->curl->get('https://api.weixin.qq.com/sns/userinfo',array(
                        'access_token'=>$t->access_token,
                        'openid'=>$t->openid,
                    ));
                    $u = json_decode($usrinfo);
                    if (isset($u->errcode)) {
                        $this->redirect($this->createUrl('social/foodShareApi',array('img_id'=>$state)));
                    } else {
                        $json = Yii::app()->curl->get($this->createUrl('user/login',array('uid'=>$u->unionid)));
                        $j = json_decode($json);
                        if (!$j->isSuccess) {
                            $aid = Yii::app()->db->createCommand('SELECT aid FROM dc_image WHERE img_id=:img_id')->bindValue(':img_id', $state)->queryScalar();
                            $params = array(
                                'aid'=>$aid,
                                'u_name'=>$u->nickname,
                                'u_gender'=>$u->sex,
                                'u_city'=>1001,
                                'wechat'=>$u->openid,
                                'SID'=>$j->SID,
                            );
                            $params['sig'] = $this->signature();
                            $res_register = Yii::app()->curl->get($this->createUrl('user/registerApi', $params));
                            $json_register = json_decode($res_register); 
                            if (!isset($json_register->usr_id)) {
                                $this->redirect($this->createUrl('social/foodShareApi',array('img_id'=>$state)));
                            }
                        }
                        $this->layout = FALSE;
                        $r = Yii::app()->db->createCommand('SELECT i.img_id, i.url, i.aid, i.cmt, i.food, i.create_time, a.name, a.tx, a.type, a.gender, u.usr_id, u.tx AS u_tx, u.name AS u_name  FROM dc_image i LEFT JOIN dc_animal a ON i.aid=a.aid LEFT JOIN dc_user u ON a.master_id=u.usr_id WHERE i.img_id=:img_id')->bindValue(':img_id', $state)->queryRow();
                        $this->render('food', array('r'=>$r, 'sid'=>$j->SID));
                    }
                }                
            }

        }
    }

    */
}

