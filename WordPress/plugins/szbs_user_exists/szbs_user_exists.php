<?php
/**
 * @package szbs_user_exists
 * @version 1.0
 */
/*
Plugin Name: 注册显示名与dz混合检查
Plugin URI: http://www.shanzhuoboshi.com/
Description: 注册显示名与dz混合检查
Author URI: http://www.shanzhuoboshi.com/
*/




/**
* szbs_user_exists()函数注释
* 该函数用于判断用户注册时是否在wp和dz中已经存在
* 用于szbs-login.php中
* 输入：$username  用户名
* 输出：存在为非null，不存在为null（分别用1、2、3、4代表wp用户名、wp昵称、dz用户名、dz昵称存在）
**/
function szbs_user_exists($username){
	global $wpdb;

	//wp用户名
	if ( $user = get_user_by('login', $username ) ) 
		return 1;

	//wp昵称
	$results_wp_num = $wpdb->get_results("SELECT count(*) as num FROM ".$wpdb->prefix."users where display_name='".$username."'");
	if($results_wp_num[0]->num > 0)
		return 2;

	//判断dz数据，若函数不存在则不检查dz用户和昵称
	if(function_exists('get_dz_datainfo')){
		$wpdb_dz = get_dz_datainfo();
		if($wpdb_dz == false)
			return false;
		$dz_db_prefix = function_exists('get_dz_prefix') ? get_dz_prefix() : 'pre_';

		//dz用户
		$membernum_results = $wpdb_dz->get_results("SELECT count(*) as num FROM ".$dz_db_prefix."common_member where username='".$username."'");
		if($membernum_results[0]->num > 0)
			return 3;
		
		$dz_info = get_dz_info();

		//dz昵称
		$displaynum_results = $wpdb_dz->get_results("SELECT count(*) as num FROM ".$dz_db_prefix."common_member_profile where ".$dz_info['nickfield']."='".$username."'");
		if($displaynum_results[0]->num > 0)
			return 4;
	}

	return null;
	
	
}//end func szbs_user_exists






add_filter( 'user_profile_update_errors', 'szbs_chk_display_name' );

/**
* szbs_chk_display_name()函数注释
* 该函数user_profile_update_errors()函数的过滤器，作用是在wp修改昵称或者显示名的时候同时修改dz的相应字段
* 用于user.php中
* 输入：&$errors, $update=null, &$user=null 同user_profile_update_errors()
* 输出：同user_profile_update_errors()
**/
function szbs_chk_display_name(&$errors, $update=null, &$user=null) {
	global $wpdb;

	if(wp_nickname_exists()||dz_nickname_exists()){
		$errors->add( 'user_login', __( '<strong>错误</strong>:昵称【'.$_POST['nickname'].'】重复，请填写其他的昵称.' ));
	}
	if(wp_display_name_exists()||dz_display_name_exists()){
		$errors->add( 'user_login', __( '<strong>错误</strong>:显示名【'.$_POST['display_name'].'】重复，请填写其他的显示名.' ));
	}

	$err_type = $errors->errors;

	if(count($err_type)==0)
		update_dz_nick();
}
// end func szbs_chk_display_name






//判断wp昵称是否存在
function wp_nickname_exists(){
	global $wpdb,$_POST;

	//检查输入的昵称和wp中的用户名是否重复
	$temp_num = $wpdb->get_results("SELECT count(*) as num FROM ".$wpdb->prefix."users where ID<>".$_POST['user_id']." and user_login='".$_POST['nickname']."'");
	if($temp_num[0]->num > 0)
		return true;

	//检查输入的昵称和wp中的昵称是否重复
	$temp_num = $wpdb->get_results("SELECT count(*) as num FROM ".$wpdb->prefix."usermeta where user_id<>".$_POST['user_id']." and meta_key='nickname' and meta_value='".$_POST['nickname']."'");
	if($temp_num[0]->num > 0)
		return true;


	//检查输入的昵称和wp中的显示名是否重复
	$temp_num =$wpdb->get_results("SELECT count(*) as num FROM ".$wpdb->prefix."users where ID<>".$_POST['user_id']." and display_name='".$_POST['nickname']."'");
	if($temp_num[0]->num > 0)
		return true;

	return false;
}//end func wp_nickname_exists





//判断wp显示名检查
function wp_display_name_exists(){
	global $wpdb,$_POST;

	//检查选择的显示名和wp中的用户名是否重复
	$temp_num = $wpdb->get_results("SELECT count(*) as num FROM ".$wpdb->prefix."users where ID<>".$_POST['user_id']." and user_login='".$_POST['display_name']."'");
	if($temp_num[0]->num > 0)
		return true;
	
	//检查选择的显示名和wp中的昵称是否重复
	$temp_num = $wpdb->get_results("SELECT count(*) as num FROM ".$wpdb->prefix."usermeta where user_id<>".$_POST['user_id']." and meta_key='nickname' and meta_value='".$_POST['display_name']."'");
	if($temp_num[0]->num > 0)
		return true;

	//检查选择的显示名和wp中的显示名是否重复
	$temp_num = $wpdb->get_results("SELECT count(*) as num FROM ".$wpdb->prefix."users where ID<>".$_POST['user_id']." and display_name='".$_POST['display_name']."'");
	if($temp_num[0]->num > 0)
		return true;

	return false;
}//end func wp_display_name_exists




//判断dz昵称是否存在
function dz_nickname_exists(){
	global $wpdb,$_POST;

	if(!function_exists('get_dz_datainfo'))
		return false;
	
	//得到dz数据信息
	$wpdb_dz = get_dz_datainfo();
	if($wpdb_dz == false)
		return false;
	$dz_db_prefix = function_exists('get_dz_prefix') ? get_dz_prefix() : 'pre_';

	$dz_info = get_dz_info();

	//输入的昵称和dz的用户名比较
	$temp_num = $wpdb_dz->get_results("SELECT count(*) as num FROM ".$dz_db_prefix."common_member where username<>'".$dz_info['wp_username']."' and username='".$_POST['nickname']."'");
	if($temp_num[0]->num>0)
		return true;

	//输入的昵称和dz的昵称比较
	$temp_num = $wpdb_dz->get_results("SELECT count(*) as num FROM ".$dz_db_prefix."common_member_profile where uid<>'".$dz_info['dz_uid']."' and ".$dz_info['nickfield']."='".$_POST['nickname']."'");
	if($temp_num[0]->num>0)
		return true;

	return false;

}//end func dz_nickname_exists




//判断dz显示名是否存在
function dz_display_name_exists(){
	global $wpdb,$_POST;

	if(!function_exists('get_dz_datainfo'))
		return false;

	//得到dz数据信息
	$wpdb_dz = get_dz_datainfo();
	if($wpdb_dz == false)
		return false;
	$dz_db_prefix = function_exists('get_dz_prefix') ? get_dz_prefix() : 'pre_';

	$dz_info = get_dz_info();

	//输入的显示名和dz的用户名比较
	$temp_num = $wpdb_dz->get_results("SELECT count(*) as num FROM ".$dz_db_prefix."common_member where username<>'".$dz_info['wp_username']."' and username='".$_POST['display_name']."'");
	if($temp_num->num>0)
		return true;

	//输入的显示名和dz的昵称比较
	$temp_num = $wpdb_dz->get_results("SELECT count(*) as num FROM ".$dz_db_prefix."common_member_profile where uid<>'".$dz_info['dz_uid']."' and ".$dz_info['nickfield']."='".$_POST['display_name']."'");
	if($temp_num->num>0)
		return true;
	
	return false;
}//end func dz_display_name_exists




//
function get_dz_info(){
	global $wpdb,$_POST;

	if(!function_exists('get_dz_datainfo'))
		return false;

	$dz_info = array();
 
	//得到wp的用户名
	$results_wp_username = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."users where ID=".$_POST['user_id']."");
	$dz_info['wp_username'] = $results_wp_username[0]->user_login;

	//得到dz数据信息
	$wpdb_dz = get_dz_datainfo();
	if($wpdb_dz == false)
		return false;
	$dz_db_prefix = function_exists('get_dz_prefix') ? get_dz_prefix() : 'pre_';

	//dz用户资料中昵称的字段
	$nickfield_results = $wpdb_dz->get_results("SELECT fieldid  FROM ".$dz_db_prefix."common_member_profile_setting where title='昵称'");
	$dz_info['nickfield'] = $nickfield_results ? $nickfield_results[0] -> fieldid : 'field2';

	//得到dz用户名的id
	$dz_uid_results = $wpdb_dz->get_results("SELECT uid FROM ".$dz_db_prefix."common_member where username='".$dz_info['wp_username']."'");
	$dz_info['dz_uid'] = $dz_uid_results[0]->uid;

	return $dz_info;

}//end func get_dz_info





//
function update_dz_nick(){

	if(!function_exists('get_dz_datainfo'))
		return false;

	//得到dz数据信息
	$wpdb_dz = get_dz_datainfo();
	if($wpdb_dz == false)
		return false;
	$dz_db_prefix = function_exists('get_dz_prefix') ? get_dz_prefix() : 'pre_';

	$dz_info = get_dz_info();

	if($dz_info['dz_uid']>0)//等于0为没有激活
		$wpdb_dz->update($dz_db_prefix."common_member_profile",array($dz_info['nickfield']=>$_POST['display_name']),array('uid'=>$dz_info['dz_uid']));

	return $dz_info['dz_uid'];
}//end func update_dz_nick
?>
