<?
//IncludeModuleLangFile(__FILE__);
class CAllIBlockSection
{
	function GetFilter($arFilter=Array())
	{
		global $DB;
		$arIBlockFilter = Array();
		$arSqlSearch = Array();
		$bSite = false;
		foreach($arFilter as $key => $val)
		{
			$res = CIBlock::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);
			switch($key)
			{
			case "ACTIVE":
			case "GLOBAL_ACTIVE":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "string_equal", $cOperationType);
				break;
			case "IBLOCK_ACTIVE":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.ACTIVE", $val, "string_equal", $cOperationType);
				break;
			case "LID":
			case "SITE_ID":
				$str = CIBlock::FilterCreate("BS.SITE_ID", $val, "string_equal", $cOperationType);
				if(strlen($str) > 0)
				{
					$arIBlockFilter[] = $str;
					$bSite = true;
				}
				break;
			case "IBLOCK_NAME":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.NAME", $val, "string", $cOperationType);
				break;
			case "IBLOCK_EXTERNAL_ID":
			case "IBLOCK_XML_ID":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.XML_ID", $val, "string", $cOperationType);
				break;
			case "IBLOCK_TYPE":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.IBLOCK_TYPE_ID", $val, "string", $cOperationType);
				break;
			case "TIMESTAMP_X":
			case "DATE_CREATE":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "date", $cOperationType);
				break;
			case "IBLOCK_CODE":
				$arIBlockFilter[] = CIBlock::FilterCreate("B.CODE", $val, "string", $cOperationType);
				break;
			case "IBLOCK_ID":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "number", $cOperationType);
				$arIBlockFilter[] = CIBlock::FilterCreate("B.ID", $val, "number", $cOperationType);
				break;
			case "NAME":
			case "XML_ID":
			case "TMP_ID":
			case "CODE":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "string", $cOperationType);
				break;
			case "EXTERNAL_ID":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.XML_ID", $val, "string", $cOperationType);
				break;
			case "ID":
			case "DEPTH_LEVEL":
			case "MODIFIED_BY":
			case "CREATED_BY":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.".$key, $val, "number", $cOperationType);
				break;
			case "SECTION_ID":
				if(!is_array($val) && IntVal($val)<=0)
					$arSqlSearch[] = CIBlock::FilterCreate("BS.IBLOCK_SECTION_ID", "", "number", $cOperationType, false);
				else
					$arSqlSearch[] = CIBlock::FilterCreate("BS.IBLOCK_SECTION_ID", $val, "number", $cOperationType);
				break;
			case "RIGHT_MARGIN":
				$arSqlSearch[] = "BS.RIGHT_MARGIN ".($cOperationType=="N"?">":"<=").IntVal($val);
				break;
			case "LEFT_MARGIN":
				$arSqlSearch[] = "BS.LEFT_MARGIN ".($cOperationType=="N"?"<":">=").IntVal($val);
				break;
			case "LEFT_BORDER":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.LEFT_MARGIN", $val, "number", $cOperationType);
				break;
			case "RIGHT_BORDER":
				$arSqlSearch[] = CIBlock::FilterCreate("BS.RIGHT_MARGIN", $val, "number", $cOperationType);
				break;
				$arSqlSearch[] = CIBlock::FilterCreate("BS.MODIFIED_BY", $val, "number", $bFullJoinTmp, $cOperationType);
				break;
			}
		}

		static $IBlockFilter_cache = array();
		if($bSite)
		{
			if(is_array($arIBlockFilter) && count($arIBlockFilter)>0)
			{
				$sIBlockFilter = "";
				foreach($arIBlockFilter as $val)
					if(strlen($val)>0)
						$sIBlockFilter .= "  AND ".$val;

				if(!array_key_exists($sIBlockFilter, $IBlockFilter_cache))
				{
					$strSql =
						"SELECT DISTINCT B.ID ".
						"FROM b_iblock B, b_iblock_site BS ".
						"WHERE B.ID = BS.IBLOCK_ID ".
							$sIBlockFilter;

					$arIBLOCKFilter = array();
					$dbRes = $DB->Query($strSql);
					while($arRes = $dbRes->Fetch())
						$arIBLOCKFilter[] = $arRes["ID"];
					$IBlockFilter_cache[$sIBlockFilter] = $arIBLOCKFilter;
				}
				else
				{
					$arIBLOCKFilter = $IBlockFilter_cache[$sIBlockFilter];
				}

				if(count($arIBLOCKFilter) > 0)
					$arSqlSearch[] = "B.ID IN (".implode(", ", $arIBLOCKFilter).") ";
			}
		}
		else
		{
			foreach($arIBlockFilter as $val)
				if(strlen($val) > 0)
					$arSqlSearch[] = $val;
		}

		return $arSqlSearch;
	}

	function GetTreeList($arFilter=Array())
	{
		return CIBlockSection::GetList(Array("left_margin"=>"asc"), $arFilter);
	}

	function GetNavChain($IBLOCK_ID, $SECTION_ID)
	{
		global $DB;

		$res = new CIBlockResult(
			$DB->Query(
				"SELECT BS.*, B.LIST_PAGE_URL, B.SECTION_PAGE_URL ".
				"FROM b_iblock_section M, b_iblock_section BS, b_iblock B ".
				"WHERE M.ID=".IntVal($SECTION_ID).
				($IBLOCK_ID>0?"	AND M.IBLOCK_ID=".IntVal($IBLOCK_ID)." ":"").
				"	AND M.IBLOCK_ID=BS.IBLOCK_ID ".
				"	AND B.ID=BS.IBLOCK_ID ".
				"	AND M.LEFT_MARGIN>=BS.LEFT_MARGIN ".
				"	AND M.RIGHT_MARGIN<=BS.RIGHT_MARGIN ".
				"ORDER BY BS.LEFT_MARGIN"
			)
		);
		$res->bIBlockSection = true;
		return $res;
	}


	///////////////////////////////////////////////////////////////////
	// Function returns section by ID
	///////////////////////////////////////////////////////////////////
	function GetByID($ID)
	{
		return CIBlockSection::GetList(Array(), Array("ID"=>IntVal($ID)));
	}

	///////////////////////////////////////////////////////////////////
	// New section
	///////////////////////////////////////////////////////////////////
	function Add($arFields, $bResort=true, $bUpdateSearch=true)
	{
		global $USER, $DB;

		if(is_set($arFields, "EXTERNAL_ID"))
			$arFields["XML_ID"] = $arFields["EXTERNAL_ID"];
		Unset($arFields["GLOBAL_ACTIVE"]);
		Unset($arFields["DEPTH_LEVEL"]);
		Unset($arFields["LEFT_MARGIN"]);
		Unset($arFields["RIGHT_MARGIN"]);

		if(is_set($arFields, "PICTURE") && strlen($arFields["PICTURE"]["name"])<=0 && strlen($arFields["PICTURE"]["del"])<=0)
			unset($arFields["PICTURE"]);

		if(is_set($arFields, "DETAIL_PICTURE") && strlen($arFields["DETAIL_PICTURE"]["name"])<=0 && strlen($arFields["DETAIL_PICTURE"]["del"])<=0)
			unset($arFields["DETAIL_PICTURE"]);

		$arFields["IBLOCK_SECTION_ID"] = intval($arFields["IBLOCK_SECTION_ID"]);
		if($arFields["IBLOCK_SECTION_ID"] == 0)
			$arFields["IBLOCK_SECTION_ID"] = false;

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "DESCRIPTION_TYPE") && $arFields["DESCRIPTION_TYPE"]!="html")
			$arFields["DESCRIPTION_TYPE"]="text";

		$arFields["SEARCHABLE_CONTENT"] =
			ToUpper(
				$arFields["NAME"]."\r\n".
				($arFields["DESCRIPTION_TYPE"]=="html" ?
					HTMLToTxt($arFields["DESCRIPTION"]) :
					$arFields["DESCRIPTION"]
				)
			);
		unset($arFields["DATE_CREATE"]);
		$arFields["~DATE_CREATE"] = $DB->CurrentTimeFunction();
		$user_id = (is_object($USER)? intval($USER->GetID()): false);
		$arFields["CREATED_BY"] = $user_id;
		$arFields["MODIFIED_BY"] = $user_id;

		if(!$this->CheckFields(&$arFields))
		{
			$Result = false;
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		elseif(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("IBLOCK_".$arFields["IBLOCK_ID"]."_SECTION", $ID, $arFields))
		{
			$Result = false;
		}
		else
		{
			unset($arFields["ID"]);
			$ID = intval($DB->Add("b_iblock_section", $arFields, Array("DESCRIPTION","SEARCHABLE_CONTENT"), "iblock"));

			if($bResort)
			{
				if(!array_key_exists("SORT", $arFields))
					$arFields["SORT"] = 500;

				$arParent = false;
				if($arFields["IBLOCK_SECTION_ID"] !== false)
				{
					$strSql = "
						SELECT BS.ID, BS.ACTIVE, BS.GLOBAL_ACTIVE, BS.DEPTH_LEVEL, BS.LEFT_MARGIN, BS.RIGHT_MARGIN
						FROM b_iblock_section BS
						WHERE BS.IBLOCK_ID = ".$arFields["IBLOCK_ID"]."
						AND BS.ID = ".$arFields["IBLOCK_SECTION_ID"]."
					";
					$rsParent = $DB->Query($strSql);
					$arParent = $rsParent->Fetch();
				}

				//Find rightmost child of the parent
				$strSql = "
					SELECT BS.ID, BS.RIGHT_MARGIN, BS.GLOBAL_ACTIVE, BS.DEPTH_LEVEL
					FROM b_iblock_section BS
					WHERE BS.IBLOCK_ID = ".$arFields["IBLOCK_ID"]."
					AND ".($arFields["IBLOCK_SECTION_ID"] !== false? "BS.IBLOCK_SECTION_ID=".$arFields["IBLOCK_SECTION_ID"]: "BS.IBLOCK_SECTION_ID IS NULL")."
					AND BS.NAME <= '".$DB->ForSQL($arFields["NAME"])."'
					AND BS.SORT <= ".intval($arFields["SORT"])."
					AND BS.ID <> ".$ID."
					ORDER BY BS.SORT DESC, BS.NAME DESC
				";
				$rsChild = $DB->Query($strSql);
				if($arChild = $rsChild->Fetch())
				{
					//We found the left neighbour
					$arUpdate = array(
						"LEFT_MARGIN" => intval($arChild["RIGHT_MARGIN"])+1,
						"RIGHT_MARGIN" => intval($arChild["RIGHT_MARGIN"])+2,
						"DEPTH_LEVEL" => intval($arChild["DEPTH_LEVEL"]),
					);
					//in case we adding active section
					if($arFields["ACTIVE"] != "N")
					{
						//Look up GLOBAL_ACTIVE of the parent
						//if none then take our own
						if($arParent)//We must inherit active from the parent
							$arUpdate["GLOBAL_ACTIVE"] = $arParent["ACTIVE"] == "Y"? "Y": "N";
						else //No parent was found take our own
							$arUpdate["GLOBAL_ACTIVE"] = "Y";
					}
					else
					{
						$arUpdate["GLOBAL_ACTIVE"] = "N";
					}
				}
				else
				{
					//If we have parent, when take its left_margin
					if($arParent)
					{
						$arUpdate = array(
							"LEFT_MARGIN" => intval($arParent["LEFT_MARGIN"])+1,
							"RIGHT_MARGIN" => intval($arParent["LEFT_MARGIN"])+2,
							"GLOBAL_ACTIVE" => ($arParent["GLOBAL_ACTIVE"] == "Y") && ($arFields["ACTIVE"] != "N")? "Y": "N",
							"DEPTH_LEVEL" => intval($arParent["DEPTH_LEVEL"])+1,
						);
					}
					else
					{
						//We are only one/leftmost section in the iblock.
						$arUpdate = array(
							"LEFT_MARGIN" => 1,
							"RIGHT_MARGIN" => 2,
							"GLOBAL_ACTIVE" => $arFields["ACTIVE"] != "N"? "Y": "N",
							"DEPTH_LEVEL" => 1,
						);
					}
				}
				$DB->Query("
					UPDATE b_iblock_section SET
						TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
						,LEFT_MARGIN = ".$arUpdate["LEFT_MARGIN"]."
						,RIGHT_MARGIN = ".$arUpdate["RIGHT_MARGIN"]."
						,DEPTH_LEVEL = ".$arUpdate["DEPTH_LEVEL"]."
						,GLOBAL_ACTIVE = '".$arUpdate["GLOBAL_ACTIVE"]."'
					WHERE
						ID = ".$ID."
				");
				$DB->Query("
					UPDATE b_iblock_section SET
						TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
						,LEFT_MARGIN = LEFT_MARGIN + 2
						,RIGHT_MARGIN = RIGHT_MARGIN + 2
					WHERE
						IBLOCK_ID = ".$arFields["IBLOCK_ID"]."
						AND LEFT_MARGIN >= ".$arUpdate["LEFT_MARGIN"]."
						AND ID <> ".$ID."
				");
				if($arParent)
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,RIGHT_MARGIN = RIGHT_MARGIN + 2
						WHERE
							IBLOCK_ID = ".$arFields["IBLOCK_ID"]."
							AND LEFT_MARGIN <= ".$arParent["LEFT_MARGIN"]."
							AND RIGHT_MARGIN >= ".$arParent["RIGHT_MARGIN"]."
					");
			}

			$GLOBALS["USER_FIELD_MANAGER"]->Update("IBLOCK_".$arFields["IBLOCK_ID"]."_SECTION", $ID, $arFields);

			if($bUpdateSearch)
				CIBlockSection::UpdateSearch($ID);

			$Result = $ID;
			$arFields["ID"] = &$ID;

			/************* QUOTA *************/
			$_SESSION["SESS_RECOUNT_DB"] = "Y";
			/************* QUOTA *************/
		}

		$arFields["RESULT"] = &$Result;

		$events = GetModuleEvents("iblock", "OnAfterIBlockSectionAdd");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEvent($arEvent, &$arFields);

		return $Result;
	}

	///////////////////////////////////////////////////////////////////
	// Update section properties
	///////////////////////////////////////////////////////////////////
	function Update($ID, $arFields, $bResort=true, $bUpdateSearch=true)
	{
		global $USER, $DB;

		$ID = intval($ID);

		$db_record = CIBlockSection::GetByID($ID);
		if(!($db_record = $db_record->Fetch()))
			return false;

		if(is_set($arFields, "EXTERNAL_ID"))
			$arFields["XML_ID"] = $arFields["EXTERNAL_ID"];

		Unset($arFields["GLOBAL_ACTIVE"]);
		Unset($arFields["DEPTH_LEVEL"]);
		Unset($arFields["LEFT_MARGIN"]);
		Unset($arFields["RIGHT_MARGIN"]);
		unset($arFields["IBLOCK_ID"]);
		unset($arFields["DATE_CREATE"]);
		unset($arFields["CREATED_BY"]);


		if(is_set($arFields, "PICTURE"))
		{
			if(strlen($arFields["PICTURE"]["name"])<=0 && strlen($arFields["PICTURE"]["del"])<=0)
				unset($arFields["PICTURE"]);
			else
				$arFields["PICTURE"]["old_file"] = $db_record["PICTURE"];
		}

		if(is_set($arFields, "DETAIL_PICTURE"))
		{
			if(strlen($arFields["DETAIL_PICTURE"]["name"])<=0 && strlen($arFields["DETAIL_PICTURE"]["del"])<=0)
				unset($arFields["DETAIL_PICTURE"]);
			else
				$arFields["DETAIL_PICTURE"]["old_file"] = $db_record["DETAIL_PICTURE"];
		}

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "DESCRIPTION_TYPE") && $arFields["DESCRIPTION_TYPE"]!="html")
			$arFields["DESCRIPTION_TYPE"] = "text";

		if($arFields["IBLOCK_SECTION_ID"] == "0")
			$arFields["IBLOCK_SECTION_ID"] = false;

		$DESC_tmp = is_set($arFields, "DESCRIPTION")?$arFields["DESCRIPTION"]:$db_record["DESCRIPTION"];
		$arFields["SEARCHABLE_CONTENT"] =
			ToUpper(
				(is_set($arFields, "NAME") ? $arFields["NAME"] : $db_record["NAME"])."\r\n".
				((is_set($arFields, "DESCRIPTION_TYPE") ? $arFields["DESCRIPTION_TYPE"] : $db_record["DESCRIPTION_TYPE"])=="html" ?
					HTMLToTxt($DESC_tmp) :
					$DESC_tmp
				)
			);
		$arFields["MODIFIED_BY"] = (is_object($USER)? intval($USER->GetID()): false);

		if(!$this->CheckFields(&$arFields, $ID))
		{
			$Result = false;
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		elseif(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("IBLOCK_".$db_record["IBLOCK_ID"]."_SECTION", $ID, $arFields))
		{
			$Result = false;
		}
		else
		{
			unset($arFields["ID"]);
			$strUpdate = $DB->PrepareUpdate("b_iblock_section", $arFields, "iblock");
			if(strlen($strUpdate) > 0)
			{
				$strSql = "UPDATE b_iblock_section SET ".$strUpdate." WHERE ID = ".$ID;
				$arBinds=Array();
				if(array_key_exists("DESCRIPTION", $arFields))
					$arBinds["DESCRIPTION"] = $arFields["DESCRIPTION"];
				if(array_key_exists("SEARCHABLE_CONTENT", $arFields))
					$arBinds["SEARCHABLE_CONTENT"] = $arFields["SEARCHABLE_CONTENT"];
				$DB->QueryBind($strSql, $arBinds);
			}

			if($bResort)
			{
				//Move inside the tree
				if((isset($arFields["SORT"]) && $arFields["SORT"]!=$db_record["SORT"])
					|| (isset($arFields["NAME"]) && $arFields["NAME"]!=$db_record["NAME"])
					|| (isset($arFields["IBLOCK_SECTION_ID"]) && $arFields["IBLOCK_SECTION_ID"]!=$db_record["IBLOCK_SECTION_ID"]))
				{
					//First "delete" from the tree
					$distance = intval($db_record["RIGHT_MARGIN"]) - intval($db_record["LEFT_MARGIN"]) + 1;
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,LEFT_MARGIN = -LEFT_MARGIN
							,RIGHT_MARGIN = -RIGHT_MARGIN
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND LEFT_MARGIN >= ".intval($db_record["LEFT_MARGIN"])."
							AND LEFT_MARGIN <= ".intval($db_record["RIGHT_MARGIN"])."
					");
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,LEFT_MARGIN = LEFT_MARGIN - ".$distance."
							,RIGHT_MARGIN = RIGHT_MARGIN - ".$distance."
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND LEFT_MARGIN > ".$db_record["RIGHT_MARGIN"]."
					");
					//Next insert into the the tree almost as we do when inserting the new one

					$PARENT_ID = isset($arFields["IBLOCK_SECTION_ID"])? intval($arFields["IBLOCK_SECTION_ID"]): intval($db_record["IBLOCK_SECTION_ID"]);
					$NAME = isset($arFields["NAME"])? $arFields["NAME"]: $db_record["NAME"];
					$SORT = isset($arFields["SORT"])? intval($arFields["SORT"]): intval($db_record["SORT"]);

					$arParents = array();
					$strSql = "
						SELECT BS.ID, BS.ACTIVE, BS.GLOBAL_ACTIVE, BS.DEPTH_LEVEL, BS.LEFT_MARGIN, BS.RIGHT_MARGIN
						FROM b_iblock_section BS
						WHERE BS.IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
						AND BS.ID in (".intval($db_record["IBLOCK_SECTION_ID"]).", ".intval($arFields["IBLOCK_SECTION_ID"]).")
					";
					$rsParents = $DB->Query($strSql);
					while($arParent = $rsParents->Fetch())
					{
						$arParents[$arParent["ID"]] = $arParent;
					}

					//Find rightmost child of the parent
					$strSql = "
						SELECT BS.ID, BS.RIGHT_MARGIN, BS.DEPTH_LEVEL
						FROM b_iblock_section BS
						WHERE BS.IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
						AND ".($PARENT_ID > 0? "BS.IBLOCK_SECTION_ID=".$PARENT_ID: "BS.IBLOCK_SECTION_ID IS NULL")."
						AND BS.NAME <= '".$DB->ForSQL($NAME)."'
						AND BS.SORT <= ".$SORT."
						AND BS.ID <> ".$ID."
						ORDER BY BS.SORT DESC, BS.NAME DESC
					";
					$rsChild = $DB->Query($strSql);
					if($arChild = $rsChild->Fetch())
					{
						//We found the left neighbour
						$arUpdate = array(
							"LEFT_MARGIN" => intval($arChild["RIGHT_MARGIN"])+1,
							"DEPTH_LEVEL" => intval($arChild["DEPTH_LEVEL"]),
						);
					}
					else
					{
						//If we have parent, when take its left_margin
						if($arParents[$PARENT_ID])
						{
							$arUpdate = array(
								"LEFT_MARGIN" => intval($arParents[$PARENT_ID]["LEFT_MARGIN"])+1,
								"DEPTH_LEVEL" => intval($arParents[$PARENT_ID]["DEPTH_LEVEL"])+1,
							);
						}
						else
						{
							//We are only one/leftmost section in the iblock.
							$arUpdate = array(
								"LEFT_MARGIN" => 1,
								"DEPTH_LEVEL" => 1,
							);
						}
					}

					$move_distance = intval($db_record["LEFT_MARGIN"]) - $arUpdate["LEFT_MARGIN"];
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,LEFT_MARGIN = LEFT_MARGIN + ".$distance."
							,RIGHT_MARGIN = RIGHT_MARGIN + ".$distance."
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND LEFT_MARGIN >= ".$arUpdate["LEFT_MARGIN"]."
					");
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,LEFT_MARGIN = -LEFT_MARGIN - ".$move_distance."
							,RIGHT_MARGIN = -RIGHT_MARGIN - ".$move_distance."
							".($arUpdate["DEPTH_LEVEL"] != intval($db_record["DEPTH_LEVEL"])? ",DEPTH_LEVEL = DEPTH_LEVEL - ".($db_record["DEPTH_LEVEL"] - $arUpdate["DEPTH_LEVEL"]): "")."
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND LEFT_MARGIN <= ".(-intval($db_record["LEFT_MARGIN"]))."
							AND LEFT_MARGIN >= ".(-intval($db_record["RIGHT_MARGIN"]))."
					");
					if(intval($arFields["IBLOCK_SECTION_ID"]) != intval($db_record["IBLOCK_SECTION_ID"]))
					{
						foreach($arParents as $parent_id => $arParent)
						{
							if($parent_id == intval($arFields["IBLOCK_SECTION_ID"]))
							{
								$DB->Query("
									UPDATE b_iblock_section SET
										TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
										,RIGHT_MARGIN = RIGHT_MARGIN + ".$distance."
									WHERE
										IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
										AND LEFT_MARGIN <= ".$arParent["LEFT_MARGIN"]."
										AND RIGHT_MARGIN >= ".$arParent["RIGHT_MARGIN"]."
								");
							}
							if($parent_id == intval($db_record["IBLOCK_SECTION_ID"]))
							{
								$DB->Query("
									UPDATE b_iblock_section SET
										TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
										,RIGHT_MARGIN = RIGHT_MARGIN - ".$distance."
									WHERE
										IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
										AND LEFT_MARGIN <= ".$arParent["LEFT_MARGIN"]."
										AND RIGHT_MARGIN >= ".$arParent["RIGHT_MARGIN"]."
								");
							}
						}
					}
				}

				//Check if parent was changed
				$bRecalc = false;
				if(isset($arFields["IBLOCK_SECTION_ID"]) && $arFields["IBLOCK_SECTION_ID"]!=$db_record["IBLOCK_SECTION_ID"])
				{
					$rsSection = CIBlockSection::GetByID($ID);
					$db_record = $rsSection->Fetch();

					$strSql = "
						SELECT ID, GLOBAL_ACTIVE
						FROM b_iblock_section
						WHERE IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
						AND ID = ".intval($arFields["IBLOCK_SECTION_ID"])."
					";
					$rsParent = $DB->Query($strSql);
					$arParent = $rsParent->Fetch();
					//If new parent is not globally active
					//or we are not active either
					//we must be not globally active too
					if(($arParent && $arParent["GLOBAL_ACTIVE"] == "N") || ($arFields["ACTIVE"] == "N"))
					{
						$DB->Query("
							UPDATE b_iblock_section SET
								TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
								,GLOBAL_ACTIVE = 'N'
							WHERE
								IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
								AND LEFT_MARGIN >= ".intval($db_record["LEFT_MARGIN"])."
								AND RIGHT_MARGIN <= ".intval($db_record["RIGHT_MARGIN"])."
						");
					}
					//New parent is globally active
					//And we WAS NOT active
					//But is going to be
					elseif($db_record["ACTIVE"] == "N" && $arFields["ACTIVE"] == "Y")
					{
						$bRecalc = true;
					}
					//Otherwise we may not to change anything
				}
				//Parent not changed
				//but we are going to change activity flag
				elseif(isset($arFields["ACTIVE"]) && $arFields["ACTIVE"] != $db_record["ACTIVE"])
				{
					//Make all children globally inactive
					if($arFields["ACTIVE"] == "N")
					{
						$DB->Query("
							UPDATE b_iblock_section SET
								TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
								,GLOBAL_ACTIVE = 'N'
							WHERE
								IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
								AND LEFT_MARGIN >= ".intval($db_record["LEFT_MARGIN"])."
								AND RIGHT_MARGIN <= ".intval($db_record["RIGHT_MARGIN"])."
						");
					}
					else
					{
						//Check for parent activity
						$strSql = "
							SELECT ID, GLOBAL_ACTIVE
							FROM b_iblock_section
							WHERE IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND ID = ".intval($db_record["IBLOCK_SECTION_ID"])."
						";
						$rsParent = $DB->Query($strSql);
						$arParent = $rsParent->Fetch();
						//Parent is active
						//and we changed
						//so need to recalc
						if(!$arParent || $arParent["GLOBAL_ACTIVE"] == "Y")
							$bRecalc = true;
					}
				}

				//Check if we need to change global activity flag
				//for us and our children
				if($bRecalc === true)
				{
					//Make all children globally active
					$DB->Query("
						UPDATE b_iblock_section SET
							TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
							,GLOBAL_ACTIVE = 'Y'
						WHERE
							IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
							AND LEFT_MARGIN >= ".intval($db_record["LEFT_MARGIN"])."
							AND RIGHT_MARGIN <= ".intval($db_record["RIGHT_MARGIN"])."
					");
					//Select those who is not active
					$strSql = "
						SELECT ID, LEFT_MARGIN, RIGHT_MARGIN
						FROM b_iblock_section
						WHERE IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
						AND LEFT_MARGIN >= ".intval($db_record["LEFT_MARGIN"])."
						AND RIGHT_MARGIN <= ".intval($db_record["RIGHT_MARGIN"])."
						AND ACTIVE = 'N'
						ORDER BY LEFT_MARGIN
					";
					$arUpdate = array();
					$prev_right = 0;
					$rsChildren = $DB->Query($strSql);
					while($arChild = $rsChildren->Fetch())
					{
						if($arChild["RIGHT_MARGIN"] > $prev_right)
						{
							$prev_right = $arChild["RIGHT_MARGIN"];
							$arUpdate[] = "(LEFT_MARGIN >= ".$arChild["LEFT_MARGIN"]." AND RIGHT_MARGIN <= ".$arChild["LEFT_MARGIN"].")\n";
						}
					}
					if(count($arUpdate) > 0)
					{
						$DB->Query("
							UPDATE b_iblock_section SET
								TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
								,GLOBAL_ACTIVE = 'N'
							WHERE
								IBLOCK_ID = ".$db_record["IBLOCK_ID"]."
								AND (".implode(" OR ", $arUpdate).")
						");
					}
				}
			}

			$GLOBALS["USER_FIELD_MANAGER"]->Update("IBLOCK_".$db_record["IBLOCK_ID"]."_SECTION", $ID, $arFields);

			if($bUpdateSearch)
				CIBlockSection::UpdateSearch($ID);

			$Result = true;

			/*********** QUOTA ***************/
			$_SESSION["SESS_RECOUNT_DB"] = "Y";
			/*********** QUOTA ***************/
		}

		$arFields["ID"] = $ID;
		$arFields["IBLOCK_ID"] = $db_record["IBLOCK_ID"];
		$arFields["RESULT"] = &$Result;

		$events = GetModuleEvents("iblock", "OnAfterIBlockSectionUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEvent($arEvent, &$arFields);

		return $Result;
	}

	///////////////////////////////////////////////////////////////////
	// Function delete section by its ID
	///////////////////////////////////////////////////////////////////
	function Delete($ID)
	{
		$err_mess = "FILE: ".__FILE__."<br>LINE: ";
		global $DB, $APPLICATION;
		$ID = IntVal($ID);

		$APPLICATION->ResetException();
		$db_events = GetModuleEvents("iblock", "OnBeforeIBlockSectionDelete");
		while($arEvent = $db_events->Fetch())
			if(ExecuteModuleEvent($arEvent, $ID)===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}

		$s = CIBlockSection::GetByID($ID);
		if($s = $s->Fetch())
		{
			$iblockelements = CIBlockElement::GetList(Array(), Array("SECTION_ID"=>$ID, "SHOW_HISTORY"=>"Y", "IBLOCK_ID"=>$s["IBLOCK_ID"]), false, false, array("ID", "IBLOCK_ID", "WF_PARENT_ELEMENT_ID"));
			while($iblockelement = $iblockelements->Fetch())
			{
				$strSql = "
					SELECT IBLOCK_SECTION_ID
					FROM b_iblock_section_element
					WHERE
						IBLOCK_ELEMENT_ID = ".$iblockelement["ID"]."
						AND IBLOCK_SECTION_ID<>".$ID."
						AND ADDITIONAL_PROPERTY_ID IS NULL
					ORDER BY
						IBLOCK_SECTION_ID
				";
				$db_section_element = $DB->Query($strSql);
				if($ar_section_element = $db_section_element->Fetch())
				{
					$DB->Query("
						UPDATE b_iblock_element
						SET IBLOCK_SECTION_ID=".$ar_section_element["IBLOCK_SECTION_ID"]."
						WHERE ID=".IntVal($iblockelement["ID"])."
					", false, $err_mess.__LINE__);
				}
				elseif(IntVal($iblockelement["WF_PARENT_ELEMENT_ID"])<=0)
				{
					if(!CIBlockElement::Delete($iblockelement["ID"]))
						return false;
				}
			}


			$iblocksections = CIBlockSection::GetList(Array(), Array("SECTION_ID"=>$ID));
			while($iblocksection = $iblocksections->Fetch())
			{
				if(!CIBlockSection::Delete($iblocksection["ID"]))
					return false;
			}

			CFile::Delete($s["PICTURE"]);
			CFile::Delete($s["DETAIL_PICTURE"]);

			static $arDelCache;
			if(!is_array($arDelCache))
				$arDelCache = Array();
			if(!is_set($arDelCache, $s["IBLOCK_ID"]))
			{
				$arDelCache[$s["IBLOCK_ID"]] = false;
				$db_ps = $DB->Query("SELECT ID,IBLOCK_ID,VERSION,MULTIPLE FROM b_iblock_property WHERE PROPERTY_TYPE='G' AND (LINK_IBLOCK_ID=".$s["IBLOCK_ID"]." OR LINK_IBLOCK_ID=0 OR LINK_IBLOCK_ID IS NULL)", false, $err_mess.__LINE__);
				while($ar_ps = $db_ps->Fetch())
				{
					if($ar_ps["VERSION"]==2)
					{
						if($ar_ps["MULTIPLE"]=="Y")
							$strTable = "b_iblock_element_prop_m".$ar_ps["IBLOCK_ID"];
						else
							$strTable = "b_iblock_element_prop_s".$ar_ps["IBLOCK_ID"];
					}
					else
					{
						$strTable = "b_iblock_element_property";
					}
					$arDelCache[$s["IBLOCK_ID"]][$strTable][] = $ar_ps["ID"];
				}
			}

			if($arDelCache[$s["IBLOCK_ID"]])
			{
				foreach($arDelCache[$s["IBLOCK_ID"]] as $strTable=>$arProps)
				{
					if(strncmp("b_iblock_element_prop_s", $strTable, 23)==0)
					{
						foreach($arProps as $prop_id)
						{
							$strSql = "UPDATE ".$strTable." SET PROPERTY_".$prop_id."=null,DESCRIPTION_".$prop_id."=null WHERE PROPERTY_".$prop_id."=".$s["ID"];
							if(!$DB->Query($strSql, false, $err_mess.__LINE__))
								return false;
						}
					}
					elseif(strncmp("b_iblock_element_prop_m", $strTable, 23)==0)
					{
						$strSql = "SELECT IBLOCK_PROPERTY_ID, IBLOCK_ELEMENT_ID FROM ".$strTable." WHERE IBLOCK_PROPERTY_ID IN (".implode(", ", $arProps).") AND VALUE_NUM=".$s["ID"];
						$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
						while($ar = $rs->Fetch())
						{
							$strSql = "
								UPDATE ".str_replace("prop_m", "prop_s", $strTable)."
								SET	PROPERTY_".$ar["IBLOCK_PROPERTY_ID"]."=null,
									DESCRIPTION_".$ar["IBLOCK_PROPERTY_ID"]."=null
								WHERE IBLOCK_ELEMENT_ID = ".$ar["IBLOCK_ELEMENT_ID"]."
							";
							if(!$DB->Query($strSql, false, $err_mess.__LINE__))
								return false;
						}
						$strSql = "DELETE FROM ".$strTable." WHERE IBLOCK_PROPERTY_ID IN (".implode(", ", $arProps).") AND VALUE_NUM=".$s["ID"];
						if(!$DB->Query($strSql, false, $err_mess.__LINE__))
							return false;
					}
					else
					{
						$strSql = "DELETE FROM ".$strTable." WHERE IBLOCK_PROPERTY_ID IN (".implode(", ", $arProps).") AND VALUE_NUM=".$s["ID"];
						if(!$DB->Query($strSql, false, $err_mess.__LINE__))
							return false;
					}
				}
			}

			$DB->Query("DELETE FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID=".IntVal($ID), false, $err_mess.__LINE__);

			if(CModule::IncludeModule("search"))
				CSearch::DeleteIndex("iblock", "S".$ID);

			$GLOBALS["USER_FIELD_MANAGER"]->Delete("IBLOCK_".$s["IBLOCK_ID"]."_SECTION", $ID);

			//Delete the hole in the tree
			if(($s["RIGHT_MARGIN"] > 0) && ($s["LEFT_MARGIN"] > 0))
			{
				$DB->Query("
					UPDATE b_iblock_section SET
						TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X")."
						,LEFT_MARGIN = LEFT_MARGIN - 2
						,RIGHT_MARGIN = RIGHT_MARGIN - 2
					WHERE
						IBLOCK_ID = ".$s["IBLOCK_ID"]."
						AND RIGHT_MARGIN > ".$s["RIGHT_MARGIN"]."
				");
			}
			/************* QUOTA *************/
			$_SESSION["SESS_RECOUNT_DB"] = "Y";
			/************* QUOTA *************/

			$res = $DB->Query("DELETE FROM b_iblock_section WHERE ID=".IntVal($ID), false, $err_mess.__LINE__);

			if($res)
			{
				$db_events = GetModuleEvents("iblock", "OnAfterIBlockSectionDelete");
				while($arEvent = $db_events->Fetch())
					ExecuteModuleEvent($arEvent, $s);
			}

			return $res;
		}

		return true;
	}

	///////////////////////////////////////////////////////////////////
	// Check function called from Add and Update
	///////////////////////////////////////////////////////////////////
	function CheckFields($arFields, $ID=false)
	{
		global $DB, $APPLICATION;
		$this->LAST_ERROR = "";

		if(($ID===false || is_set($arFields, "NAME")) && strlen($arFields["NAME"])<=0)
			$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_SECTION")."<br>";

		if($ID===false && !is_set($arFields, "IBLOCK_ID"))
			$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_ID")."<br>";

		if(is_set($arFields, "IBLOCK_ID"))
		{
			$r = CIBlock::GetByID($arFields["IBLOCK_ID"]);
			if(!$r->Fetch())
				$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_ID")."<br>";
		}

		if(is_set($arFields, "PICTURE"))
		{
			$error = CFile::CheckImageFile($arFields["PICTURE"]);
			if (strlen($error)>0) $this->LAST_ERROR .= $error."<br>";
		}

		if(is_set($arFields, "DETAIL_PICTURE"))
		{
			$error = CFile::CheckImageFile($arFields["DETAIL_PICTURE"]);
			if (strlen($error)>0) $this->LAST_ERROR .= $error."<br>";
		}

		if(strlen($this->LAST_ERROR)<=0)
		{
			if(IntVal($arFields["IBLOCK_SECTION_ID"])>0)
			{
				$r = CIBlockSection::GetByID($arFields["IBLOCK_SECTION_ID"]);
				if($ar = $r->Fetch())
				{
					if($ID)
					{
						$rthis = CIBlockSection::GetByID($ID);
						if($arthis = $rthis->Fetch())
						{
							if($ar["IBLOCK_ID"]!=$arthis["IBLOCK_ID"])
								$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_SECTION_ID_PARENT")."<br>";
							else
							{
								$strSql =
									"SELECT 'x' ".
									"FROM b_iblock_section bsto, b_iblock_section bsfrom ".
									"WHERE bsto.ID=".$arFields["IBLOCK_SECTION_ID"]." ".
									"	AND bsfrom.ID=".IntVal($ID)." ".
									"	AND bsto.LEFT_MARGIN>=bsfrom.LEFT_MARGIN ".
									"	AND bsto.LEFT_MARGIN<=bsfrom.RIGHT_MARGIN ";

								$rch = $DB->Query($strSql);
								if($rch->Fetch())
									$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_SECTION_RECURSE")."<br>";
							}
						}
						else
							$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_SECTION_ID_PARENT")."<br>";
					}
				}
				else
					$this->LAST_ERROR .= GetMessage("IBLOCK_BAD_BLOCK_SECTION_PARENT")."<br>";
			}
		}

		$APPLICATION->ResetException();
		if($ID===false)
			$db_events = GetModuleEvents("iblock", "OnBeforeIBlockSectionAdd");
		else
		{
			$arFields["ID"] = $ID;
			$db_events = GetModuleEvents("iblock", "OnBeforeIBlockSectionUpdate");
		}

		/****************************** QUOTA ******************************/
		if(empty($this->LAST_ERROR) && (COption::GetOptionInt("main", "disk_space") > 0))
		{
			$quota = new CDiskQuota();
			if(!$quota->checkDiskQuota($arFields))
				$this->LAST_ERROR = $quota->LAST_ERROR;
		}
		/****************************** QUOTA ******************************/

		while($arEvent = $db_events->Fetch())
		{
			$bEventRes = ExecuteModuleEvent($arEvent, &$arFields);
			if($bEventRes===false)
			{
				if($err = $APPLICATION->GetException())
					$this->LAST_ERROR .= $err->GetString()."<br>";
				else
				{
					$APPLICATION->ThrowException("Unknown error");
					$this->LAST_ERROR .= "Unknown error.<br>";
				}
				break;
			}
		}

		if(strlen($this->LAST_ERROR)>0)
			return false;

		return true;
	}


	function ReSort($IBLOCK_ID, $ID=0, $cnt=0, $depth=0, $ACTIVE="Y")
	{
		global $DB;
		$IBLOCK_ID = IntVal($IBLOCK_ID);

		if($ID>0)
			$DB->Query("UPDATE b_iblock_section SET TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X").", RIGHT_MARGIN=".IntVal($cnt).", LEFT_MARGIN=".IntVal($cnt)." WHERE ID=".IntVal($ID));

		$strSql =
			"SELECT BS.ID, BS.ACTIVE ".
			"FROM b_iblock_section BS ".
			"WHERE BS.IBLOCK_ID = ".$IBLOCK_ID." ".
			"	AND ".($ID>0?"BS.IBLOCK_SECTION_ID=".IntVal($ID):"BS.IBLOCK_SECTION_ID IS NULL")." ".
			"ORDER BY BS.SORT, BS.NAME ";

		$cnt++;
		$res = $DB->Query($strSql);
		while($arr = $res->Fetch())
			$cnt = CIBlockSection::ReSort($IBLOCK_ID, $arr["ID"], $cnt, $depth+1, ($ACTIVE=="Y" && $arr["ACTIVE"]=="Y" ? "Y" : "N"));

		if($ID==0)
			return true;

		$DB->Query("UPDATE b_iblock_section SET TIMESTAMP_X=".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X").", RIGHT_MARGIN=".IntVal($cnt).", DEPTH_LEVEL=".IntVal($depth).", GLOBAL_ACTIVE='".$ACTIVE."' WHERE ID=".IntVal($ID));
		return $cnt+1;
	}

	function UpdateSearch($ID, $bOverWrite=false)
	{
		if(!CModule::IncludeModule("search")) return;

		global $DB;
		$ID = Intval($ID);

		static $arGroups = array();
		static $arSITE = array();

		$strSql =
			"SELECT BS.ID, BS.NAME, BS.DESCRIPTION_TYPE, BS.DESCRIPTION, BS.XML_ID as EXTERNAL_ID, ".
			"	BS.CODE, BS.IBLOCK_ID, B.IBLOCK_TYPE_ID, ".
			"	".$DB->DateToCharFunction("BS.TIMESTAMP_X")." as LAST_MODIFIED, ".
			"	B.CODE as IBLOCK_CODE, B.XML_ID as IBLOCK_EXTERNAL_ID, B.SECTION_PAGE_URL, ".
			"	B.ACTIVE as ACTIVE1, ".
			"	BS.GLOBAL_ACTIVE as ACTIVE2, ".
			"	B.INDEX_SECTION ".
			"FROM b_iblock_section BS, b_iblock B ".
			"WHERE BS.IBLOCK_ID=B.ID ".
			"	AND BS.ID=".$ID;

		$dbrIBlockSection = $DB->Query($strSql);

		if($arIBlockSection = $dbrIBlockSection->Fetch())
		{
			$IBLOCK_ID = $arIBlockSection["IBLOCK_ID"];
			$SECTION_URL =
					"=ID=".$arIBlockSection["ID"].
					"&EXTERNAL_ID=".$arIBlockSection["EXTERNAL_ID"].
					"&IBLOCK_TYPE_ID=".$arIBlockSection["IBLOCK_TYPE_ID"].
					"&IBLOCK_ID=".$arIBlockSection["IBLOCK_ID"].
					"&IBLOCK_CODE=".$arIBlockSection["IBLOCK_CODE"].
					"&IBLOCK_EXTERNAL_ID=".$arIBlockSection["IBLOCK_EXTERNAL_ID"].
					"&CODE=".$arIBlockSection["CODE"];

			if($arIBlockSection["ACTIVE1"]!="Y" || $arIBlockSection["ACTIVE2"]!="Y" || $arIBlockSection["INDEX_SECTION"]!="Y")
			{
				CSearch::DeleteIndex("iblock", "S".$arIBlockElement["ID"]);
				return;
			}

			if(!array_key_exists($IBLOCK_ID, $arGroups))
			{
				$arGroups[$IBLOCK_ID] = array();
				$strSql =
					"SELECT GROUP_ID ".
					"FROM b_iblock_group ".
					"WHERE IBLOCK_ID= ".$IBLOCK_ID." ".
					"	AND PERMISSION>='R' ".
					"ORDER BY GROUP_ID";

				$dbrIBlockGroup = $DB->Query($strSql);
				while($arIBlockGroup = $dbrIBlockGroup->Fetch())
				{
					$arGroups[$IBLOCK_ID][] = $arIBlockGroup["GROUP_ID"];
					if($arIBlockGroup["GROUP_ID"]==2) break;
				}
			}

			if(!array_key_exists($IBLOCK_ID, $arSITE))
			{
				$arSITE[$IBLOCK_ID] = array();
				$strSql =
					"SELECT SITE_ID ".
					"FROM b_iblock_site ".
					"WHERE IBLOCK_ID= ".$IBLOCK_ID;

				$dbrIBlockSite = $DB->Query($strSql);
				while($arIBlockSite = $dbrIBlockSite->Fetch())
					$arSITE[$IBLOCK_ID][] = $arIBlockSite["SITE_ID"];
			}

			$BODY =
				($arIBlockSection["DESCRIPTION_TYPE"]=="html" ?
					CSearch::KillTags($arIBlockSection["DESCRIPTION"])
				:
					$arIBlockSection["DESCRIPTION"]
				);

			$BODY .= $GLOBALS["USER_FIELD_MANAGER"]->OnSearchIndex("IBLOCK_".$arIBlockSection["IBLOCK_ID"]."_SECTION", $arIBlockSection["ID"]);
			CSearch::Index(
					"iblock",
					"S".$ID,
					Array(
						"LAST_MODIFIED"=>$arIBlockSection["LAST_MODIFIED"],
						"TITLE"=>$arIBlockSection["NAME"],
						"PARAM1"=>$arIBlockSection["IBLOCK_TYPE_ID"],
						"PARAM2"=>$IBLOCK_ID,
						"SITE_ID"=>$arSITE[$IBLOCK_ID],
						"PERMISSIONS"=>$arGroups[$IBLOCK_ID],
						"URL"=>$SECTION_URL,
						"BODY"=>$BODY
					),
					$bOverWrite
				);
		}
	}

	function GetMixedList($arOrder=Array("SORT"=>"ASC"), $arFilter=Array(), $bIncCnt = false, $arSelectedFields = false)
	{
		global $DB;

		$arResult = array();

		$arSectionFilter = array (
			"IBLOCK_ID"		=>$arFilter["IBLOCK_ID"],
			"?NAME"			=>$arFilter["NAME"],
			"SECTION_ID"		=>$arFilter["SECTION_ID"],
			">=ID"			=>$arFilter["ID_1"],
			"<=ID"			=>$arFilter["ID_2"],
			">=TIMESTAMP_X"		=>$arFilter["TIMESTAMP_X_1"],
			"<=TIMESTAMP_X"		=>$arFilter["TIMESTAMP_X_2"],
			"MODIFIED_BY"		=>$arFilter["MODIFIED_USER_ID"]? $arFilter["MODIFIED_USER_ID"]: $arFilter["MODIFIED_BY"],
			">=DATE_CREATE"		=>$arFilter["DATE_CREATE_1"],
			"<=DATE_CREATE"		=>$arFilter["DATE_CREATE_2"],
			"CREATED_BY"		=>$arFilter["CREATED_USER_ID"]? $arFilter["CREATED_USER_ID"]: $arFilter["CREATED_BY"],
			"CODE"			=>$arFilter["CODE"],
			"EXTERNAL_ID"		=>$arFilter["EXTERNAL_ID"],
			"ACTIVE"		=>$arFilter["ACTIVE"],

			"CNT_ALL"		=>$arFilter["CNT_ALL"],
			"ELEMENT_SUBSECTIONS"	=>$arFilter["ELEMENT_SUBSECTIONS"],
		);
		$obSection = new CIBlockSection;
		$rsSection = $obSection->GetList($arOrder, $arSectionFilter, $bIncCnt);
		while($arSection = $rsSection->Fetch())
		{
			$arSection["TYPE"]="S";
			$arResult[]=$arSection;
		}

		$arElementFilter = array (
			"IBLOCK_ID"		=>$arFilter["IBLOCK_ID"],
			"?NAME"			=>$arFilter["NAME"],
			"SECTION_ID"		=>$arFilter["SECTION_ID"],
			">=ID"			=>$arFilter["ID_1"],
			"<=ID"			=>$arFilter["ID_2"],
			">=TIMESTAMP_X"		=>$arFilter["TIMESTAMP_X_1"],
			"<=TIMESTAMP_X"		=>$arFilter["TIMESTAMP_X_2"],
			"CODE"			=>$arFilter["CODE"],
			"EXTERNAL_ID"		=>$arFilter["EXTERNAL_ID"],
			"MODIFIED_USER_ID"	=>$arFilter["MODIFIED_USER_ID"],
			"MODIFIED_BY"		=>$arFilter["MODIFIED_BY"],
			">=DATE_CREATE"		=>$arFilter["DATE_CREATE_1"],
			"<=DATE_CREATE"		=>$arFilter["DATE_CREATE_2"],
			"CREATED_BY"		=>$arFilter["CREATED_BY"],
			"CREATED_USER_ID"	=>$arFilter["CREATED_USER_ID"],
			">=DATE_ACTIVE_FROM"	=>$arFilter["DATE_ACTIVE_FROM_1"],
			"<=DATE_ACTIVE_FROM"	=>$arFilter["DATE_ACTIVE_FROM_2"],
			">=DATE_ACTIVE_TO"	=>$arFilter["DATE_ACTIVE_TO_1"],
			"<=DATE_ACTIVE_TO"	=>$arFilter["DATE_ACTIVE_TO_2"],
			"ACTIVE"		=>$arFilter["ACTIVE"],
			"?SEARCHABLE_CONTENT"	=>$arFilter["DESCRIPTION"],
			"?TAGS"			=>$arFilter["?TAGS"],
			"WF_STATUS"		=>$arFilter["WF_STATUS"],

			"SHOW_NEW"		=> "Y",
		);
		foreach($arFilter as $key=>$value)
		{
			if(substr($key, 0, 1) == "?")
				$key = substr($key, 1);
			if(substr($key,0,9)=="PROPERTY_")
				$arElementFilter[$key]=$value;
			elseif(substr($key,0,8)=="CATALOG_")
				$arElementFilter[$key]=$value;
			elseif(substr($key,0,8)=="CATALOG_")
				$arElementFilter[$key]=$value;
		}
		if(strlen($arFilter["SECTION_ID"])<= 0)
			unset($arElementFilter["SECTION_ID"]);

		if(!is_array($arSelectedFields))
			$arSelectedFields = Array("*");

		$obElement = new CIBlockElement;

		$rsElement = $obElement->GetList($arOrder, $arElementFilter, false, false, $arSelectedFields);
		while($arElement = $rsElement->Fetch())
		{
			$arElement["TYPE"]="E";
			$arResult[]=$arElement;
		}

		$rsResult = new CDBResult;
		$rsResult->InitFromArray($arResult);

		return $rsResult;
	}

	///////////////////////////////////////////////////////////////////
	// GetSectionElementsCount($ID, $arFilter=Array())
	///////////////////////////////////////////////////////////////////
	function GetSectionElementsCount($ID, $arFilter=Array())
	{
		global $DB, $USER;

		$arJoinProps = array();
		$bJoinFlatProp = false;
		$arSqlSearch = array();

		if(array_key_exists("PROPERTY", $arFilter))
		{
			$val = $arFilter["PROPERTY"];
			foreach($val as $propID=>$propVAL)
			{
				$res = CIBlock::MkOperationFilter($propID);
				$propID = $res["FIELD"];
				$cOperationType = $res["OPERATION"];
				if($db_prop = CIBlockProperty::GetPropertyArray($propID, CIBlock::_MergeIBArrays($arFilter["IBLOCK_ID"], $arFilter["IBLOCK_CODE"])))
				{

					$bSave = false;
					if(array_key_exists($db_prop["ID"], $arJoinProps))
						$iPropCnt = $arJoinProps[$db_prop["ID"]];
					elseif($db_prop["VERSION"]!=2 || $db_prop["MULTIPLE"]=="Y")
					{
						$bSave = true;
						$iPropCnt=count($arJoinProps);
					}

					if(!is_array($propVAL))
						$propVAL = Array($propVAL);

					if($db_prop["PROPERTY_TYPE"]=="N" || $db_prop["PROPERTY_TYPE"]=="G" || $db_prop["PROPERTY_TYPE"]=="E")
					{
						if($db_prop["VERSION"]==2 && $db_prop["MULTIPLE"]=="N")
						{
							$r = CIBlock::FilterCreate("FPS.PROPERTY_".$db_prop["ORIG_ID"], $propVAL, "number", $cOperationType);
							$bJoinFlatProp = $db_prop["IBLOCK_ID"];
						}
						else
							$r = CIBlock::FilterCreate("FPV".$iPropCnt.".VALUE_NUM", $propVAL, "number", $cOperationType);
					}
					else
					{
						if($db_prop["VERSION"]==2 && $db_prop["MULTIPLE"]=="N")
						{
							$r = CIBlock::FilterCreate("FPS.PROPERTY_".$db_prop["ORIG_ID"], $propVAL, "string", $cOperationType);
							$bJoinFlatProp = $db_prop["IBLOCK_ID"];
						}
						else
							$r = CIBlock::FilterCreate("FPV".$iPropCnt.".VALUE", $propVAL, "string", $cOperationType);
					}

					if(strlen($r)>0)
					{
						if($bSave)
						{
							$db_prop["iPropCnt"] = $iPropCnt;
							$arJoinProps[$db_prop["ID"]] = $db_prop;
						}
						$arSqlSearch[] = $r;
					}
				}
			}
		}

		$strSqlSearch = "";
		foreach($arSqlSearch as $r)
			if(strlen($r)>0)
				$strSqlSearch .= "\n\t\t\t\tAND  (".$r.") ";

		$strSqlSearchProp = "";
		foreach($arJoinProps as $propID=>$db_prop)
		{
			if($db_prop["VERSION"]==2)
				$strTable = "b_iblock_element_prop_m".$db_prop["IBLOCK_ID"];
			else
				$strTable = "b_iblock_element_property";
			$i = $db_prop["iPropCnt"];
			$strSqlSearchProp .= "
				INNER JOIN b_iblock_property FP".$i." ON FP".$i.".IBLOCK_ID=BS.IBLOCK_ID AND
				".(IntVal($propID)>0?" FP".$i.".ID=".IntVal($propID)." ":" FP".$i.".CODE='".$DB->ForSQL($propID, 200)."' ")."
				INNER JOIN ".$strTable." FPV".$i." ON FP".$i.".ID=FPV".$i.".IBLOCK_PROPERTY_ID AND FPV".$i.".IBLOCK_ELEMENT_ID=BE.ID
			";
		}
		if($bJoinFlatProp)
			$strSqlSearchProp .= "
				INNER JOIN b_iblock_element_prop_s".$bJoinFlatProp." FPS ON FPS.IBLOCK_ELEMENT_ID = BE.ID
			";

		$strHint = $DB->type=="MYSQL"?"STRAIGHT_JOIN":"";
		$strSql = "
			SELECT ".$strHint." COUNT(DISTINCT BE.ID) as CNT
			FROM b_iblock_section BS
				INNER JOIN b_iblock_section BSTEMP ON (BSTEMP.IBLOCK_ID=BS.IBLOCK_ID
					AND BSTEMP.LEFT_MARGIN >= BS.LEFT_MARGIN
					AND BSTEMP.RIGHT_MARGIN <= BS.RIGHT_MARGIN)
				INNER JOIN b_iblock_section_element BSE ON BSE.IBLOCK_SECTION_ID=BSTEMP.ID
				INNER JOIN b_iblock_element BE ON BE.ID=BSE.IBLOCK_ELEMENT_ID AND BE.IBLOCK_ID=BS.IBLOCK_ID
			".$strSqlSearchProp."
			WHERE BS.ID=".IntVal($ID)."
				AND ((BE.WF_STATUS_ID=1 AND BE.WF_PARENT_ELEMENT_ID IS NULL )
				".($arFilter["CNT_ALL"]=="Y"?" OR BE.WF_NEW='Y' ":"").")
				".($arFilter["CNT_ACTIVE"]=="Y"?
					" AND BE.ACTIVE='Y'
					AND (BE.ACTIVE_TO >= ".$DB->CurrentDateFunction()." OR BE.ACTIVE_TO IS NULL)
					AND (BE.ACTIVE_FROM <= ".$DB->CurrentDateFunction()." OR BE.ACTIVE_FROM IS NULL)"
				:"")."
				".$strSqlSearch;
		//echo "<pre>",htmlspecialchars($strSql),"</pre>";
		$res = $DB->Query($strSql);
		$res = $res->Fetch();
		return $res["CNT"];
	}

	function GetCount($arFilter=Array())
	{
		global $DB, $USER;

		$arSqlSearch = CIBlockSection::GetFilter($arFilter);

		if(!$USER->IsAdmin())
		{
			$min_permission = strlen($arFilter["MIN_PERMISSION"])==1 ? $arFilter["MIN_PERMISSION"] : "R";
			$arSqlSearch[] = "
					IBG.GROUP_ID IN (".$USER->GetGroups().")
					AND IBG.PERMISSION>='".$min_permission."'
					AND (IBG.PERMISSION='X' OR B.ACTIVE='Y')
			";
			$bJoinIBG = true;
		}
		else
		{
			$bJoinIBG = false;
		}

		$strSqlSearch = "";
		foreach($arSqlSearch as $i=>$strSearch)
			if(strlen($strSearch)>0)
				$strSqlSearch .= "\n\t\t\tAND  (".$strSearch.") ";

		$strSql = "
			SELECT COUNT(DISTINCT BS.ID) as C
			FROM b_iblock_section BS
				INNER JOIN b_iblock B ON BS.IBLOCK_ID = B.ID
				".($bJoinIBG? "LEFT JOIN b_iblock_group IBG ON IBG.IBLOCK_ID=B.ID" : "")."
			WHERE 1=1
			".$strSqlSearch."
		";
		//echo "<pre>",htmlspecialchars($strSql),"</pre>";
		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$res_cnt = $res->Fetch();
		return IntVal($res_cnt["C"]);
	}

	function UserTypeRightsCheck($entity_id)
	{
		if(preg_match("/^IBLOCK_(\d+)_SECTION$/", $entity_id, $match))
		{
			return CIBlock::GetPermission($match[1]);
		}
		else
			return "D";
	}
}

?>