<?php
/**
 * @package Wordpress_Connect_Discuz
 * @version 1.0
 */
/*
Plugin Name: Wordpress连接Discuz
Plugin URI: http://www.shanzhuoboshi.com/
Description: 在Wordpress和Discuz整合过程中，能够使两者的数据库互通，相互之间直接调用，并提供数据库的参数
Author URI: http://www.shanzhuoboshi.com/
*/


//yangwen 2012-10-16
register_activation_hook(__FILE__, 'wp_conn_dz_install');
$table_name = $wpdb->prefix . "user_card_setting";



//
function wp_conn_dz_install(){
	global $wpdb;

	$table_name = $wpdb->prefix . "user_card_setting";//如果放在外边则不能执行建表操作

	$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (".
	"`dz_db_host` varchar(255) NOT NULL default 'localhost',".
	"`dz_db_name` varchar(255) NOT NULL default 'testszbs',".
	"`dz_db_password` varchar(255) NOT NULL default '123456',".
	"`dz_db_user` varchar(255) NOT NULL default 'root',".
	"`dz_dir` varchar(255) NOT NULL default 'dz25',".
	"`dz_db_prefix` varchar(255) NOT NULL default 'pre_wp_');";

	require_once(ABSPATH . "wp-admin/includes/upgrade.php");

	dbDelta($sql);

	if($wpdb->get_var("SELECT count(*) FROM `" . $table_name . "`")==0){
		$wpdb->query("INSERT INTO `" . $table_name . "` (`dz_db_host`,`dz_db_name`,`dz_db_password`,`dz_db_user`,`dz_dir`,`dz_db_prefix`) VALUES ('localhost', 'testszbs','123456','root','dz25','pre_wp_');");
	}
	return ;

}//end func wp_conn_dz_install


//
function get_dz_datainfo(){
	global $wpdb,$table_name;

	if($wpdb->get_var("SELECT count(*) FROM `" . $table_name . "`")==1){
		//读取dz参数——数据表形式
		$dz_config_data = $wpdb->get_results("SELECT * FROM `" . $table_name . "`");
		$dz_db_host = $dz_config_data[0]->dz_db_host;
		$dz_db_user = $dz_config_data[0]->dz_db_user;
		$dz_db_password = $dz_config_data[0]->dz_db_password;
		$dz_db_name = $dz_config_data[0]->dz_db_name;
		//读取dz参数——数据表形式
		if(mysql_connect($dz_db_host,$dz_db_user,$dz_db_password,true)!=false){
			$wpdb_dz = new wpdb( $dz_db_user, $dz_db_password, $dz_db_name, $dz_db_host );
		}else{
			$wpdb_dz = false;
		}
	}else{
		$wpdb_dz = false;
	}

	return $wpdb_dz;

}//end func get_dz_datainfo

//
function get_dz_prefix(){
	global $wpdb,$table_name;

	if($wpdb->get_var("SELECT count(*) FROM `" . $table_name . "`")==1){
		$dz_prefix = $wpdb->get_var("SELECT dz_db_prefix FROM `" . $table_name . "`");
	}else{
		$dz_prefix = false;
	}
	return $dz_prefix;

}//end func get_dz_prefix



function wp_dz_connection_setting_page() {
	global $wpdb,$table_name;
	
    // 如果是POST，先更新数据库
	if($_POST['dz_config_settings']){
		$test_con = mysql_connect($_POST['dz_db_host'],$_POST['dz_db_user'],$_POST['dz_db_password'],true);
		if($test_con==false){
			echo '您输入的参数不正确，不能连接指定的主机';
		}elseif(mysql_select_db($_POST['dz_db_name'], $test_con)==false){
				echo '您输入的数据库'.$_POST['dz_db_name'].'不正确，不能连接指定的数据库'.$_POST['dz_db_name'];
		}elseif($wpdb->get_var("SELECT count(*) FROM `" . $table_name . "`")==0){
			$wpdb->insert($table_name,array('dz_db_name'=>$_POST['dz_db_name'],'dz_db_password'=>$_POST['dz_db_password'],'dz_db_user'=>$_POST['dz_db_user'],'dz_db_host'=>$_POST['dz_db_host'],'dz_dir'=>$_POST['dz_dir'],'dz_db_prefix'=>$_POST['dz_db_prefix']));
			echo '创建成功';
		}else{
			$wpdb->query("UPDATE ".$table_name." SET dz_db_name='".$_POST['dz_db_name']."',dz_db_password='".$_POST['dz_db_password']."',dz_db_user='".$_POST['dz_db_user']."',dz_db_host='".$_POST['dz_db_host']."',dz_dir='".$_POST['dz_dir']."',dz_db_prefix='".$_POST['dz_db_prefix']."'");
			echo '编辑成功';
		}
	}
    
    // 读取SQL里的DZ设置
	if($wpdb->get_var("SELECT count(*) FROM `" . $table_name . "`")==1){
		//读取dz参数——数据表形式
		$dz_config_data = $wpdb->get_results("SELECT * FROM `" . $table_name . "`");
		$dz_db_user = $dz_config_data[0]->dz_db_user;
		$dz_db_password = $dz_config_data[0]->dz_db_password;
		$dz_db_name = $dz_config_data[0]->dz_db_name;
		$dz_db_host = $dz_config_data[0]->dz_db_host;
		$dz_dir = $dz_config_data[0]->dz_dir;
		$dz_db_prefix = $dz_config_data[0]->dz_db_prefix;
		//读取dz参数——数据表形式
	}


  	
	

?>
	<div class="wrap">
	<h2>插件设置: </h2>
	<div class="postbox-container" style="width: 100%;" >
		<form name="wp-ajaxify-comments-settings-update" method="post" action="">
			<div id="poststuff">
				<div class="postbox">
					<h3 id="plugin-settings">插件设置</h3>
					<div class="inside">
						<table class="form-table">
							<tr><th scope="row">discuz数据库主机：</th><td><input type="text" name="dz_db_host" size="20" value="<?php echo $dz_db_host;?>"></td></tr>
							<tr><th scope="row">discuz数据库名称：</th><td><input type="text" name="dz_db_name" size="20" value="<?php echo $dz_db_name;?>"></td></tr>
							<tr><th scope="row">discuz数据库用户名：</th><td><input type="text" name="dz_db_user" size="20" value="<?php echo $dz_db_user;?>"></td></tr>
							<tr><th scope="row">discuz数据库密码：</th><td><input type="text" name="dz_db_password" size="20" value="<?php echo $dz_db_password;?>"></td></tr>
							<tr><th scope="row">discuz数据表前缀：</th><td><input type="text" name="dz_db_prefix" size="20" value="<?php echo $dz_db_prefix;?>"></td></tr>
							<tr><th scope="row">discuz所在目录：</th><td><input type="text" name="dz_dir" size="20" value="<?php echo $dz_dir;?>"></td></tr>
						</table>
						<p class="submit">
						  <input type="submit" name="dz_config_settings" class="button-primary" value="保存"/>
						</p>
					</div>
				</div>
			</div>

		</form>	
	
	</div>
<?php }  // end func wp_dz_connection_setting_page


function wp_admin_card() {
	add_options_page('user_dz_card', 'dz数据库设置', 'manage_options', 'user_dz_card', 'wp_dz_connection_setting_page');
}
add_action( 'admin_menu', 'wp_admin_card' );