<?php
namespace frontend\controllers;

use Yii;
use yii\web\Response;

class LineController extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($action->id == 'callback') {
            $this->enableCsrfValidation = false; //»Ô´¡ÒÃãªé§Ò¹ csrf
        }
    
        return parent::beforeAction($action);
    }
    

    public function actionCallback()
    {
        
        $json_string = file_get_contents('php://input');
        $jsonObj = json_decode($json_string); //ÃÑº JSON ÁÒ decode à»ç¹ StdObj
        $to = $jsonObj->{"result"}[0]->{"content"}->{"from"}; //ËÒ¼ÙéÊè§
        $text = $jsonObj->{"result"}[0]->{"content"}->{"text"}; //ËÒ¢éÍ¤ÇÒÁ·Õèâ¾ÊÁÒ
        
        $text_ex = explode(':', $text); //àÍÒ¢éÍ¤ÇÒÁÁÒáÂ¡ : ä´éà»ç¹ Array
        
        if($text_ex[0] == "ÍÂÒ¡ÃÙé"){ //¶éÒ¢éÍ¤ÇÒÁ¤×Í "ÍÂÒ¡ÃÙé" ãËé·Ó¡ÒÃ´Ö§¢éÍÁÙÅ¨Ò¡ Wikipedia ËÒ¨Ò¡ä·Â¡èÍ¹
            //https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro=&explaintext=&titles=PHP
            $ch1 = curl_init();
            curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch1, CURLOPT_URL, 'https://th.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro=&explaintext=&titles='.$text_ex[1]);
            $result1 = curl_exec($ch1);
            curl_close($ch1);
            
            $obj = json_decode($result1, true);
            
            foreach($obj['query']['pages'] as $key => $val){

                $result_text = $val['extract'];
            }
            
            if(empty($result_text)){//¶éÒäÁè¾ºãËéËÒ¨Ò¡ en
                $ch1 = curl_init();
                curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch1, CURLOPT_URL, 'https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro=&explaintext=&titles='.$text_ex[1]);
                $result1 = curl_exec($ch1);
                curl_close($ch1);
                
                $obj = json_decode($result1, true);
                
                foreach($obj['query']['pages'] as $key => $val){
                
                    $result_text = $val['extract'];
                }
            }
            if(empty($result_text)){//ËÒ¨Ò¡ en äÁè¾º¡çºÍ¡ÇèÒ äÁè¾º¢éÍÁÙÅ µÍº¡ÅÑºä»
                $result_text = 'äÁè¾º¢éÍÁÙÅ';
            }
            $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>$result_text];
            
        }else if($text_ex[0] == "ÍÒ¡ÒÈ"){//¶éÒ¾ÔÁ¾ìÁÒÇèÒ ÍÒ¡ÒÈ ¡çãËéä»´Ö§ API ¨Ò¡ wunderground ÁÒ
            //http://api.wunderground.com/api/yourkey/forecast/lang:TH/q/Thailand/%E0%B8%81%E0%B8%A3%E0%B8%B8%E0%B8%87%E0%B9%80%E0%B8%97%E0%B8%9E%E0%B8%A1%E0%B8%AB%E0%B8%B2%E0%B8%99%E0%B8%84%E0%B8%A3.json
            $ch1 = curl_init();
            curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch1, CURLOPT_URL, 'http://api.wunderground.com/api/yourkey/forecast/lang:TH/q/Thailand/'.str_replace(' ', '%20', $text_ex[1]).'.json');
            $result1 = curl_exec($ch1);
            curl_close($ch1);
            
            $obj = json_decode($result1, true);
            if(isset($obj['forecast']['txt_forecast']['forecastday'][0]['fcttext_metric'])){
                $result_text = $obj['forecast']['txt_forecast']['forecastday'][0]['fcttext_metric'];
            }else{//¶éÒäÁèà¨Í¡ÑºµÍº¡ÅÑºÇèÒäÁè¾º¢éÍÁÙÅ
                $result_text = 'äÁè¾º¢éÍÁÙÅ';
            }
            
            $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>$result_text];
        }else if($text == 'ºÍ¡ÁÒ'){//¤ÓÍ×è¹æ ·ÕèµéÍ§¡ÒÃãËé Bot µÍº¡ÅÑºàÁ×èÍâ¾Ê¤Ó¹ÕéÁÒ àªè¹â¾ÊÇèÒ ºÍ¡ÁÒ ãËéµÍºÇèÒ ¤ÇÒÁÅÑº¹Ð
            $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>"¤ÇÒÁÅÑº¹Ð"];
        }else{//¹Í¡¹Ñé¹ãËéâ¾Ê ÊÇÑÊ´Õ
            $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>"ÊÇÑÊ´Õ"];
        }

        // toChannel?eventType
        $post_data = ["to"=>[$to],"toChannel"=>"1383378250","eventType"=>"138311608800106203","content"=>$response_format_text]; //Êè§¢éÍÁÙÅä»
        
        $ch = curl_init("https://trialbot-api.line.me/v1/events");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charser=UTF-8',
            'X-Line-ChannelID: 1514503495',
            'X-Line-ChannelSecret: 546930ef36b33b86e770a4477a5786c2',
            'X-Line-Trusted-User-With-ACL: Uba681ccc072bd5d1f574e4c516ea6c1a'
        ));
        $result = curl_exec($ch);
        curl_close($ch);
        
    }
}
?>
