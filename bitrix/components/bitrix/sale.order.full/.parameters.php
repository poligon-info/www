<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arYesNo = Array(
	"Y" => GetMessage("SOF_DESC_YES"),
	"N" => GetMessage("SOF_DESC_NO"),
);

$arComponentParameters = Array(
	"PARAMETERS" => Array(
		"PATH_TO_BASKET" => Array(
			"NAME" => GetMessage("SOF_PATH_TO_BASKET"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "basket.php",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"PATH_TO_PERSONAL" => Array(
			"NAME" => GetMessage("SOF_PATH_TO_PERSONAL"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "index.php",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"PATH_TO_AUTH" => Array(
			"NAME" => GetMessage("SOF_PATH_TO_AUTH"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "/auth.php",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"PATH_TO_PAYMENT" => Array(
			"NAME" => GetMessage("SOF_PATH_TO_PAYMENT"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "payment.php",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"ALLOW_PAY_FROM_ACCOUNT" => Array(
			"NAME"=>GetMessage("SOF_ALLOW_PAY_FROM_ACCOUNT"), 
			"TYPE"=>"LIST", "MULTIPLE"=>"N", 
			"VALUES"=>array(
					"N" => GetMessage("SOF_DESC_NO"), 
					"Y" => GetMessage("SOF_DESC_YES")
				), 
			"DEFAULT"=>"Y", 
			"COLS"=>25, 
			"ADDITIONAL_VALUES"=>"N",
			"PARENT" => "BASE",
		),
		"SHOW_MENU" => Array(
			"NAME"=>GetMessage("SOF_SHOW_MENU"), 
			"TYPE"=>"LIST", "MULTIPLE"=>"N", 
			"VALUES"=>array(
					"N" => GetMessage("SOF_DESC_NO"), 
					"Y" => GetMessage("SOF_DESC_YES")
				), 
			"DEFAULT"=>"Y", 
			"COLS"=>25, 
			"ADDITIONAL_VALUES"=>"N",
			"PARENT" => "BASE",
		),
		
		"USE_AJAX_LOCATIONS" => Array(
			"NAME" => GetMessage("SOF_USE_AJAX_LOCATIONS"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"DEFAULT" => "Y",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		
		"SHOW_AJAX_DELIVERY_LINK" => array(
			"NAME" => GetMessage('SOF_SHOW_AJAX_DELIVERY_LINK'),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"VALUES" => array(
				"Y" => GetMessage('SOF_SHOW_AJAX_DELIVERY_LINK_Y'),
				"N" => GetMessage('SOF_SHOW_AJAX_DELIVERY_LINK_N'),
				"S" => GetMessage('SOF_SHOW_AJAX_DELIVERY_LINK_S'),
			),
			"DEFAULT" => "Y",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
		"COUNT_DELIVERY_TAX" => Array(
			"NAME"=>GetMessage("SOF_COUNT_DELIVERY_TAX"), 
			"TYPE"=>"LIST", "MULTIPLE"=>"N", 
			"VALUES"=>array(
					"N" => GetMessage("SOF_DESC_NO"), 
					"Y" => GetMessage("SOF_DESC_YES")
				), 
			"DEFAULT"=>"N", 
			"COLS"=>25, 
			"ADDITIONAL_VALUES"=>"N",
			"PARENT" => "BASE",
		),
		"COUNT_DISCOUNT_4_ALL_QUANTITY" => Array(
			"NAME"=>GetMessage("SOF_COUNT_DISCOUNT_4_ALL_QUANTITY"), 
			"TYPE"=>"LIST", "MULTIPLE"=>"N", 
			"VALUES"=>array(
					"N" => GetMessage("SOF_DESC_NO"), 
					"Y" => GetMessage("SOF_DESC_YES")
				), 
			"DEFAULT"=>"N", 
			"COLS"=>25, 
			"ADDITIONAL_VALUES"=>"N",
			"PARENT" => "BASE",
		),
		"SET_TITLE" => Array(),
		"PRICE_VAT_INCLUDE" => array(
			"NAME" => GetMessage('SOF_VAT_INCLUDE'),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"DEFAULT" => "Y",
			"ADDITIONAL_VALUES"=>"N",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),

		"PRICE_VAT_SHOW_VALUE" => array(
			"NAME" => GetMessage('SOF_VAT_SHOW_VALUE'),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"DEFAULT" => "Y",
			"ADDITIONAL_VALUES"=>"N",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),

	)
);
?>