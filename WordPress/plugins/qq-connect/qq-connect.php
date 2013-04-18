<?php
/*
Plugin Name: 腾讯连接
Author:  Denis
Author URI: http://fairyfish.net/
Plugin URI: http://fairyfish.net/2010/12/20/qq-connect/
Description: 使用腾讯微博瓣账号登陆你的 WordPress 博客，并且留言使用腾讯微博的头像，博主可以同步日志到腾讯微博，用户可以同步留言到腾讯微博。
Version: 2.2
*/
$qq_consumer_key = 'b7720f12ccef4164a9013b2edd899e6f';
$qq_consumer_secret = '670c717e9a331d367c93bf39567352db';
$sc_loaded = false;

add_action('init', 'qc_init');
function qc_init(){
	if (session_id() == "") {
		session_start();
	}
	if(!is_user_logged_in()) {
			
        if(isset($_GET['oauth_token'])){
			if(isset($_SESSION["qq_oauth_token_secret"])&&$_SESSION["qq_oauth_token_secret"]!=false){
				qc_confirm();
			}
        } 
    } 
}

add_action("wp_head", "qc_wp_head");
add_action("admin_head", "qc_wp_head");
add_action("login_head", "qc_wp_head");
function qc_wp_head(){
    if(is_user_logged_in()) {
        if(isset($_GET['oauth_token'])){
			echo '<script type="text/javascript">window.opener.qc_reload("");window.close();</script>';
        }
	}
}

add_action('comment_form', 'qq_connect');
add_action("login_form", "qq_connect");
add_action("register_form", "qq_connect",12);
function qq_connect($id=""){
	global $qc_loaded;
	if($qc_loaded) {
		return;
	}
	
	if(is_user_logged_in()&&!is_admin()){
		global $user_ID;
		$qcdata = get_user_meta($user_ID, 'qcdata',true);
		
		if($qcdata){
?>
	<p id="qc_connect" class="qc_button"><label for="post_2_qq_t">同步到腾讯微博</label><input name="post_2_qq_t" type="checkbox" id="post_2_qq_t" value="1" style="width:30px;"  /></p>
<?php	
		}
		return;
	}

	$qc_url = WP_PLUGIN_URL.'/'.dirname(plugin_basename (__FILE__));
	
?>
	<script type="text/javascript">
    function qc_reload(){
       var url=location.href;
       var temp = url.split("#");
       url = temp[0];
       url += "#qc_button";
       location.href = url;
       location.reload();
    }
    </script>	
	<style type="text/css"> 
	.qc_button img{ border:none;}
    </style>
	<p id="qc_connect" class="qc_button">
	<img onclick='window.open("<?php echo $qc_url; ?>/qq-start.php", "dcWindow","width=800,height=600,left=150,top=100,qcrollbar=no,resize=no");return false;' src="<?php echo $qc_url; ?>/qq_button.png" alt="使用腾讯微博登陆" style="cursor: pointer; margin-right: 20px;" />
	</p>
<?php
    $qc_loaded = true;
}
//yangwen 12-09-27
add_filter("get_avatar", "qc_get_avatar",10,4);
function qc_get_avatar($avatar, $id_or_email='',$size='32') {
	global $comment;
	if(is_object($comment)) {
		$id_or_email = $comment->user_id;
	}
	if (is_object($id_or_email)){
		$id_or_email = $id_or_email->user_id;
	}
	if($qcid = get_usermeta($id_or_email, 'qcid')){
		$out = $qcid.'/100';
		$avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
		return $avatar;
	}else {
		return $avatar;
	}
}

function qc_confirm(){
    global $qq_consumer_key, $qq_consumer_secret;
	
	if(!class_exists('qqOAuth')){
		include dirname(__FILE__).'/qqOAuth.php';
	}
	
	$to = new qqOAuth($qq_consumer_key, $qq_consumer_secret, $_GET['oauth_token'],$_SESSION['qq_oauth_token_secret']);
	
	$_SESSION['qq_oauth_token_secret'] = false;
	
	$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);

	$to = new qqOAuth($qq_consumer_key, $qq_consumer_secret, $tok['oauth_token'], $tok['oauth_token_secret']);

	$qqInfo = $to->OAuthRequest('http://open.t.qq.com/api/user/info?format=json', 'GET',array());

	if($qqInfo == "no auth"){
		echo '<script type="text/javascript">window.close();</script>';
		return;
	}
	
	$qqInfo = json_decode($qqInfo);
	
	$qqInfo = $qqInfo ->data;
		
	qc_login($qqInfo->head.'|'.$qqInfo->name.'|'.$qqInfo->nick.'|'.$tok['oauth_token'] .'|'.$tok['oauth_token_secret']); 
}

function qc_login($Userinfo) {
	$userinfo = explode('|',$Userinfo);
	if(count($userinfo) < 5) {
		wp_die("An error occurred while trying to contact qq Connect.");
	}

	$userdata = array(
		'user_pass' => wp_generate_password(),
		'user_login' => $userinfo[1],
		'display_name' => $userinfo[2],
		'user_email' => $userinfo[1].'@t.qq.com'
	);

	if(!function_exists('wp_insert_user')){
		include_once( ABSPATH . WPINC . '/registration.php' );
	} 
  
	$wpuid = get_user_by_login($userinfo[1]);
	
	if(!$wpuid){
		if($userinfo[0]){
			$wpuid = wp_insert_user($userdata);
		
			if($wpuid){
				update_usermeta($wpuid, 'qcid', $userinfo[0]);
				$qc_array = array (
					"oauth_access_token" => $userinfo[3],
					"oauth_access_token_secret" => $userinfo[4],
				);
				update_usermeta($wpuid, 'qcdata', $qc_array);
			}
		}
	} else {
		update_usermeta($wpuid, 'qcid', $userinfo[0]);
		$qc_array = array (
			"oauth_access_token" => $userinfo[3],
			"oauth_access_token_secret" => $userinfo[4],
		);
		update_usermeta($wpuid, 'qcdata', $qc_array);
	}
  
	if($wpuid) {
		wp_set_auth_cookie($wpuid, true, false);
		wp_set_current_user($wpuid);
	}
}

if(!function_exists('get_user_by_meta')){

	function get_user_by_meta($meta_key, $meta_value) {
	  global $wpdb;
	  $sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s' AND meta_value = '%s'";
	  return $wpdb->get_var($wpdb->prepare($sql, $meta_key, $meta_value));
	}
	
	function get_user_by_login($user_login) {
	  global $wpdb;
	  $sql = "SELECT ID FROM $wpdb->users WHERE user_login = '%s'";
	  return $wpdb->get_var($wpdb->prepare($sql, $user_login));
	}
}

if(!function_exists('connect_login_form_login')){
	add_action("login_form_login", "connect_login_form_login");
	add_action("login_form_register", "connect_login_form_login");
	function connect_login_form_login(){
		if(is_user_logged_in()){
			$redirect_to = admin_url('profile.php');
			wp_safe_redirect($redirect_to);
		}
	}
}

add_action('comment_post', 'qc_comment_post',1000);
function qc_comment_post($id){
	$comment_post_id = $_POST['comment_post_ID'];
	
	if(!$comment_post_id){
		return;
	}
	$current_comment = get_comment($id);
	$current_post = get_post($comment_post_id);
	$qcdata = get_user_meta($current_comment->user_id, 'qcdata',true);

	
	if($qcdata){
		if($_POST['post_2_qq_t']){
			if(!class_exists('qqOAuth')){
				include dirname(__FILE__).'/qqOAuth.php';
			}
			
			global $qq_consumer_key, $qq_consumer_secret;
			$to = new qqOAuth($qq_consumer_key, $qq_consumer_secret,$qcdata['oauth_access_token'], $qcdata['oauth_access_token_secret']);
			$status = $current_comment->comment_content. ' '.get_comment_link($id);			
			$resp = $to->OAuthRequest('http://open.t.qq.com/api/t/add','POST',array('format'=>'json','clientip'=>my_get_ip(),'content'=>$status));			
		}
	}
}

function my_get_ip(){
	if(getenv('HTTP_CLIENT_IP')) { 
	$onlineip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR')) { 
	$onlineip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR')) { 
	$onlineip = getenv('REMOTE_ADDR');
	} else { 
	$onlineip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
	}
}

add_action('admin_menu', 'qc_options_add_page');

function qc_options_add_page() {
	add_options_page('同步到腾讯微博', '同步到腾讯微博', 'manage_options', 'qc_options', 'qc_options_do_page');
}

function qc_options_do_page() {
?>
	<div class="wrap">
		<h2>同步到腾讯微博</h2>
        <?php
		if($_POST["sync_qq_t_submit"]){
			
			$message = '腾讯微博同步成功';
	
			$sync_qq_t_saved = get_option("sync_qq_t");
			$sync_qq_t = trim($_POST['sync_qq_t']);
			
			if ($sync_qq_t_saved != $sync_qq_t)
				if(!update_option("sync_qq_t",$sync_qq_t))
					$message = "更新失败";
		
			echo '<div id="message" class="updated fade"><p>'.$message.'.</p></div>';
			
			
		}
		$sync_qq_t = get_option("sync_qq_t");
		?>
		<form method="post" action="<?php menu_page_url('qc_options',true)?>">
        	<p>请先使用要绑定的腾讯微博帐号登录。然后推出再使用管理员帐号登录并在下面输入框中输入绑定的腾讯微博的用户名（不是 QQ 号，如：<a href="http://t.qq.com/denishua/">denishua</a>）。</p>
            <p>有问题请在腾讯微博上咨询 <a href="http://t.qq.com/denishua/">denishua</a>。</p>
            <p><label for="sync_qq_t">输入绑定的腾讯微博的用户名:</label> <input name="sync_qq_t" type="text" id="sync_qq_t"  value="<?php echo $sync_qq_t; ?>" /></p>
            <p class="submit"><input type="submit" value="<?php _e("Save changes");?>" name="sync_qq_t_submit" class="button-primary" /></p>
        </form>
	</div>
	<?php
}

function update_qq_t($status=null,$qq_t_userid){
	$qcdata =  get_user_meta($qq_t_userid, 'qcdata',true);
	
	if(!class_exists('qqOAuth')){
		include dirname(__FILE__).'/qqOAuth.php';
	}
	global $qq_consumer_key, $qq_consumer_secret;
	$to = new qqOAuth($qq_consumer_key, $qq_consumer_secret,$qcdata['oauth_access_token'], $qcdata['oauth_access_token_secret']);
	$resp = $to->OAuthRequest('http://open.t.qq.com/api/t/add','POST',array('format'=>'json','clientip'=>my_get_ip(),'content'=>$status));	
}

add_action('publish_post', 'publish_post_2_qq_t', 0);
function publish_post_2_qq_t($post_ID){
	$qq_t_userid = get_user_by_login(get_option("sync_qq_t"));
	
	if(!$qq_t_userid) return;
	
	$c_post = get_post($post_ID);
	
	$status = $c_post->post_title.' '.get_permalink($post_ID);
	update_qq_t($status,$qq_t_userid);
	add_post_meta($post_ID, 'qq_t', 'true', true);
}

if(!function_exists('wpjam_modify_dashboard_widgets')){
	
	add_action('wp_dashboard_setup', 'wpjam_modify_dashboard_widgets' );
	function wpjam_modify_dashboard_widgets() {
		global $wp_meta_boxes;
		
		wp_add_dashboard_widget('wpjam_dashboard_widget', '我爱水煮鱼', 'wpjam_dashboard_widget_function');
	}
	
	function wpjam_dashboard_widget_function() {?>
		<p><a href="http://wpjam.com/&amp;utm_medium=wp-plugin&amp;utm_campaign=wp-plugin&amp;utm_source=<?php bloginfo('home');?>" title="WordPress JAM" target="_blank"><img src="http://wpjam.com/wp-content/themes/WPJ-Parent/images/logo_index_1.png" alt="WordPress JAM"></a><br />
        <a href="http://wpjam.com/&amp;utm_medium=wp-plugin&amp;utm_campaign=wp-plugin&amp;utm_source=<?php bloginfo('home');?>" title="WordPress JAM" target="_blank"> WordPress JAM</a> 是中国最好的 WordPress 二次开发团队，我们精通 WordPress，可以制作 WordPress 主题，开发 WordPress 插件，WordPress 整站优化。</p>
        <hr />
	<?php 
		echo '<div class="rss-widget">';
		wp_widget_rss_output('http://feed.fairyfish.net/', array( 'show_author' => 0, 'show_date' => 1, 'show_summary' => 0 ));
		echo "</div>";
	}
}