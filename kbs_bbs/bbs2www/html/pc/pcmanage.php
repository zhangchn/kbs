<?php
	/*
	**manage personal corp.
	**@id: windinsn Nov 19,2003	
	*/
	/*
	**	对收藏夹的剪切、复制操作需要 session 支持 windinsn nov 25,2003
	*/
	require("pcfuncs.php");
	$favaction = $_COOKIE["BLOGFAVACTION"];
	
					
	if ($loginok != 1)
		html_nologin();
	elseif(!strcmp($currentuser["userid"],"guest"))
	{
		html_init("gb2312");
		html_error_quit("guest 没有Blog!");
		exit();
	}
	else
	{
		$link = pc_db_connect();
		$pc = pc_load_infor($link,$_GET["userid"]);
		
		if(!$pc)
		{
			pc_db_close($link);
			html_error_quit("对不起，您要查看的Blog不存在");
			exit();
		}
		
		if(!pc_is_admin($currentuser,$pc))
		{
			pc_db_close($link);
			html_error_quit("对不起，您要查看的Blog不存在");
			exit();
		}
		
		if($pc["EDITOR"] != 0)
			$pcconfig["EDITORALERT"] = NULL;
			
		$act = $_GET["act"]?$_GET["act"]:$_POST["act"];
		
		if(($act == "post" || $act == "edit") && !$_POST["subject"] && $pc["EDITOR"] == 0)
			pc_html_init("gb2312",stripslashes($pc["NAME"]),"","","",TRUE);
		else
			pc_html_init("gb2312",stripslashes($pc["NAME"]));
		
		if($act == "cut" || $act == "copy")
		{
			$access = intval($_POST["access"]);
			if(stristr($_POST["target"],'T'))
			{
				$target = intval(substr($_POST["target"],1,strlen($_POST["target"])-1));
				$in_section = 1;
				if(!pc_load_topic($link,$pc["UID"],$target,$topicname))
					$target = 0; //如果参数错误就移入未分类
			}
			else
			{
				$target = intval($_POST["target"]);
				$in_section = 0;
				if($target < 0 || $target > 4 )
					$target = 2;//如果参数错误先移入私人区
			}
			
			
			if(!$in_section && 3 == $target ) //跨区  移入收藏区
			{
				$rootpid = pc_fav_rootpid($link,$pc["UID"]);
				if($rootpid)
				{
					html_error_quit("收藏夹根目录错误!");
					exit();
				}
			}
			else
				$rootpid = 0;
			
			if($in_section)
			{
				if($act == "cut")
					$query = "UPDATE nodes SET created = created , `tid` = '".$target."' , `changed` = NOW( ) , `pid` = '0' WHERE `uid` = '".$pc["UID"]."' AND `type` = 0 AND ( `nid` = '0' ";
				else
					$query = "SELECT * FROM nodes WHERE `uid` = '".$pc["UID"]."' AND `type` = 0 AND ( `nid` = '0' ";
			}
			else
			{
				if($act == "cut" && $target == 3)
					$query = "UPDATE nodes SET created = created , `access` = '".$target."' , `changed` = '".date("YmdHis")."' , `pid` = '".$rootpid."', `tid` = 0 WHERE `uid` = '".$pc["UID"]."' AND ( `nid` = '0' ";
				elseif($act == "cut")
					$query = "UPDATE nodes SET created = created , `access` = '".$target."' , `changed` = '".date("YmdHis")."' , `pid` = '0' , `tid` = 0 WHERE `uid` = '".$pc["UID"]."' AND `type` = 0  AND ( `nid` = '0' ";
				else
					$query = "SELECT * FROM nodes WHERE `uid` = '".$pc["UID"]."' AND `type` = 0 AND ( `nid` = '0' ";
			}
				
			$j = 0;
			for($i = 1 ;$i < $pc["NLIM"]+1 ; $i ++)
			{
				if($_POST["art".$i])
				{
					$query .= " OR `nid` = '".(int)($_POST["art".$i])."' ";
					$j ++;
				}
			}
			$query .= " ) ";
			
			if($act == "cut")
			$query .= " AND nodetype = 0 ";
			//nodetype != 0的是公有blog的log文件
			
			if($in_section)
			{
				if("cut" == $act)
				{
					mysql_query($query,$link);
				}
				else
				{
					$result = mysql_query($query,$link);
					$num_rows = mysql_num_rows($result);
					$j = $num_rows;
					if(pc_used_space($link,$pc["UID"],$access)+$num_rows > $pc["NLIM"])
					{
						html_error_quit("目标区域文章数超过上限 (".$pc["NLIM"]." 篇)!");
						exit();
					}
					for($i = 0;$i < $num_rows ; $i ++)
					{
						/*	目前复制文章的时候评论不同步复制	*/
						$rows = mysql_fetch_array($result);
						$query = "INSERT INTO `nodes` ( `pid` , `tid` , `type` , `source` , `hostname` , `changed` , `created` , `uid` , `comment` , `commentcount` , `subject` , `body` , `access` , `visitcount` ,`htmltag`)  ".
							" VALUES ('0','".$target."' , '0', '".addslashes($rows[source])."', '".addslashes($rows[hostname])."','NOW( )' , '".$rows[created]."', '".$pc["UID"]."', '".$rows[comment]."', '0', '".addslashes($rows[subject])."', '".addslashes($rows[body])."', '".$access."', '0','".$rows[htmltag]."');";
						mysql_query($query,$link);
					}
					if($access == 0)
						pc_update_record($link,$pc["UID"]," + ".$j);
				}
			}
			else
			{
				if($act == "cut")
				{
					if(pc_used_space($link,$pc["UID"],$target)+$j > $pc["NLIM"])
					{
						html_error_quit("目标区域文章数超过上限 (".$pc["NLIM"]." 篇)!");
						exit();
					}
					else
					{
						mysql_query($query,$link);
					}
				}
				else
				{
					$result = mysql_query($query,$link);
					$num_rows = mysql_num_rows($result);
					$j = $num_rows;
					
					if(pc_used_space($link,$pc["UID"],$target)+$num_rows > $pc["NLIM"])
					{
						html_error_quit("目标区域文章数超过上限 (".$pc["NLIM"]." 篇)!");
						exit();
					}
					for($i = 0;$i < $num_rows ; $i ++)
					{
						/*	目前复制文章的时候评论不同步复制	*/
						$rows = mysql_fetch_array($result);
						$query = "INSERT INTO `nodes` ( `pid` , `tid` , `type` , `source` , `hostname` , `changed` , `created` , `uid` , `comment` , `commentcount` , `subject` , `body` , `access` , `visitcount` ,`htmltag`)  ".
							" VALUES ('".$rootpid."','0' , '0', '".addslashes($rows[source])."', '".addslashes($rows[hostname])."',NOW( ) , '".$rows[created]."', '".$pc["UID"]."', '".$rows[comment]."', '0', '".addslashes($rows[subject])."', '".addslashes($rows[body])."', '".$target."', '0','".$rows[htmltag]."');";
						mysql_query($query,$link);
					}
				}	
				if($access == 0 && $act == "cut")
					pc_update_record($link,$pc["UID"]," - ".$j);
				if($target == 0)
					pc_update_record($link,$pc["UID"]," + ".$j);
			}
			$log_action = "CUT/COPY NODE";
?>
<p align="center">
<a href="javascript:history.go(-1);">操作成功,点击返回</a>
</p>
<?php
		}
		elseif($act == "post")
		{
			if($_POST["subject"])
			{
				$ret = pc_add_node($link,$pc,$_GET["pid"],$_POST["tid"],$_POST["emote"],$_POST["comment"],$_GET["tag"],$_POST["htmltag"],$_POST["trackback"],$_POST["subject"],$_POST["blogbody"],0,$_POST["trackbackurl"],$_POST["trackbackname"]);
				$error_alert = "";
				switch($ret)
				{
					case -1:
						html_error_quit("缺少日志主题");
						exit();
						break;
					case -2:
						html_error_quit("目录不存在");
						exit();
						break;
					case -3:
						html_error_quit("该目录的日志数已达上限");
						exit();
						break;
					case -4:
						html_error_quit("分类不存在");
						exit();
						break;
					case -5:
						html_error_quit("由于系统原因日志添加失败,请联系管理员");
						exit();
						break;
					case -6:
						$error_alert = "由于系统错误,引用通告发送失败!";
						break;
					case -7:
						$error_alert = "TrackBack Ping URL 错误,引用通告发送失败!";
						break;
					case -8:
						$error_alert = "对方服务器无响应,引用通告发送失败!";
						break;
					default:
				}
				
				if($error_alert)
					echo "<script language=\"javascript\">alert('".$error_alert."');</script>";
				//管理员管理时的log
				$log_action = "ADD NODE: ".$_POST["subject"];
?>
<script language="javascript">
window.location.href="pcdoc.php?userid=<?php echo $pc["USER"]; ?>&tag=<?php echo $_GET["tag"]; ?>&tid=<?php echo $_POST["tid"]; ?>&pid=<?php echo $_GET["pid"]; ?>";
</script>
<?php
			}
			else
			{
				$tid = intval($_GET["tid"]);
				$pid = intval($_GET["pid"]);
				$tag = intval($_GET["tag"]);
				if($tag < 0 || $tag > 4)
					$tag =2 ;
				
				if($tid)
				{
					if(!pc_load_topic($link,$pc["UID"],$tid,$topicname,$tag))
					{
						html_error_quit("所指定的分类不存在，请重试!");
						exit();
					}
				}
				if($pid)
				{
					if(!pc_load_directory($link,$pc["UID"],$pid))
					{
						html_error_quit("所指定的分类不存在，请重试!");
						exit();
					}
				}
?>
<br><center>
<form name="postform" action="pcmanage.php?userid=<?php echo $pc["USER"]; ?>&act=post&<?php echo "tag=".$tag."&pid=".$pid; ?>" method="post" onsubmit="if(this.subject.value==''){alert('请输入文章主题!');return false;}">
<table cellspacing="0" cellpadding="5" border="0" width="90%" class="t1">
<tr>
	<td class="t2">发表文章</td>
</tr>
<tr>
	<td class="t8">主题
	<input type="text" size="100" maxlength="200" name="subject" class="f1">
	</td>
</tr>
<tr>
	<td class="t5">
	评论
	<input type="radio" name="comment" value="1" checked class="f1">允许
	<input type="radio" name="comment" value="0" class="f1">不允许
	</td>
</tr>
<tr>
	<td class="t8">
	Blog
	<select name="tid" class="f1">
<?php
		$blogs = pc_blog_menu($link,$pc["UID"],$tag);
		for($i = 0 ; $i < count($blogs) ; $i ++)
		{
			if($blogs[$i]["TID"] == $tid )
				echo "<option value=\"".$blogs[$i]["TID"]."\" selected>".html_format($blogs[$i]["NAME"])."</option>";
			else
				echo "<option value=\"".$blogs[$i]["TID"]."\">".html_format($blogs[$i]["NAME"])."</option>";
		}
?>
	</select>
	</td>
</tr>
<tr>
	<td class="t13">心情符号</td>
</tr>
<tr>
	<td class="t5"><?php @require("emote.html"); ?></td>
</tr>
<tr>
	<td class="t11">内容
	<input type="checkbox" name="htmltag" value=1 <?php if($pc["EDITOR"] == 0) echo "checked"; ?>>使用HTML标记
	</td>
</tr>
<tr>
	<td class="t8"><textarea name="blogbody" class="f1" cols="120" rows="30" id="blogbody"  onkeydown='if(event.keyCode==87 && event.ctrlKey) {document.postform.submit(); return false;}'  onkeypress='if(event.keyCode==10) return document.postform.submit()' wrap="physical"><?php echo $pcconfig["EDITORALERT"].$_POST["blogbody"]; ?></textarea></td>
</tr>
<tr>
	<td class="t13">
	引用通告
	</td>
</tr>
<tr>
	<td class="t5">
	文章链接: <input type="text" size="80" maxlength="255" name="trackbackname" class="f1" value="<?php echo htmlspecialchars($_GET[tbArtAddr]); ?>"><br />
	Trackback Ping URL: <input type="text" size="80" maxlength="255" name="trackbackurl" value="<?php echo htmlspecialchars($_GET[tbTBP]); ?>" class="f1">
	(必须以"http://"开头)
	</td>
</tr>
<tr>
	<td class="t8">
	允许引用
	<input type="checkbox" name="trackback" value="1" checked>
	</td>
</tr>
<tr>
	<td class="t2">
		<input type="button" name="ins" value="插入HTML" class="b1" onclick="return insertHTML();" />
		<input type="button" name="hil" value="高亮" class="b1" onclick="return highlight();" />
		<input type="submit" value="发表本文" class="b1">
		<input type="button" value="返回上页" onclick="history.go(-1)" class="b1">
	</td>
</tr>
</table>
</form></center>
<?php				
			}
		}
		elseif($act == "edit")
		{
			$nid = (int)($_GET["nid"]);
			$query = "SELECT `nodetype` , `subject` , `body` ,`comment`,`type`,`tid`,`access`,`htmltag`,`trackback` FROM nodes WHERE `nid` = '".$nid."' AND `uid` = '".$pc["UID"]."' LIMIT 0 , 1 ;";
			$result = mysql_query($query,$link);
			$rows = mysql_fetch_array($result);
			mysql_free_result($result);
			if(!$rows)
			{
				html_error_quit("文章不存在!");
				exit();
			}
			if($rows[nodetype] != 0)
			{
				html_error_quit("该文不可编辑!");
				exit();
			}
			
			if($_POST["subject"])
			{
				if($_POST["comment"]==1)
					$c = 0;
				else
					$c = 1;
				$useHtmlTag = ($_POST["htmltag"]==1)?1:0;
				$trackback = ($_POST["trackback"]==1)?1:0;
				$emote = (int)($_POST["emote"]);
				$query = "UPDATE nodes SET `subject` = '".addslashes($_POST["subject"])."' , `body` = '".addslashes(html_editorstr_format($_POST["blogbody"]))."' , `changed` = '".date("YmdHis")."' , `comment` = '".$c."' , `tid` = '".(int)($_POST["tid"])."' , `emote` = '".$emote."' , `htmltag` = '".$useHtmlTag."' , `trackback` = '".$trackback."' WHERE `nid` = '".$nid."' AND nodetype = 0;";
				mysql_query($query,$link);
				pc_update_record($link,$pc["UID"]);
				if($rows[subject]==$_POST["subject"])
					$log_action = "EDIT NODE: ".$rows[subject];
				else
				{
					$log_action = "EDIT NODE: ".$_POST["subject"];
					$log_content = "OLD SUBJECT: ".$rows[subject]."\nNEW SUBJECT: ".$_POST["subject"];
				}
?>
<p align="center">
<a href="javascript:history.go(-2);">操作成功,点击返回</a>
</p>
<?php
			}
			else
			{
?>
<br><center>			
<form name="postform" action="pcmanage.php?userid=<?php echo $pc["USER"]; ?>&act=edit&nid=<?php echo $nid; ?>" method="post" onsubmit="if(this.subject.value==''){alert('请输入文章主题!');return false;}">
<table cellspacing="0" cellpadding="5" border="0" width="90%" class="t1">
<?php
		if($rows[type]==1)
		{
?>
<tr>
	<td class="t2">修改目录</td>
</tr>
<tr>
	<td class="t8">
	主题
	<input type="text" size="100" class="f1" maxlength="200" name="subject" value="<?php echo htmlspecialchars(stripslashes($rows[subject])); ?>">
	</td>
</tr>
<tr>
	<td class="t2">
		<input type="submit" value="修改目录" class="b1">
		<input type="button" value="返回上页" class="b1" onclick="history.go(-1)">
	</td>
</tr>
<?php
		}
		else
		{
?>
<tr>
	<td class="t2">修改文章</td>
</tr>
<tr>
	<td class="t8">主题
	<input type="text" size="100" class="f1" name="subject" value="<?php echo htmlspecialchars($rows[subject]); ?>">
	</td>
</tr>
<tr>
	<td class="t5">
	评论
	<input type="radio" name="comment" class="f1" value="0" <?php if($rows[comment]!=0) echo "checked"; ?>>允许
	<input type="radio" name="comment" class="f1" value="1" <?php if($rows[comment]==0) echo "checked"; ?>>不允许
	</td>
</tr>
<tr>
	<td class="t8">
	Blog
	<select name="tid" class="f1">
<?php
		$blogs = pc_blog_menu($link,$pc["UID"],$rows[access]);
		for($i = 0 ; $i < count($blogs) ; $i ++)
		{
			if($blogs[$i]["TID"] == $rows[tid])
				echo "<option value=\"".$blogs[$i]["TID"]."\" selected>".html_format($blogs[$i]["NAME"])."</option>";
			else
				echo "<option value=\"".$blogs[$i]["TID"]."\" >".html_format($blogs[$i]["NAME"])."</option>";
		}
?>
	</select>
	</td>
</tr>
<tr>
	<td class="t13">心情符号</td>
</tr>
<tr>
	<td class="t5"><?php @require("emote.html"); ?></td>
</tr>
<tr>
	<td class="t11">内容
	<input type="checkbox" name="htmltag" value=1 <?php if(strstr($rows[body],$pcconfig["NOWRAPSTR"]) || $rows[htmltag] == 1) echo "checked"; ?> >使用HTML标记
	</td>
</tr>
<tr>
	<td class="t8">
	<textarea name="blogbody" class="f1" cols="120" rows="30" id="blogbody"  onkeydown='if(event.keyCode==87 && event.ctrlKey) {document.postform.submit(); return false;}'  onkeypress='if(event.keyCode==10) return document.postform.submit()' wrap="physical"><?php echo $pcconfig["EDITORALERT"]; ?><?php echo htmlspecialchars($rows[body]); ?></textarea>
	</td>
</tr>
<tr>
	<td class="t5">
	允许引用
	<input type="checkbox" name="trackback" value="1" <?php if($rows[trackback]==1) echo "checked"; ?>>
	</td>
</tr>
<tr>
	<td class="t2">
		<input type="button" name="ins" value="插入HTML" class="b1" onclick="return insertHTML();" />
		<input type="button" name="hil" value="高亮" class="b1" onclick="return highlight();" />
		<input type="submit" value="修改本文" class="b1">
		<input type="button" value="返回上页" onclick="history.go(-1)" class="b1">
	</td>
</tr>
<?php
		}
?>
</table>
</form></center>
<?php				
			}
		}
		elseif($act == "del")
		{
			$nid = (int)($_GET["nid"]);	
			$query = "SELECT `access`,`type`,`nodetype`,`subject` FROM nodes WHERE `uid` = '".$pc["UID"]."' AND `nid` = '".$nid."' ;";
			$result = mysql_query($query,$link);
			$rows = mysql_fetch_array($result);
			mysql_free_result($result);
			if(!$rows)
			{
				html_error_quit("文章不存在!");
				exit();
			}
			if($rows[nodetype]!=0)
			{
				html_error_quit("该文不能删除!");
				exit();
			}
			
			if($rows[access] == 4)
			{
				//彻底删除	
				$query = "DELETE FROM nodes WHERE `nid` = '".$nid."' ";
				mysql_query($query,$link);
				$query = "DELETE FROM comments WHERE `nid` = '".$nid."' ";
				mysql_query($query,$link);
				$query = "DELETE FROM trackback WHERE `nid` = '".$nid."' ";
				mysql_query($query,$link);
				$log_action = "DEL NODE: ".$rows[subject];
			}
			else
			{
				if($rows[type] == 1)
				{
					$query = "SELECT `nid` FROM nodes WHERE `pid` = '".$nid."' LIMIT 0, 1 ;";
					$result = mysql_query($query);
					if($rows0 = mysql_fetch_array($result))
					{
						mysql_free_result($result);
						html_error_quit("请先删除该目录下的文章!");
						exit();
					}
					mysql_free_result($result);
					$query = "DELETE FROM nodes WHERE `nid` = '".$nid."' ;";
					mysql_query($query,$link);
					$log_action = "DEL DIR: ".$rows[subject];
				}
				else
				{
					$query = "UPDATE nodes SET `access` = '4' , `changed` = '".date("YmdHis")."' , `tid` = '0' WHERE `nid` = '".$nid."' ;";
					mysql_query($query,$link);
					$log_action = "DEL TO JUNK: ".$rows[subject];
					if($rows[access] == 0)
						pc_update_record($link,$pc["UID"]," - 1");
				}
			}
			pc_update_record($link,$pc["UID"]);
?>
<p align="center">
<a href="javascript:history.go(-1);">操作成功,点击返回</a>
</p>
<?php		
		}
		elseif($act == "clear")
		{
			$query = "SELECT `nid` FROM nodes WHERE `uid` = '".$pc["UID"]."' AND `access` = '4' ;";	
			$result = mysql_query($query,$link);
			$query = "DELETE FROM comments WHERE `nid` = '0' ";
			$query_tb = "DELETE FROM trackback WHERE `nid` = '0' ";
			while($rows = mysql_fetch_array($result))
			{
				$query.= "  OR `nid` = '".$rows[nid]."' ";	
				$query_tb.= "  OR `nid` = '".$rows[nid]."' ";	
			}
			mysql_query($query,$link);
			mysql_query($query_tb,$link);
			$query = "DELETE FROM nodes WHERE `uid` = '".$pc["UID"]."' AND `access` = '4' ;";
			mysql_query($query,$link);
			$log_action = "EMPTY JUNK";
			pc_update_record($link,$pc["UID"]);
?>
<p align="center">
<a href="javascript:history.go(-1);">操作成功,点击返回</a>
</p>
<?php			
		}
		elseif($act == "tedit")
		{
			$tid = pc_load_topic($link,$pc["UID"],intval($_GET["tid"]),$topicname);
			if(!$tid)
			{
				html_error_quit("Blog不存在!");
				exit();
			}
			if($_POST["topicname"])
			{
				/*
				if($_POST["access"] != $rows[access])
				{
					$query = "UPDATE nodes SET `access` = '".$_POST["access"]."' , `changed` = '".date("YmdHis")."' WHERE `uid` = '".$pc["UID"]."' AND `tid` = '".$rows[tid]."' ;";
					mysql_query($query,$link);
				}
				*/
				//$query = "UPDATE topics SET `topicname` = '".$_POST["topicname"]."' , `access` = '".$_POST["access"]."' WHERE `uid` = '".$pc["UID"]."' AND `tid` = '".$rows[tid]."' ;";
				$query = "UPDATE topics SET `topicname` = '".addslashes($_POST["topicname"])."' WHERE `uid` = '".$pc["UID"]."' AND `tid` = '".$tid."' ;";
				mysql_query($query,$link);
				$log_action = "UPDATE TOPIC: ".$_POST["topicname"];
				$log_content = "OLD TITLE: ".$topicname."\nNEW TITLE: ".$_POST["topicname"];
				pc_update_record($link,$pc["UID"]);
				
?>
<p align="center">
<a href="javascript:history.go(-2);">操作成功,点击返回</a>
</p>
<?php			
			}
			else
			{
				$sec = array("公开区","好友区","私人区");
?>
<br>
<center>
<form action="pcmanage.php?userid=<?php echo $pc["USER"]; ?>&act=tedit&tid=<?php echo $rows[tid]; ?>" method="post" onsubmit="if(this.topicname.value==''){alert('请输入Blog名称!');return false;}">
<table cellspacing="0" cellpadding="5" border="0" width="90%" class="t1">
<tr>
	<td class="t2">修改Blog</td>
</tr>
<?php /*
<tr>
	<td>
	所在分区
	<select name="access">
<?php
		for($i =0 ;$i < 3 ;$i++ )
		{
			if($i == $rows[access])
				echo "<option value=\"".$i."\" selected>".$sec[$i]."</option>\n";
			else
				echo "<option value=\"".$i."\">".$sec[$i]."</option>\n";
		}
?>	
	</select>
	</td>
</tr>

*/
?>
<tr>
	<td class="t8">
	Blog名
	<input type="text" class="f1" name="topicname" value="<?php echo htmlspecialchars(stripslashes($rows[topicname])); ?>">
	</td>
</tr>
<tr>
	<td class="t2">
	<input type="submit" value="修改Blog" class="b1">
	<input type="button" value="返回上页" class="b1" onclick="history.go(-1)">
	</td>
</tr>
</table>
</form></center>
<?php
			}
		}
		elseif($act == "tdel")
		{
			$tid = pc_load_topic($link,$pc["UID"],intval($_GET["tid"]),$topicname);
			if(!$tid)
			{
				html_error_quit("Blog不存在!");
				exit();
			}
			$query = "SELECT `nid` FROM nodes WHERE `tid` = '".$tid."' ;";
			$result = mysql_query($query,$link);
			$rows = mysql_fetch_array($result);
			mysql_free_result($result);
			if($rows)
			{
				html_error_quit("请先删除Blog中的文章!");
				exit();
			}
			else
			{
				$query = "DELETE FROM topics WHERE `uid` = '".$pc["UID"]."' AND `tid` = '".$tid."' ;";
				mysql_query($query,$link);
				pc_update_record($link,$pc["UID"]);
				$log_action = "DEL TOPIC: ".$topicname;
?>
<p align="center">
<a href="pcdoc.php?userid=<?php echo $pc["USER"]; ?>&tag=6">操作成功,点击返回</a>
</p>
<?php				
			}
		}
		elseif($act == "tadd" && $_POST["topicname"])
		{
			if(!pc_add_topic($link,$pc,$_POST["access"],$_POST["topicname"]))
			{
				html_error_quit("分类添加失败");
				exit();
			}
			$log_action = "ADD TOPIC: ".$_POST["topicname"];
?>
<p align="center">
<a href="pcdoc.php?userid=<?php echo $pc["USER"]; ?>&tag=6">操作成功,点击返回</a>
</p>
<?php
		}
		elseif($act == "sedit" && $_POST["pcname"])
		{
			$favmode = (int)($_POST["pcfavmode"]);
			if($favmode != 1 && $favmode != 2)
				$favmode = 0;
			$query = "UPDATE `users` SET `createtime` = `createtime` , `corpusname` = '".addslashes(undo_html_format($_POST["pcname"]))."',`description` = '".addslashes(undo_html_format($_POST["pcdesc"]))."',`theme` = '".addslashes(undo_html_format($_POST["pcthem"]))."' , `backimage` = '".addslashes(undo_html_format($_POST["pcbkimg"]))."' , `logoimage` = '".addslashes(undo_html_format($_POST["pclogo"]))."' , `modifytime` = '".date("YmdHis")."' , `htmleditor` = '".(int)($_POST["htmleditor"])."', `style` = '".(int)($_POST["template"])."' , `indexnodechars` = '".(int)($_POST["indexnodechars"])."' , `indexnodes` = '".(int)($_POST["indexnodes"])."' , `favmode` = '".$favmode."' , `useremail` = '".addslashes($_POST["pcuseremail"])."' , `userinfor` = '".addslashes($_POST["userinfor"])."'  WHERE `uid` = '".$pc["UID"]."';";	
			mysql_query($query,$link);
			
			$log_action = "UPDATE SETTINGS";
			
?>
<p align="center">
<a href="pcdoc.php?userid=<?php echo $pc["USER"]; ?>">操作成功,点击返回</a>
</p>
<?php
		}
		elseif($act == "adddir" && $_POST["dir"])
		{
			$pid = (int)($_POST["pid"]);
			if(pc_dir_num($link,$pc["UID"],$pid)+1 > $pc["DLIM"])
			{
				html_error_quit("目标文件夹中的目录数已达上限 ".$pc["DLIM"]. " 个!");
				exit();
			}
			$query = "INSERT INTO `nodes` ( `nid` , `pid` , `type` , `source` , `hostname` , `changed` , `created` , `uid` , `comment` , `commentcount` , `subject` , `body` , `access` , `visitcount` , `tid` , `emote` ) ".
				"VALUES ('', '".$pid."', '1', '', '".$_SERVER["REMOTE_ADDR"]."','".date("YmdHis")."', '".date("YmdHis")."', '".$pc["UID"]."', '0', '0', '".addslashes($_POST["dir"])."', NULL , '3', '0', '0', '0');";
			mysql_query($query,$link);
			pc_update_record($link,$pc["UID"]);
			$log_action = "ADD DIR: ".$_POST["dir"];
?>
<script language="javascript">
window.location.href="pcdoc.php?userid=<?php echo $pc["USER"]; ?>&tag=3&pid=<?php echo $pid; ?>";
</script>
<?php
		}
		elseif($act == "favcut" || $act == "favcopy")
		{
			//目前不支持目录的剪切和复制
			$query = "SELECT `nid`,`type`,`pid`,`subject`,`tid` FROM nodes WHERE `nid` = '".(int)($_GET["nid"])."' AND `uid` = '".$pc["UID"]."' AND `access` = 3  AND `type` = 0 LIMIT 0 , 1;";
			$result = mysql_query($query,$link);
			$rows = mysql_fetch_array($result);
			if(!$rows)
			{
				html_error_quit("文章不存在!");
				exit();
			}
			$favaction = array(
					"ACT" => $act,
					"NID" => $rows[nid],
					"TYPE" => $rows[type],
					"PID" => $rows[pid]
					);
			mysql_free_result($result);
			setcookie("BLOGFAVACTION",$favaction);
?>
<br>
<p align="center">
<a href="pcdoc.php?userid=<?php echo $pc["USER"]; ?>&tag=3&tid=<?php echo $rows[tid]; ?>&pid=<?php echo $rows[pid]; ?>">操作成功,已将 <font class=f2><?php echo $rows[subject]; ?></font> 放入剪贴板，点击返回</a>
</p>
<?php			
		}
		elseif($act == "favpaste")
		{
			if(!$favaction || !is_array($favaction))
			{
				html_error_quit("您的剪贴板是空的，请先剪切或者复制一个文件!");
				exit();
			}
			$pid = intval($_GET["pid"]);
			if(!pc_load_directory($link,$pc["UID"],$pid))
			{
				html_error_quit("目标文件夹不存在!");
				exit();
			}
			
			if(pc_file_num($link,$pc["UID"],$pid)+1 > $pc["NLIM"])
			{
				html_error_quit("目标文件夹中的文件数已达上限 ".$pc["NLIM"]. " 个!");
				exit();
			}
			
			if($favaction["ACT"] == "favcut")
			{
				$query = "UPDATE nodes SET `pid` = '".$pid."' WHERE `nid` = '".$favaction["NID"]."';";
			}
			else
			{
				$query = "SELECT * FROM nodes WHERE `nid` = '".$favaction["NID"]."' LIMIT 0 , 1 ;";
				$result = mysql_query($query,$link);
				$rows = mysql_fetch_array($result);
				mysql_free_result($result);
				$query = "INSERT INTO `nodes` ( `nid` , `pid` , `type` , `source` , `hostname` , `changed` , `created` , `uid` , `comment` , `commentcount` , `subject` , `body` , `access` , `visitcount` , `tid` , `emote` ,`htmltag`) ".
					"VALUES ('', '".$pid."', '0', '".$rows[source]."', '".$rows[hostname]."', '".date("YmdHis")."' , '".$rows[created]."', '".$pc["UID"]."', '".$rows[comment]."', '".$rows[commentcount]."', '".$rows[subject]."', '".$rows[body]."', '3', '".$rows[visitcount]."', '".$rows[tid]."', '".$rows[emote]."','".$rows[htmltag]."');";
			}
			mysql_query($query,$link);
			unset($favaction);
			setcookie("BLOGFAVACTION");
			pc_update_record($link,$pc["UID"]);
			$log_action = "CUT/COPY FAV";
?>
<script language="javascript">
window.location.href="pcdoc.php?userid=<?php echo $pc["USER"]; ?>&tag=3&pid=<?php echo $pid; ?>";
</script>
<?php		
		}
	
		if($pc["TYPE"]==1)
			pc_group_logs($link,$pc,$log_action,$log_content);
		
		html_normal_quit();
	}
	
?>