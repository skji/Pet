<?php

class SocialController extends Controller
{
    public function filters() 
    {
        return array(
        );
    }

    public function actionWechatFoodShareApi($img_id)
    {
       $r = Yii::app()->db->createCommand('SELECT i.img_id, i.url, i.aid, i.cmt, i.food, i.create_time, a.name, a.tx, a.type, a.gender, u.usr_id, u.tx AS u_tx, u.name AS u_name  FROM dc_image i LEFT JOIN dc_animal a ON i.aid=a.aid LEFT JOIN dc_user u ON a.master_id=u.usr_id WHERE i.img_id=:img_id')->bindValue(':img_id', $img_id)->queryRow();

       $this->render('food', array('r'=>$r));
    }

}

