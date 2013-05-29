<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
require_once dirname(__FILE__).'/data/function/szbs.func.php';

class plugin_szbs {
	function plugin_szbs() {
		return;
	}
	function common() {
		global $_G;
		global $navtitle;
		global $nobbname;
		file_list();
		//szbs_init();

		//var_dump(CURMODULE);
		$wp_cookie = $_COOKIE;
		foreach ( $wp_cookie as $ckey=>$cvalue){
			//var_dump();
			if(strpos($ckey,'wordpress_logged_in_')!==false){
				$wp_username = substr($cvalue,0,strpos($cvalue,'|'));
				//var_dump($wp_username);
			}
		}
		return ;
	}

	//
	//测试
	function global_cpnav_extra1() {
		global $_G;
		$_G['member']['username'] = get_displayname($_G['member']['username']);
		return ;
	}//end func
	//
	function global_header() {

	}//end func
	//
	function  global_footer(){
		if(!file_exists(DISCUZ_ROOT.'source/plugin/szbs/szbs/'.CURSCRIPT.'_'.CURMODULE.'.php')) {
			file_put_contents(DISCUZ_ROOT.'source/plugin/szbs/szbs/'.CURSCRIPT.'_'.CURMODULE.'.php',"<?php\n/**\n*\t[szbs!] (C)2012-2099 szbs Inc.\n*\tThis is NOT a freeware, use is subject to license terms\n*\n*\t\$Id: ".CURSCRIPT."_".CURMODULE.".php ".date('Y-m-d H:i:s')." YangWen \$\n*/\n\nif(!defined('IN_DISCUZ')) {\n\t	exit('Access Denied');\n}\n\$return = CURSCRIPT.'-'.CURMODULE;\n\n?>");
		}
		require_once DISCUZ_ROOT.'source/plugin/szbs/szbs/'.CURSCRIPT.'_'.CURMODULE.'.php';
		return $return;
	}


function decode($str){
 preg_match_all("/(\d{2,5})/", $str,$a);//匹配所有长度为2到5个的数字
    $a = $a[0];
 $utf='';
    foreach ($a as $dec){
        if ($dec < 128){
            $utf .= chr($dec);
        }else if ($dec < 2048){
            $utf .= chr(192 + (($dec - ($dec % 64)) / 64));
            $utf .= chr(128 + ($dec % 64));
        }else{
            $utf .= chr(224 + (($dec - ($dec % 4096)) / 4096));
            $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
            $utf .= chr(128 + ($dec % 64));
        }
    }
    return $utf;
 }

function encode($c){
 $res='';
    $len = strlen($c);
    $a = 0;
    while($a < $len){
        $ud = 0;
        if (ord($c{$a}) >=0 && ord($c{$a})<=127){
            $ud = ord($c{$a});
            $a += 1;
        }else if (ord($c{$a}) >=192 && ord($c{$a})<=223){
            $ud = (ord($c{$a})-192)*64 + (ord($c{$a+1})-128);
            $a += 2;
        }else if (ord($c{$a}) >=224 && ord($c{$a})<=239){
            $ud = (ord($c{$a})-224)*4096 + (ord($c{$a+1})-128)*64 + (ord($c{$a+2})-128);
            $a += 3;
        }else if (ord($c{$a}) >=240 && ord($c{$a})<=247){
            $ud = (ord($c{$a})-240)*262144 + (ord($c{$a+1})-128)*4096 + (ord($c{$a+2})-128)*64 + (ord($c{$a+3})-128);
            $a += 4;
        }else if (ord($c{$a}) >=248 && ord($c{$a})<=251){
            $ud = (ord($c{$a})-248)*16777216 + (ord($c{$a+1})-128)*262144 + (ord($c{$a+2})-128)*4096 + (ord($c{$a+3})-128)*64 + (ord($c{$a+4})-128);
            $a += 5;
        }else if (ord($c{$a}) >=252 && ord($c{$a})<=253){
            $ud = (ord($c{$a})-252)*1073741824 + (ord($c{$a+1})-128)*16777216 + (ord($c{$a+2})-128)*262144 + (ord($c{$a+3})-128)*4096 + (ord($c{$a+4})-128)*64 + (ord($c{$a+5})-128);
            $a += 6;
        }else if (ord($c{$a}) >=254 && ord($c{$a})<=255){
            $ud = false;
        }
        $res .= "$ud";
    }
    return $res;
 }



}


class plugin_szbs_home extends plugin_szbs {
	function  spacecp_profile_top(){
		return;

	}//end func
	function  spacecp_profile_top_output(){
		global $_G;

		global $settings;
		global $htmls;
		global $profilegroup;
		$settings['sheying']['available']= 1;
		$settings['sheying']['title']= '摄影器材';
		$settings['sheying']['required']= 1;
		//$settings['shexiang']['title']= '摄影器材';
		$htmls['sheying'] = '<input type="text" name=""><input type="radio" name="">啊记得结啊';


		$opactives['sheying'] = ' class="a"';
		$profilegroup['sheying']['available'] = 1;
		$profilegroup['sheying']['title'] = '摄影器材';
		$profilegroup['shebei']['available'] = 1;
		$profilegroup['shebei']['title'] = '设备';

		$display_name = DB::result_first("SELECT field2 FROM ".DB::table('common_member_profile')." where uid='".$_G['uid']."'");
		$htmls['field2'] = '<input type="text" id="field2" name="field2" class="px" value="'.$display_name.'" tabindex="1" onblur="ajaxget(\'forum.php?mod=ajax&inajax=yes&infloat=register&handlekey=register&ajaxmenu=1&action=checkdisplayname&displayname=\'+ (BROWSER.ie && document.charset == \'utf-8\' ? encodeURIComponent(field2.value) : field2.value), \'showerror_field2\', \'showerror_field2\');" /><div class="rq mtn" id="showerror_field2"></div><p class="d"></p>';



		return;
	}//end func
	//
	function space_menu_extra_output(){
		global $_G,$space;
		$_G['member']['username'] = get_displayname($_G['member']['username']);
		var_dump('dad');
		return;
	}//end func
	function  space_profile_baseinfo_top(){
		return;

	}//end func


}//end class


//
class plugin_szbs_forum extends plugin_szbs{
	//
	function index_status_extra_output(){
		global $_G,$whosonline,$forum,$forumlist;
		$_G['cache']['userstats']['newsetuser'] = get_displayname($_G['cache']['userstats']['newsetuser']);
		$forum['lastpost']['author'] = get_displayname($forum['lastpost']['author']);
		foreach ($whosonline as $k=>$v){
			$whosonline[$k]['username'] =  get_displayname($v['username']);
		}
		foreach ($forumlist as $k=>$v ){
			$forumlist[$k]['lastpost']['author'] =  get_quhtml($v['lastpost']['author']);
		}
		return;
	}//end func
	function  forumdisplay_filter_extra_output(){
		global $_G;
		foreach ($_G['forum_threadlist'] as $k=>$v ){
			$_G['forum_threadlist'][$k]['author'] =  get_displayname($v['author']);
			$_G['forum_threadlist'][$k]['lastposter'] =  get_displayname($v['lastposter']);
		}
	}
	function viewthread_avatar_output(){
		global $_G,$postlist;
		foreach ($postlist as $k=>$v){
			$postlist[$k]['author'] =  get_displayname($v['author']);
		}
	}

}//end class


class plugin_szbs_member extends plugin_szbs  {
	//

	//
	function register_top(){
		global $htmls;
		return;

	}//end func
	function register_top_output() {
		global $_G;
		//if($_G['xueba_setting']['reg_yindao']==1) {
			//return '<div class="hm wx" ><a href="plugin.php?id=yiqixueba:yindao">注册向导</a></div>';
		//}

		//dump($_G['cache']['fields_register']);
		return;
	}//end func
	//
	function register_input_output() {
		global $_G;
		//dump($_G['cache']['fields_register']);
		//dump('djashd');
		return ;
	}//end func
}//end class

?>