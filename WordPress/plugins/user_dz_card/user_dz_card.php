<?php
/**
 * @package User_DZ_Card
 * @version 1.0
 */
/*
Plugin Name: 评论用户浮动名片显示
Plugin URI: http://www.shanzhuoboshi.com/
Description: 在Wordpress和Discuz整合过程中，WP的评论用户头像部分也和DZ一样，出现浮动框，此插件就是实现这种功能
Author URI: http://www.shanzhuoboshi.com/
*/

//得到wp的评论数
function getWPCommentCount($display_name){
	global $wpdb;

	$wpregstate = getWPregState($display_name);

	if( $wpregstate == false ){
        // WP显示名不存在
        
        // 得到评论数量，用户不存在（id=0），但作者是这个显示名
		$results_wp_num = $wpdb->get_results("SELECT count(*) as c_num FROM ".$wpdb->prefix."comments where user_id=0 and comment_author = '".$display_name."'");
	}else{
        // WP显示名已经被注册了
        
        // 从数据库读取相关的用户，然后统计评论数
		$results_wp = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."users where display_name='".$display_name."'");
		$results_wp_num = $wpdb->get_results("SELECT count(*) as c_num FROM ".$wpdb->prefix."comments where user_id='".$results_wp[0]->ID."'");
	}

	return $results_wp_num[0]->c_num;
}//end func getWPCommentCount






//得到显示名是否wp的注册用户的注册时间
function getRegisterDate($display_name){
	global $wpdb;

	$wpregstate = getWPregState($display_name);

	if( $wpregstate == false ){
        // WP显示名不存在
		return '未注册用户';
	}else{
		$results_wp = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."users where display_name='".$display_name."'");
		return $results_wp[0]->user_registered;
	}

}//end func getRegisterDate






//判断是否在discuz激活
function isDZUser($display_name){
	global $wpdb;

	if(!function_exists('get_dz_datainfo'))
		return false;

	$wpdb_dz = get_dz_datainfo();

	if($wpdb_dz == false)
		return false;

	$dz_db_prefix = function_exists('get_dz_prefix')?get_dz_prefix():'';
	$results_wp = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."users where display_name='".$display_name."'");
	$results = $wpdb_dz->get_results("SELECT * FROM ".$dz_db_prefix."common_member where username='".$results_wp[0]->user_login."'");

	if($results[0]->uid){
		return $results[0]->uid;
	}else{
		return false;
	}
}//end func isDZUser





//得到某用户在dz的信息数组
//$card数组的键值为中文的名称，值为对应的中文名称的值
//首先得到dz设置作者头像（名片）显示的选项，根据选项得到相应的值、
//
//比如：在discuz的后台设置作者头像（名片）显示的选项为：好友、...、专业
//
//则此函数输出$card = array('好友'=>'<a href = "xxx/yyy/zzz.php?id=aaa...">value</a>',...,'专业'=>'美术');
//链接地址在没有的时候为空字符''
function getDZInfoArray($uid){

	if(!function_exists('get_dz_datainfo'))
		return false;

	$wpdb_dz = get_dz_datainfo();

	if($wpdb_dz == false)
		return false;
	
	$card = array();
	
	$dz_db_prefix = function_exists('get_dz_prefix')?get_dz_prefix():'';
	$usernameinfo_setting_wp = get_dz_usernameinfo_setting($wpdb_dz,$dz_db_prefix);
	$dz_usernameinfo_key = get_dz_usernameinfo_key($wpdb_dz,$dz_db_prefix);
	$dz_usernameinfo_val = get_dz_usernameinfo_val($uid,$wpdb_dz,$dz_db_prefix);

	foreach ( $usernameinfo_setting_wp[0] as $key=>$value){
		if($value['menu'] ==1){
			$card[$dz_usernameinfo_key[$key]] = $dz_usernameinfo_val[$key];
		}
	}
	
	return $card;

} //end func getDZInfoArray

//得到某显示名是否在wp注册
function getWPregState($display_name){
	global $wpdb;

	$results_wp_display_num = $wpdb->get_results("SELECT count(*) as displaynum FROM ".$wpdb->prefix."users where display_name='".$display_name."'");
	$display_num = $results_wp_display_num[0] ->displaynum;

	if($display_num==0){
		return false;
	}else{
		return true;
	}
}//end func getWPregState


//读取discuz设置中，名片显示项的设置参数（哪些项显示，哪些项不显示）
//在discuz中，common_setting表中存放的数据，customauthorinfo项结构为
//在后台的《界面--界面设置--帖子内容页》中进行设置
//分别对显示顺序、帖子左侧、作者头像菜单等进行设置
//此处读取的是作者头像菜单中的设置
//比如：设置在作者头像（名片）显示用的“好友”信息，则此函数输出$usernameinfo_setting_wp[x] = 'friends';
function get_dz_usernameinfo_setting($wpdb_dz,$dz_db_prefix){
	if($wpdb_dz == false)
		return false;

    $usernameinfo_setting = array();
	$usernameinfo_setting_wp = array();

	$usernameinfo_setting_results = $wpdb_dz->get_results("SELECT * FROM ".$dz_db_prefix."common_setting where skey='customauthorinfo'");
	$usernameinfo_setting = unserialize($usernameinfo_setting_results[0]->svalue);


	foreach ( $usernameinfo_setting[0] as $key=>$value){
		$usernameinfo_setting_wp[str_replace("field_","",$key)] = $value['menu'];
	}
	return $usernameinfo_setting_wp;

}//end func get_dz_usernameinfo_setting

//得到名片显示的key所对应的中文名
//如：friends对应名片上的显示就是好友
//则此函数的输出为：$usernameinfo_keys['friends'] = '好友';
function get_dz_usernameinfo_key($wpdb_dz,$dz_db_prefix){

	if($wpdb_dz == false)
		return false;

	$member_key_results = $wpdb_dz->get_results("SELECT * FROM ".$dz_db_prefix."common_member_profile_setting where available=1" );

	$extcreditsinfo_results = $wpdb_dz->get_results("SELECT * FROM ".$dz_db_prefix."common_setting where skey='extcredits'");
	$extcreditsinfo = unserialize($extcreditsinfo_results[0]->svalue);

	$usernameinfo_keys = array(
		'uid'=>'UID',
		'friends'=>'好友',
		'doings'=>'记录',
		'blogs'=>'日志',
		'albums'=>'相册',
		'posts'=>'帖子',
		'threads'=>'主题',
		'sharings'=>'分享',
		'digest'=>'精华',
		'credits'=>'积分',
		'readperm'=>'阅读权限',
		'regtime'=>'注册时间',
		'lastdate'=>'最后登录',
		'oltime'=>'在线时间',
		'creditinfo'=>'信用度',
		'follower'=>'听众数',
		'following'=>'收听数',
	);

	foreach ( $member_key_results as $memberkey=>$membervalue){
		$usernameinfo_keys[$membervalue->fieldid] = $membervalue->title;
	}

	foreach ( $extcreditsinfo as $key=>$value){
		if($value['available']==1){
			$usernameinfo_keys['extcredits'.$key] = $value['title'];
		}
	}

	return $usernameinfo_keys;
}//end func get_dz_usernameinfo_key

//得到discuz的用户信息中的相对应$key的值以及链接地址
//比如某个用的好友数量为4，则此函数输出$dz_usernameinfo_val['friends'] = '<a href="url">value</a>');
function get_dz_usernameinfo_val($uid,$wpdb_dz,$dz_db_prefix){
	if($wpdb_dz == false)
		return false;

	$dz_dir = getDzdir();

	$count_value_results = $wpdb_dz->get_results("SELECT * FROM ".$dz_db_prefix."common_member_count where uid=".$uid );
	$member_value_results = $wpdb_dz->get_results("SELECT * FROM ".$dz_db_prefix."common_member_profile where uid=".$uid);

	foreach ( $count_value_results as $key=>$value){
		if($key =='oltime'){
			$dz_usernameinfo_val[$key] = $value[$key].'小时';
		}elseif($key =='creditinfo'){
			$dz_usernameinfo_val[$key] = '<a href="'.$dz_dir.'/home.php?mod=space&uid='.$uid.'&do=trade&view=eccredit#buyercredit">'.$value[$key].'</a>';
		}elseif($key =='friends'){
			$dz_usernameinfo_val[$key] = '<a href="'.$dz_dir.'/home.php?mod=space&uid='.$uid.'&do=friend&view=me&from=space">'.$value[$key].'</a>';
		}elseif($key =='doings'){
			$dz_usernameinfo_val[$key] = '<a href="'.$dz_dir.'/home.php?mod=space&uid='.$uid.'&do=doing&view=me&from=space">'.$value[$key].'</a>';
		}elseif($key =='blogs'){
			$dz_usernameinfo_val[$key] = '<a href="'.$dz_dir.'/home.php?mod=space&uid='.$uid.'&do=blog&view=me&from=space">'.$value[$key].'</a>';
		}elseif($key =='albums'){
			$dz_usernameinfo_val[$key] = '<a href="'.$dz_dir.'/home.php?mod=space&uid='.$uid.'&do=album&view=me&from=space">'.$value[$key].'</a>';
		}elseif($key =='sharings'){
			$dz_usernameinfo_val[$key] =  '<a href="'.$dz_dir.'/home.php?mod=space&uid='.$uid.'&do=share&view=me&from=space">'.$value[$key].'</a>';
		}elseif($key =='follower'){
			$dz_usernameinfo_val[$key] = '<a href="'.$dz_dir.'/home.php?mod=follow&do=follower&uid='.$uid.'">'.$value[$key].'</a>';
		}elseif($key =='following'){
			$dz_usernameinfo_val[$key] =  '<a href="'.$dz_dir.'/home.php?mod=follow&do=following&uid='.$uid.'>'.$value[$key].'</a>';
		}elseif($key =='posts'){
			$dz_usernameinfo_val[$key] =  '<a href="'.$dz_dir.'/home.php?mod=space&uid='.$uid.'&do=thread&type=reply&view=me&from=space">'.$value[$key].'</a>';
		}elseif($key =='posts'){
			$dz_usernameinfo_val[$key] =  '<a href="'.$dz_dir.'/home.php?mod=space&uid='.$uid.'&do=thread&type=reply&view=me&from=space">'.$value[$key].'</a>';
		}elseif($key =='threads'){
			$dz_usernameinfo_val[$key] =  '<a href="'.$dz_dir.'/home.php?mod=space&uid='.$uid.'&do=thread&type=thread&view=me&from=space">'.$value[$key].'</a>';
		}elseif($value[$key]!=''){
			$dz_usernameinfo_val[$key] = $value[$key];
		}

	}
	foreach ( $member_value_results as $key=>$value){
		if($key == 'birthday'){
			$dz_usernameinfo_val[$key] = $value['birthyear'].'年'.$value['birthmonth'].'月';
		}elseif($key == 'birthcity'){
			$dz_usernameinfo_val[$key] = $value['birthprovince'].$value['birthcity'].$value['birthdist'].$value['birthcommunity'];
		}elseif($key == 'residecity'){
			$dz_usernameinfo_val[$key] = $value['resideprovince'].$value['residecity'].$value['residedist'].$value['residecommunity'];
		}elseif($key == 'gender'){
			$dz_usernameinfo_val[$key] = $value['gender']=='0'?'保密':($value['gender']=='1'?'男':'女');
		}elseif($key == 'qq'){
			$dz_usernameinfo_val[$key] = '<a href="http://wpa.qq.com/msgrd?V=1&Uin='.$value['qq'].'&Site=shanzhuoboshi&Menu=yes" target="_blank" title="QQ"><img src="'.$dz_dir.'/static/image/common/connect_qq.gif" width="16" height="16" /></a>';
		}elseif($key == 'icq'){
			$dz_usernameinfo_val[$key] = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$value['icq'].'" target="_blank" title="icq"><img src="'.$dz_dir.'/static/image/common/icq.gif" width="16" height="16" /></a>';
		}elseif($key == 'yahoo'){
			$dz_usernameinfo_val[$key] = '<a href="http://edit.yahoo.com/config/send_webmesg?.target='.$value['yahoo'].'" target="_blank" title="QQ"><img src="'.$dz_dir.'/static/image/common/yahoo.gif" width="16" height="16" /></a>';
		}elseif($key == 'taobao'){
			$dz_usernameinfo_val[$key] = '<a href="javascript:;" onclick="window.open(\'http://amos.im.alisoft.com/msg.aw?v=2&uid=\'+encodeURIComponent(\''.$value['taobao'].'\')+\'&site=cntaobao&s=2&charset=utf-8\')" title="阿里旺旺'.$value['taobao'].'" target="_blank" title="taobao"><img src="'.$dz_dir.'/static/image/common/taobao.gif" width="16" height="16" /></a>';
		}elseif($key == 'site'){
			$dz_usernameinfo_val[$key] = '<a href="'.$value['site'].'" target="_blank" title="site"><img src="'.$dz_dir.'/static/image/common/forumlink.gif" width="16" height="16" /></a>';
		}else{
			$dz_usernameinfo_val[$key] = $value[$key];
		}
		
	}

	return $dz_usernameinfo_val;

}//end func get_dz_usernameinfo_val

//discuz在wordpress中的安装目录（相对地址）
function getDzdir(){
	global $wpdb;
	$dz_dir = $wpdb->get_var("SELECT dz_dir FROM `".$wpdb->prefix."user_card_setting`");
	$dz_dir = $dz_dir ? $dz_dir : '';
	
	return $dz_dir;
}//end func getDzdir
?>
