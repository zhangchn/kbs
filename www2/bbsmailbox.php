<?php
	/*this file display mailbox to user	windinsn nov 7,2003*/
	require("funcs.php");
login_init();
	bbs_session_modify_user_mode(BBS_MODE_MAIL);
	
	if ($loginok != 1)
		html_nologin();
	else
	{
		html_init("gb2312","","",1);
		if(!strcmp($currentuser["userid"],"guest"))
			html_error_quit("guest û���Լ�������!");
		
		if (isset($_GET["path"])){
			$mail_path = $_GET["path"];
			$mail_title = $_GET["title"];
		}
		else {
			$mail_path = ".DIR";    //default is .DIR
			$mail_title = "�ռ���";
		}
		if (isset($_GET["start"]))
			$start = $_GET["start"];
		else
			$start = 999999;   //default*/
                if (strstr($mail_path,'..'))
			html_error_quit("��ȡ�ʼ�����ʧ��!");
		$mail_fullpath = bbs_setmailfile($currentuser["userid"],$mail_path);
		$mail_num = bbs_getmailnum2($mail_fullpath);
		if($mail_num < 0 || $mail_num > 30000)
			html_error_quit("Too many mails!");
		$num = 19;
		if ($start > $mail_num - 19)
			$start = $mail_num - 19;
       		 if ($start < 0)
		{
			$start = 0;
			if ($num > $mail_num) $num = $mail_num;
		}
		$maildata = bbs_getmails($mail_fullpath,$start,$num);
		if ($maildata == FALSE)
			html_error_quit("��ȡ�ʼ�����ʧ��!");

		//system mailboxs
		$mail_box = array(".DIR",".SENT",".DELETED");
		$mail_boxtitle = array("�ռ���","������","������");
		//$mail_boxnums = array(bbs_getmailnum2(bbs_setmailfile($currentuser["userid"],".DIR")),bbs_getmailnum2(bbs_setmailfile($currentuser["userid"],".SENT")),bbs_getmailnum2(bbs_setmailfile($currentuser["userid"],".DELETED")));
		//custom mailboxs
		$mail_cusbox = bbs_loadmaillist($currentuser["userid"]);
		//$totle_mails = $mail_boxnums[0]+$mail_boxnums[1]+$mail_boxnums[2];
		$i = 2;
		if ($mail_cusbox != -1){
			foreach ($mail_cusbox as $mailbox){
				$i++;
				$mail_box[$i] = $mailbox["pathname"];
				$mail_boxtitle[$i] = $mailbox["boxname"];
				//$mail_boxnums[$i] = bbs_getmailnum2(bbs_setmailfile($currentuser["userid"],$mailbox["pathname"]));
				//$totle_mails+= $mail_boxnums[$i];
				}
			}
		$mailboxnum = $i + 1;
		$mail_title_encode = rawurlencode($mail_title);
?>
<script type="text/javascript">
<!--
function checkall(form)  {
  for (var i=0;i<form.elements.length;i++)    {
    var e = form.elements[i];
    if (e.name != 'chkall')       e.checked = form.chkall.checked; 
   }
  }
function bbsconfirm(url,infor){
	if(confirm(infor)){
		window.location.href=url;
		return true;
		}
	return false;
}
-->
</script>
<body topmargin="0">
<p align="left" class="b2">
<a href="bbssec.php" class="b2"><?php echo BBS_FULL_NAME; ?></a>
-
<a href="bbsmail.php">
<?php echo $currentuser["userid"]; ?>������
</a></p>
<center>
<table border="0" width="750" cellspacing="0" cellpadding="0">
	<tr>
	<td align="center" valign="middle" background="images/m1.gif" width="80" height="26">
	<a href="bbspstmail.php" class="mb1">д�ʼ�</a>
	</td>
<?php
	$current_i = 0;
	for($i=0;$i<$mailboxnum;$i++){
		if($mail_path==$mail_box[$i]&&$mail_title==$mail_boxtitle[$i]){
			$current_i = $i;
?>
<td align="center" valign="middle" background="images/m2.gif" width="80" height="26" class="mb2">
<?php echo htmlspecialchars($mail_boxtitle[$i]); ?>
</td>
<?php			
			}
		else{
?>
<td align="center" valign="middle" background="images/m1.gif" width="80" height="26">
<a href="bbsmailbox.php?path=<?php echo $mail_box[$i];?>&title=<?php echo urlencode($mail_boxtitle[$i]);?>" class="mb1"><?php echo htmlspecialchars($mail_boxtitle[$i]); ?></a>
</td>
<?php		
			}
		}
?>
		<td width="<?php echo (int)(670-80*$mailboxnum);	?>"><img src="images/empty.gif"></td>
	</tr>
	<tr>
		<td colspan="<?php echo $current_i + 1;	?>" align="left" background="images/m10.gif"><img src="images/m11.gif" align="top"></td>
		<td background="images/m6.gif"><img src="images/empty.gif"></td>
		<td colspan="<?php echo $mailboxnum - $current_i ;	?>" align="right" background="images/m10.gif"><img src="images/m12.gif" align="top"></td>
	</tr>
	<tr>
		<td height=200 colspan="<?php echo $mailboxnum+2;	?>">
		<table width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td width="7" background="images/m3.gif"><img src="images/empty.gif"></td>
				<td background="images/m6.gif" height="400" align="center" valign="top">
<?php				
	/*mail list start*/					
?>
<p align="center" class="b9">
���� <font class="b3"><?php echo $mail_title; ?></font> �ﹲ�� <font class="b3"><?php echo $mail_num; ?></font> ���ʼ�
[<a href="bbsmail.php" class="b9">���������б�</a>]
</p>
<form action="bbsmailact.php?act=move&<?php echo "dir=".urlencode($mail_path)."&title=".$mail_title_encode; ?>" method="POST">
<table width="95%" cellspacing="0" cellpadding="5" class="mt1">
	<tr>
		<td class="mt2" width="30">�Ѷ�</td>
		<td class="mt2" width="30">ѡ��</td>
		<td class="mt2" width="30">���</td>
		<td class="mt2" width="30">״̬</td>
		<td class="mt2" width="100"><?php echo ($mail_path==".SENT"?"������":"������"); ?></td>
		<td class="mt2">����</td>
		<td class="mt2" width="100">ʱ��</td>
		<td class="mt2" width="50">��С</td>
		<td class="mt2" width="40">ɾ��</td>
	</tr>
<?php
	if($mail_num == 0)
	{
?>
<tr><td colspan="8" align="center"><font color="#999999">�ļ�����Ŀǰû���ʼ�</font></td></tr>
<?php
	}
	else
	{
		for ($i = 0; $i < $num; $i++)
		{
?>
<tr>
	<td class="mt3">
	<?php 
		if(stristr($maildata[$i]["FLAGS"],"N"))
			echo "<img src='images/nmail.gif' alt='δ���ʼ�' border='0'>";
		else
			echo "<img src='images/omail.gif' alt='�Ѷ��ʼ�' border='0'>";
	?>
	</td>
	<td class="mt4">
	<input type="checkbox" name="file<?php echo $i; ?>" value="<?php echo $maildata[$i]["FILENAME"]	?>">
	</td>
	<td class="mt3"><?php echo $start+$i+1;?></td>
	<td class="mt4"><nobr>&nbsp;<?php echo $maildata[$i]["FLAGS"]; if ($maildata[$i]["ATTACHPOS"]>0) echo "<font color='red'>@</font>"; ?>&nbsp;</nobr></td>
	<td class="mt3"><a href="bbsqry.php?userid=<?php echo $maildata[$i]["OWNER"];?>"><?php echo $maildata[$i]["OWNER"];?></a></td>
	<td class="mt5">&nbsp;<a href="bbsmailcon.php?dir=<?php echo $mail_path;?>&num=<?php echo $i+$start;?>&title=<?php echo $mail_title_encode;?>"><?php
if(strncmp($maildata[$i]["TITLE"],"Re: ",4))
	echo "��" .  htmlspecialchars($maildata[$i]["TITLE"]);
else
    echo htmlspecialchars($maildata[$i]["TITLE"]);
?> </a></td>
	<td class="mt3"><?php echo strftime("%b&nbsp;%e&nbsp;%H&nbsp;:%M",$maildata[$i]["POSTTIME"]);?></td>
      <td class="mt3" style="text-align:right;padding-right:10pt;"><?php echo sizestring($maildata[$i]['EFFSIZE']); ?></td>
	<td class="mt4"><input type="button" name="del" value="ɾ��" class="bt1" onclick="bbsconfirm('bbsmailact.php?act=del&<?php echo "dir=".urlencode($mail_path)."&file=".urlencode($maildata[$i]["FILENAME"])."&title=".$mail_title_encode; ?>','ȷ��ɾ�����ʼ���?')"></td>
</tr>
<?php
		}
	}
?>
</table>
<table cellpadding="3" cellspacing="0" width="95%" border="0" class="b9">
<tr><td class="b9">
<input type="button" value="�����ʼ�" class="bt1" onclick="window.location.href='bbspstmail.php'">
<input onclick="checkall(this.form)" type="checkbox" value="on" name="chkall" align="absmiddle">
ѡ�б�ҳ�����ʼ�
<?php
/*
<input type="submit" value="�Ƶ�" class="bt1">
<select name="object" id="object" class="bt2">
<?php
	for($i=0;$i<$mailboxnum;$i++){
		if($mail_path==$mail_box[$i]&&$mail_title==$mail_boxtitle[$i])
			continue;
		echo "<option value='".urlencode($mail_boxtitle[$i])."'>".htmlspecialchars($mail_boxtitle[$i])."</option>";
		}
?>
</select>
*/
?>
<input type="hidden" name="act2" value="delarea">
<input type="submit" value="ɾ����ѡ�ʼ�" class="bt1" onclick="if(confirm('ɾ��ѡ�е��ʼ���?')){submit();return true;}return false;">
</td>
<td class="b9" align="right">
<?php
		if ($start > 0)
		{
			$i = $start - 19;
			if ($i < 0)$i = 0;
			echo "<a href=\"bbsmailbox.php?path=$mail_path&start=0&title=$mail_title_encode\">��һҳ</a> ";
			echo "<a href=\"bbsmailbox.php?path=$mail_path&start=$i&title=$mail_title_encode\">��һҳ</a> ";
		}
		if ($start < $mail_num - 19)
		{
			$i = $start + 19;
			if ($i > $mail_num -1)$i = $mail_num -1;
			echo "<a href=\"bbsmailbox.php?path=$mail_path&start=$i&title=$mail_title_encode\">��һҳ</a> ";
			echo "<a href=\"bbsmailbox.php?path=$mail_path&title=$mail_title_encode\">���һҳ</a> ";
		}
?>
[<a href="bbsmail.php" class="b9">���������б�</a>]
&nbsp;&nbsp;</td></tr></table></form>
<table cellpadding="3" cellspacing="0" width="95%" border="0" class="b9">
<form action="bbsdelmail.php?<?php echo "dir=".urlencode($mail_path)."&title=".$mail_title_encode; ?>" method="POST">
<input type="hidden" name="dir" value="<?php echo $mail_path; ?>">
<input type="hidden" name="title" value="<?php echo $mail_title_encode; ?>">
<tr><td class="b9">
����ɾ����
��ʼ���
<input type="text" size="3" class="b9" name="dstart">
�������
<input type="text" size="3" class="b9" name="dend">
ɾ�����ͣ�
<input type="radio" class="b9" name="dtype" value="0" checked>��ͨ
<input type="radio" class="b9" name="dtype" value="1">ǿ��&nbsp;&nbsp;
<input type="submit" value="����ɾ���ʼ�" class="bt1"/>
</td></tr>
</form></table>
<?php				
				
				
	/*mail list end*/			
?>				
				</td>
				<td width="7" background="images/m4.gif"><img src="images/empty.gif"></td>
			</tr>
		
		</table>
		</td>
	<tr>
		
		<td colspan="<?php echo $mailboxnum+2;	?>">
		<table width="100%" cellspacing="0" cellpadding="0"><tr>
			<td width="9" height="26"><img src="images/m7.gif"></td>
			<td background="images/m5.gif" height="26"><img src="images/empty.gif"></td>
			<td width="9" height="26"><img src="images/m8.gif"></td>
		</tr></table>
		</td>
	</tr>
</table><br>
</center>
<?php
	html_normal_quit();
	}
?>