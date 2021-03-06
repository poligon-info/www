<?
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# admin@bitrixsoft.com                       #
##############################################
if(isset($_REQUEST["id"])) $id = $_REQUEST["id"];
require_once(dirname(__FILE__)."/../include/prolog_admin_before.php");
define("HELP_FILE", "settings/module_admin.php");

if(!$USER->CanDoOperation('edit_other_settings') && !$USER->CanDoOperation('view_other_settings'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$isAdmin = $USER->CanDoOperation('edit_other_settings');

IncludeModuleLangFile(__FILE__);

$arModules = array();
function OnModuleInstalledEvent($id)
{
	$db_events = GetModuleEvents("main", "OnModuleInstalled");
	while ($arEvent = $db_events->Fetch())
		ExecuteModuleEvent($arEvent, $id);
}

//�������� ������ �������� � ����� modules
$handle=@opendir($DOCUMENT_ROOT.BX_ROOT."/modules");
if($handle)
{
	while (false !== ($dir = readdir($handle)))
	{
		if(is_dir($DOCUMENT_ROOT.BX_ROOT."/modules/".$dir) && $dir!="." && $dir!=".." && $dir!="main")
		{
			$module_dir = $DOCUMENT_ROOT.BX_ROOT."/modules/".$dir;
			if(file_exists($module_dir."/install/index.php"))
			{
				include_once($module_dir."/install/index.php");
				$info = new $dir;
				$arModules[$dir]["MODULE_ID"] = $info->MODULE_ID;
				$arModules[$dir]["MODULE_NAME"] = $info->MODULE_NAME;
				$arModules[$dir]["MODULE_DESCRIPTION"] = $info->MODULE_DESCRIPTION;
				$arModules[$dir]["MODULE_VERSION"] = $info->MODULE_VERSION;
				$arModules[$dir]["MODULE_VERSION_DATE"] = $info->MODULE_VERSION_DATE;
				$arModules[$dir]["MODULE_SORT"] = $info->MODULE_SORT;
				$arModules[$dir]["IsInstalled"] = $info->IsInstalled();
			}
		}
	}
	closedir($handle);
}

uasort($arModules, create_function('$a, $b', 'if($a["MODULE_SORT"] == $b["MODULE_SORT"]) return strcasecmp($a["MODULE_NAME"], $b["MODULE_NAME"]); return ($a["MODULE_SORT"] < $b["MODULE_SORT"])? -1 : 1;'));

$fb = ($id == 'fileman' && !$USER->CanDoOperation('fileman_install_control'));
if((strlen($uninstall)>0 || strlen($install)>0) && $isAdmin && !$fb)
{
	$id = str_replace("\\", "", str_replace("/", "", $id));
	if(@file_exists($DOCUMENT_ROOT.BX_ROOT."/modules/".$id."/install/index.php"))
	{
		include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/".$id."/install/index.php");
		$Module = new $id;
		if($Module->IsInstalled() && strlen($uninstall)>0)
		{
			OnModuleInstalledEvent($id);
			$Module->DoUninstall();
			LocalRedirect($APPLICATION->GetCurPage()."?lang=".LANGUAGE_ID);
		}
		elseif(!$Module->IsInstalled() && strlen($install) > 0)
		{
			if (strtolower($DB->type)=="mysql" && defined("MYSQL_TABLE_TYPE") && strlen(MYSQL_TABLE_TYPE)>0)
			{
				$DB->Query("SET table_type = '".MYSQL_TABLE_TYPE."'", true);
			}

			OnModuleInstalledEvent($id);
			$Module->DoInstall();
			LocalRedirect($APPLICATION->GetCurPage()."?lang=".LANG);
		}
	}
}

$APPLICATION->SetTitle(GetMessage("TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%" class="list-table">
	<tr class="head" valign="middle" align="center">
		<td width="60%"><b><?echo GetMessage("MOD_NAME")?></b></td>
		<td><b><?echo GetMessage("MOD_VERSION")?></b></td>
		<td><b><?echo GetMessage("MOD_DATE_UPDATE")?></b></td>
		<td><b><?echo GetMessage("MOD_SETUP")?></b></td>
		<td><b><?echo GetMessage("MOD_ACTION")?></b></td>
	</tr>
	<tr>
		<td><b><?=GetMessage("MOD_MAIN_MODULE")?></b><br><?
		$str = str_replace("#A1#","<a  href='sysupdate.php?lang=".LANG."'>",GetMessage("MOD_MAIN_DESCRIPTION"));
		$str = str_replace("#A2#","</a>",$str);
		echo $str;?></td>
		<td><?echo SM_VERSION;?></td>
		<td nowrap><?echo CDatabase::FormatDate(SM_VERSION_DATE, "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("SHORT"));?></td>
		<td><?=GetMessage("MOD_INSTALLED")?></td>
		<td>&nbsp;</td>
	</tr>
<?
foreach($arModules as $info) :
?>
	<tr>
		<td><b><?echo $info["MODULE_NAME"]?></b><br><?echo $info["MODULE_DESCRIPTION"]?></td>
		<td><?echo $info["MODULE_VERSION"]?></td>
		<td nowrap><?echo CDatabase::FormatDate($info["MODULE_VERSION_DATE"], "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("SHORT"));?></td>
		<td><?if($info["IsInstalled"]):?><?echo GetMessage("MOD_INSTALLED")?><?else:?><span class="required"><?echo GetMessage("MOD_NOT_INSTALLED")?></span><?endif?></td>
		<td>
			<form action="<?echo $APPLICATION->GetCurPage()?>">
				<input type="hidden" name="lang" value="<?echo LANG?>">
				<input type="hidden" name="id" value="<?echo htmlspecialchars($info["MODULE_ID"])?>">
				<?=bitrix_sessid_post()?>
				<?if($info["IsInstalled"]):?>
					<?$fb = ($info["MODULE_ID"] == 'fileman' && !$USER->CanDoOperation('fileman_install_control'));	?>
					<input <?if (!$isAdmin || $fb) echo "disabled" ?> type="submit" name="uninstall" value="<?echo GetMessage("MOD_DELETE")?>">
				<?else:?>
					<input <?if (!$isAdmin) echo "disabled" ?> type="submit" name="install" value="<?echo GetMessage("MOD_INSTALL_BUTTON")?>">
				<?endif?>
			</form>
		</td>
	</tr>
<?
endforeach;
?>
</table>
<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>
