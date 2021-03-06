<?
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################

$strError = "";
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/prolog.php");
define("HELP_FILE", "settings/sites/template_admin.php");

$edit_php = $USER->CanDoOperation('edit_php');
if(!$edit_php && !$USER->CanDoOperation('view_other_settings') && !$USER->CanDoOperation('lpa_template_edit'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

//$lpa = ($USER->CanDoOperation('edit_other_settings') && $USER->CanDoOperation('lpa_template_edit') && !$edit_php); // Limit PHP access: for non admin users
//$lpa = ($USER->CanDoOperation('edit_other_settings') && $USER->CanDoOperation('lpa_template_edit') && !$edit_php); // Limit PHP access: for non admin users
IncludeModuleLangFile(__FILE__);

$sTableID = "tbl_template";
$lAdmin = new CAdminList($sTableID, $oSort);

if($_REQUEST['mode']=='list' || $_REQUEST['mode']=='frame')
	CFile::DisableJSFunction(true);

if($lAdmin->EditAction() && $edit_php)
{
	foreach($FIELDS as $ID=>$arFields)
	{
		$DB->StartTransaction();
		//$ID = IntVal($ID);

		if(!$lAdmin->IsUpdated($ID))
			continue;

		$ob = new CSiteTemplate;
		if(!$ob->Update($ID, $arFields))
		{
			$lAdmin->AddUpdateError(GetMessage("SAVE_ERROR").$ID.": ".$ob->LAST_ERROR, $ID);
			$DB->Rollback();
		}
		$DB->Commit();
	}
}

if(($arID = $lAdmin->GroupAction()) && $edit_php)
{
	if($_REQUEST['action_target']=='selected')
	{
		$arID = Array();
		$rsData = CSiteTemplate::GetList($by, $order, Array());
		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach($arID as $ID)
	{
		if(strlen($ID)<=0)
			continue;

		switch($_REQUEST['action'])
		{
		case "delete":
			@set_time_limit(0);
			$DB->StartTransaction();
			if(!CSiteTemplate::Delete($ID))
			{
				$DB->Rollback();
				$lAdmin->AddGroupError(GetMessage("DELETE_ERROR"), $ID);
			}
			$DB->Commit();
			break;
		case "export":
			?>
			<script language="JavaScript">
				exportData('<?=$ID?>');
			</script>
			<?
			break;
		case "copy":
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$ID, $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".($ID==".default"?"default":$ID)."_copy", false, true);
			break;
		}
	}
}
$rsData = CSiteTemplate::GetList();
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("PAGES"), false));

$lAdmin->AddHeaders(array(
	array("id"=>"ID",				"content"=>"ID", 	"sort"=>"id", "default"=>true),
	array("id"=>"NAME",				"content"=>GetMessage('MAIN_T_ADMIN_NAME'), "default"=>true),
	array("id"=>"DESCRIPTION", 		"content"=>GetMessage('MAIN_T_ADMIN_DESCRIPTION'), "default"=>true),

));

while($arRes = $rsData->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arRes, "template_edit.php?ID=".$f_ID, GetMessage("MAIN_EDIT_TITLE"));
	$row->AddViewField("ID", "<a href='template_edit.php?lang=".LANGUAGE_ID."&ID=".$f_ID."' title='".GetMessage("MAIN_EDIT_TITLE")."'>".$f_ID."</a>");
	if(strlen($f_SCREENSHOT)>0)
		$row->AddViewField("ID", "<a href='template_edit.php?lang=".LANGUAGE_ID."&ID=".$f_ID."' title='".GetMessage("MAIN_EDIT_TITLE")."'>".$f_ID."</a>".CFile::ShowImage($f_SCREENSHOT, 130, 100, "hspace=0 vspace=4 border=0 align=left", "", true));
	else
		$row->AddViewField("ID", "<a href='template_edit.php?lang=".LANGUAGE_ID."&ID=".$f_ID."' title='".GetMessage("MAIN_EDIT_TITLE")."'>".$f_ID."</a>");

	if ($edit_php)
	{
		$row->AddInputField("NAME");
		$row->AddInputField("DESCRIPTION");
	}
	else
	{
		$row->AddViewField("NAME", $f_NAME);
		$row->AddViewField("DESCRIPTION", $f_DESCRIPTION);
	}

	$arActions = Array();

	$arActions[] = array("ICON"=>"edit", "TEXT"=>($USER->CanDoOperation('edit_other_settings') || $USER->CanDoOperation('lpa_template_edit')? GetMessage("MAIN_ADMIN_MENU_EDIT") : GetMessage("MAIN_ADMIN_MENU_VIEW")), "ACTION"=>$lAdmin->ActionRedirect("template_edit.php?ID=".$f_ID));
	if ($edit_php)
	{
		$arActions[] = array("ICON"=>"copy", "TEXT"=>GetMessage("MAIN_ADMIN_MENU_COPY"), "ACTION"=>$lAdmin->ActionDoGroup($f_ID, "copy"));
	$arActions[] = array("ICON"=>"export", "TEXT"=>GetMessage("MAIN_ADMIN_LIST_EXPORT"), "ACTION"=>"exportData('".$f_ID."')");
		if($edit_php && $f_DEFAULT!="Y")
		{
				$arActions[] = array("SEPARATOR"=>true);
				$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("MAIN_T_ADMIN_DEL"), "ACTION"=>"if(confirm('".GetMessage('MAIN_T_ADMIN_DEL_CONF')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"));
		}
	}

	$row->AddActions($arActions);
}

$lAdmin->AddFooter(
	array(
		array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
		array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
	)
);

if ($edit_php)
{
	$groupAction["copy"] = GetMessage("MAIN_T_ADMIN_COPY_1");
	$groupAction["delete"] = GetMessage("MAIN_ADMIN_LIST_DELETE");
	$lAdmin->AddGroupActionTable($groupAction);
}

$aContext = array();
if ($edit_php)
{
	$aContext[] = array(
			"TEXT"	=> GetMessage("MAIN_ADD_TEMPL"),
			"LINK"	=> "template_edit.php?lang=".LANGUAGE_ID,
			"TITLE"	=> GetMessage("MAIN_ADD_TEMPL_TITLE"),
			"ICON"	=> "btn_new"
		);
	$aContext[] = array(
			"TEXT"	=> GetMessage("MAIN_LOAD"),
			"LINK"	=> "template_load.php?lang=".LANGUAGE_ID,
			"TITLE"	=> GetMessage("MAIN_T_IMPORT"),
			"ICON"	=> ""
		);
}
$lAdmin->AddAdminContextMenu($aContext);

// �������� �� ����� ������ ������ (� ������ ������, ������ ������ ����������� �� �����)
$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("MAIN_T_ADMIN_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<script>
function exportData(val)
{
	window.open("template_export.php?ID="+val);
}
</script>
<?$lAdmin->DisplayList();?>
<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>
