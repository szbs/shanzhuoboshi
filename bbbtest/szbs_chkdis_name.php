<?php
if($key=='field2'){
	if(DB::result_first("SELECT count(*) FROM ".DB::table('ucenter_members')." where username='".$value."'")>0||DB::result_first("SELECT count(*) FROM ".DB::table('common_member_profile')." where uid<>".$_G['uid']." and field2='".$value."'")>0||chk_wp_username($value)>0){
		profile_showerror('field2');
	}
}
//我在这里修改一下，重新下载下来之后的修改
//dump('dawsdsa');


?>