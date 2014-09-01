<?php

class AnimalController extends Controller
{
    public function filters() 
    {
        return array(
            'checkUpdate',
            'checkSig',
            'getUserId',
            /*
            array(
                'COutputCache + welcomeApi',
                'duration' => 86800,
                'dependency' => array(
                    'class' => 'CExpressionDependency',
                    'expression' => 'date("m.d.y")',
                ),
            ),
             */
            /*
            array(
                'COutputCache + infoApi',
                'duration' => 3600,
                'varyBySession' => true,
                'dependency' => array(
                    'class' => 'CDbCacheDependency',
                    'sql' => "SELECT MAX(update_time) FROM dc_user WHERE usr_id=:usr_id",
                    'params' => array(
                        ':usr_id' => $this->usr_id,
                    ),
                ),
            ),
             */
            array(
                'COutputCache + imagesApi',
                'duration' => 3600,
                'varyByParam' => array('img_id', 'usr_id'),
                'varyBySession' => true,
                'dependency' => array(
                    'class' => 'CDbCacheDependency',
                    'sql' => "SELECT MAX(update_time) FROM dc_image WHERE usr_id=:usr_id",
                    'params' => array(
                        ':usr_id' => $this->usr_id,
                    ),
                ),
            ),
            /*
            array(
                'COutputCache + followingApi',
                'duration' => 3600,
                'varyByParam' => array('follow_id'),
                'varyBySession' => true,
                'dependency' => array(
                    'class' => 'CDbCacheDependency',
                    'sql' => "SELECT COUNT(*), MAX(update_time) FROM dc_friend WHERE (usr_id=:usr_id AND relation IN (0,1)) OR (follow_id=:usr_id AND relation IN (0,-1))",
                    'params' => array(
                        ':usr_id' => $this->usr_id,
                    ),
                ),
            ),
            array(
                'COutputCache + followerApi',
                'duration' => 3600,
                'varyByParam' => array('usr_id'),
                'varyBySession' => true,
                'dependency' => array(
                    'class' => 'CDbCacheDependency',
                    'sql' => "SELECT COUNT(*), MAX(update_time) FROM dc_friend WHERE (follow_id=:follow_id AND relation IN (0,1)) OR (usr_id=:follow_id AND relation IN(0,-1))",
                    'params' => array(
                        ':follow_id' => $this->usr_id,
                    ),
                ),
            ),
             */
            array(
                'COutputCache + othersApi',
                'duration' => 3600,
                'varyByParam' => array('usr_ids'),
            )
        );
    }

    public function actionInfoApi($aid)
    {
        $r = Yii::app()->db->createCommand('SELECT a.aid, a.name, a.tx, a.gender, a.from, a.type, a.age, a.master_id, a.t_rq, u.name, u.tx, u.rank, COUNT(DISTINCT c.usr_id) AS fans, COUNT(DISTINCT f.usr_id) AS followers FROM dc_animal a JOIN dc_user u ON a.master_id=u.usr_id LEFT JOIN dc_circle c ON a.aid=c.aid LEFT JOIN dc_follow f ON a.aid=f.aid WHERE a.aid=:aid')->bindValue(':aid', $aid)->queryColumn();

        $this->echoJsonData($r);
    }

    public function actionTxApi($aid)
    {
        $a = Animal::model()->findByPk($aid);

        if (isset($_FILES['tx'])) {
            $fname = basename($_FILES['tx']['name']);
            $path = Yii::app()->basePath.'/../images/tx_ani/'.$aid.'_'.$fname;
            if (move_uploaded_file($_FILES['tx']['tmp_name'], $path)) {
                $a->tx = $aid.'_'.$fname;
                $a->saveAttributes(array('tx'));
            }
        }

        $this->echoJsonData(array('tx'=>$a->tx));
    }


    public function actionImagesApi($aid, $img_id=NULL)
    {
        $c = new CDbCriteria;

        $c->compare('aid', $aid);
        $c->limit = 10;
        $c->order = 't.img_id DESC';
        if(isset($img_id)) {
            $c->addCondition("t.img_id<:img_id");
            $c->params[':img_id'] = $img_id;
        }

        $images = Image::model()->findAll($c);

        $this->echoJsonData($images);
    }

    public function actionFollowApi($aid)
    {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $f = Follow::model()->findByPk(array(
                'usr_id' => $this->usr_id,
                'aid' => $aid,
            ));
            if (!isset($f)) {
                $f = new Follow;
                $f->usr_id = $this->usr_id;
                $f->aid = $aid;
                $f->create_time = time();
            }
            $f->save();

            //events
            //$user = User::model()->findByPk($this->usr_id);
            //PMail::create($usr_id, $user, $user->name.'关注了你');

            $transaction->commit();

            $this->echoJsonData(array(
                'isSuccess' => true,
            ));
        } catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }

    public function actionUnFollowApi($aid)
    {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $f = Follow::model()->findByPk(array(
                'usr_id' => $this->usr_id,
                'aid' => $aid,
            ));
            $f->delete();

            $transaction->commit();

            $this->echoJsonData(array(
                'isSuccess' => true,
            ));
        } catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }

    public function actionFansApi($aid, $rank=999999999, $usr_id=0)
    {
        $r = Yii::app()->db->createCommand('SELECT c.usr_id as usr_id, rank, t_contri, u.tx, name, gender, city FROM dc_circle c INNER JOIN dc_user u ON c.usr_id=u.usr_id WHERE c.aid=:aid AND rank<:rank AND c.usr_id>:usr_id  ORDER BY rank DESC, c.usr_id DESC LIMIT 30')->bindValues(array(
            ':aid'=>$aid,
            ':rank'=>$rank,
            ':usr_id'=>$usr_id,
        ))->queryAll();

        $this->echoJsonData($r);
    }

    public function actionItemsApi($aid)
    {
        $i = Yii::app()->db->createCommand('SELECT items FROM dc_animal WHERE aid=:aid')->bindValue(':aid', $aid)->queryScalar();
        $r = unserialize($i);

        $this->echoJsonData($r);
    }

    public function actionNewsApi($aid, $nid=999999999)
    {
        $r = Yii::app()->db->createCommand('SELECT nid, aid, type, content FROM dc_news WHERE aid=:aid AND nid<:nid ORDER BY nid DESC')->bindValues(array(
            ':aid' => $aid,
            ':nid' => $nid,
        ))->queryAll();

        $this->echoJsonData($r);
    }

    public function actionCreateApi($name, $gender, $age, $type)
    {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $animal = new Animal();
            $animal->name = $name;
            $animal->gender = $gender;
            $animal->age = $age;
            $animal->type = $type;
            $animal->master_id = $this->usr_id;
            $animal->save();
            $session = Yii::app()->session;
            $aid = $animal->aid = $animal->aid + 10000000000*$session['planet'];
            $animal->saveAttributes(array('aid'));
            $circle = new Circle();
            $circle->aid = $aid;
            $circle->usr_id = $this->usr_id;
            $circle->save();
            $transaction->commit();

            $this->echoJsonData(array('isSuccess'=>true));
        } catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }

    public function actionJoinApi($aid)
    {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $circle = new Circle();
            $circle->aid = $aid;
            $circle->usr_id = $this->usr_id;
            $circle->save();
            $transaction->commit();

            $this->echoJsonData(array('isSuccess'=>true));
        } catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }

    public function actionExitApi($aid)
    {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $circle = Circle::model()->findByPk(array(
                'aid' => $aid,
                'usr_id' => $this->usr_id,
            ));
            $circle->delete();
            $transaction->commit();

            $this->echoJsonData(array('isSuccess'=>true));
        } catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }

    public function actionVoiceUpApi($aid)
    {
        if (isset($_FILES['voice'])) {
            $fname = basename($_FILES['voice']['name']);
            $path = Yii::app()->basePath.'/../assets/voices/ani/voice_'.date().'_'.$aid;
            if (move_uploaded_file($_FILES['voice']['tmp_name'], $path)) {
                $this->echoJsonData(array('isSuccess'=>TRUE));
            } else {
                throw new PException('上传失败'); 
            }
        } else {
            throw new PException('音频文件不存在'); 
        }

        
    }

    public function actionVoiceDownApi($aid)
    {
        $path = Yii::app()->basePath.'/../assets/voices/ani/voice_'.date().'_'.$aid;
        if (file_exists($path)) {
            $this->echoJsonData(array('url'=>$path));
        } else {
            throw new PException('音频文件不存在');
        }
        
    }

    public function actionTouchApi($aid)
    {
       $this->echoJsonData(array('isSuccess'=>TRUE)); 
    }

    public function actionShakeApi($aid)
    {
       $this->echoJsonData(array('isSuccess'=>TRUE)); 
    }

    public function actionSendGiftApi($item_id, $aid, $img_id=NULL, $is_buy=FALSE)
    {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $item = Item::model()->findByPk($item_id);

            $user = User::model()->findByPk($this->usr_id);
            if (!$is_buy) {
                $items = unserialize($user->items);
                if (isset($items[$item_id])&&$items[$item_id]>0) {
                    $items[$item_id]--;
                } else {
                    throw new PException('礼物数量不足');
                }
                $user->items = serialize($items);
            }
            $user->exp+=$item->exp;
            $user->saveAtrributes(array('items', 'exp'));

            $animal = Animal::model()->findByPk($aid);
            $animal->d_rq+=$item->rq;
            $animal->m_rq+=$item->rq;
            $animal->y_rq+=$item->rq;
            $animal->t_rq+=$item->rq;
            $a_items = unserialize($animal->items);
            if (isset($a_items[$item_id])) {
                $a_items[$item_id]++;
            } else {
                $a_items[$item_id] = 1;
            }
            $animal->items = unserialize($a_items);
            $animal->saveAttributes(array('d_rq', 'm_rq', 'y_rq', 't_rq', 'items'));

            if (isset($img_id)) {
                $image = Image::model()->findByPk($img_id);
                $image->gifts++;
                if (isset($image->senders)&&$image->senders!='') {
                    $image->senders = $this->usr_id.','.$image->senders;
                } else {
                    $image->senders = $this->usr_id;
                }
                $image->saveAttributes(array('gifts', 'senders'));
            }

            $transaction->commit();

            $this->echoJsonData(array('isSuccess'=>true));
        } catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }

    public function actionCardApi($aid)
    {
        $images = Yii::app()->db->createCommand('SELECT img_id, url FROM dc_image WHERE aid=:aid ORDER BY update_time LIMIT 4')->queryAll();

        $master_id = Yii::app()->db->createCommand('SELECT master_id FROM dc_animal WHERE aid=:aid')->bindValue(':aid', $aid)->queryScalar();
        $master = Yii::app()->db->createCommand('SELECT usr_id, name, tx, gender, city FROM dc_user WHERE usr_id=:usr_id')->bindValue(':usr_id', $master_id)->queryRow();

        $this->echoJsonData(array(
            'master' => $master,
            'images' => $images,
        ));
    }

    public function actionRecommendApi($type=NULL, $aid=NULL)
    {
        $t_rq = 999999999;
        if (isset($aid)) {
            $t_rq = Yii::app()->db->createCommand('SELECT t_rq FROM dc_animal WHERE aid=:aid')->bindValue(':aid', $aid)->queryScalar();
        }
        if (isset($type)) {
            $r = Yii::app()->db->createCommand('SELECT a.aid, a.name, a.tx, a.gender, a.from, a.type, a.age, a.t_rq, COUNT(DISTINCT c.usr_id) AS fans FROM dc_animal a LEFT JOIN dc_circle c ON a.aid=c.aid WHERE a.t_rq<:t_rq AND type=:type ORDER BY a.t_rq DESC LIMIT 30')->bindValues(array(
                ':t_rq'=>$t_rq,
                ':type'=>$type,
            ))->queryAll();
        } else {
            $session = Yii::app()->session;
            
            $r = Yii::app()->db->createCommand('SELECT a.aid, a.name, a.tx, a.gender, a.from, a.type, a.age,  a.t_rq, COUNT(DISTINCT c.usr_id) AS fans FROM dc_animal a LEFT JOIN dc_circle c ON a.aid=c.aid WHERE a.t_rq<:t_rq AND type BETWEEN :type1 AND :type2 ORDER BY a.t_rq DESC LIMIT 30')->bindValues(array(
                ':t_rq'=>$t_rq,
                ':type1'=>10000000000*$session['planet'],
                ':type2'=>10000000000*($session['planet']+1),
            ))->queryAll();
        }
        
        $this->echoJsonData(array($r));
    }

    public function actionSearchApi($name, $aid=0)
    {
        $session = Yii::app()->session;

        $r = Yii::app()->db->createCommand('SELECT a.aid, a.name, a.tx, a.gender, a.from, a.type, a.age, a.t_rq, COUNT(DISTINCT c.usr_id) AS fans FROM dc_animal a LEFT JOIN dc_circle c ON a.aid=c.aid WHERE a.aid>:aid AND name LIKE "%:name%" ORDER BY a.aid ASC LIMIT 30')->bindValues(array(
            ':name'=>$name,
            ':aid'=>$aid+10000000000*$session['planet'],
        ))->queryAll();

        $this->echoJsonData(array($r));
    }

    public function actionOthersApi($aids, $aid=NULL)
    {
        $tmp_ids = explode(',',$aids);
        if (isset($aid)) {
            foreach ($tmp_ids as $k=>$tmp_id) {
                if ($tmp_id!=$aid) {
                    unset($tmp_ids[$k]);
                } else {
                    break;
                }
            }
        }
        for ($i = 0; $i < 30 && isset($tmp_ids[$i]); $i++) {
            $search_ids[] = $tmp_ids[$i];
        }
        $search_ids = implode(',', $search_ids);

        $r = Yii::app()->db->createCommand('SELECT aid, name, tx, age, gender, (SELECT COUNT(*) FROM dc_follow f WHERE f.aid=a.aid AND usr_id=:usr_id) AS is_follow FROM dc_animal a WHERE aid IN (:aids)')->bindValues(array(
            ':usr_id'=>$this->usr_id,
            ':aids'=>$search_ids,
        ))->queryAll();

        $this->echoJsonData($r);
    }

    public function actionAddressApi()
    {
        $aid = $_REQUEST['aid'];
        
        $animal = Animal::model()->findByPk($aid);
        if (isset($_POST['name'])) {
            $address = array(
                'name'=>$_POST['name'],
                'telephone'=>$_POST['telephone'],
                'zipcode'=>$_POST['zipcode'],
                'region'=>$_POST['region'],
                'building'=>$_POST['building'],
            );
            $animal->address = serialize($address);
            $animal->saveAttributes(array('address'));
        }

        $this->echoJsonData(array(unserialize($animal->address)));
    }
}