<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/search/classes/general/sitemap.php");

class CSiteMap extends CAllSiteMap
{
	function GetURLs($site_id, $ID, $limit=0)
	{
		global $DB;
		$strSql="
		SELECT
			sc.ID
			,sc.MODULE_ID
			,sc.ITEM_ID
			,sc.LID
			,sc.TITLE
			,sc.PARAM1
			,sc.PARAM2
			,sc.UPD
			,sc.DATE_FROM
			,sc.DATE_TO
			,L.DIR
			,L.SERVER_NAME
			,sc.URL as URL
			,scsite.URL as SITE_URL
			,scsite.SITE_ID
			,".$DB->DateToCharFunction("sc.DATE_CHANGE")." as FULL_DATE_CHANGE
			,".$DB->DateToCharFunction("sc.DATE_CHANGE", "SHORT")." as DATE_CHANGE
		FROM	b_search_content sc
			INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID
			INNER JOIN b_lang L ON scsite.SITE_ID=L.LID
			INNER JOIN b_search_content_group scg ON sc.ID=scg.SEARCH_CONTENT_ID
		WHERE
			scg.GROUP_ID=2
			AND scsite.SITE_ID='".$DB->ForSQL($site_id,2)."'
			AND sc.ID>$ID
		ORDER BY
			sc.ID
		";
		if(intval($limit)>0)
		{
			$strSql .= "LIMIT ".intval($limit);
		}
		$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		parent::CDBResult($r->result);
	}
}
?>