<?php
namespace frontend\controllers;

use Yii;
use yii\web\Response;
$access_token = '26mLZVOEtRXaQIudkprlUPDieWqKn4UJvCT+fnJtZuottjSgJFx6vyjyViS1QZT7X9N0YVVm6tSdVln2CwHqaYSKv960dHeXv1DT8zyyIpVG25/D+xk2sW3KHzXI+akkzCTPzoqJ1SXT0FwJg/Mp/QdB04t89/1O/w1cDnyilFU=';

class LineController extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($action->id == 'callback') {
            $this->enableCsrfValidation = false; //�Դ�����ҹ csrf
        }
    
        return parent::beforeAction($action);
    }
    

    public function actionCallback()
    {
        
        $json_string = file_get_contents('php://input');
        $jsonObj = json_decode($json_string); //�Ѻ JSON �� decode �� StdObj
        $to = $jsonObj->{"result"}[0]->{"content"}->{"from"}; //�Ҽ����
        $text = $jsonObj->{"result"}[0]->{"content"}->{"text"}; //�Ң�ͤ����������
        
        $text_ex = explode(':', $text); //��Ң�ͤ������¡ : ���� Array
        
        if($text_ex[0] == "��ҡ���"){ //��Ң�ͤ������ "��ҡ���" ���ӡ�ô֧�����Ũҡ Wikipedia �Ҩҡ�¡�͹
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
            
            if(empty($result_text)){//�����辺����Ҩҡ en
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
            if(empty($result_text)){//�Ҩҡ en ��辺��͡��� ��辺������ �ͺ��Ѻ�
                $result_text = '��辺������';
            }
            $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>$result_text];
            
        }else if($text_ex[0] == "�ҡ��"){//��Ҿ��������� �ҡ�� �����仴֧ API �ҡ wunderground ��
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
            }else{//�������͡Ѻ�ͺ��Ѻ�����辺������
                $result_text = '��辺������';
            }
            
            $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>$result_text];
        }else if($text == '�͡��'){//������ ����ͧ������ Bot �ͺ��Ѻ������ʤӹ���� ������� �͡�� ���ͺ��� �����Ѻ��
            $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>"�����Ѻ��"];
        }else{//�͡�������� ���ʴ�
            $response_format_text = ['contentType'=>1,"toType"=>1,"text"=>"���ʴ�"];
        }

        // toChannel?eventType
        $post_data = ["to"=>[$to],"toChannel"=>"1514503495","eventType"=>"138311608800106203","content"=>$response_format_text]; //�觢������
        
        $ch = curl_init("https://trialbot-api.line.me/v1/events");
		$headers = array('Content-Type: application/json', 'Authorization: Bearer ' . $access_token);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        
    }
}
echo "OK";
?>