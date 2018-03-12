<?php
// 接到微信服务器发送过来的四个参数
$signature=$_GET['signature'];//微信加密签名
$timestamp=$_GET['timestamp'];//时间戳
$nonce=$_GET['nonce'];// 随机数
$echostr=$_GET['echostr'];// 随机字符串
// 记录日志并且查看参数  这步只能在服务器上运行  新浪sae会报错
// logger("signature=".$signature."|timestamp=".$timestamp."|nonce=".$nonce."|echostr=".$echostr);
// 定义token
$token="mxw";
// 字典序排序
$arr=array($token,$nonce,$timestamp);
sort($arr,SORT_STRING);
$str=implode($arr);
// sha1加密
$str=sha1($str);
if($str==$signature){
	// 若连接成功且为验证时返回echostr
	if($echostr){
		echo $echostr;
	}else{
		// 不返回echostr则证明运行业务逻辑
		receive();
	}    
}
function receive(){
	//接到post方式发送的xml文件  
	$postXml=$GLOBALS['HTTP_RAW_POST_DATA'];
	logger($postXml);
	//将xml数据转换为对象格式
	$postObj=simplexml_load_string($postXml);
	// 提取消息类型
	$type=$postObj->MsgType;
	switch ($type) {
		case 'text':
			echo responsetext($postObj,$postObj->Content);
			break;
		case 'image':
			echo responseImg($postObj);
			break;
		case 'voice':
			responsetext($postObj);	
			break;
		case 'video':
			echo receiveVideo($postObj);
			break;
		case 'shortvideo':
			echo textback($postObj,'微信没给用户开通权限。。。');
			break;
// 		case 'location':
// 			// 获取经纬度
// 			$ly=$postObj->Location_Y;
// 			$lx=$postObj->Location_X;
// 			$jd=$postObj->Label;
// 			$xml = "<xml>
//             		<ToUserName><![CDATA[".$postObj->FromUserName."]]></ToUserName>
//             		<FromUserName><![CDATA[".$postObj->ToUserName."]]></FromUserName>
//             		<CreateTime>".time()."</CreateTime>
//             		<MsgType><![CDATA[location]]></MsgType>
//             		<Location_X>".$lx."</Location_X>
//             		<Location_Y>".$ly."</Location_Y>
//             		<Label><![CDATA[".$jd."]]></Label>
//             		<MsgId>".$postObj->MsgId."</MsgId>
//             		</xml>";
// 		    echo  $xml;
// // 		    echo textback($postObj,$jd);
// 			break;	
        case 'event':
            echo responseEvent($postObj);
            break;
		case 'news':
			// 事件类型消息
			echo textback($postObj,'222');
			break;	
// 		default:
// 			echo textback($postObj,"这是未知消息");
// 			break;
	}
}

function responseEvent($postObj){
    $event = $postObj->Event;
    switch ($event) {
        case 'subscribe':
            if ($postObj->EventKey){//扫码关注
                if ($postObj->EventKey == 'qrscene_306'){//扫码关注成功
                    echo textback($postObj,'初次关注。。。');
                }
            }else {
                echo textback($postObj,'正常关注。。。');
            }
            break;
        case 'SCAN':
            echo textback($postObj,'已关注后，扫码进来。。。');
            break;
        case 'LOCATION':
            $x = $postObj->Latitude;
            $y = $postObj->Longitude;
            $jd=$postObj->Precision;
            $msg = $postObj->FromUserName.','.$postObj->ToUserName.'经度：'.$y.'，纬度：'.$x.',精度：'.$jd;
            echo textback($postObj,$msg);
            break;
        case 'CLICK':
            $xml = "<xml>
                    <ToUserName><![CDATA[".$postObj->FromUserName."]]></ToUserName>
                    <FromUserName><![CDATA[".$postObj->ToUserName."]]></FromUserName>
                    <CreateTime>".time()."</CreateTime>
                    <MsgType><![CDATA[event]]></MsgType>
                    <Event><![CDATA[CLICK]]></Event>
                    <EventKey><![CDATA[EVENTKEY]]></EventKey>
                    </xml>";
            echo $xml;
            break;
    }
}


function wangyan($postObj,$content){
    $url = "http://www.tuling123.com/openapi/api";
    $data = array(
        'key'=>'cd0d20d93678451ab7a19cea1ab3b77a',
        'info'=>$content,
        'userid'=>$postObj->FromUserName,
    );
    $jsonarr = json_encode($data);
    $arr = https_request($url,$jsonarr,1);
    $arrs = json_decode($arr,true);
    return  textback($postObj,$arrs['text']);
}
// 文本回复函数
function textback($postObj,$content){
	$xml="<xml>
	<ToUserName><![CDATA[".$postObj->FromUserName."]]></ToUserName>
	<FromUserName><![CDATA[".$postObj->ToUserName."]]></FromUserName>
	<CreateTime>".time()."</CreateTime>
	<MsgType><![CDATA[text]]></MsgType>
	<Content><![CDATA[".$content."]]></Content>
	</xml>";
	return $xml;
}


function responseImg($postObj){
	$mediaid=$postObj->MediaId;
	$xml="<xml>
		<ToUserName><![CDATA[".$postObj->FromUserName."]]></ToUserName>
		<FromUserName><![CDATA[".$postObj->ToUserName."]]></FromUserName>
		<CreateTime>".time()."</CreateTime>
		<MsgType><![CDATA[image]]></MsgType>
		<Image>
		<MediaId><![CDATA[".$mediaid."]]></MediaId>
		</Image>
		</xml>";
	return $xml;			
}




//单图文消息的回复函数
function otwback($postObj,$title,$description,$picurl,$url){
	$xml="<xml>
	<ToUserName><![CDATA[".$postObj->FromUserName."]]></ToUserName>
	<FromUserName><![CDATA[".$postObj->ToUserName."]]></FromUserName>
	<CreateTime>".time()."</CreateTime>
	<MsgType><![CDATA[news]]></MsgType>
	<ArticleCount>1</ArticleCount>
	<Articles>
	<item>
	<Title><![CDATA[".$title."]]></Title>
	<Description><![CDATA[".$description."]]></Description>
	<PicUrl><![CDATA[".$picurl."]]></PicUrl>
	<Url><![CDATA[".$url."]]></Url>
	</item>
	</Articles>
	</xml>";
	return $xml;
}
// 文本和语音消息回复的函数
function responsetext($postObj){
	// 判断消息时语音还是文字
	if($postObj->Content){
		$content=trim($postObj->Content);
	}else{
		$content=trim($postObj->Recognition);
	}
	//判断用户输入内容是否含有“天气”一词
	if (strstr($content,'天气')){
	    if (substr_count($content,'天气') != 0 && $content != '天气'){
// 	        $con = '查询天气情况';
            //处理用户输入的字符串，取出城市相关词语
	        $total = substr_count($content,'天气');
	        $carr = explode("天气", $content);
	        $city = '';
	        for ($i=0;$i<=$total;$i++){
	            if ($carr[$i] != ''){
	                $city = $carr[$i];
	            }
	            break;
	        }
	        if ($city != ''){
// 	            $con = $city;
	            echo weather($postObj,$city);
	        }else {
	            $con = '请输入要查询的城市+天气...';
	        }
	    }else {
	        $con = '请输入要查询的城市+天气';
	    }
	}else {
	    $url="http://www.tuling123.com/openapi/api";
	    $data = array(
	        'key'       =>'cd0d20d93678451ab7a19cea1ab3b77a',
	        'info'      =>$content,
	        'userid'    =>$postObj->FromUserName,
	    );
	    $json=json_encode($data);
	    $js=https_request($url,$json,1);
	    $arr=json_decode($js,true);
	    $con = $arr['text'];
	}
	echo textback($postObj,$con);
}

















// // 事件类型的消息处理
// function responseevent($postObj){
// 	// 先提取事件类型
// 	$event=$postObj->Event;
// 	if($event=="subscribe"){
// 		if($postObj->EventKey=="qrscene_123"){
// 			echo textback($postObj,"你关注了我，然后又扫了我");
// 		}else{
// 			echo textback($postObj,"欢迎订阅，么么哒");
// 		}		
// 	}elseif($event=="LOCATION"){
// 		$lx=$postObj->Longitude;
// 		$ly=$postObj->Latitude;
// 		echo textback($postObj,"您所在的经度为".$lx."您所在的纬度为".$ly);
// 	}elseif($event=="SCAN"){
// 		if($postObj->EventKey=="123"){
// 			echo textback($postObj,"扫描成功,么么哒");
// 		}
// 	}elseif ($event=="CLICK") {
// 		if($postObj->EventKey=="v1_m"){
// 			$xml="<xml>
// 			<ToUserName><![CDATA[".$postObj->FromUserName."]]></ToUserName>
// 			<FromUserName><![CDATA[".$postObj->ToUserName."]]></FromUserName>
// 			<CreateTime>".time()."</CreateTime>
// 			<MsgType><![CDATA[music]]></MsgType>
// 			<Music>
// 			<Title><![CDATA[理想]]></Title>
// 			<Description><![CDATA[赵雷]]></Description>
// 			<MusicUrl><![CDATA[http://dingphp.top/lx.mp3]]></MusicUrl>
// 			<HQMusicUrl><![CDATA[http://dingphp.top/lx.mp3]]></HQMusicUrl>
// 			<ThumbMediaId><![CDATA[Rg_7JK5HCWBsYWRKDglbqWFkguq4do5RBPHUtUii0VM]]></ThumbMediaId>
// 			</Music>
// 			</xml>";
// 			echo $xml;
// 		}elseif($postObj->EventKey=="v2_2_zan"){
// 			echo textback($postObj,"谢谢点赞，么么哒");
// 		}
// 	}
// }
// 回复天气预报图文消息
function weather($postObj,$city){
	$url="http://api.map.baidu.com/telematics/v3/weather?location=".$city."&output=json&ak=qtrAGmPtP8PpECbY6rhRrBIk7mODvAbG";
	$json=https_request($url);
	$arr=json_decode($json,true);
	if($arr['error']===0){
		// 图文消息的个数
		$count=count($arr['results'][0]['weather_data'])+1;
		$xml="<xml>
		<ToUserName><![CDATA[".$postObj->FromUserName."]]></ToUserName>
		<FromUserName><![CDATA[".$postObj->ToUserName."]]></FromUserName>
		<CreateTime>".time()."</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>".$count."</ArticleCount>
		<Articles>
		<item>
		<Title><![CDATA[".$arr['results'][0]['currentCity']."的天气情况]]></Title> 
		</item>";
		foreach ($arr['results'][0]['weather_data'] as $v) {
			$xml.="<item>
			<Title><![CDATA[".$v['date'].$v['weather'].$v['wind'].$v['temperature']."]]></Title>
			<PicUrl><![CDATA[".$v['dayPictureUrl']."]]></PicUrl>
			<Url><![CDATA[http://www.david0306.cn/weathers.php?city=".$city."]]></Url>
			</item>";
		}
		$xml.="</Articles>
		</xml>";
		return $xml;
	}else{
		return textback($postObj,"未查询到该城市天气信息");
	}	
}








function logger($Content=''){
    //记录日志
    $myfile=fopen("log.txt","a")or die("can't open");
    fwrite($myfile,$Content."\r\n--------------------------------\r\n");
    fclose($myfile);
}

function https_request($url,$data=null,$tl=null){
	// 初始化一个 cURL 对象
	$curl = curl_init();
	// 设置你需要抓取的URL
	curl_setopt($curl, CURLOPT_URL,$url);
	//必须加这个，不加不好使（不多加解释，东西太多了）
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//对认证证书进行检验
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);	
	if (!empty($data)){//post方式，否则是get方式
		//设置模拟post方式
		curl_setopt($curl,CURLOPT_POST,1);
		//传数据，get方式是直接在地址栏传的，这是post传参的解决方式
		curl_setopt($curl,CURLOPT_POSTFIELDS,$data);//$data可以是数组，json
	}
	if (!empty($tl)){//post方式，否则是get方式
		// 图灵机器人要设置头格式
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			"Content-type:text/html;charset=UTF-8",)
		);
	}	
	// 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。1是保存，0是输出
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	// 运行cURL，请求网页
	$output = curl_exec($curl);	
	// 关闭URL请求
	curl_close($curl);	
	return $output;
}