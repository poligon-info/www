<?
IncludeModuleLangFile(__FILE__); 
define("FORUM_SystemFolder", 4);
//*****************************************************************************************************************
//	PM
//************************************!****************************************************************************
class CAllForumPrivateMessage
{
	function Send($arFields = array())
	{
		global $DB;
		$version = COption::GetOptionString("forum", "UsePMVersion", "2");
		if(!CForumPrivateMessage::CheckFields($arFields))
			return false;

		$arFields["RECIPIENT_ID"] = $arFields["USER_ID"];
		$arFields["IS_READ"] = $arFields["IS_READ"]!="Y" ? "N" : "Y";
		$arFields["USE_SMILES"] = $arFields["USE_SMILES"]!="Y" ? "N" : "Y";
		$arFields["FOLDER_ID"] = intval($arFields["FOLDER_ID"])<=0 ? 1 : intval($arFields["FOLDER_ID"]);
		$arFields["REQUEST_IS_READ"] = $arFields["REQUEST_IS_READ"]!="Y" ? "N" : "Y";

		if(!isset($arFields["POST_DATE"]))
			$arFields["~POST_DATE"] = $DB->GetNowFunction();
		
		if ($version == 2 && $arFields["COPY_TO_OUTBOX"] == "Y")
		{
			$arFieldsTmp = $arFields;
			$arFieldsTmp["USER_ID"] = $arFields["AUTHOR_ID"];
			$arFieldsTmp["IS_READ"] = "Y";
			$arFieldsTmp["FOLDER_ID"] = "3";
			$DB->Add("b_forum_private_message", $arFieldsTmp, Array("POST_MESSAGE"));
		}
		return $DB->Add("b_forum_private_message", $arFields, Array("POST_MESSAGE"));
	}

	function Copy($ID, $arFields = array())
	{
		global $DB;
		$ID = intval($ID);
		$list = array();
		$list = CForumPrivateMessage::GetList(array(), array("ID"=>$ID));
		$list = $list->GetNext();
		if(CForumPrivateMessage::CheckFields($arFields))
		{
			$keys = array_keys($arFields);
			foreach ($keys as $key)
			if (is_set($list, $key))
			$list[$key] = $arFields[$key];

			if(!isset($list["POST_DATE"]))
			$list["~POST_DATE"] = $DB->GetNowFunction();

			$list["IS_READ"] = "Y";
			$list["REQUEST_IS_READ"] = $list["REQUEST_IS_READ"]!="Y" ? "N" : "Y";

			unset($list["ID"]);
			unset($list["~ID"]);
			
			return $DB->Add("b_forum_private_message", $list, Array("POST_MESSAGE"));
		}
		return false;
	}

	function Update($ID, $arFields)
	{
		global $DB, $USER;
		$ID = intval($ID);
		
		if (is_set($arFields, "AUTHOR_ID")&&(intVal($arFields["AUTHOR_ID"])))
			$arFields["AUTHOR_ID"] = $arFields["USER_ID"];
		if (is_set($arFields, "RECIPIENT_ID")&&(intVal($arFields["RECIPIENT_ID"])))
			$arFields["RECIPIENT_ID"] = $arFields["USER_ID"];
		if (is_set($arFields, "POST_DATE")&&(strLen(trim($arFields["POST_DATE"])) <= 0))
			$arFields["~POST_DATE"] =  $DB->GetNowFunction();
		if(is_set($arFields, "USE_SMILES") && $arFields["USE_SMILES"]!="Y")
			$arFields["USE_SMILES"]="N";
		if(is_set($arFields, "IS_READ") && $arFields["IS_READ"]!="Y")
			$arFields["IS_READ"]="N";
		if(is_set($arFields, "FOLDER_ID") && (intval($arFields["FOLDER_ID"]) < 0))
			$arFields["FOLDER_ID"] = 4;

		if(CForumPrivateMessage::CheckFields($arFields, true))
		{
			$strUpdate = $DB->PrepareUpdate("b_forum_private_message", $arFields);
			$strSql = "UPDATE b_forum_private_message SET ".$strUpdate." WHERE ID=".$ID;
			$res = $DB->QueryBind($strSql, Array("POST_MESSAGE"=>$arFields["POST_MESSAGE"]), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			return $res;
		}
		return false;
	}

	function Delete($ID)
	{
		global $DB, $USER;
		$ID = IntVal($ID);

		$list = array();
		$list = CForumPrivateMessage::GetList(array(), array("ID"=>$ID));
		$arFields = $list->GetNext();
		if ($arFields["FOLDER_ID"] == 4)
		{
			$DB->Query("DELETE FROM b_forum_private_message WHERE ID=".$ID);
			return true;
		}
		else
		{
			if(CForumPrivateMessage::Update($ID, array("FOLDER_ID"=>4, "IS_READ"=>"Y", "USER_ID"=>$USER->GetId())))
			return true;
			else
			return false;
		}
	}

	function MakeRead($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		$version = intVal(COption::GetOptionString("forum", "UsePMVersion", "2"));
		if($ID>0)
		{
			$db_res = CForumPrivateMessage::GetListEx(array(), array("ID" => $ID));
			if ($db_res && ($resFields = $db_res->Fetch()) && ($resFields["IS_READ"] != "Y"))
			{
				$strSql = "UPDATE b_forum_private_message SET IS_READ='Y' WHERE ID=".$ID;
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($version == 1 && ($resFields["IS_READ"] == "N"))
				{
					$resFields = array_merge($resFields, array("USER_ID"=>$resFields["AUTHOR_ID"], "FOLDER_ID"=>3, "IS_READ"=>"Y"));
					$resFields["REQUEST_IS_READ"] = $resFields["REQUEST_IS_READ"]!="Y" ? "N" : "Y";
					if(CForumPrivateMessage::CheckFields($resFields, "E"))
					{
						unset($resFields["ID"]);
						return $DB->Add("b_forum_private_message", $resFields, Array("POST_MESSAGE"));
					}
				}
			}
		}
		return false;
	}
	
	function CheckPermissions($ID)
	{
		global $USER, $APPLICATION;
		
		if($USER->IsAdmin() || $APPLICATION->GetGroupRight("forum")>="W")
			return true;
			
		$dbr = CForumPrivateMessage::GetByID($ID);
		if($arRes = $dbr->Fetch())
		{
			if((intVal($arRes["USER_ID"]) == $USER->GetID()) || 
				((intVal($arRes["AUTHOR_ID"]) == intVal($USER->GetID())) && ($arRes["IS_READ"]=="N")))
			return true;
		}
		return false;
	}
	
	function CheckFields(&$arFields, $update = false)
	{
		global $APPLICATION, $USER;
		$strError = "";
		if ((CForumPrivateMessage::PMSize($USER->GetId()) < COption::GetOptionInt("forum", "MaxPrivateMessages", 100)))
		{
			if((is_set($arFields, "USER_ID")&&(strlen($arFields["USER_ID"])<=0)))
			$strError .= GetMessage("PM_ERR_USER_EMPTY");
			if((is_set($arFields, "POST_SUBJ"))&&(strlen($arFields["POST_SUBJ"])<=0))
			$strError .= GetMessage("PM_ERR_SUBJ_EMPTY");
			if((is_set($arFields, "POST_MESSAGE"))&&(strlen($arFields["POST_MESSAGE"])<=0))
			$strError .= GetMessage("PM_ERR_TEXT_EMPTY");
		}
		else
		{
			$strError = GetMessage("PM_ERR_NO_SPACE");
			if ($update)
				return true;
		}
		if($strError)
		{
			$APPLICATION->ThrowException($strError);
			return false;
		}
		$arFields["REQUEST_IS_READ"] = $arFields["REQUEST_IS_READ"]!="Y" ? "N" : "Y";
		return true;
	}

	function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		return CForumPrivateMessage::GetList(Array(), Array("ID"=>$ID));
	}

	function GetList($arOrder = Array("ID" => "DESC"), $arFilter, $bCnt=false)
	{
		global $DB;

		$arSql = array();
		$orSql = array();
		if(!is_array($arFilter))
		$filter_keys = array();
		else
		$filter_keys = array_keys($arFilter);
		for($i = 0; $i < count($filter_keys); $i++)
		{
			$val = $arFilter[$filter_keys[$i]];
			$key = strtoupper($filter_keys[$i]);
			switch($key)
			{
				case "OWNER_ID":
				$orSql = array("M.AUTHOR_ID=".intVal($val), "M.FOLDER_ID=1", "M.IS_READ='N'");
				break;
				case "ID":
				case "FOLDER_ID":
				case "AUTHOR_ID":
				case "RECIPIENT_ID":
				case "USER_ID":
				$arSql[] = "M.".$key."=".intVal($val);
				break;
				case "POST_SUBJ":
				case "POST_MESSAGE":
				$arSql[] = "M.".$key."='".$DB->ForSQL($val)."'";
				break;
				case "USE_SMILES":
				case "IS_READ":
				$t_val = strtoupper($val);
				if($t_val=="Y" || $t_val=="N")
				$arSql[] = "M.".strtoupper($key)."='".$t_val."'";
				break;
			}
		}
		$arOFields = array(
		"ID" => "M.ID",
		"AUTHOR_ID"	=> "M.AUTHOR_ID",
		"POST_DATE"	=> "M.POST_DATE",
		"POST_SUBJ"	=> "M.POST_SUBJ",
		"POST_MESSAGE"	=> "M.POST_MESSAGE",
		"USER_ID"	=> "M.USER_ID",
		"FOLDER_ID"	=> "M.FOLDER_ID",
		"IS_READ"	=> "M.IS_READ",
		"USE_SMILES"	=> "M.USE_SMILES",
		"AUTHOR_NAME"=>"AUTHOR_NAME"
		);
		$arSqlOrder = array();
		$err_mess = "FILE: ".__FILE__."<br>LINE: ";
		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);
			if(array_key_exists($by, $arOFields))
			{
				if ($order != "ASC")
				$order = "DESC".($DB->type=="ORACLE" ? " NULLS LAST" : "");
				else
				$order = "ASC".($DB->type=="ORACLE" ? " NULLS FIRST" : "");
				$arSqlOrder[] = $arOFields[$by]." ".$order;
			}
		}

		if (!$bCnt)
		{
			$strSql =
			"SELECT M.ID, M.AUTHOR_ID, FU.LOGIN AS AUTHOR_NAME, M.RECIPIENT_ID, ".
			"	".$DB->DateToCharFunction("M.POST_DATE", "FULL")." as POST_DATE, ".
			"	M.POST_SUBJ, M.POST_MESSAGE, M.FOLDER_ID, M.IS_READ, M.USER_ID, M.USE_SMILES, M.REQUEST_IS_READ ";
		}
		else
		{
			$strSql =
			"SELECT COUNT(M.ID) AS CNT ";
		}
		$strSql .= "FROM b_forum_private_message M LEFT JOIN b_user FU ON(M.AUTHOR_ID = FU.ID)";
		$strSql .= (count($arSql)>0) ? " WHERE (".implode(" AND ", $arSql).")" : "";
		$strSql .= (count($orSql)>0) ? " OR (".implode(" AND ", $orSql).")" : "";
		$strSql .= (count($arSqlOrder)>0) ? " ORDER BY ".implode(", ", $arSqlOrder) : "";


		//echo $strSql;
		$dbRes = $DB->Query($strSql, false, $err_mess.__LINE__);
		//echo $strSql;
		return $dbRes;
	}

	function PMSize($USER_ID, $CountMess = false)
	{
		$USER_ID = intVal($USER_ID);
		if (COption::GetOptionString("forum", "UsePMVersion", "2") == 2)
			$count = CForumPrivateMessage::GetList(array(), array("USER_ID"=>$USER_ID), true);
		else 
			$count = CForumPrivateMessage::GetList(array(), array("USER_ID"=>$USER_ID, "OWNER_ID"=>$USER_ID), true);
		
		$count = $count->GetNext();
		if ($CountMess)
			return $count["CNT"]/$CountMess;
		return $count["CNT"];
	}

	function GetNewPM()
	{
		global $DB, $USER;
		static $PMessageCache = false;
		if ($PMessageCache === false)
		{
			$strSql =
			"SELECT COUNT(PM.ID) as UNREAD_PM ".
			"FROM b_forum_private_message PM ".
			"WHERE PM.USER_ID = ".$USER->GetID()." ".
			"	AND PM.FOLDER_ID = 1 ".
			"	AND PM.IS_READ = 'N'";
			
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	
			if ($db_res && ($res = $db_res->Fetch()))
				$PMessageCache = $res;
			else
				$PMessageCache = 0;
		}
		return $PMessageCache;
	}

}
//*****************************************************************************************************************
//	PM. Folders.
//************************************!****************************************************************************
class CALLForumPMFolder
{
	function Add($title)
	{
		global $DB, $USER, $APPLICATION;
		$res = CForumPMFolder::GetList(array(), array("TITLE"=>$title, "USER_ID"=>$USER->GetId()));
		if ($resFolder = $res->Fetch())
		{
			$APPLICATION->ThrowException(GetMessage("PM_ERR_FOLDER_EXIST"));
			return false;
		}
		return $DB->Add("b_forum_pm_folder", array("TITLE"=>$title, "USER_ID"=>$USER->GetId(), "SORT"=>"0"));
	}
	
	function Update($ID, $arFields = array())
	{
		global $DB, $USER, $APPLICATION;
		$ID = intval($ID);

		$res = CForumPMFolder::GetList(array(), array("TITLE"=>$arFields["TITLE"], "USER_ID"=>$USER->GetId()));
		while ($resFolder = $res->GetNext())
		{
			if($resFolder["ID"]!=$ID)
			{
				$APPLICATION->ThrowException(GetMessage("PM_ERR_FOLDER_EXIST"));
				return  false;
			}
		}
		$strUpdate = $DB->PrepareUpdate("b_forum_pm_folder", $arFields);
		$strSql = "UPDATE b_forum_pm_folder SET ".$strUpdate." WHERE ID=".$ID;
		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $res;

	}
	
	function GetList($arOrder = array("SORT" => "DESC", "TITLE"=>"DESC"), $arFilter, $bCnt=false)
	{
		global $DB;

		$arSqlSearch = array();
		$filter_keys = (is_array($arFilter) ? array_keys($arFilter) : array());

		for($i = 0; $i < count($filter_keys); $i++)
		{
			$val = $arFilter[$filter_keys[$i]];
			$key = strtoupper($filter_keys[$i]);
			switch($key)
			{
				case "ID":
				case "USER_ID":
				case "SORT":
					$arSqlSearch[] = "F.".$key."=".intVal($val);
				break;
				case "TITLE":
					$arSqlSearch[] = "F.".$key."='".$DB->ForSQL($val)."'";
				break;
			}
		}

		$arOFields = array(
			"ID" => "F.ID",
			"USER_ID"	=> "F.USER_ID",
			"SORT"	=> "F.SORT",
			"TITLE"	=> "F.TITLE");
		$arSqlOrder = array();
		$err_mess = "FILE: ".__FILE__."<br>LINE: ";
		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if(array_key_exists($by, $arOFields))
			{
				if ($order != "ASC")
				$order = "DESC".($DB->type=="ORACLE" ? " NULLS LAST" : "");
				else
				$order = "ASC".($DB->type=="ORACLE" ? " NULLS FIRST" : "");
				$arSqlOrder[] = $arOFields[$by]." ".$order;
			}
		}

		if (!$bCnt)
		$strSql =
		"SELECT F.ID, F.USER_ID, F.SORT, F.TITLE, COUNT(FPM.ID) AS CNT ".
		"FROM b_forum_pm_folder F LEFT JOIN b_forum_private_message FPM ON(F.ID = FPM.FOLDER_ID) ";
		else
		$strSql =
		"SELECT COUNT(F.ID) AS CNT ".
		"FROM b_forum_pm_folder F ";
		$strSql .= (count($arSqlSearch)>0) ? " WHERE ".implode(" AND ", $arSqlSearch) : "";
		if(!$bCnt)
		$strSql .= " GROUP BY F.ID, F.USER_ID, F.SORT, F.TITLE";
		$strSql .= (count($arSqlOrder)>0) ? " ORDER BY ".implode(", ", $arSqlOrder) : "";
		$dbRes = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $dbRes;
	}
	
	function CheckPermissions($ID)
	{
		global $USER, $APPLICATION;
		$ID = intVal($ID);
		if($USER->IsAdmin()||$APPLICATION->GetGroupRight("forum")>="W")
		return true;
		$dbr = CForumPMFolder::GetList(array(), array("ID"=>$ID));
		if($arRes = $dbr->Fetch())
		{
			if(($arRes["USER_ID"]==$USER->GetID())||($arRes["USER_ID"]==0))
			return true;
		}
		return false;
	}
	
	function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if($ID < FORUM_SystemFolder)
		return false;

		$DB->Query("DELETE FROM b_forum_private_message WHERE FOLDER_ID=".$ID);
		return $DB->Query("DELETE FROM b_forum_pm_folder WHERE ID=".$ID);
	}
}
?>