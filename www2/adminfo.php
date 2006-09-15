<?php
	require("www2-admin.php");

    if(!($currentuser["userlevel"] & BBS_PERM_ACCOUNTS))
        admin_deny();

    admin_header("改别人资料", "修改使用者资料");
    if(isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = $currentuser["userid"];
    
?>
<form method="post" action="adminfo.php" class="medium">
<fieldset><legend>要修改的用户ID</legend><div class="inputs">
<label>ID:</label><input type="text" name="userid" value="<?php print($userid); ?>" size="12" maxlength="12">
<input type="submit" value="确定">
</div></fieldset></form>
<?php
    $userinfo = array();
    $uid = bbs_admin_getuserinfo($userid, $userinfo);
    if($uid == -1)
        html_error_quit("无法初始化数组。");
    if($uid > 0) {
?>
<form method="post" action="adminfo.php" class="medium">
<fieldset><legend>个人资料</legend><div class="inputs">
<label>帐号:</label><?php echo $userinfo["userid"];?><br/>
<label>昵称:</label><input type="text" name="username" value="<?php echo htmlspecialchars($userinfo["username"],ENT_QUOTES);?>" size="24" maxlength="39"><br/>
<label>真实姓名:</label><input type="text" name="realname" value="<?php echo $userinfo["realname"];?>" size="16" maxlength="39"><br/>
<label>居住地址:</label><input type="text" name="address" value="<?php echo $userinfo["address"];?>" size="40" maxlength="79"><br/>
<label>电子信箱:</label><input type="text" name="email" value="<?php echo $userinfo["email"];?>" size="40" maxlength="79"><br/>
<label>性别:</label><input type="radio" name="gender" value='M'<?php echo ($userinfo["gender"]==77)?" checked":""; ?>>男 <input type="radio" name="gender" value="F"<?php echo ($userinfo["gender"]==77)?"":" checked"; ?>>女<br />
<label>生日:</label><input type="text" name="birthyear" value="<?php echo $userinfo["birthyear"]+1900; ?>" size="4" maxlength="4"> 年 <input type="text" name="birthmonth" value="<?php echo $userinfo["birthmonth"]; ?>" size="2" maxlength="2"> 月 <input type="text" name="birthday" value="<?php echo $userinfo["birthday"]; ?>" size="2" maxlength="2"> 日<br/>
<label>当前职务:</label><input type="text" name="title" value="<?php echo $userinfo["title"];?>" size="15" maxlength="254"><br/>
<label>真实Email:</label><input type="text" name="realemail" value="<?php echo $userinfo["realemail"];?>" size="40" maxlength="79"><br/>
<label>上站次数:</label><input type="text" name="numlogins" value="<?php echo $userinfo["numlogins"];?>" size="6" maxlength="7"><br/>
<label>发表大作:</label><input type="text" name="numposts" value="<?php echo $userinfo["numposts"];?>" size="6" maxlength="7"><br/>
<label>注册时间:</label><?php echo date("D M j H:i:s Y",$userinfo["firstlogin"]);?> <input type="checkbox" name="firstlogin" value="yes">提前1分钟<br/>
<label>最近光临:</label><?php echo date("D M j H:i:s Y",$userinfo["lastlogin"]);?> <input type="checkbox" name="lastlogin" value="yes">设为今天<br/>
<?php if (isset($userinfo["score_user"])) { ?>
<label>用户积分:</label><?php echo $userinfo["score_user"];?><br/>
<?php } ?>
</div></fieldset>
<div class="oper">
<input type="submit" name="submit" value="确定" /> <input type="reset" value="复原" />
</div>
</form>
<?php
    }
	page_footer();
?>
