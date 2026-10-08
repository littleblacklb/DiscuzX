<?php
/**
 *      author: jzsjiale
 *      qq: 836333583
 *      e-mail: 836333583@qq.com
 *      dismall: https://addon.dismall.com/?@32563.developer
 *      createtime: 201907021510
 *      updatetime: 201907021652
 */
if (! defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once __DIR__ . '/vendor/autoload.php';

use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Captcha\V20190722\CaptchaClient;
use TencentCloud\Captcha\V20190722\Models\DescribeCaptchaResultRequest;

class CaptchaVerify
{

    public $secretId;
    public $secretKey;
    public $captchaAppId;
    public $appSecretKey;
    public $ticket;
    public $userIp;
    public $randstr;
    public $captchaType;

    public function __construct($secretId = "",$secretKey = "",$captchaAppId = "",$appSecretKey = "",$ticket = "",$userIp = "",$randstr = "",$captchaType = 9){
        $this->secretId = $secretId;
        $this->secretKey = $secretKey ;
        $this->captchaAppId = $captchaAppId ;
        $this->appSecretKey = $appSecretKey ;
        $this->ticket = $ticket ;
        $this->userIp = $userIp ;
        $this->randstr = $randstr ;
        $this->captchaType = $captchaType ;
    }

    function getCaptchaResult(){
        try {

            $cred = new Credential($this->secretId, $this->secretKey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("captcha.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new CaptchaClient($cred, "", $clientProfile);

            $req = new DescribeCaptchaResultRequest();

            $params = '{"CaptchaType":'.$this->captchaType.',"Ticket":"'.$this->ticket.'","UserIp":"'.$this->userIp.'","Randstr":"'.$this->randstr.'","CaptchaAppId":'.$this->captchaAppId.',"AppSecretKey":"'.$this->appSecretKey.'"}';
            $req->fromJsonString($params);


            $resp = $client->DescribeCaptchaResult($req);

            return $resp->toJsonString();
        }
        catch(TencentCloudSDKException $e) {
            echo $e;
        }
    }
}