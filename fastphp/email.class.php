<?php

/**
 * 邮件发送类
 * User: aupl
 * Date: 2017/6/15
 * Time: 8:38
 *
 *  $mail = new email();
 *  $result = $mail->sendEmail("xxxx@qq.com",$body);
 */
class email
{

    private $host;
    private $port;
    private $username;
    private $password;
    private $from;
    private $fromName;
    private $mail;

    public function __construct($config = array()){
        $_config = array(
            'host' => EMAIL_STMP,
            'port' => EMAIL_PORT,
            'username' => EMAIL_USER,
            'password' => EMAIL_PASSWORD,
            'from' => EMAIL_FROM,
            'fromName' => EMAIL_FROM_NAME,
        );

        $_config = array_merge($_config, $config);
        include FRAME_ROOT.'/vendor/phpmailer/PHPMailerAutoload.php';
        $this->mail     = new PHPMailer();
        $this->host     = $_config['host'];
        $this->port     = $_config['port'];
        $this->username = $_config['username'];
        $this->password = $_config['password'];
        $this->from     = $_config['from'];
        $this->fromName = $_config['fromName'];
    }

    /**
     * @param $mailTo          接收邮件人[可支持数组多条发送]
     * @param $body            邮件内容
     * @param string $subject  邮件标题
     * @param bool $debug      是否开启调试
     * @return bool            返回值 true/false
     */
    public function sendEmail($mailTo, $body, $subject = '', $debug = false){

        if ($debug) {
            $this->mail->SMTPDebug = 3;
        }

        $this->mail->setLanguage('zh_cn');
        $this->mail->isSMTP();
        $this->mail->Host     = $this->host;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->username;
        $this->mail->Password = $this->password;
        $this->mail->From     = $this->from;
        $this->mail->FromName = $this->fromName;

        if (is_array($mailTo)) {
            foreach ($mailTo as $item) {
                $this->mail->addAddress($item);
            }
        } else {
            $this->mail->addAddress($mailTo);
        }

        $this->mail->WordWrap = 50;
        $this->mail->isHTML(true);
        $this->mail->Subject  = empty($subject) ? '大唐天下邮件提醒' : $subject;
        $this->mail->Body     = $body;
        $this->mail->AltBody  = "这是一封HTML邮件，请用HTML方式浏览!";

        if (!$this->mail->send()) {
            if ($debug) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $this->mail->ErrorInfo;
                exit;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * 发送邮箱验证码
     * @param $email  邮箱号码
     * @param $time   验证码时间
     * @param $length 验证码长度
     * @return bool
     */
    public function sendCode($email,$time=600,$length=6){
        if (!F::isEmail($email)){
            return array('code'=>'1002','info'=>'邮箱格式错误!');
        }
        //防止频繁发送，间隔需要120秒(放在cache里)
        $cache = new cache();
        $cacheCode = $cache->get('validCode_'.md5($email));

        if (!$cacheCode) {   //如果此号码没有发送码记录，则set
            $code = getRandChar($length);
            $save = array(
                'code'  =>  $code,
                'ctime'  =>  time(),
                'errTimes'   => 0
            );
        }else {     //有缓存信息表示SMS_SENDINTERVAL时间内多次操作
            //判断是否频繁发生
            if(($cacheCode['ctime']+60) > time() ){
            //    $this->ajaxReturn(message::getJsonMsgStruct('1002',"验证码发送太频繁,请在".SMS_SENDINTERVAL."S后再次发送"),true);
                return array('code'=>'1002','info'=>'验证码发送过于频繁,请60S后再试!');
            }
            $code = getRandChar($length);
            $save = array(
                'code'  =>  $code,
                'ctime'  =>  time(),
                'errTimes'   => 0
            );
        }
        $emailContent=<<<EOF
<div style="background-color:#ECECEC; padding: 35px;">
<table cellpadding="0" align="center" style="width: 600px; margin: 0px auto; text-align: left; position: relative; border-top-left-radius: 5px; border-top-right-radius: 5px; border-bottom-right-radius: 5px; border-bottom-left-radius: 5px; font-size: 14px; font-family:微软雅黑, 黑体; line-height: 1.5; box-shadow: rgb(153, 153, 153) 0px 0px 5px; border-collapse: collapse; background-position: initial initial; background-repeat: initial initial;background:#fff;">
<tbody>
<tr>
<th valign="middle" style="height: 25px; line-height: 25px; padding: 15px 35px; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: #C46200; background-color: #FEA138; border-top-left-radius: 5px; border-top-right-radius: 5px; border-bottom-right-radius: 0px; border-bottom-left-radius: 0px;">
<font face="微软雅黑" size="5" style="color: rgb(255, 255, 255); ">找回密码邮箱验证码!</font>
</th>
</tr>
<tr>
<td>
<div style="padding:25px 35px 40px; background-color:#fff;">
<h2 style="margin: 5px 0px; "><font color="#333333" style="line-height: 20px; "><font style="line-height: 22px; " size="4">亲爱的 %s：</font></font></h2>
<p style="line-height:30px;">首先感谢您加入！
<!--请您在发表言论时，遵守当地法律法规。-->
<br>
您的验证码是:<b>%s</b> <br>有效期10分钟,过期失效请尽快验证!<br>

平台地址:<b>%s</b>
<!--如果您有什么疑问可以联系管理员，Email: {adminemail}。--></p>
<p align="right">%s 官方团队</p>
<p align="right">%s</p>
</div>
</td>
</tr>
</tbody>
</table>
</div>
EOF;
        $body=sprintf($emailContent,$email,$code,BASEURL,EMAIL_FROM_NAME,date('Y-m-d H:i'));
        $result =$this->sendEmail($email,$body,'大唐天下邮箱验证码');
        if ($result){
            $cache->set('validCode_'.md5($email), $save, $time);    //验证码保存10分钟
            $save['des']='发送了邮箱验证码';
            log::writeLogMongo(8000, 't_email', $email, $save);
            return array('code'=>'1001','info'=>'邮件验证码发送成功,请登录您邮箱查看!');
        }else{
            return array('code'=>'1002','info'=>'邮箱发送失败,请重试!');
        }
    }

    /**
     * 验证邮箱验证码
     * @param $email
     * @param $code
     * @return bool
     */
    public function verifyCode($email,$code){

        if (empty($email) ||empty($code)){
            return array('code'=>'1002','info'=>'邮箱或验证码为空,请刷新后重试!');
        }

        $cache =new cache();
        $cacheCode = $cache->get('validCode_'.md5($email));
        if ($cacheCode){
            $errTimes = isset($cacheInfo['errTimes'])?$cacheInfo['errTimes']:0;
            if ($errTimes+600>time()){
                $cache->del('validCode_'.md5($email));
                return array('code'=>'1002','info'=>'验证码超时,请重新获取!');
            }
            if ($cacheCode['code']!==$code){
                $num=$errTimes+1;

                //检查验证次数
                if($num >= 5 ){
                    $cache->del('validCode_'.md5($email)); //清除缓存
                    return array('code'=>'1002','info'=>'验证码错误超过5次，请重新发送!');
                }
                $save = array(
                    'code'      =>  $cacheInfo['code'],
                    'ctime'     =>  $cacheInfo['ctime'],
                    'errTimes'   => $num
                );
                $cache->set('validCode_'.md5($email), $save, 600);    //更新发送时间
                return array('code'=>'1002','info'=>'验证码错误,请核对后再试!');
            }else{
                $cache->del('validCode_'.md5($email));
                return array('code'=>'1001','info'=>'验证码正确!');
            }
        }else{
            return array('code'=>'1002','info'=>'验证码不存在,请重新获取!');
        }

    }

    /**
     * 查找邮箱是否存在
     * @param $email
     * @return bool
     */
    public function findEmail($email){
        if (!F::isEmail($email)){
            return false;
        }

        $db = new DBModel();
        $count = $db->count('t_user',["u_email"=>$email]);
        if ($count){
            return true;
        }else{
            return false;
        }

    }



}