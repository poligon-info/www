<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("sale")) return;

$rsCountryList = CSaleLocation::GetCountryList(array("SORT" => "ASC"));
$arCountries = array();
while ($arCountry = $rsCountryList->Fetch())
{
	$arCountries[$arCountry["ID"]] = $arCountry["NAME_LANG"];
}

$arComponentParameters = Array(
	"PARAMETERS" => Array(
		"CITY_OUT_LOCATION" => array(
			"NAME" => GetMessage("SALE_SAL_PARAM_CITY_OUT_LOCATION"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"ADDITIONAL_VALUES" => "N",
			"MULTIPLE" => "N",
			"PARENT" => "BASE",
		),
		
		"COUNTRY_INPUT_NAME" => array(
			"NAME" => GetMessage("SALE_SAL_PARAM_COUNTRY_INPUT_NAME"),
			"TYPE" => "STRING",
			"DEFAULT" => "COUNTRY",
			"PARENT" => "BASE",
		),

		"CITY_INPUT_NAME" => array(
			"NAME" => GetMessage("SALE_SAL_PARAM_CITY_INPUT_NAME"),
			"TYPE" => "STRING",
			"DEFAULT" => "LOCATION",
			"PARENT" => "BASE",
		),
		
		"COUNTRY" => array(
			"NAME" => GetMessage("SALE_SAL_PARAM_COUNTRY"),
			"TYPE" => "LIST",
			"VALUES" => $arCountries,
			"ADDITIONAL_VALUES" => "N",
			"MULTIPLE" => "N",
			"PARENT" => "BASE",
		),
		
		"ONCITYCHANGE" => array(
			"NAME" => GetMessage("SALE_SAL_PARAM_ONCITYCHANGE"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
			"PARENT" => "BASE",
		),
	)
);
?>