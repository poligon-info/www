<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/*************************************************************************
	Processing of received parameters
*************************************************************************/
if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 3600;

$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
$arParams["SECTION_ID"] = intval($arParams["SECTION_ID"]);
$arParams["SECTION_CODE"] = trim($arParams["SECTION_CODE"]);

$arParams["SECTION_URL"]=trim($arParams["SECTION_URL"]);
if(strlen($arParams["SECTION_URL"])<=0)
	$arParams["SECTION_URL"] = "section.php?IBLOCK_ID=#IBLOCK_ID#&SECTION_ID=#SECTION_ID#";

$arParams["TOP_DEPTH"] = intval($arParams["TOP_DEPTH"]);
if($arParams["TOP_DEPTH"] <= 0)
	$arParams["TOP_DEPTH"] = 2;
$arParams["COUNT_ELEMENTS"] = $arParams["COUNT_ELEMENTS"]!="N";
$arParams["DISPLAY_PANEL"] = $arParams["DISPLAY_PANEL"]=="Y";
$arParams["ADD_SECTIONS_CHAIN"] = $arParams["ADD_SECTIONS_CHAIN"]!="N"; //Turn on by default

$arResult["SECTIONS"]=array();

/*************************************************************************
			Work with cache
*************************************************************************/
if($this->StartResultCache(false, $USER->GetGroups()))
{
	if(!CModule::IncludeModule("iblock"))
	{
		$this->AbortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}
	$arFilter = array(
		"ACTIVE" => "Y",
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
	);

	$arResult["SECTION"] = false;
	if(strlen($arParams["SECTION_CODE"])>0)
	{
		$arFilter["CODE"] = $arParams["SECTION_CODE"];
		$rsSections = CIBlockSection::GetList(array(), $arFilter, true);
		$arResult["SECTION"] = $rsSections->GetNext();
	}
	elseif($arParams["SECTION_ID"]>0)
	{
		$arFilter["ID"] = $arParams["SECTION_ID"];
		$rsSections = CIBlockSection::GetList(array(), $arFilter, true);
		$arResult["SECTION"] = $rsSections->GetNext();
	}

	if(is_array($arResult["SECTION"]))
	{
		unset($arFilter["ID"]);
		unset($arFilter["CODE"]);
		$arFilter["LEFT_MARGIN"]=$arResult["SECTION"]["LEFT_MARGIN"]+1;
		$arFilter["RIGHT_MARGIN"]=$arResult["SECTION"]["RIGHT_MARGIN"];
		$arFilter["<="."DEPTH_LEVEL"]=$arResult["SECTION"]["DEPTH_LEVEL"] + $arParams["TOP_DEPTH"];

		$arResult["SECTION"]["PATH"] = array();
		$rsPath = GetIBlockSectionPath($arResult["SECTION"]["IBLOCK_ID"], $arResult["SECTION"]["ID"]);
		while($arPath = $rsPath->GetNext())
		{
			if(strlen($arParams["SECTION_URL"]) > 0)
				$arPath["SECTION_PAGE_URL"] = str_replace(
					array("#SERVER_NAME#", "#SITE_DIR#", "#IBLOCK_ID#", "#SECTION_ID#", "#SECTION_CODE#"),
					array(SITE_SERVER_NAME, SITE_DIR, $arPath["IBLOCK_ID"], $arPath["ID"], $arPath["CODE"]),
					$arParams["SECTION_URL"]
				);
			$arResult["SECTION"]["PATH"][]=$arPath;
		}
	}
	else
	{
		$arResult["SECTION"] = array("ID"=>0, "DEPTH_LEVEL"=>0);
		$arFilter["<="."DEPTH_LEVEL"] = $arParams["TOP_DEPTH"];
	}

	$arFilter["CNT_ACTIVE"]="Y";
	//ORDER BY
	$arSort = array(
		"left_margin"=>"asc",
	);
	//EXECUTE
	$rsSections = CIBlockSection::GetList($arSort, $arFilter, $arParams["COUNT_ELEMENTS"]);
	while($arSection = $rsSections->GetNext())
	{
		if(strlen($arParams["SECTION_URL"]) > 0)
			$arSection["SECTION_PAGE_URL"] = htmlspecialchars(str_replace(
				array("#SERVER_NAME#", "#SITE_DIR#", "#IBLOCK_ID#", "#SECTION_ID#", "#SECTION_CODE#"),
				array(SITE_SERVER_NAME, SITE_DIR, $arSection["IBLOCK_ID"], $arSection["ID"], $arSection["CODE"]),
				$arParams["SECTION_URL"]
			));

		$arSection["PICTURE"] = CFile::GetFileArray($arSection["PICTURE"]);

		$arResult["SECTIONS"][]=$arSection;
	}
	//echo "<pre>",htmlspecialchars(print_r($arResult,true)),"</pre>";
	$this->IncludeComponentTemplate();
}

if(count($arResult["SECTIONS"])>0 || isset($arResult["SECTION"]))
{
	if($USER->IsAuthorized())
	{
		if($GLOBALS["APPLICATION"]->GetShowIncludeAreas() && CModule::IncludeModule("iblock"))
			$this->AddIncludeAreaIcons(CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, 0, $arParams["IBLOCK_TYPE"], true));
		if($arParams["DISPLAY_PANEL"] && CModule::IncludeModule("iblock"))
			CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, 0, $arParams["IBLOCK_TYPE"], false, $this->GetName());
	}

	if($arParams["ADD_SECTIONS_CHAIN"] && isset($arResult["SECTION"]) && is_array($arResult["SECTION"]["PATH"]))
	{
		foreach($arResult["SECTION"]["PATH"] as $arPath)
		{
			$APPLICATION->AddChainItem($arPath["NAME"], $arPath["SECTION_PAGE_URL"]);
		}
	}
}

?>
