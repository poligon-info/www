<?
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2006 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/include.php");

$saleModulePermissions = $APPLICATION->GetGroupRight("sale");
if ($saleModulePermissions == "D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);

$arAccessibleSites = array();
$dbAccessibleSites = CSaleGroupAccessToSite::GetList(
		array(),
		array("GROUP_ID" => $GLOBALS["USER"]->GetUserGroupArray()),
		false,
		false,
		array("SITE_ID")
	);
while ($arAccessibleSite = $dbAccessibleSites->Fetch())
{
	if (!in_array($arAccessibleSite["SITE_ID"], $arAccessibleSites))
		$arAccessibleSites[] = $arAccessibleSite["SITE_ID"];
}

$sTableID = "tbl_sale_order";

$oSort = new CAdminSorting($sTableID, "ID", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterFields = array(
	"filter_id_from",
	"filter_id_to",
	"filter_date_from",
	"filter_date_to",
	"filter_date_update_from",
	"filter_date_update_to",
	"filter_lang",
	"filter_currency",
	"filter_status",
	"filter_date_status_from",
	"filter_date_status_to",
	"filter_payed_from",
	"filter_payed_to",
	"filter_payed",
	"filter_allow_delivery",
	"filter_ps_status",
	"filter_pay_system",
	"filter_canceled",
	"filter_buyer",
	"filter_product_id",
	"filter_product_xml_id",
	"filter_affiliate_id",
	"filter_date_delivery_from",
	"filter_date_delivery_to",
//	"filter_discount_name",
//	"filter_discount_value",
	"filter_discount_coupon",
);

$arOrderProps = array();
$dbProps = CSaleOrderProps::GetList(
	array("PERSON_TYPE_ID" => "ASC", "SORT" => "ASC"),
	array(),
	false,
	false,
	array("ID", "NAME", "PERSON_TYPE_NAME", "PERSON_TYPE_ID", "SORT", "IS_FILTERED", "TYPE", "IS_FILTERED")
);
while ($arProps = $dbProps->GetNext())
	$arOrderProps[IntVal($arProps["ID"])] = $arProps;

foreach ($arOrderProps as $key => $value)
	if ($value["IS_FILTERED"] == "Y")
		$arFilterFields[] = "filter_prop_".$key;

$lAdmin->InitFilter($arFilterFields);

$filter_lang = Trim($filter_lang);
if (strlen($filter_lang) > 0)
{
	if (!in_array($filter_lang, $arAccessibleSites) && $saleModulePermissions < "W")
		$filter_lang = "";
}

$arFilter = Array();
if (IntVal($filter_id_from)>0) $arFilter[">=ID"] = IntVal($filter_id_from);
if (IntVal($filter_id_to)>0) $arFilter["<=ID"] = IntVal($filter_id_to);
if (strlen($filter_date_from)>0) $arFilter["DATE_FROM"] = Trim($filter_date_from);
if (strlen($filter_date_to)>0)
{
	if ($arDate = ParseDateTime($filter_date_to, CSite::GetDateFormat("FULL", SITE_ID)))
	{
		if (StrLen($filter_date_to) < 11)
		{
			$arDate["HH"] = 23;
			$arDate["MI"] = 59;
			$arDate["SS"] = 59;
		}

		$filter_date_to = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", SITE_ID)), mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));
		$arFilter["DATE_TO"] = $filter_date_to;
	}
	else
	{
		$filter_date_to = "";
	}
}
if (strlen($filter_date_update_from)>0)
{
	$arFilter["DATE_UPDATE_FROM"] = Trim($filter_date_update_from);
}
elseif(!isset($filter_date_update_from) && strlen($set_filter)<=0)
{
	$filter_date_update_from_DAYS_TO_BACK = COption::GetOptionString("sale", "order_list_date", 30);
	$filter_date_update_from = GetTime(time()-86400*COption::GetOptionString("sale", "order_list_date", 30));
	$arFilter["DATE_UPDATE_FROM"] = $filter_date_update_from;
}
if (strlen($filter_date_update_to)>0)
{
	if ($arDate = ParseDateTime($filter_date_update_to, CSite::GetDateFormat("FULL", SITE_ID)))
	{
		if (StrLen($filter_date_update_to) < 11)
		{
			$arDate["HH"] = 23;
			$arDate["MI"] = 59;
			$arDate["SS"] = 59;
		}

		$filter_date_update_to = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", SITE_ID)), mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));
		$arFilter["DATE_UPDATE_TO"] = $filter_date_update_to;
	}
	else
	{
		$filter_date_update_to = "";
	}
}

if (strlen($filter_lang)>0 && $filter_lang!="NOT_REF") $arFilter["LID"] = Trim($filter_lang);
if (strlen($filter_currency)>0) $arFilter["CURRENCY"] = Trim($filter_currency);

if (isset($filter_status) && !is_array($filter_status) && strlen($filter_status) > 0)
	$filter_status = array($filter_status);
if (isset($filter_status) && is_array($filter_status) && count($filter_status) > 0)
{
	for ($i = 0; $i < count($filter_status); $i++)
	{
		$filter_status[$i] = Trim($filter_status[$i]);
		if (strlen($filter_status[$i]) > 0)
			$arFilter["STATUS_ID"][] = $filter_status[$i];
	}
}
if (strlen($filter_date_status_from)>0) $arFilter["DATE_STATUS_FROM"] = Trim($filter_date_status_from);
if (strlen($filter_date_status_to)>0)
{
	if ($arDate = ParseDateTime($filter_date_status_to, CSite::GetDateFormat("FULL", SITE_ID)))
	{
		if (StrLen($filter_date_status_to) < 11)
		{
			$arDate["HH"] = 23;
			$arDate["MI"] = 59;
			$arDate["SS"] = 59;
		}

		$filter_date_status_to = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", SITE_ID)), mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));
		$arFilter["DATE_STATUS_TO"] = $filter_date_status_to;
	}
	else
	{
		$filter_date_status_to = "";
	}
}

if (strlen($filter_payed_from)>0) $arFilter["DATE_PAYED_FROM"] = Trim($filter_payed_from);
if (strlen($filter_payed_to)>0)
{
	if ($arDate = ParseDateTime($filter_payed_to, CSite::GetDateFormat("FULL", SITE_ID)))
	{
		if (StrLen($filter_payed_to) < 11)
		{
			$arDate["HH"] = 23;
			$arDate["MI"] = 59;
			$arDate["SS"] = 59;
		}

		$filter_payed_to = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", SITE_ID)), mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));
		$arFilter["DATE_PAYED_TO"] = $filter_payed_to;
	}
	else
	{
		$filter_payed_to = "";
	}
}


if (strlen($filter_payed)>0) $arFilter["PAYED"] = Trim($filter_payed);
if (strlen($filter_allow_delivery)>0) $arFilter["ALLOW_DELIVERY"] = Trim($filter_allow_delivery);
if (strlen($filter_ps_status)>0) $arFilter["PS_STATUS"] = Trim($filter_ps_status);
if (IntVal($filter_pay_system)>0) $arFilter["PAY_SYSTEM_ID"] = IntVal($filter_pay_system);
if (strlen($filter_canceled)>0) $arFilter["CANCELED"] = Trim($filter_canceled);
if (strlen($filter_buyer)>0) $arFilter["%BUYER"] = Trim($filter_buyer);
if (IntVal($filter_product_id)>0) $arFilter["BASKET_PRODUCT_ID"] = IntVal($filter_product_id);
if (strlen($filter_product_xml_id)>0) $arFilter["BASKET_PRODUCT_XML_ID"] = Trim($filter_product_xml_id);
if (IntVal($filter_affiliate_id)>0) $arFilter["AFFILIATE_ID"] = IntVal($filter_affiliate_id);
if (strlen($filter_date_delivery_from)>0) $arFilter[">=DATE_ALLOW_DELIVERY"] = Trim($filter_date_delivery_from);
if (strlen($filter_date_delivery_to)>0)
{
	if ($arDate = ParseDateTime($filter_date_delivery_to, CSite::GetDateFormat("FULL", SITE_ID)))
	{
		if (StrLen($filter_date_delivery_to) < 11)
		{
			$arDate["HH"] = 23;
			$arDate["MI"] = 59;
			$arDate["SS"] = 59;
		}

		$filter_date_delivery_to = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", SITE_ID)), mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));
		$arFilter["<=DATE_ALLOW_DELIVERY"] = $filter_date_delivery_to;
	}
	else
	{
		$filter_date_delivery_to = "";
	}
}
if (strlen($filter_discount_name)>0) $arFilter["BASKET_DISCOUNT_NAME"] = Trim($filter_discount_name);
if (strlen($filter_discount_value)>0) $arFilter["BASKET_DISCOUNT_VALUE"] = Trim($filter_discount_value);
if (strlen($filter_discount_coupon)>0) $arFilter["BASKET_DISCOUNT_COUPON"] = Trim($filter_discount_coupon);

foreach ($arOrderProps as $key => $value)
	if ($value["IS_FILTERED"] == "Y")
		if (StrLen(${"filter_prop_".$key}) > 0)
		{
			if($value["TYPE"]=="TEXT" || $value["TYPE"]=="TEXTAREA")
				$arFilter["%PROPERTY_VALUE_".$key] = Trim(${"filter_prop_".$key});
			else
				$arFilter["PROPERTY_VALUE_".$key] = Trim(${"filter_prop_".$key});
		}


if ($saleModulePermissions < "W")
{
	if (count($arAccessibleSites) <= 0)
		$arAccessibleSites = array("**");

	if (strlen($filter_lang) <= 0)
		$arFilter["LID"] = $arAccessibleSites;
}

if ($saleModulePermissions == "W")
	$arFilterTmp = $arFilter;
else
	$arFilterTmp = array_merge(
		$arFilter,
		array(
			"STATUS_PERMS_GROUP_ID" => $GLOBALS["USER"]->GetUserGroupArray(),
			">=STATUS_PERMS_PERM_VIEW" => "Y"
		)
	);

if ($lAdmin->EditAction() && $saleModulePermissions >= "U")
{
	foreach ($FIELDS as $ID => $arFields)
	{
		$ID = IntVal($ID);

		if (!$lAdmin->IsUpdated($ID))
			continue;

		//$DB->StartTransaction();
		$dbOrderTmp = CSaleOrder::GetList(
			array(),
			array("ID" => $ID),
			false,
			false,
			array("ID", "CANCELED", "ALLOW_DELIVERY", "STATUS_ID")
		);
		if ($arOrderTmp = $dbOrderTmp->Fetch())
		{
			if (array_key_exists("CANCELED", $arFields)
				&& ($arFields["CANCELED"] == "Y" || $arFields["CANCELED"] == "N")
				&& $arFields["CANCELED"] != $arOrderTmp["CANCELED"])
			{
				if (CSaleOrder::CanUserCancelOrder($ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID()))
				{
					if (!CSaleOrder::CancelOrder($ID, $arFields["CANCELED"], ""))
					{
						if ($ex = $APPLICATION->GetException())
							$lAdmin->AddUpdateError($ex->GetString(), $ID);
						else
							$lAdmin->AddUpdateError(GetMessage("SOA_ERROR_CANCEL"), $ID);
					}
				}
				else
				{
					$lAdmin->AddUpdateError(GetMessage("SOA_PERMS_CANCEL"), $ID);
				}
			}

			if (array_key_exists("ALLOW_DELIVERY", $arFields)
				&& ($arFields["ALLOW_DELIVERY"] == "Y" || $arFields["ALLOW_DELIVERY"] == "N")
				&& $arFields["ALLOW_DELIVERY"] != $arOrderTmp["ALLOW_DELIVERY"])
			{
				if (CSaleOrder::CanUserChangeOrderFlag($ID, "PERM_DELIVERY", $GLOBALS["USER"]->GetUserGroupArray()))
				{
					if (!CSaleOrder::DeliverOrder($ID, $arFields["ALLOW_DELIVERY"]))
					{
						if ($ex = $APPLICATION->GetException())
							$lAdmin->AddUpdateError($ex->GetString(), $ID);
						else
							$lAdmin->AddUpdateError(GetMessage("SOA_ERROR_DELIV"), $ID);
					}
				}
				else
				{
					$lAdmin->AddUpdateError(GetMessage("SOA_PERMS_DELIV"), $ID);
				}
			}

			if (array_key_exists("STATUS_ID", $arFields)
				&& StrLen($arFields["STATUS_ID"]) > 0
				&& $arFields["STATUS_ID"] != $arOrderTmp["STATUS_ID"])
			{
				if (CSaleOrder::CanUserChangeOrderStatus($ID, $arFields["STATUS_ID"], $GLOBALS["USER"]->GetUserGroupArray()))
				{
					if (!CSaleOrder::StatusOrder($ID, $arFields["STATUS_ID"]))
					{
						if ($ex = $APPLICATION->GetException())
							$lAdmin->AddUpdateError($ex->GetString(), $ID);
						else
							$lAdmin->AddUpdateError(GetMessage("SOA_ERROR_STATUS"), $ID);
					}
				}
				else
				{
					$lAdmin->AddUpdateError(GetMessage("SOA_PERMS_STATUS"), $ID);
				}
			}
		}
		else
		{
			$lAdmin->AddUpdateError(GetMessage("SOA_NO_ORDER"), $ID);
		}

		//$DB->Commit();
	}
}

$events = GetModuleEvents("sale", "OnOrderListFilter");
if ($arEvent = $events->Fetch())
	$arFilterTmp = ExecuteModuleEvent($arEvent, $arFilterTmp);

if (($arID = $lAdmin->GroupAction()) && $saleModulePermissions >= "U")
{
	$arAffectedOrders = array();

	if ($_REQUEST['action_target'] == 'selected')
	{
		$arGroupByTmp = (($saleModulePermissions == "W") ? False : array("ID", "PAY_SYSTEM_ID", "PERSON_TYPE_ID", "MAX" => "STATUS_PERMS_PERM_VIEW"));

		$dbOrderList = CSaleOrder::GetList(
				array($by => $order),
				$arFilterTmp,
				$arGroupByTmp,
				false,
				array("ID", "PAY_SYSTEM_ID", "PERSON_TYPE_ID")
			);
		while ($arOrderList = $dbOrderList->Fetch())
		{
			$arID[] = $arOrderList['ID'];

			if ($_REQUEST['action'] == "update_ps_status")
				$arAffectedOrders[$arOrderList["ID"]] = array(
						"PAY_SYSTEM_ID" => $arOrderList["PAY_SYSTEM_ID"],
						"PERSON_TYPE_ID" => $arOrderList["PERSON_TYPE_ID"]
					);
		}
	}
	elseif ($_REQUEST['action'] == "update_ps_status")
	{
		$arGroupByTmp = (($saleModulePermissions == "W") ? False : array("ID", "PAY_SYSTEM_ID", "PERSON_TYPE_ID", "MAX" => "STATUS_PERMS_PERM_VIEW"));

		$dbOrderList = CSaleOrder::GetList(
				array($by => $order),
				array("ID" => $arID),
				$arGroupByTmp,
				false,
				array("ID", "PAY_SYSTEM_ID", "PERSON_TYPE_ID")
			);
		while ($arOrderList = $dbOrderList->Fetch())
		{
			$arAffectedOrders[$arOrderList["ID"]] = array(
					"PAY_SYSTEM_ID" => $arOrderList["PAY_SYSTEM_ID"],
					"PERSON_TYPE_ID" => $arOrderList["PERSON_TYPE_ID"]
				);
		}
	}

	foreach ($arID as $ID)
	{
		if (strlen($ID) <= 0)
			continue;

		if (CSaleOrder::IsLocked($ID, $lockedBY, $dateLock) && $_REQUEST['action'] != "unlock")
		{
			$lAdmin->AddGroupError(str_replace("#DATE#", "$dateLock", str_replace("#ID#", "$lockedBY", GetMessage("SOE_ORDER_LOCKED"))), $ID);
		}
		else
		{
			switch ($_REQUEST['action'])
			{
				case "delete":
					@set_time_limit(0);

					if (CSaleOrder::CanUserDeleteOrder($ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID()))
					{
						$DB->StartTransaction();

						if (!CSaleOrder::Delete($ID))
						{
							$DB->Rollback();

							if ($ex = $APPLICATION->GetException())
								$lAdmin->AddGroupError($ex->GetString(), $ID);
							else
								$lAdmin->AddGroupError(GetMessage("SALE_DELETE_ERROR"), $ID);
						}
						else
							$DB->Commit();
					}
					else
					{
						$lAdmin->AddGroupError(str_replace("#ID#", $ID, GetMessage("SO_NO_PERMS2DEL")), $ID);
					}

					break;
				case "update_ps_status":

					$psResultFile = "";

					if (!isset($LOCAL_PAY_SYS_CACHE) || !is_array($LOCAL_PAY_SYS_CACHE))
						$LOCAL_PAY_SYS_CACHE = array();

					if (!isset($LOCAL_PAY_SYS_CACHE[$arAffectedOrders[$ID]["PAY_SYSTEM_ID"]][$arAffectedOrders[$ID]["PERSON_TYPE_ID"]])
						|| !is_array($LOCAL_PAY_SYS_CACHE[$arAffectedOrders[$ID]["PAY_SYSTEM_ID"]][$arAffectedOrders[$ID]["PERSON_TYPE_ID"]]))
					{
						$LOCAL_PAY_SYS_CACHE[$arAffectedOrders[$ID]["PAY_SYSTEM_ID"]][$arAffectedOrders[$ID]["PERSON_TYPE_ID"]] = CSalePaySystem::GetByID($arAffectedOrders[$ID]["PAY_SYSTEM_ID"], $arAffectedOrders[$ID]["PERSON_TYPE_ID"]);
					}

					$psActionPath = $_SERVER["DOCUMENT_ROOT"].$LOCAL_PAY_SYS_CACHE[$arAffectedOrders[$ID]["PAY_SYSTEM_ID"]][$arAffectedOrders[$ID]["PERSON_TYPE_ID"]]["PSA_ACTION_FILE"];
					$psActionPath = str_replace("\\", "/", $psActionPath);
					while (substr($psActionPath, strlen($psActionPath) - 1, 1) == "/")
						$psActionPath = substr($psActionPath, 0, strlen($psActionPath) - 1);

					if (file_exists($psActionPath) && is_dir($psActionPath))
					{
						if (file_exists($psActionPath."/result.php") && is_file($psActionPath."/result.php"))
							$psResultFile = $psActionPath."/result.php";
					}
					elseif (strlen($LOCAL_PAY_SYS_CACHE[$arAffectedOrders[$ID]["PAY_SYSTEM_ID"]][$arAffectedOrders[$ID]["PERSON_TYPE_ID"]]["PSA_RESULT_FILE"]) > 0)
					{
						if (file_exists($_SERVER["DOCUMENT_ROOT"].$LOCAL_PAY_SYS_CACHE[$arAffectedOrders[$ID]["PAY_SYSTEM_ID"]][$arAffectedOrders[$ID]["PERSON_TYPE_ID"]]["PSA_RESULT_FILE"])
							&& is_file($_SERVER["DOCUMENT_ROOT"].$LOCAL_PAY_SYS_CACHE[$arAffectedOrders[$ID]["PAY_SYSTEM_ID"]][$arAffectedOrders[$ID]["PERSON_TYPE_ID"]]["PSA_RESULT_FILE"]))
							$psResultFile = $_SERVER["DOCUMENT_ROOT"].$LOCAL_PAY_SYS_CACHE[$arAffectedOrders[$ID]["PAY_SYSTEM_ID"]][$arAffectedOrders[$ID]["PERSON_TYPE_ID"]]["PSA_RESULT_FILE"];
					}

					if (strlen($psResultFile) > 0)
					{
						$ORDER_ID = $ID;
						/*???*/
						CSalePaySystemAction::InitParamArrays(array(), $ID, $_SERVER["DOCUMENT_ROOT"].$LOCAL_PAY_SYS_CACHE[$arAffectedOrders[$ID]["PAY_SYSTEM_ID"]][$arAffectedOrders[$ID]["PERSON_TYPE_ID"]]["PSA_PARAMS"]);
						/*???*/
						if (include($psResultFile))
						{
							$ORDER_ID = IntVal($ORDER_ID);
							$arOrder = CSaleOrder::GetByID($ORDER_ID);
							if ($arOrder)
							{
								if ($arOrder["PS_STATUS"] == "Y" && $arOrder["PAYED"] == "N")
								{
									if ($arOrder["CURRENCY"] == $arOrder["PS_CURRENCY"]
										&& DoubleVal($arOrder["PRICE"]) == DoubleVal($arOrder["PS_SUM"]))
									{
										if (!CSaleOrder::PayOrder($order["ID"], "Y", True, True))
										{
											if ($ex = $APPLICATION->GetException())
												$lAdmin->AddGroupError($ex->GetString(), $ID);
											else
												$lAdmin->AddGroupError(str_replace("#ID#", $ORDER_ID, GetMessage("SO_CANT_PAY_ORDER")), $ID);
										}
									}
								}
							}
							else
							{
								$lAdmin->AddGroupError(str_replace("#ID#", $ORDER_ID, GetMessage("SO_NO_ORDER")), $ID);
							}
						}
					}

					break;

				case "unlock":
					CSaleOrder::UnLock($ID);
				break;
			}
		}
	}
}


$arColumn2Field = array(
		"ID" => array("ID", "DATE_INSERT"),
		"LID" => array("LID"),
		"PERSON_TYPE" => array("PERSON_TYPE_ID"),
		"PAYED" => array("PAYED", "DATE_PAYED", "EMP_PAYED_ID"),
		"PAY_VOUCHER_NUM" => array("PAY_VOUCHER_NUM"),
		"PAY_VOUCHER_DATE" => array("PAY_VOUCHER_DATE"),
		"PAYED" => array("PAYED", "DATE_PAYED", "EMP_PAYED_ID"),
		"CANCELED" => array("CANCELED", "DATE_CANCELED", "EMP_CANCELED_ID"),
		"STATUS" => array("STATUS_ID", "DATE_STATUS", "EMP_STATUS_ID"),
		"PRICE_DELIVERY" => array("PRICE_DELIVERY", "CURRENCY"),
		"ALLOW_DELIVERY" => array("ALLOW_DELIVERY", "DATE_ALLOW_DELIVERY", "EMP_ALLOW_DELIVERY_ID"),
		"PRICE" => array("PRICE", "CURRENCY"),
		"SUM_PAID" => array("SUM_PAID", "CURRENCY"),
		"USER" => array("USER_ID"),
		"PAY_SYSTEM" => array("PAY_SYSTEM_ID"),
		"DELIVERY" => array("DELIVERY_ID"),
		"DATE_UPDATE" => array("DATE_UPDATE"),
		"PS_STATUS" => array("PS_STATUS", "PS_RESPONSE_DATE"),
		"PS_SUM" => array("PS_SUM", "PS_CURRENCY"),
		"TAX_VALUE" => array("TAX_VALUE", "CURRENCY"),
		"LOCK_STATUS" => array("LOCK_STATUS", "LOCK_USER_NAME"),
		"BASKET" => array(),
		"COMMENTS" => array("COMMENTS"),
	);

$arHeaders = array(
	array("id"=>"ID", "content"=>"ID", "sort"=>"ID", "default"=>true),
	array("id"=>"LID","content"=>GetMessage("SI_SITE"), "sort"=>"LID"),
	array("id"=>"PERSON_TYPE","content"=>GetMessage("SI_PAYER_TYPE"), "sort"=>"PERSON_TYPE_ID"),
	array("id"=>"PAYED","content"=>GetMessage("SI_PAID"), "sort"=>"DATE_PAYED", "default"=>true, "title" => GetMessage("SO_S_DATE_PAYED")),
	array("id"=>"PAY_VOUCHER_NUM","content"=>GetMessage("SI_NO_PP"), "sort"=>"PAY_VOUCHER_NUM"),
	array("id"=>"PAY_VOUCHER_DATE","content"=>GetMessage("SI_DATE_PP"), "sort"=>"PAY_VOUCHER_DATE"),
	array("id"=>"CANCELED","content"=>GetMessage("SI_CANCELED"), "sort"=>"CANCELED", "default"=>true),
	array("id"=>"STATUS","content"=>GetMessage("SI_STATUS"), "sort"=>"DATE_STATUS", "default"=>true, "title" => GetMessage("SO_S_DATE_STATUS")),
	array("id"=>"PRICE_DELIVERY","content"=>GetMessage("SI_DELIVERY"), "sort"=>"PRICE_DELIVERY"),
	array("id"=>"ALLOW_DELIVERY","content"=>GetMessage("SI_ALLOW_DELIVERY"), "sort"=>"ALLOW_DELIVERY"),
	array("id"=>"PRICE","content"=>GetMessage("SI_SUM"), "sort"=>"PRICE", "default"=>true),
	array("id"=>"SUM_PAID","content"=>GetMessage("SI_SUM_PAID"), "sort"=>"SUM_PAID"),
	array("id"=>"USER","content"=>GetMessage("SI_BUYER"), "sort"=>"USER_ID", "default"=>true),
	array("id"=>"PAY_SYSTEM","content"=>GetMessage("SI_PAY_SYS"), "sort"=>"PAY_SYSTEM_ID", "default"=>true),
	array("id"=>"DELIVERY","content"=>GetMessage("SI_DELIVERY_SYS"), "sort"=>"DELIVERY_ID"),
	array("id"=>"DATE_UPDATE","content"=>GetMessage("SI_DATE_UPDATE"), "sort"=>"DATE_UPDATE"),
	array("id"=>"PS_STATUS","content"=>GetMessage("SI_PAYMENT_PS"), "sort"=>"PS_STATUS", "default"=>true),
	array("id"=>"PS_SUM","content"=>GetMessage("SI_PS_SUM"), "sort"=>"PS_SUM"),
	array("id"=>"TAX_VALUE","content"=>GetMessage("SI_TAX"), "sort"=>"TAX_VALUE"),
	array("id"=>"LOCK_STATUS","content"=>GetMessage("SI_LOCK_STATUS"), "sort"=>"", "default"=>true),
	array("id"=>"BASKET","content"=>GetMessage("SI_ITEMS"), "sort"=>"", "default"=>true),
	array("id"=>"COMMENTS","content"=>GetMessage("SI_COMMENTS"), "sort"=>"COMMENTS", "default"=>false),
);

foreach ($arOrderProps as $key => $value)
{
	$arHeaders[] = array("id" => "PROP_".$key, "content" => $value["NAME"]." (".$value["PERSON_TYPE_NAME"].")", "sort" => "", "default" => false);
	$arColumn2Field["PROP_".$key] = array();
}

$lAdmin->AddHeaders($arHeaders);

$arSelectFields = array();
$arVisibleColumns = $lAdmin->GetVisibleHeaderColumns();
$bNeedProps = False;
foreach ($arVisibleColumns as $visibleColumn)
{
	if (!$bNeedProps && SubStr($visibleColumn, 0, StrLen("PROP_")) == "PROP_")
		$bNeedProps = True;

	if (array_key_exists($visibleColumn, $arColumn2Field))
	{
		if (is_array($arColumn2Field[$visibleColumn]) && count($arColumn2Field[$visibleColumn]) > 0)
		{
			for ($i = 0; $i < count($arColumn2Field[$visibleColumn]); $i++)
			{
				if (!in_array($arColumn2Field[$visibleColumn][$i], $arSelectFields))
					$arSelectFields[] = $arColumn2Field[$visibleColumn][$i];
			}
		}
	}
}


if($saleModulePermissions == "W")
{
	if (array_key_exists("BASKET_DISCOUNT_NAME", $arFilter) || array_key_exists("BASKET_DISCOUNT_VALUE", $arFilter) || array_key_exists("BASKET_DISCOUNT_COUPON", $arFilter))
		$arGroupByTmp = $arSelectedFields;
	else
		$arGroupByTmp = false;
}
else
	$arGroupByTmp = array_merge($arSelectFields, array("MAX" => "STATUS_PERMS_PERM_VIEW"));

$dbOrderList = CSaleOrder::GetList(
	array($by => $order),
	$arFilterTmp,
	$arGroupByTmp,
	false,
	$arSelectFields
);

$dbOrderList = new CAdminResult($dbOrderList, $sTableID);
$dbOrderList->NavStart();

$lAdmin->NavText($dbOrderList->GetNavPrint(GetMessage("SALE_PRLIST")));

while ($arOrder = $dbOrderList->NavNext(true, "f_"))
{
	$row =& $lAdmin->AddRow($f_ID, $arOrder, "sale_order_detail.php?ID=".$f_ID."&lang=".LANGUAGE_ID.GetFilterParams("filter_"), GetMessage("SALE_DETAIL_DESCR"));

	$row->AddField("ID", "<b><a href='/bitrix/admin/sale_order_detail.php?ID=".$f_ID.GetFilterParams("filter_")."&lang=".LANGUAGE_ID."' title='".GetMessage("SALE_DETAIL_DESCR")."'>".GetMessage("SO_ORDER_ID_PREF").$arOrder["ID"]."</a></b><br>".GetMessage("SO_FROM").$arOrder["DATE_INSERT"]);

	$fieldValue = "";
	if (in_array("LID", $arVisibleColumns))
	{
		if (!isset($LOCAL_SITE_LIST_CACHE[$arOrder["LID"]])
			|| !is_array($LOCAL_SITE_LIST_CACHE[$arOrder["LID"]]))
		{
			$dbSite = CSite::GetByID($arOrder["LID"]);
			if ($arSite = $dbSite->Fetch())
				$LOCAL_SITE_LIST_CACHE[$arOrder["LID"]] = htmlspecialcharsEx($arSite["NAME"]);
		}
		$fieldValue = "[".$arOrder["LID"]."] ".$LOCAL_SITE_LIST_CACHE[$arOrder["LID"]];
	}
	$row->AddField("LID", $fieldValue);

	$fieldValue = "";
	if (in_array("PERSON_TYPE", $arVisibleColumns))
	{
		if (!isset($LOCAL_PERSON_TYPE_CACHE[$arOrder["PERSON_TYPE_ID"]])
			|| !is_array($LOCAL_PERSON_TYPE_CACHE[$arOrder["PERSON_TYPE_ID"]]))
		{
			if ($arPersonType = CSalePersonType::GetByID($arOrder["PERSON_TYPE_ID"]))
				$LOCAL_PERSON_TYPE_CACHE[$arOrder["PERSON_TYPE_ID"]] = htmlspecialcharsEx($arPersonType["NAME"]);
		}
		$fieldValue = "[";
		if ($saleModulePermissions >= "W")
			$fieldValue .= "<a href=\"/bitrix/admin/sale_person_type.php?lang=".LANG."\">";
		$fieldValue .= $arOrder["PERSON_TYPE_ID"];
		if ($saleModulePermissions >= "W")
			$fieldValue .= "</a>";
		$fieldValue .= "] ".$LOCAL_PERSON_TYPE_CACHE[$arOrder["PERSON_TYPE_ID"]];
	}
	$row->AddField("PERSON_TYPE", $fieldValue);

	$fieldValue = "";
	if (in_array("PAYED", $arVisibleColumns))
	{
		$fieldValue .= (($arOrder["PAYED"] == "Y") ? GetMessage("SO_YES") : GetMessage("SO_NO"))."<br>";
		$fieldValue .= $arOrder["DATE_PAYED"];
		if (IntVal($arOrder["EMP_PAYED_ID"]) > 0)
		{
			if (!isset($LOCAL_PAYED_USER_CACHE[$arOrder["EMP_PAYED_ID"]])
				|| !is_array($LOCAL_PAYED_USER_CACHE[$arOrder["EMP_PAYED_ID"]]))
			{
				$dbUser = CUser::GetByID($arOrder["EMP_PAYED_ID"]);
				if ($arUser = $dbUser->Fetch())
					$LOCAL_PAYED_USER_CACHE[$arOrder["EMP_PAYED_ID"]] = htmlspecialcharsEx($arUser["NAME"].((strlen($arUser["NAME"])<=0 || strlen($arUser["LAST_NAME"])<=0) ? "" : " ").$arUser["LAST_NAME"]." (".$arUser["LOGIN"].")");
			}
			$fieldValue .= "<br>[<a href=\"/bitrix/admin/user_edit.php?ID=".$arOrder["EMP_PAYED_ID"]."&lang=".LANG."\">".$arOrder["EMP_PAYED_ID"]."</a>] ";
			$fieldValue .= $LOCAL_PAYED_USER_CACHE[$arOrder["EMP_PAYED_ID"]];
		}
	}
	$row->AddField("PAYED", $fieldValue);

	$row->AddField("PAY_VOUCHER_NUM", $f_PAY_VOUCHER_NUM);
	$row->AddField("PAY_VOUCHER_DATE", $f_PAY_VOUCHER_DATE);

	if ($row->bEditMode != true
		|| $row->bEditMode == true && !CSaleOrder::CanUserCancelOrder($f_ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID()))
	{
		$fieldValue = "";
		if (in_array("CANCELED", $arVisibleColumns))
		{
			$fieldValue .= (($arOrder["CANCELED"] == "Y") ? GetMessage("SO_YES") : GetMessage("SO_NO"))."<br>";
			$fieldValue .= $arOrder["DATE_CANCELED"];
			if (IntVal($arOrder["EMP_CANCELED_ID"]) > 0)
			{
				if (!isset($LOCAL_PAYED_USER_CACHE[$arOrder["EMP_CANCELED_ID"]])
					|| !is_array($LOCAL_PAYED_USER_CACHE[$arOrder["EMP_CANCELED_ID"]]))
				{
					$dbUser = CUser::GetByID($arOrder["EMP_CANCELED_ID"]);
					if ($arUser = $dbUser->Fetch())
						$LOCAL_PAYED_USER_CACHE[$arOrder["EMP_CANCELED_ID"]] = htmlspecialcharsEx($arUser["NAME"].((strlen($arUser["NAME"])<=0 || strlen($arUser["LAST_NAME"])<=0) ? "" : " ").$arUser["LAST_NAME"]." (".$arUser["LOGIN"].")");
				}
				$fieldValue .= "<br>[<a href=\"/bitrix/admin/user_edit.php?ID=".$arOrder["EMP_CANCELED_ID"]."&lang=".LANG."\">".$arOrder["EMP_CANCELED_ID"]."</a>] ";
				$fieldValue .= $LOCAL_PAYED_USER_CACHE[$arOrder["EMP_CANCELED_ID"]];
			}
		}
		$row->AddField("CANCELED", $fieldValue, $fieldValue);
	}
	else
	{
		$row->AddCheckField("CANCELED");
	}

	if (in_array("STATUS", $arVisibleColumns))
	{
		if ($row->bEditMode == true)
		{
			$arStatusList = False;
			$arFilter = array("LID" => LANG);
			$arGroupByTmp = false;
			if ($saleModulePermissions < "W")
			{
				$arFilter["GROUP_ID"] = $GLOBALS["USER"]->GetUserGroupArray();
				$arFilter["PERM_STATUS_FROM"] = "Y";
				$arFilter["ID"] = $arOrder["STATUS_ID"];
				$arGroupByTmp = array("ID", "NAME", "MAX" => "PERM_STATUS_FROM");
			}
			$dbStatusList = CSaleStatus::GetList(
					array(),
					$arFilter,
					$arGroupByTmp,
					false,
					array("ID", "NAME")
				);
			$arStatusList = $dbStatusList->GetNext();
		}

		if ($row->bEditMode != true
			|| $row->bEditMode == true && !$arStatusList)
		{
			$fieldValue = "";
			if (in_array("STATUS", $arVisibleColumns))
			{
				if (!isset($LOCAL_STATUS_CACHE[$arOrder["STATUS_ID"]])
					|| !is_array($LOCAL_STATUS_CACHE[$arOrder["STATUS_ID"]]))
				{
					if ($arStatus = CSaleStatus::GetByID($arOrder["STATUS_ID"]))
						$LOCAL_STATUS_CACHE[$arOrder["STATUS_ID"]] = htmlspecialcharsEx($arStatus["NAME"]);
				}

				$fieldValue .= "[";
				if ($saleModulePermissions >= "W")
					$fieldValue .= "<a href=\"/bitrix/admin/sale_status_edit.php?ID=".$arOrder["STATUS_ID"]."&lang=".LANG."\">";
				$fieldValue .= $arOrder["STATUS_ID"];
				if ($saleModulePermissions >= "W")
					$fieldValue .= "</a>";

				$fieldValue .= "] ".$LOCAL_STATUS_CACHE[$arOrder["STATUS_ID"]]."<br>";
				$fieldValue .= $arOrder["DATE_STATUS"];
				if (IntVal($arOrder["EMP_STATUS_ID"]) > 0)
				{
					if (!isset($LOCAL_PAYED_USER_CACHE[$arOrder["EMP_STATUS_ID"]])
						|| !is_array($LOCAL_PAYED_USER_CACHE[$arOrder["EMP_STATUS_ID"]]))
					{
						$dbUser = CUser::GetByID($arOrder["EMP_STATUS_ID"]);
						if ($arUser = $dbUser->Fetch())
							$LOCAL_PAYED_USER_CACHE[$arOrder["EMP_STATUS_ID"]] = htmlspecialcharsEx($arUser["NAME"].((strlen($arUser["NAME"])<=0 || strlen($arUser["LAST_NAME"])<=0) ? "" : " ").$arUser["LAST_NAME"]." (".$arUser["LOGIN"].")");
					}
					$fieldValue .= "<br>[<a href=\"/bitrix/admin/user_edit.php?ID=".$arOrder["EMP_STATUS_ID"]."&lang=".LANG."\">".$arOrder["EMP_STATUS_ID"]."</a>] ";
					$fieldValue .= $LOCAL_PAYED_USER_CACHE[$arOrder["EMP_STATUS_ID"]];
				}
			}
			$row->AddField("STATUS", $fieldValue, $fieldValue);
		}
		else
		{
			if ($row->VarsFromForm() && $_REQUEST["FIELDS"])
				$val = $_REQUEST["FIELDS"][$f_ID]["STATUS_ID"];
			else
				$val = $f_STATUS_ID;

			$fieldValue = "<select name=\"FIELDS[".$f_ID."][STATUS_ID]\">";
			$arFilter = array("LID" => LANG);
			$arGroupByTmp = false;
			
			if ($saleModulePermissions < "W")
			{
				$arFilter["GROUP_ID"] = $GLOBALS["USER"]->GetUserGroupArray();
				$arFilter["PERM_STATUS"] = "Y";
				//$arGroupByTmp = array("ID", "NAME", "MAX" => "PERM_STATUS");
			}
			$dbStatusListTmp = CSaleStatus::GetList(
					array("SORT" => "ASC"),
					$arFilter,
					$arGroupByTmp,
					false,
					array("ID", "NAME")
				);
			while($arStatusListTmp = $dbStatusListTmp->GetNext())
			{
				$fieldValue .= "<option value=\"".$arStatusListTmp["ID"]."\"".(($arStatusListTmp["ID"] == $val) ? " selected" : "").">[".$arStatusListTmp["ID"]."] ".$arStatusListTmp["NAME"]."</option>";
			}
			$fieldValue .= "</select>";

			$row->AddField("STATUS", $fieldValue, $fieldValue);
		}
	}
	else
	{
		$row->AddField("STATUS", "");
	}

	$row->AddField("PRICE_DELIVERY", SaleFormatCurrency($arOrder["PRICE_DELIVERY"], $arOrder["CURRENCY"]));

	$fieldValue = "";
	if (in_array("ALLOW_DELIVERY", $arVisibleColumns))
	{
		if ($row->bEditMode != true
			|| $row->bEditMode == true && !CSaleOrder::CanUserChangeOrderFlag($f_ID, "PERM_DELIVERY", $GLOBALS["USER"]->GetUserGroupArray()))
		{
			$fieldValue .= (($arOrder["ALLOW_DELIVERY"] == "Y") ? GetMessage("SO_YES") : GetMessage("SO_NO"))."<br>";
			$fieldValue .= $arOrder["DATE_ALLOW_DELIVERY"];
			if (IntVal($arOrder["EMP_ALLOW_DELIVERY_ID"]) > 0)
			{
				if (!isset($LOCAL_PAYED_USER_CACHE[$arOrder["EMP_ALLOW_DELIVERY_ID"]])
					|| !is_array($LOCAL_PAYED_USER_CACHE[$arOrder["EMP_ALLOW_DELIVERY_ID"]]))
				{
					$dbUser = CUser::GetByID($arOrder["EMP_ALLOW_DELIVERY_ID"]);
					if ($arUser = $dbUser->Fetch())
						$LOCAL_PAYED_USER_CACHE[$arOrder["EMP_ALLOW_DELIVERY_ID"]] = htmlspecialcharsEx($arUser["NAME"].((strlen($arUser["NAME"])<=0 || strlen($arUser["LAST_NAME"])<=0) ? "" : " ").$arUser["LAST_NAME"]." (".$arUser["LOGIN"].")");
				}
				$fieldValue .= "<br>[<a href=\"/bitrix/admin/user_edit.php?ID=".$arOrder["EMP_ALLOW_DELIVERY_ID"]."&lang=".LANG."\">".$arOrder["EMP_ALLOW_DELIVERY_ID"]."</a>] ";
				$fieldValue .= $LOCAL_PAYED_USER_CACHE[$arOrder["EMP_ALLOW_DELIVERY_ID"]];
			}
		
			$row->AddField("ALLOW_DELIVERY", $fieldValue, $fieldValue);
		}
		else
		{
			$row->AddCheckField("ALLOW_DELIVERY");
		}
	}
	else
	{
		$row->AddField("ALLOW_DELIVERY", $fieldValue, $fieldValue);
	}

	$row->AddField("PRICE", SaleFormatCurrency($arOrder["PRICE"], $arOrder["CURRENCY"]));
	$row->AddField("SUM_PAID", SaleFormatCurrency($arOrder["SUM_PAID"], $arOrder["CURRENCY"]));

	$fieldValue = "";
	if (in_array("USER", $arVisibleColumns))
	{
		if (!isset($LOCAL_PAYED_USER_CACHE[$arOrder["USER_ID"]])
			|| !is_array($LOCAL_PAYED_USER_CACHE[$arOrder["USER_ID"]]))
		{
			$dbUser = CUser::GetByID($arOrder["USER_ID"]);
			if ($arUser = $dbUser->Fetch())
				$LOCAL_PAYED_USER_CACHE[$arOrder["USER_ID"]] = htmlspecialcharsEx($arUser["NAME"].((strlen($arUser["NAME"])<=0 || strlen($arUser["LAST_NAME"])<=0) ? "" : " ").$arUser["LAST_NAME"]." (".$arUser["LOGIN"].")");
		}
		$fieldValue .= "[<a href=\"/bitrix/admin/user_edit.php?ID=".$arOrder["USER_ID"]."&lang=".LANG."\">".$arOrder["USER_ID"]."</a>] ";
		$fieldValue .= $LOCAL_PAYED_USER_CACHE[$arOrder["USER_ID"]];
	}
	$row->AddField("USER", $fieldValue);

	$fieldValue = "";
	if (in_array("PAY_SYSTEM", $arVisibleColumns))
	{
		if (IntVal($arOrder["PAY_SYSTEM_ID"]) > 0)
		{
			if (!isset($LOCAL_PAY_SYSTEM_CACHE[$arOrder["PAY_SYSTEM_ID"]])
				|| !is_array($LOCAL_PAY_SYSTEM_CACHE[$arOrder["PAY_SYSTEM_ID"]]))
			{
				if ($arPaySys = CSalePaySystem::GetByID($arOrder["PAY_SYSTEM_ID"]))
					$LOCAL_PAY_SYSTEM_CACHE[$arOrder["PAY_SYSTEM_ID"]] = htmlspecialcharsEx($arPaySys["NAME"]);
			}

			$fieldValue .= "[";
			if ($saleModulePermissions >= "W")
				$fieldValue .= "<a href=\"/bitrix/admin/sale_pay_system_edit.php?ID=".$arOrder["PAY_SYSTEM_ID"]."&lang=".LANG."\">";
			$fieldValue .= $arOrder["PAY_SYSTEM_ID"];
			if ($saleModulePermissions >= "W")
				$fieldValue .= "</a>";

			$fieldValue .= "] ".$LOCAL_PAY_SYSTEM_CACHE[$arOrder["PAY_SYSTEM_ID"]];
		}
	}
	$row->AddField("PAY_SYSTEM", $fieldValue);

	$fieldValue = "";
	if (in_array("DELIVERY", $arVisibleColumns))
	{
		if (strpos($arOrder["DELIVERY_ID"], ":") !== false)
		{
			if (!isset($obDelivery))
			{
				$obDelivery = new CSaleDeliveryHandler();
				$obDelivery->GetList(array("SITE_ID" => "ALL", "ACTIVE" => "ALL"));
			}
			
			$arId = explode(":", $arOrder["DELIVERY_ID"]);

			$rsDelivery = CSaleDeliveryHandler::GetBySID($arId[0]);
			$arDelivery = $rsDelivery->Fetch();
			
			$fieldValue .= "[";
			if ($saleModulePermissions >= "W")
				$fieldValue .= "<a href=\"/bitrix/admin/sale_delivery_handler_edit.php?SID=".$arId[0]."&lang=".LANG."\">";
			$fieldValue .= $arOrder["DELIVERY_ID"];
			if ($saleModulePermissions >= "W")
				$fieldValue .= "</a>";
				
			$fieldValue .= "] ".htmlspecialcharsEx($arDelivery["NAME"]);
			$fieldValue .= " (".htmlspecialcharsEx($arDelivery["PROFILES"][$arId[1]]["TITLE"]).")";
		}
		elseif (IntVal($arOrder["DELIVERY_ID"]) > 0)
		{
			if (!isset($LOCAL_DELIVERY_CACHE[$arOrder["DELIVERY_ID"]])
				|| !is_array($LOCAL_DELIVERY_CACHE[$arOrder["DELIVERY_ID"]]))
			{
				if ($arDelivery = CSaleDelivery::GetByID($arOrder["DELIVERY_ID"]))
					$LOCAL_DELIVERY_CACHE[$arOrder["DELIVERY_ID"]] = htmlspecialcharsEx($arDelivery["NAME"]);
			}

			$fieldValue .= "[";
			if ($saleModulePermissions >= "W")
				$fieldValue .= "<a href=\"/bitrix/admin/sale_delivery_edit.php?ID=".$arOrder["DELIVERY_ID"]."&lang=".LANG."\">";
			$fieldValue .= $arOrder["DELIVERY_ID"];
			if ($saleModulePermissions >= "W")
				$fieldValue .= "</a>";

			$fieldValue .= "] ".$LOCAL_DELIVERY_CACHE[$arOrder["DELIVERY_ID"]];
		}
	}
	$row->AddField("DELIVERY", $fieldValue);

	$row->AddField("DATE_UPDATE", $arOrder["DATE_UPDATE"]);

	$fieldValue = "";
	if ($arOrder["PS_STATUS"] == "Y")
		$fieldValue = GetMessage("SO_SUCCESS")."<br>".$arOrder["PS_RESPONSE_DATE"];
	elseif ($arOrder["PS_STATUS"] == "N")
		$fieldValue = GetMessage("SO_UNSUCCESS")."<br>".$arOrder["PS_RESPONSE_DATE"];
	else
		$fieldValue = GetMessage("SO_NONE");
	$row->AddField("PS_STATUS", $fieldValue);

	$row->AddField("PS_SUM", SaleFormatCurrency($arOrder["PS_SUM"], $arOrder["PS_CURRENCY"]));
	$row->AddField("TAX_VALUE", SaleFormatCurrency($arOrder["TAX_VALUE"], $arOrder["CURRENCY"]));

	$lamp = "/bitrix/images/sale/".$arOrder['LOCK_STATUS'].".gif";
	if ($arOrder['LOCK_STATUS']=="green")
		$lamp_alt = GetMessage("SMOL_GREEN_ALT");
	elseif($arOrder['LOCK_STATUS']=="yellow")
		$lamp_alt = GetMessage("SMOL_YELLOW_ALT");
	else
		$lamp_alt = str_replace("#LOCK_USER_NAME#", $arOrder['LOCK_USER_NAME'], GetMessage("SMOL_RED_ALT"));

	$row->AddViewField("LOCK_STATUS", '<img src="'.$lamp.'" hspace="4" alt="'.htmlspecialchars($lamp_alt).'" title="'.htmlspecialchars($lamp_alt).'" />');


	$fieldValue = "";
	if (in_array("BASKET", $arVisibleColumns))
	{
		$bNeedLine = False;
		$dbItemsList = CSaleBasket::GetList(
				array("NAME" => "ASC"),
				array("ORDER_ID" => $arOrder["ID"])
			);
		while ($arItem = $dbItemsList->GetNext())
		{
			if ($bNeedLine)
				$fieldValue .= "<hr size=\"1\" width=\"90%\">";
			$bNeedLine = True;

			$fieldValue .= "[".$arItem["PRODUCT_ID"]."] ";

			if (strlen($arItem["DETAIL_PAGE_URL"]) > 0)
				$fieldValue .= "<a href=\"".$arItem["DETAIL_PAGE_URL"]."\">";

			$fieldValue .= $arItem["NAME"];

			if (strlen($arItem["DETAIL_PAGE_URL"]) > 0)
				$fieldValue .= "</a>";

			$fieldValue .= " <nobr>(".$arItem["QUANTITY"]." ".GetMessage("SO_SHT").")</nobr>";
		}
	}
	$row->AddField("BASKET", $fieldValue);

	if ($bNeedProps)
	{
		$dbProps = CSaleOrderPropsValue::GetOrderProps($arOrder["ID"]);
		while ($arProps = $dbProps->GetNext())
			if (array_key_exists($arProps["ORDER_PROPS_ID"], $arOrderProps))
			{
				if($arProps["TYPE"] == "MULTISELECT" || $arProps["TYPE"] == "SELECT" || $arProps["TYPE"] == "RADIO")
				{
					if($arProps["TYPE"] == "MULTISELECT")
					{
						$valMulti = "";
						$curVal = split(",", $arProps["VALUE"]);
						$bNeedLine = false;
						foreach ($curVal as $val)
						{
							if ($bNeedLine)
								$valMulti .= "<hr size=\"1\" width=\"90%\">";
							$arPropVariant = CSaleOrderPropsVariant::GetByValue($arProps["ORDER_PROPS_ID"], $val);
							$valMulti .= "[".htmlspecialcharsEx($val)."] ".htmlspecialcharsEx($arPropVariant["NAME"])."<br />";
							$bNeedLine = true;
						}
						$row->AddField("PROP_".$arProps["ORDER_PROPS_ID"], $valMulti);
					}
					else
					{
						$arPropVariant = CSaleOrderPropsVariant::GetByValue($arProps["ORDER_PROPS_ID"], $arProps["VALUE"]);
						$row->AddField("PROP_".$arProps["ORDER_PROPS_ID"], "[".htmlspecialcharsEx($arProps["VALUE"])."] ".htmlspecialcharsEx($arPropVariant["NAME"]));
					}
				}
				elseif($arProps["TYPE"] == "CHECKBOX")
				{
					if($arProps["VALUE"] == "Y")
						$row->AddField("PROP_".$arProps["ORDER_PROPS_ID"], GetMessage("SALE_YES"));
				}
				elseif($arProps["TYPE"] == "LOCATION")
				{
					$arVal = CSaleLocation::GetByID($arProps["VALUE"], LANG);
					$row->AddField("PROP_".$arProps["ORDER_PROPS_ID"], htmlspecialcharsEx($arVal["COUNTRY_NAME"].((strlen($arVal["COUNTRY_NAME"])<=0 || strlen($arVal["CITY_NAME"])<=0) ? "" : " - ").$arVal["CITY_NAME"]));
				}
				else
					$row->AddField("PROP_".$arProps["ORDER_PROPS_ID"], $arProps["VALUE"]);
			}
	}
	else
	{
		foreach ($arOrderProps as $key => $value)
			$row->AddField("PROP_".$key, "");
	}

	$arActions = array();

	if ($arOrder['LOCK_STATUS'] == "red" && $saleModulePermissions >= "W" || $arOrder['LOCK_STATUS'] == "yellow")
	{
		$arActions[] = array(
			"ICON" => "unlock",
			"TEXT" => GetMessage("IBEL_A_UNLOCK"),
			"TITLE" => GetMessage("IBLOCK_UNLOCK_ALT"),
			"ACTION" => "if(confirm('".GetMessage("IBLOCK_UNLOCK_CONFIRM")."')) ".$lAdmin->ActionDoGroup($arOrder["ID"], "unlock", GetFilterParams('find_'))
		);
		$arActions[] = array("SEPARATOR" => true);
	}

	$arActions[] = array("ICON"=>"view", "TEXT"=>GetMessage("SALE_DETAIL_DESCR"), "ACTION"=>$lAdmin->ActionRedirect("sale_order_detail.php?ID=".$f_ID."&lang=".LANGUAGE_ID.GetFilterParams("filter_")), "DEFAULT"=>true);
	$arActions[] = array("ICON"=>"print", "TEXT"=>GetMessage("SALE_PRINT_DESCR"), "ACTION"=>$lAdmin->ActionRedirect("sale_order_print.php?ID=".$f_ID."&lang=".LANGUAGE_ID.GetFilterParams("filter_")));
	if (CSaleOrder::CanUserUpdateOrder($f_ID, $GLOBALS["USER"]->GetUserGroupArray()))
		$arActions[] = array("ICON"=>"edit", "TEXT"=>GetMessage("SALE_OEDIT_DESCR"), "ACTION"=>$lAdmin->ActionRedirect("sale_order_edit.php?ID=".$f_ID."&lang=".LANGUAGE_ID.GetFilterParams("filter_")));
	if ($saleModulePermissions == "W"
		|| $f_PAYED != "Y" && CSaleOrder::CanUserDeleteOrder($f_ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID()))
	{
		$arActions[] = array("SEPARATOR" => true);
		$arActions[] = array("ICON"=>"delete", "TEXT"=>GetMessage("SALE_DELETE_DESCR"), "ACTION"=>"if(confirm('".GetMessage('SALE_CONFIRM_DEL_MESSAGE')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete"));
	}

	$row->AddActions($arActions);
}

$arFooterArray = array(
	array(
		"title" => GetMessage('SOAN_FILTERED'),
		"value" => $dbOrderList->SelectedRowsCount()
	),
	array(
		"counter" => true,
		"title" => GetMessage('SOAN_SELECTED'),
		"value" => "0"
	)
);

if ($saleModulePermissions == "W")
{
	$dbOrderList = CSaleOrder::GetList(
		array("CURRENCY" => "ASC"),
		$arFilterTmp,
		array("CURRENCY", "SUM" => "PRICE"),
		false,
		array("CURRENCY", "SUM" => "PRICE")
	);
	while ($arOrderList = $dbOrderList->Fetch())
	{
		$arFooterArray[] = array(
			"title" => GetMessage("SOAN_ITOG")." ".$arOrderList["CURRENCY"].":",
			"value" => SaleFormatCurrency($arOrderList["PRICE"], $arOrderList["CURRENCY"])
		);
	}
}
elseif (($saleModulePermissions < "W") && (COption::GetOptionString("sale", "show_order_sum", "N")=="Y"))
{
	$arOrdersSum = array();
	$dbOrderList = CSaleOrder::GetList(
		array($by => $order),
		$arFilterTmp,
		$arGroupByTmp,
		false,
		$arSelectFields
	);
	while ($arOrder = $dbOrderList->Fetch())
	{
		if (!array_key_exists($arOrder["CURRENCY"], $arOrdersSum))
			$arOrdersSum[$arOrder["CURRENCY"]] = 0;
		$arOrdersSum[$arOrder["CURRENCY"]] += $arOrder["PRICE"];
	}

	foreach ($arOrdersSum as $key => $value)
	{
		$arFooterArray[] = array(
			"title" => GetMessage("SOAN_ITOG")." ".$key.":",
			"value" => SaleFormatCurrency($value, $key)
		);
	}
}

$lAdmin->AddFooter($arFooterArray);

$arGroupActionsTmp = array(
	"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
	"update_ps_status" => GetMessage("SOAN_UPDATE_PS_STATUS"),
	/*
	"export_excel" => array(
		"action" => "exportData('excel')",
		"value" => "export_excel",
		"name" => str_replace("#EXP#", "Excel", GetMessage("SOAN_EXPORT_2"))
	),
	*/
	"export_csv" => array(
		"action" => "exportData('csv')",
		"value" => "export_csv",
		"name" => str_replace("#EXP#", "CSV", GetMessage("SOAN_EXPORT_2"))
	),
	"export_commerceml" => array(
		"action" => "exportData('commerceml')",
		"value" => "export_commerceml",
		"name" => str_replace("#EXP#", "CommerceML", GetMessage("SOAN_EXPORT_2"))
	),
	"export_commerceml2" => array(
		"action" => "exportData('commerceml2')",
		"value" => "export_commerceml2",
		"name" => str_replace("#EXP#", "CommerceML 2.0", GetMessage("SOAN_EXPORT_2"))
	)

);

$strPath2Export = BX_PERSONAL_ROOT."/php_interface/include/sale_export/";
if (file_exists($_SERVER["DOCUMENT_ROOT"].$strPath2Export))
{
	if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].$strPath2Export))
	{
		while (($file = readdir($handle)) !== false)
		{
			if ($file == "." || $file == "..")
				continue;
			if (is_file($_SERVER["DOCUMENT_ROOT"].$strPath2Export.$file) && substr($file, strlen($file)-4)==".php")
			{
				$export_name = substr($file, 0, strlen($file) - 4);
				$arGroupActionsTmp["export_".$export_name] = array(
					"action" => "exportData('".$export_name."')",
					"value" => "export_".$export_name,
					"name" => str_replace("#EXP#", $export_name, GetMessage("SOAN_EXPORT_2"))
				);
			}
		}
	}
	closedir($handle);
}


$lAdmin->AddGroupActionTable($arGroupActionsTmp);

$lAdmin->AddAdminContextMenu();
$lAdmin->CheckListMode();


/*********************************************************************/
/********************  PAGE  *****************************************/
/*********************************************************************/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/prolog.php");

$APPLICATION->SetTitle(GetMessage("SALE_SECTION_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

?>

<script language="JavaScript">
<!--
function exportData(val)
{
	var oForm = document.form_<?= $sTableID ?>;
	var expType = oForm.action_target.checked;

	var par = "";
	if (!expType)
	{
		var num = oForm.elements.length;
		for (var i = 0; i < num; i++)
		{
			if (oForm.elements[i].tagName.toUpperCase() == "INPUT"
				&& oForm.elements[i].type.toUpperCase() == "CHECKBOX"
				&& oForm.elements[i].name.toUpperCase() == "ID[]"
				&& oForm.elements[i].checked == true)
			{
				if (par.length > 0)
					par = par + "&";

				par = par + "OID[]=" + oForm.elements[i].value;
			}
		}
	}

	if (expType)
	{
		par = "<?= GetFilterParams("filter_") ?>";
	}

	if (par.length > 0)
	{
		window.open("sale_order_export.php?EXPORT_FORMAT="+val+"&"+par, "vvvvv");
	}
}
//-->
</script>

<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?
$arFilterFieldsTmp = array(
	GetMessage("SALE_F_DATE_UPDATE"),
	GetMessage("SALE_F_ID"),
	GetMessage("SALE_F_LANG_CUR"),
	GetMessage("SALE_F_STATUS"),
	GetMessage("SALE_F_DATE_STATUS"),
	GetMessage("SALE_F_PAYED"),
	GetMessage("SALE_F_DATE_PAYED"),
	GetMessage("SALE_F_ALLOW_DELIVERY"),
	GetMessage("SALE_F_DATE_ALLOW_DELIVERY"),
	GetMessage("SALE_F_PS_STATUS"),
	GetMessage("SALE_F_PAY_SYSTEM"),
	GetMessage("SALE_F_CANCELED"),
	GetMessage("SALE_F_BUYER"),
	GetMessage("SO_PRODUCT_ID"),
	GetMessage("SO_PRODUCT_XML_ID"),
	GetMessage("SO_AFFILIATE_ID"),
//	GetMessage("SO_DISCOUNT_NAME"),
//	GetMessage("SO_DISCOUNT_VALUE"),
	GetMessage("SO_DISCOUNT_COUPON"),
);

foreach ($arOrderProps as $key => $value)
	if ($value["IS_FILTERED"] == "Y")
		$arFilterFieldsTmp[] = $value["NAME"];

$oFilter = new CAdminFilter(
	$sTableID."_filter",
	$arFilterFieldsTmp
);

$oFilter->Begin();
?>

	<tr>
		<td><b><?echo GetMessage("SALE_F_DATE");?>:</b></td>
		<td>
			<?echo CalendarPeriod("filter_date_from", $filter_date_from, "filter_date_to", $filter_date_to, "find_form", "Y")?>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_DATE_UPDATE");?>:</td>
		<td>
			<?echo CalendarPeriod("filter_date_update_from", $filter_date_update_from, "filter_date_update_to", $filter_date_update_to, "find_form", "Y")?>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_ID");?>:</td>
		<td>
			<script language="JavaScript">
				function filter_id_from_Change()
				{
					if (document.find_form.filter_id_to.value.length<=0)
					{
						document.find_form.filter_id_to.value = document.find_form.filter_id_from.value;
					}
				}
			</script>
			<?echo GetMessage("SALE_F_FROM");?>
			<input type="text" name="filter_id_from" OnChange="filter_id_from_Change()" value="<?echo (IntVal($filter_id_from)>0)?IntVal($filter_id_from):""?>" size="10">
			<?echo GetMessage("SALE_F_TO");?>
			<input type="text" name="filter_id_to" value="<?echo (IntVal($filter_id_to)>0)?IntVal($filter_id_to):""?>" size="10">
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_LANG_CUR");?>:</td>
		<td>
			<select name="filter_lang">
				<option value=""><?= htmlspecialcharsex(GetMessage("SALE_F_ALL")) ?></option>
				<?
				$dbSitesList = CLang::GetList(($b1="sort"), ($o1="asc"));
				while ($arSitesList = $dbSitesList->Fetch())
				{
					if (!in_array($arSitesList["LID"], $arAccessibleSites)
						&& $saleModulePermissions < "W")
						continue;

					?><option value="<?= $arSitesList["LID"] ?>"<?if ($arSitesList["LID"] == $filter_lang) echo " selected";?>>[<?= htmlspecialcharsex($arSitesList["LID"]) ?>]&nbsp;<?= htmlspecialcharsex($arSitesList["NAME"]) ?></option><?
				}
				?>
			</select>
			/
			<?echo CCurrency::SelectBox("filter_currency", $filter_currency, GetMessage("SALE_F_ALL"), false, "", ""); ?>
		</td>
	</tr>
	<tr>
		<td valign="top"><?echo GetMessage("SALE_F_STATUS")?>:<br><img src="/bitrix/images/sale/mouse.gif" width="44" height="21" border="0" alt=""></td>
		<td valign="top">
			<select name="filter_status[]" multiple size="3">
				<?
				$dbStatusList = CSaleStatus::GetList(
						array("SORT" => "ASC"),
						array("LID" => LANGUAGE_ID),
						false,
						false,
						array("ID", "NAME")
					);
				while ($arStatusList = $dbStatusList->Fetch())
				{
					?><option value="<?= $arStatusList["ID"] ?>"<?if (is_array($filter_status) && in_array($arStatusList["ID"], $filter_status)) echo " selected"?>>[<?= $arStatusList["ID"] ?>] <?= htmlspecialcharsEx($arStatusList["NAME"]) ?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_DATE_STATUS");?>:</td>
		<td>
			<?echo CalendarPeriod("filter_date_status_from", $filter_date_status_from, "filter_date_status_to", $filter_date_status_to, "find_form", "Y")?>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_PAYED")?>:</td>
		<td>
			<select name="filter_payed">
				<option value=""><?echo GetMessage("SALE_F_ALL")?></option>
				<option value="Y"<?if ($filter_payed=="Y") echo " selected"?>><?echo GetMessage("SALE_YES")?></option>
				<option value="N"<?if ($filter_payed=="N") echo " selected"?>><?echo GetMessage("SALE_NO")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_DATE_PAYED")?></td>
		<td>
			<?echo CalendarPeriod("filter_payed_from", $filter_payed_from, "filter_payed_to", $filter_payed_to, "find_form", "Y")?>
		</td>
	</tr>

	<tr>
		<td><?echo GetMessage("SALE_F_ALLOW_DELIVERY")?>:</td>
		<td>
			<select name="filter_allow_delivery">
				<option value=""><?echo GetMessage("SALE_F_ALL")?></option>
				<option value="Y"<?if ($filter_allow_delivery=="Y") echo " selected"?>><?echo GetMessage("SALE_YES")?></option>
				<option value="N"<?if ($filter_allow_delivery=="N") echo " selected"?>><?echo GetMessage("SALE_NO")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_DATE_ALLOW_DELIVERY");?>:</td>
		<td>
			<?echo CalendarPeriod("filter_date_delivery_from", $filter_date_delivery_from, "filter_date_delivery_to", $filter_date_delivery_to, "find_form", "Y")?>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_PS_STATUS")?>:</td>
		<td>
			<select name="filter_ps_status">
				<option value=""><?echo GetMessage("SALE_F_ALL")?></option>
				<option value="Y"<?if ($filter_ps_status=="Y") echo " selected"?>><?echo GetMessage("SALE_YES")?></option>
				<option value="N"<?if ($filter_ps_status=="N") echo " selected"?>><?echo GetMessage("SALE_NO")?></option>
				<option value="X"<?if ($filter_ps_status=="X") echo " selected"?>><?echo GetMessage("SALE_YES_NO")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_PAY_SYSTEM");?>:</td>
		<td>
			<select name="filter_pay_system">
				<option value=""><?echo GetMessage("SALE_F_ALL")?></option>
				<?
				$l = CSalePaySystem::GetList(Array("SORT"=>"ASC", "NAME"=>"ASC"), Array());
				while ($l->ExtractFields("l_")):
					?><option value="<?echo $l_ID?>"<?if (IntVal($filter_pay_system)==IntVal($l_ID)) echo " selected"?>>[<?echo $l_ID ?>] <?echo $l_NAME?> <?echo "(".$l_LID.")";?></option><?
				endwhile;
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_CANCELED")?>:</td>
		<td>
			<select name="filter_canceled">
				<option value=""><?echo GetMessage("SALE_F_ALL")?></option>
				<option value="Y"<?if ($filter_canceled=="Y") echo " selected"?>><?echo GetMessage("SALE_YES")?></option>
				<option value="N"<?if ($filter_canceled=="N") echo " selected"?>><?echo GetMessage("SALE_NO")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SALE_F_BUYER");?>:</td>
		<td>
			<input type="text" name="filter_buyer" value="<?echo htmlspecialchars($filter_buyer)?>" size="40"><?=ShowFilterLogicHelp()?>
		</td>
	</tr>
	<tr>
		<td><?echo GetMessage("SO_PRODUCT_ID")?></td>
		<td>
			<script language="JavaScript">
			<!--
			function FillProductFields(index, arParams, iblockID)
			{
				if (arParams["id"])
					document.find_form.filter_product_id.value = arParams["id"];

				if (arParams["name"])
				{
					el = document.getElementById("product_name_alt");
					if(el)
						el.innerHTML = arParams["name"];
				}
			}
			//-->
			</script>
			<input name="filter_product_id" value="<?= htmlspecialchars($filter_product_id) ?>" size="5" type="text">&nbsp;<input type="button" value="..." id="cat_prod_button" onClick="window.open('sale_product_search.php?func_name=FillProductFields', '', 'scrollbars=yes,resizable=yes,width=600,height=500,top='+Math.floor((screen.height - 500)/2-14)+',left='+Math.floor((screen.width - 600)/2-5));"><span id="product_name_alt"></span>
		</td>
	</tr>
	<tr>
		<td><?= GetMessage("SO_PRODUCT_XML_ID") ?>:</td>
		<td><input name="filter_product_xml_id" value="<?= htmlspecialchars($filter_product_xml_id) ?>" size="40" type="text"></td>
	</tr>
	<tr>
		<td><?= GetMessage("SO_AFFILIATE_ID") ?>:</td>
		<td>
			<input type="text" name="filter_affiliate_id" value="<?= $filter_affiliate_id ?>" size="10" maxlength="10">
			<IFRAME name="hiddenframe_affiliate" id="id_hiddenframe_affiliate" src="" width="0" height="0" style="width:0px; height:0px; border: 0px"></IFRAME>
			<input type="button" class="button" name="FindAffiliate" OnClick="window.open('/bitrix/admin/sale_affiliate_search.php?func_name=SetAffiliateID', '', 'scrollbars=yes,resizable=yes,width=800,height=500,top='+Math.floor((screen.height - 500)/2-14)+',left='+Math.floor((screen.width - 400)/2-5));" value="...">
			<span id="div_affiliate_name"></span>
			<SCRIPT LANGUAGE=javascript>
			<!--
			function SetAffiliateID(id)
			{
				document.find_form.filter_affiliate_id.value = id;
			}

			function SetAffiliateName(val)
			{
				if (val != "NA")
					document.getElementById('div_affiliate_name').innerHTML = val;
				else
					document.getElementById('div_affiliate_name').innerHTML = '<?= GetMessage("SO1_NO_AFFILIATE") ?>';
			}

			var affiliateID = '';
			function ChangeAffiliateName()
			{
				if (affiliateID != document.find_form.filter_affiliate_id.value)
				{
					affiliateID = document.find_form.filter_affiliate_id.value;
					if (affiliateID != '' && !isNaN(parseInt(affiliateID, 10)))
					{
						document.getElementById('div_affiliate_name').innerHTML = '<i><?= GetMessage("SO1_WAIT") ?></i>';
						window.frames["hiddenframe_affiliate"].location.replace('/bitrix/admin/sale_affiliate_get.php?ID=' + affiliateID + '&func_name=SetAffiliateName');
					}
					else
						document.getElementById('div_affiliate_name').innerHTML = '';
				}
				timerID = setTimeout('ChangeAffiliateName()',2000);
			}
			ChangeAffiliateName();
			//-->
			</SCRIPT>
		</td>
	</tr>
<!--
	<tr>
		<td><?= GetMessage("SO_DISCOUNT_NAME") ?>:</td>
		<td><input name="filter_discount_name" value="<?= htmlspecialchars($filter_discount_name) ?>" size="40" type="text"></td>
	</tr>
	<tr>
		<td><?= GetMessage("SO_DISCOUNT_VALUE") ?>:</td>
		<td><input name="filter_discount_value" value="<?= htmlspecialchars($filter_discount_value) ?>" size="40" type="text"></td>
	</tr>
!-->
	<tr>
		<td><?= GetMessage("SO_DISCOUNT_COUPON") ?>:</td>
		<td><input name="filter_discount_coupon" value="<?= htmlspecialchars($filter_discount_coupon) ?>" size="40" type="text"></td>
	</tr>	
	<?
	foreach ($arOrderProps as $key => $value)
	{
		if ($value["IS_FILTERED"] == "Y")
		{
			?>
			<tr>
				<td valign="top"><?= $value["NAME"] ?>:</td>
				<td valign="top">
					<?
					$curVal = ${"filter_prop_".$key};
					if ($value["TYPE"]=="CHECKBOX")
					{
						?><input type="checkbox" name="filter_prop_<?= $key ?>" value="Y"<?if ($curVal == "Y") echo " checked";?>><?
					}
					elseif ($value["TYPE"]=="TEXT" || $value["TYPE"]=="TEXTAREA")
					{
						?><input type="text" size="30" maxlength="250" value="<?= htmlspecialchars($curVal) ?>" name="filter_prop_<?= $key ?>"><?=ShowFilterLogicHelp()?><?
					}
					elseif ($value["TYPE"]=="SELECT" || $value["TYPE"]=="MULTISELECT")
					{
						?>
						<select name="filter_prop_<?= $key ?>">
							<option value=""><?echo GetMessage("SALE_F_ALL")?></option>
							<?
							$db_vars = CSaleOrderPropsVariant::GetList(($by="SORT"), ($order="ASC"), Array("ORDER_PROPS_ID" => $key));
							while ($vars = $db_vars->Fetch())
							{
								?><option value="<?echo $vars["VALUE"]?>"<?if ($vars["VALUE"]==$curVal) echo " selected"?>><?echo htmlspecialchars($vars["NAME"])?></option><?
							}
							?>
						</select>
						<?
					}
					elseif ($value["TYPE"]=="LOCATION")
					{
						?>
						<select name="filter_prop_<?= $key ?>">
							<option value=""><?echo GetMessage("SALE_F_ALL")?></option>
							<?
							$db_vars = CSaleLocation::GetList(Array("SORT"=>"ASC", "COUNTRY_NAME_LANG"=>"ASC", "CITY_NAME_LANG"=>"ASC"), array(), LANG);
							while ($vars = $db_vars->Fetch())
							{
								?><option value="<?echo $vars["ID"]?>"<?if (IntVal($vars["ID"])==IntVal($curVal)) echo " selected"?>><?echo htmlspecialchars($vars["COUNTRY_NAME"]." - ".$vars["CITY_NAME"])?></option><?
							}
							?>
						</select>
						<?
					}
					elseif ($value["TYPE"]=="RADIO")
					{
						?><input type="radio" name="filter_prop_<?= $key ?>" value=""><?echo GetMessage("SALE_F_ALL")?><br><?
						$db_vars = CSaleOrderPropsVariant::GetList(($by="SORT"), ($order="ASC"), Array("ORDER_PROPS_ID"=>$key));
						while ($vars = $db_vars->Fetch())
						{
							?><input type="radio" name="filter_prop_<?= $key ?>" value="<?echo $vars["VALUE"]?>"<?if ($vars["VALUE"]==$curVal) echo " checked"?>><?echo htmlspecialchars($vars["NAME"])?><br><?
						}
					}
					?>
				</td>
			</tr>
			<?
		}
	}
	?>
<?
$oFilter->Buttons(
	array(
		"table_id" => $sTableID,
		"url" => $APPLICATION->GetCurPage(),
		"form" => "find_form"
	)
);
$oFilter->End();
?>
</form>

<?
$lAdmin->DisplayList();
?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>