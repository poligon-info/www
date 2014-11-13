<?
define("NEED_AUTH", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!CModule::IncludeModule("forum") || !$USER->IsAuthorized())
{
	LocalRedirect("index.php");
	die();
}

$UID = IntVal($UID);
if (!$USER->IsAdmin() || $UID<=0)
{
	$UID = IntVal($USER->GetParam("USER_ID"));
}

$bUserFound = False;
$db_userX = CUser::GetByID($UID);
if ($db_userX->ExtractFields("f_", True))
{
	$bUserFound = True;
}

ForumSetLastVisit();
define("FORUM_MODULE_PAGE", "PROFILE");
$strErrorMessage = "";
$strOKMessage = "";
$bVarsFromForm = false;

if ($REQUEST_METHOD=="POST" && $ACTION=="EDIT" && $bUserFound)
{
	if (strlen($NAME)<=0)
		$strErrorMessage .= "��������� ���� \"���\". \n";

	if (strlen($LAST_NAME)<=0)
		$strErrorMessage .= "��������� ���� \"�������\". \n";

	if (strlen($EMAIL)<=0)
		$strErrorMessage .= "��������� ���� \"E-Mail �����\". \n";
	elseif (!check_email($EMAIL))
		$strErrorMessage .= "E-Mail ����� �� �����. \n";

	if (strlen($LOGIN)<3)
		$strErrorMessage .= "��������� ���� \"�����\". ����� �� ����� ���� ������� ���� ��������. \n";

	if (strlen($new_password)>0 || strlen($password_confirm)>0)
	{
		if (strlen($new_password)<3)
			$strErrorMessage .= "��������� ���� \"������\". ������ �� ����� ���� ������� ���� ��������. \n";

		if ($new_password!=$password_confirm)
			$strErrorMessage .= "����� ������ �� �����������. \n";
	}

	if (strlen($strErrorMessage)<=0)
	{
		$z = $DB->Query("SELECT PERSONAL_PHOTO FROM b_user WHERE ID='$UID'");
		$zr = $z->Fetch();
		$arPERSONAL_PHOTO = $_FILES["PERSONAL_PHOTO"];
		$arPERSONAL_PHOTO["old_file"] = $zr["PERSONAL_PHOTO"];
		$arPERSONAL_PHOTO["del"] = $PERSONAL_PHOTO_del;

		$arFields = Array(
			"NAME" => $NAME,
			"LAST_NAME" => $LAST_NAME,
			"EMAIL" => $EMAIL,
			"LOGIN" => $LOGIN,
			"PERSONAL_ICQ" => $PERSONAL_ICQ,
			"PERSONAL_WWW" => $PERSONAL_WWW,
			"PERSONAL_PROFESSION" => $PERSONAL_PROFESSION,
			"PERSONAL_BIRTHDATE" => $PERSONAL_BIRTHDATE,
			"PERSONAL_CITY" => $PERSONAL_CITY,
			"PERSONAL_COUNTRY" => $PERSONAL_COUNTRY,
			"PERSONAL_PHOTO" => $arPERSONAL_PHOTO,
			"PERSONAL_GENDER" => $PERSONAL_GENDER
		);

		if (strlen($new_password)>0)
		{
			$arFields["PASSWORD"] = $new_password;
			$arFields["CONFIRM_PASSWORD"] = $password_confirm;
		}

		$res = $USER->Update($UID, $arFields);
		if (!$res)
			$strErrorMessage .= $USER->LAST_ERROR.". \n";
	}

	if (strlen($strErrorMessage)<=0)
	{
		$arFields = array(
			"SHOW_NAME" => ($SHOW_NAME=="Y") ? "Y" : "N",
			"DESCRIPTION" => $DESCRIPTION,
			"INTERESTS" => $INTERESTS,
			"SIGNATURE" => $SIGNATURE,
			"AVATAR" => $_FILES["AVATAR"]
			);
		$arFields["AVATAR"]["del"] = $AVATAR_del;

		if ($USER->IsAdmin())
		{
			$arFields["ALLOW_POST"] = (($ALLOW_POST=="Y") ? "Y" : "N");
		}

		$ar_res = CForumUser::GetByUSER_ID($UID);
		if ($ar_res)
		{
			$arFields["AVATAR"]["old_file"] = $ar_res["AVATAR"];
			$ID = IntVal($ar_res["ID"]);

			$ID1 = CForumUser::Update($ID, $arFields);
			if (IntVal($ID1)<=0)
				$strErrorMessage .= "������ ��������� �������. \n";
		}
		else
		{
			$arFields["USER_ID"] = $UID;

			$ID = CForumUser::Add($arFields);
			$ID = IntVal($ID);
			if ($ID<=0)
				$strErrorMessage .= "������ ���������� �������. \n";
		}
	}

	if (strlen($strErrorMessage)>0)
	{
		$bVarsFromForm = true;
	}
	else
	{
		if ($f_LOGIN!=$LOGIN || strlen($new_password)>0)
		{
			$USER->SendUserInfo($USER->GetParam("USER_ID"), LANG, "��������� ��������������� ����������");
		}
	}
}

$APPLICATION->SetTitle("�������");
$APPLICATION->SetAdditionalCSS("/bitrix/php_interface/".LANG."/forum.css");

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");

$path2curdir = str_replace("\\\\", "/", dirname(__FILE__)."/");
if (file_exists($path2curdir."menu.php"))
	include($path2curdir."menu.php");
elseif (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/public/".LANG."/menu.php"))
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/public/".LANG."/menu.php");
else
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/public/en/menu.php");

if (!$bUserFound)
{
	$strErrorMessage .= "������������ � ����� $UID �� ������. \n";
}
?>

<?echo ShowMessage(array("MESSAGE" => $strErrorMessage, "TYPE" => "ERROR"));?>
<?echo ShowMessage(array("MESSAGE" => $strOKMessage, "TYPE" => "OK"));?>

<?
if ($bUserFound):
	$db_res = CForumUser::GetList(array(), array("USER_ID"=>$UID));
	$db_res->ExtractFields("f_", True);

	if ($bVarsFromForm)
	{
		$DB->InitTableVarsForEdit("b_forum_user", "", "f_");
		$DB->InitTableVarsForEdit("b_user", "", "f_");
	}
	?>
	<form action="<?echo $APPLICATION->GetCurPage();?>" method="post" name="form1" enctype="multipart/form-data">

	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="forumborder"><tr><td>
	<table border="0" cellpadding="1" cellspacing="1" width="100%">
		<tr>
			<td class="forumhead" align="center" colspan="2" height="25" valign="middle">
				<font class="forumheadtext"><b>��������������� ����������</b></font>
			</td>
		</tr>
		<tr>
			<td class="forumbody" colspan="2">
				<font class="forumbodytext">&nbsp;���� �� ���������� (<font color="#FF0000">*</font>) ����������� ��� ����������</font>
			</td>
		</tr>
		<tr>
			<td class="forumbody" width="38%">
				<font class="forumheadtext">&nbsp;���: <font color="#FF0000">*</font></font>
			</td>
			<td class="forumbody">
				<input type="text" name="NAME" size="30" maxlength="50" value="<?echo $f_NAME; ?>">
			</td>
		</tr>
		<tr>
			<td class="forumbody" width="38%">
				<font class="forumheadtext">&nbsp;�������: <font color="#FF0000">*</font></font>
			</td>
			<td class="forumbody">
				<input type="text" name="LAST_NAME" size="30" maxlength="50" value="<?echo $f_LAST_NAME; ?>">
			</td>
		</tr>
		<tr>
			<td class="forumbody" width="38%">
				<font class="forumheadtext">&nbsp;�����: <font color="#FF0000">*</font></font>
			</td>
			<td class="forumbody">
				<input type="text" name="LOGIN" size="30" maxlength="50" value="<?echo $f_LOGIN; ?>">
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;E-Mail �����: <font color="#FF0000">*</font></font>
			</td>
			<td class="forumbody">
				<input type="text" name="EMAIL" size="30" maxlength="255" value="<?echo $f_EMAIL; ?>">
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;����� ������:<br>
				<small>&nbsp;�������, ���� �� ������ �������� ������<br></small></font>
			</td>
			<td class="forumbody">
				<input type="password" name="new_password" size="30" maxlength="100" value="">
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;����� ������ ��� ���:<br>
				<small>&nbsp;�������, ���� �� ������ �������� ������<br></small></font>
			</td>
			<td class="forumbody">
				<input type="password" name="password_confirm" size="30" maxlength="100" value="">
			</td>
		</tr>
		<tr>
			<td class="forumbody" colspan="2" height="28">&nbsp;</td>
		</tr>
		<tr>
			<td class="forumhead" align="center" colspan="2" height="25" valign="middle">
				<font class="forumheadtext"><b>������ ������</b></font>
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;���������:</font>
			</td>
			<td class="forumbody">
				<input type="text" name="PERSONAL_PROFESSION" size="30" maxlength="255" value="<?=$f_PERSONAL_PROFESSION?>">
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;Web-����:</font>
			</td>
			<td class="forumbody">
				<input type="text" name="PERSONAL_WWW" size="30" maxlength="255" value="<?if (strlen($f_PERSONAL_WWW)>0) echo $f_PERSONAL_WWW; else echo "http://";?>">
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;����� ICQ:</font>
			</td>
			<td class="forumbody">
				<input type="text" name="PERSONAL_ICQ" size="30" maxlength="255" value="<?=$f_PERSONAL_ICQ?>">
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;���:</font>
			</td>
			<td class="forumbody">
				<?
				$arr = array("reference"=>array("�������", "�������"), "reference_id"=>array("M", "F"));
				echo SelectBoxFromArray("PERSONAL_GENDER", $arr, $f_PERSONAL_GENDER, "&lt;����������&gt;");
				?>
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;���� �������� (<?echo CLang::GetDateFormat("SHORT")?>):</font>
			</td>
			<td class="forumbody">
				<?echo CalendarDate("PERSONAL_BIRTHDATE", $f_PERSONAL_BIRTHDATE, "form1", "15")?>
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;����������:</font>
			</td>
			<td class="forumbody"><font class="forumbodytext">
				<input type="hidden" name="MAX_FILE_SIZE" value="500000">
				<input name="PERSONAL_PHOTO" size="20" type="file"><br>
				<input type="checkbox" name="PERSONAL_PHOTO_del" value="Y"> ������� ���� 
				<?if (strlen($f_PERSONAL_PHOTO)>0):?>
					<br>
					<?echo CFile::ShowImage($f_PERSONAL_PHOTO, 150, 150, "border=0", "", true)?>
				<?endif;?></font>
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;������:</font>
			</td>
			<td class="forumbody">
				<?echo SelectBoxFromArray("PERSONAL_COUNTRY", GetCountryArray(), $f_PERSONAL_COUNTRY, "&lt;����������&gt;");?>
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;�����:</font>
			</td>
			<td class="forumbody">
				<input type="text" name="PERSONAL_CITY" size="30" maxlength="255" value="<?=$f_PERSONAL_CITY?>">
			</td>
		</tr>
		<tr>
			<td class="forumbody" colspan="2" height="28">&nbsp;</td>
		</tr>
		<tr>
			<td class="forumhead" colspan="2" align="center" height="25" valign="middle">
				<font class="forumheadtext"><b>�������</b></font>
			</td>
		</tr>
		<?if ($USER->IsAdmin()):?>
			<tr>
				<td class="forumbody">
					<font class="forumheadtext">&nbsp;��������� ������:</font>
				</td>
				<td class="forumbody">
					<input type="checkbox" name="ALLOW_POST" value="Y" <?if ($f_ALLOW_POST=="Y") echo "checked";?>>
				</td>
			</tr>
		<?endif;?>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;���������� ���:<br>
				<small>&nbsp;���������� �� ��� ������������ ��� ������ �����</small><br></font>
			</td>
			<td class="forumbody">
				<input type="checkbox" name="SHOW_NAME" value="Y" <?if ($f_SHOW_NAME=="Y") echo "checked";?>>
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;���������:<br>
				<small>&nbsp;��������� ��� �����������</small></font>
			</td>
			<td class="forumbody">
				<input type="text" name="DESCRIPTION" size="30" maxlength="64" value="<?echo $f_DESCRIPTION; ?>">
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;��������:</font>
			</td>
			<td class="forumbody">
				<textarea name="INTERESTS" rows="3" cols="35"><?echo $f_INTERESTS; ?></textarea>
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;�������:</font>
			</td>
			<td class="forumbody">
				<font class="forumbodytext">
				<textarea name="SIGNATURE" rows="3" cols="35"><?echo $f_SIGNATURE; ?></textarea><br>
				<small>������� ����� ���������� ��� ������ ����� ����������. ��������� ������������� ����� ������-�����, ����������� �� ������ ������.</small><br>
				</font>
			</td>
		</tr>
		<tr>
			<td class="forumbody">
				<font class="forumheadtext">&nbsp;������:<br>
				<small>&nbsp;����������� �������� �� ����� 10 kb � ����������� �� ����� 90x90 px</small></font>
			</td>
			<td class="forumbody"><font class="forumbodytext">
				<input name="AVATAR" size="20" type="file"><br>
				<input type="checkbox" name="AVATAR_del" value="Y"> ������� ���� 
				<?if (strlen($f_AVATAR)>0):?>
					<br>
					<?echo CFile::ShowImage($f_AVATAR, 90, 90, "border=0", "", true)?>
				<?endif;?></font>
			</td>
		</tr>
		<tr>
			<td class="forumbody" colspan="2" height="28">&nbsp;</td>
		</tr>
		<tr>
			<td class="forumhead" colspan="2" align="center" height="25" valign="middle">
				<font class="forumheadtext"><b><a href="subscr_list.php">�������� [��������]</a></b></font>
		  </td>
		</tr>
		<tr>
			<td class="forumbody" colspan="2" align="center" height="28">
				<input type="hidden" name="ACTION" value="EDIT">
				<input type="hidden" name="UID" value="<?echo $UID; ?>">
				<input type="hidden" name="old_LOGIN" value="<?echo $f_LOGIN; ?>">
				<input type="submit" name="submit" value="���������">&nbsp;&nbsp;<input type="reset" value="������" name="reset">
			</td>
		</tr>
	</table>
	<td><tr></table>
	</form>
	<?
endif;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");
?>