<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/iblock.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");

IncludeModuleLangFile(__FILE__);

$IBLOCK_ID=IntVal($IBLOCK_ID);
$WAY=IntVal($WAY);
$STEP=IntVal($STEP);
$INTERVAL=20;//sec TODO make setting?

if($STEP==0)
	$_SESSION["BX_IBLOCK_CONV"]=false;

$arErrors = array();
$arMessages = array();

$APPLICATION->SetTitle(GetMessage("IBCONV_TITLE"));

$sTableID = "tbl_iblock_convert";
$lAdmin = new CAdminList($sTableID);
$lAdmin->BeginCustomContent();

if(CIBlock::GetPermission($IBLOCK_ID)<"X")
{
	$arErrors[]=GetMessage("IBCONV_PERMISSION");
}
else
{
	$obIBlock = new CIBlock;
	$rsIBlock = $obIBlock->GetByID($IBLOCK_ID);
	if($arIBlock = $rsIBlock->Fetch())
	{
		if($arIBlock["VERSION"]==1)
		{
			if($STEP!=0 && $WAY!=12)
				$arErrors[]=GetMessage("IBCONV_WRONG_CONVERSION");
			elseif($STEP==1)
				$STEP = 2;
			elseif($STEP==2)
				$STEP = FirstStep12($arIBlock);
			elseif($STEP==3)
				$STEP = NextStep12($arIBlock);
			elseif($STEP==4)
				$STEP = 5;
			elseif($STEP==5)
				$STEP = LastStep12($arIBlock);
		}
		elseif($arIBlock["VERSION"]==2)
		{
			if($STEP!=0 && $WAY!=21)
				$arErrors[]=GetMessage("IBCONV_WRONG_CONVERSION");
			elseif($STEP==1)
				$STEP = 2;
			elseif($STEP==2)
				$STEP = FirstStep21($arIBlock);
			elseif($STEP==3)
				$STEP = NextStep21($arIBlock);
			elseif($STEP==4)
				$STEP = 5;
			elseif($STEP==5)
				$STEP = LastStep21($arIBlock);
		}
		else
		{
			$arErrors[]=GetMessage("IBCONV_WRONG_VERSION");
		}
	}
	else
	{
		$arErrors[]=GetMessage("IBCONV_WRONG_IBLOCK");
	}
}

foreach($arErrors as $strError)
	CAdminMessage::ShowMessage($strError);
foreach($arMessages as $strMessage)
	CAdminMessage::ShowMessage(array("MESSAGE"=>$strMessage,"TYPE"=>"OK"));

if(count($arErrors)==0):?>
	<?if($STEP==0):?>
		<p><span class="required"><?=GetMessage("IBCONV_ATTENTION")?></span> <?=GetMessage("IBCONV_WARNING_MESSAGE",array("#IBLOCK_NAME#"=>htmlspecialchars($arIBlock["NAME"])))?>
		<input type="button" name="START" value="<?=GetMessage("IBCONV_MOVE")?>" OnClick="DoNext(<?=$arIBlock["VERSION"]==2?21:12?>,1)">
	<?elseif($STEP>=1 && $STEP<=5):?>
		<?if($WAY==12):?>
			<p><ul>
			<li><?=$STEP==2?'<b>':''?><?=GetMessage("IBCONV_CREATE_TABLE")?><?=$STEP==2?'</b>':''?></li>
			<?if(is_array($_SESSION["BX_IBLOCK_CONV"])):?>
				<li><?=$STEP==3?'<b>':''?><?=GetMessage("IBCONV_PROGRESS",array("#DONE#"=>$_SESSION["BX_IBLOCK_CONV"]["DONE"],"#TODO#"=>$_SESSION["BX_IBLOCK_CONV"]["TODO"]))?><?=$STEP==3?'</b>':''?></li>
			<?else:?>
				<li><?=$STEP==3?'<b>':''?><?=GetMessage("IBCONV_INPROGRESS")?><?=$STEP==3?'</b>':''?></li>
			<?endif;?>
			<li><?=$STEP==5?'<b>':''?><?=GetMessage("IBCONV_FINALIZE")?><?=$STEP==5?'</b>':''?></li>
			</ul></p>
		<?else:?>
			<p><ul>
			<li><?=$STEP==2?'<b>':''?><?=GetMessage("IBCONV_PREPARE")?><?=$STEP==2?'</b>':''?></li>
			<?if(is_array($_SESSION["BX_IBLOCK_CONV"])):?>
				<li><?=$STEP==3?'<b>':''?><?=GetMessage("IBCONV_PROGRESS",array("#DONE#"=>$_SESSION["BX_IBLOCK_CONV"]["DONE"],"#TODO#"=>$_SESSION["BX_IBLOCK_CONV"]["TODO"]))?><?=$STEP==3?'</b>':''?></li>
			<?else:?>
				<li><?=$STEP==3?'<b>':''?><?=GetMessage("IBCONV_INPROGRESS")?><?=$STEP==3?'</b>':''?></li>
			<?endif;?>
			<li><?=$STEP==5?'<b>':''?><?=GetMessage("IBCONV_FINALIZE2")?><?=$STEP==5?'</b>':''?></li>
			</ul></p>
		<?endif?>
		<script>setTimeout('DoNext(<?=$WAY?>,<?=$STEP?>)', 500);</script>
	<?else:?>
		<p><?=GetMessage("IBCONV_FINISHED")?></p>
		<p><a href="iblock_edit.php?ID=<?=$arIBlock["ID"]?>&amp;type=<?=$arIBlock["IBLOCK_TYPE_ID"]?>&amp;lang=<?=LANG?>&amp;admin=Y"><?=GetMessage("IBCONV_FINISHED_HREF")?></a></p>
	<?endif;?>
<?endif;?>
<?
$lAdmin->EndCustomContent();
if($STEP==0)
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
else
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
?>
<script language="JavaScript" type="text/javascript">
<!--
function DoNext(way, step){
	var queryString='lang=<?=LANG?>';
	queryString+='&IBLOCK_ID=<?=$IBLOCK_ID?>';
	queryString+='&WAY='+way;
	queryString+='&STEP='+step;
	CHttpRequest.Action = function(result)
	{
		CloseWaitWindow();
		document.getElementById('tbl_iblock_convert_result_div').innerHTML = result;
	}
	ShowWaitWindow();
	CHttpRequest.Send('iblock_convert.php?'+queryString);
}
//-->
</script>
<?
function FirstStep12($arIBlock)
{
	global $DB,$_SESSION,$arErrors,$arMessages,$INTERVAL;
/*	$DB->Query("DROP TABLE b_iblock_element_prop_s31",true);
	$DB->Query("DROP TABLE b_iblock_element_prop_m31",true);
	$DB->Query("DROP SEQUENCE sq_b_iblock_element_prop_m31",true);*/
	$obIBlock = new CIBlock;
	$LAST_CONV_ELEMENT = intval($arIBlock["LAST_CONV_ELEMENT"]);
	if($LAST_CONV_ELEMENT==0)
	{
		$bOK = $obIBlock->_Add($arIBlock["ID"]);
		$DB->Query("UPDATE b_iblock SET LAST_CONV_ELEMENT = -1 WHERE ID = ".$arIBlock["ID"]);
	}
	else
		$bOK = true;
	if($bOK)
	{
		$obIBlockProperty = new CIBlockProperty;
		$strMultiple = "0";
		$strSingle = "0";
		$arProperty = array();
		$strSql = "
			SELECT
				ID
				,MULTIPLE
				,PROPERTY_TYPE
				,IBLOCK_ID
			FROM
				b_iblock_property
			WHERE
				IBLOCK_ID=".$arIBlock["ID"]."
		";
		$rs = $DB->Query($strSql);
		while($ar = $rs->Fetch())
		{
			if($ar["MULTIPLE"]=="Y")
				$strMultiple.=",".$ar["ID"];
			else
				$strSingle.=",".$ar["ID"];
			$arProperty[$ar["ID"]]=$ar;
			if($LAST_CONV_ELEMENT==0)
				$obIBlockProperty->_Add($ar["ID"], $ar);
		}
		$strSql = "
			SELECT
				COUNT('x') AS CNT
			FROM
				b_iblock_element
			WHERE
				IBLOCK_ID=".$arIBlock["ID"]."
				AND ID > ".$LAST_CONV_ELEMENT."
		";
		$rs = $DB->Query($strSql);
		$ar = $rs->Fetch();
		if($ar && $ar["CNT"]>0)
			$_SESSION["BX_IBLOCK_CONV"]=array(
				"DONE"=>0,
				"TODO"=>$ar["CNT"],
				"strMultiple"=>$strMultiple,
				"strSingle"=>$strSingle,
				"arProperty"=>$arProperty,
			);
		else
			return 4;//nothing todo
	}
	else
		$arErrors[]=GetMessage("IBCONV_TABLE_CREATION_ERROR");
	return 3;
}
function NextStep12($arIBlock)
{
	global $DB,$_SESSION,$arErrors,$arMessages,$INTERVAL;
	$LAST_CONV_ELEMENT = intval($arIBlock["LAST_CONV_ELEMENT"]);
	$strSql = "
		SELECT ID
		FROM b_iblock_element
		WHERE IBLOCK_ID = ".$arIBlock["ID"]."
		AND ID>".$LAST_CONV_ELEMENT."
		ORDER BY ID
	";
	$rsID = $DB->Query($strSql);
	$t = getmicrotime();
	$i = 0;
	while($arID = $rsID->Fetch())
	{
		$ELEMENT_ID=$arID["ID"];

		$strSql = "
			INSERT INTO b_iblock_element_prop_s".$arIBlock["ID"]."
			(IBLOCK_ELEMENT_ID)
			VALUES
			(".$ELEMENT_ID.")
		";
		$rs = $DB->Query($strSql, true);
		if(!$rs)
			continue;

		if(strlen($_SESSION["BX_IBLOCK_CONV"]["strMultiple"]))
		{
			$strSql = "
				INSERT INTO b_iblock_element_prop_m".$arIBlock["ID"]."
				(IBLOCK_ELEMENT_ID,IBLOCK_PROPERTY_ID,VALUE,VALUE_ENUM,VALUE_NUM,DESCRIPTION)
				SELECT IBLOCK_ELEMENT_ID,IBLOCK_PROPERTY_ID,VALUE,VALUE_ENUM,VALUE_NUM,DESCRIPTION
				FROM b_iblock_element_property
				WHERE IBLOCK_ELEMENT_ID = ".$ELEMENT_ID."
				AND IBLOCK_PROPERTY_ID IN (".$_SESSION["BX_IBLOCK_CONV"]["strMultiple"].")
			";
			$rs = $DB->Query($strSql);
		}

		if(strlen($_SESSION["BX_IBLOCK_CONV"]["strSingle"]))
		{
			$arFields=array();
			$strSql = "
				SELECT *
				FROM b_iblock_element_property
				WHERE IBLOCK_ELEMENT_ID = ".$ELEMENT_ID."
				AND IBLOCK_PROPERTY_ID IN (".$_SESSION["BX_IBLOCK_CONV"]["strSingle"].")
			";
			$rs = $DB->Query($strSql);
			while($ar = $rs->Fetch())
			{
				$arFields["PROPERTY_".$ar["IBLOCK_PROPERTY_ID"]] = $ar["VALUE"];
				$arFields["DESCRIPTION_".$ar["IBLOCK_PROPERTY_ID"]] = $ar["DESCRIPTION"];
			}
			if(count($arFields)>0)
			{
				$strUpdate = $DB->PrepareUpdateBind("b_iblock_element_prop_s".$arIBlock["ID"], $arFields, "iblock", false, $arBinds);
				if(strlen($strUpdate)>0)
				{
					$strSql = "UPDATE b_iblock_element_prop_s".$arIBlock["ID"]." SET ".$strUpdate." WHERE IBLOCK_ELEMENT_ID=".$ELEMENT_ID;
					$DB->QueryBind($strSql, $arBinds);
				}
			}
		}

		$strSql = "
			DELETE
			FROM b_iblock_element_property
			WHERE IBLOCK_ELEMENT_ID = ".$ELEMENT_ID."
		";
		$rs = $DB->Query($strSql);

		$DB->Query("UPDATE b_iblock SET LAST_CONV_ELEMENT = ".$ELEMENT_ID." WHERE ID = ".$arIBlock["ID"]);

		$i++;
		if((getmicrotime()-$t)>$INTERVAL)
			break;
	}
	$_SESSION["BX_IBLOCK_CONV"]["DONE"]+=$i;
	if($_SESSION["BX_IBLOCK_CONV"]["TODO"]<$_SESSION["BX_IBLOCK_CONV"]["DONE"])
		$_SESSION["BX_IBLOCK_CONV"]["TODO"]=$_SESSION["BX_IBLOCK_CONV"]["DONE"];
	//$_SESSION["BX_IBLOCK_CONV"]["STAT"][]=$i;$arMessages[]=implode(', ',$_SESSION["BX_IBLOCK_CONV"]["STAT"]);
	if($arID)
		return 3;
	else
		return 4;
}
function LastStep12($arIBlock)
{
	global $DB,$_SESSION,$arErrors,$arMessages,$INTERVAL;
	$DB->Query("UPDATE b_iblock_property SET VERSION=2 WHERE IBLOCK_ID = ".$arIBlock["ID"]);
	$DB->Query("UPDATE b_iblock SET VERSION=2,LAST_CONV_ELEMENT = 0 WHERE ID = ".$arIBlock["ID"]);
	$GLOBALS["stackCacheManager"]->Clear("b_iblock");
	return 6;
}
function FirstStep21($arIBlock)
{
	global $DB,$_SESSION,$arErrors,$arMessages,$INTERVAL;
	$strMultiple = "0";
	$strSingle = "0";
	$arProperty = array();
	$strSql = "
		SELECT
			ID
			,MULTIPLE
			,PROPERTY_TYPE
			,IBLOCK_ID
		FROM
			b_iblock_property
		WHERE
			IBLOCK_ID=".$arIBlock["ID"]."
	";
	$rs = $DB->Query($strSql);
	while($ar = $rs->Fetch())
	{
		if($ar["MULTIPLE"]=="Y")
			$strMultiple.=",".$ar["ID"];
		else
			$strSingle.=",".$ar["ID"];
		$arProperty[$ar["ID"]]=$ar;
	}
	$strSql = "
		SELECT
			COUNT('x') AS CNT
		FROM
			b_iblock_element_prop_s".$arIBlock["ID"]."
	";
	$rs = $DB->Query($strSql);
	$ar = $rs->Fetch();
	if($ar && $ar["CNT"]>0)
		$_SESSION["BX_IBLOCK_CONV"]=array(
			"DONE"=>0,
			"TODO"=>$ar["CNT"],
			"strMultiple"=>$strMultiple,
			"strSingle"=>$strSingle,
			"arProperty"=>$arProperty,
		);
	else
		return 4;//nothing todo
	return 3;
}
function NextStep21($arIBlock)
{
	global $DB,$_SESSION,$arErrors,$arMessages,$INTERVAL;
	$strSql = "
		SELECT *
		FROM b_iblock_element_prop_s".$arIBlock["ID"]."
	";
	$rsElement = $DB->Query($strSql);
	$t = getmicrotime();
	$i = 0;
	while($arElement = $rsElement->Fetch())
	{
		$ELEMENT_ID=$arElement["IBLOCK_ELEMENT_ID"];

		foreach($arElement as $key=>$value)
		{
			if(substr($key, 0 ,9)=="PROPERTY_" && strlen($value)>0)
			{
				$ID = intval(substr($key, 9));//TODO make conversion and cehck forsql and bind!!!!
				if($_SESSION["BX_IBLOCK_CONV"]["arProperty"][$ID]["PROPERTY_TYPE"]=="N")
					$value = doubleval($value);
				if($ID > 0 && $_SESSION["BX_IBLOCK_CONV"]["arProperty"][$ID]["MULTIPLE"]=="N")
				{
					$arFields = array(
						"IBLOCK_ELEMENT_ID"=>$ELEMENT_ID,
						"IBLOCK_PROPERTY_ID"=>$ID,
						"VALUE"=>$value,
						"VALUE_NUM"=>$value,
						"VALUE_ENUM"=>$value,
						"DESCRIPTION"=>$arElement["DESCRIPTION_".$ID],
					);
					$arInsert = $DB->PrepareInsert("b_iblock_element_property", $arFields);
					$rs = $DB->QueryBind("INSERT INTO b_iblock_element_property (".$arInsert[0].")VALUES(".$arInsert[1].")", array("DESCRIPTION"=>$arElement["DESCRIPTION_".$ID]));
				}
			}
		}

		$strSql = "
			INSERT INTO b_iblock_element_property
			(IBLOCK_ELEMENT_ID,IBLOCK_PROPERTY_ID,VALUE,VALUE_ENUM,VALUE_NUM,DESCRIPTION)
			SELECT IBLOCK_ELEMENT_ID,IBLOCK_PROPERTY_ID,VALUE,VALUE_ENUM,VALUE_NUM,DESCRIPTION
			FROM b_iblock_element_prop_m".$arIBlock["ID"]."
			WHERE IBLOCK_ELEMENT_ID = ".$ELEMENT_ID."
		";
		$rs = $DB->Query($strSql);

		$strSql = "
			DELETE
			FROM b_iblock_element_prop_m".$arIBlock["ID"]."
			WHERE IBLOCK_ELEMENT_ID = ".$ELEMENT_ID."
		";
		$rs = $DB->Query($strSql);

		$strSql = "
			DELETE
			FROM b_iblock_element_prop_s".$arIBlock["ID"]."
			WHERE IBLOCK_ELEMENT_ID = ".$ELEMENT_ID."
		";
		$rs = $DB->Query($strSql);

		$DB->Query("UPDATE b_iblock SET LAST_CONV_ELEMENT = ".$ELEMENT_ID." WHERE ID = ".$arIBlock["ID"]);

		$i++;
		if((getmicrotime()-$t)>$INTERVAL)
			break;
	}
	$_SESSION["BX_IBLOCK_CONV"]["DONE"]+=$i;
	if($_SESSION["BX_IBLOCK_CONV"]["TODO"]<$_SESSION["BX_IBLOCK_CONV"]["DONE"])
		$_SESSION["BX_IBLOCK_CONV"]["TODO"]=$_SESSION["BX_IBLOCK_CONV"]["DONE"];
	//$_SESSION["BX_IBLOCK_CONV"]["STAT"][]=$i;$arMessages[]=implode(', ',$_SESSION["BX_IBLOCK_CONV"]["STAT"]);
	if($arElement)
		return 3;
	else
		return 4;
}
function LastStep21($arIBlock)
{
	global $DB,$_SESSION,$arErrors,$arMessages,$INTERVAL;
	$DB->Query("UPDATE b_iblock_property SET VERSION=1 WHERE IBLOCK_ID = ".$arIBlock["ID"]);
	$DB->Query("UPDATE b_iblock SET VERSION=1,LAST_CONV_ELEMENT = 0 WHERE ID = ".$arIBlock["ID"]);
	$GLOBALS["stackCacheManager"]->Clear("b_iblock");
	$DB->Query("DROP TABLE b_iblock_element_prop_s".$arIBlock["ID"]);
	$DB->Query("DROP TABLE b_iblock_element_prop_m".$arIBlock["ID"]);
	$DB->Query("DROP SEQUENCE sq_b_iblock_element_prop_m".$arIBlock["ID"], true);
	return 6;
}


$lAdmin->DisplayList();
if($STEP==0)
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
else
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
?>
