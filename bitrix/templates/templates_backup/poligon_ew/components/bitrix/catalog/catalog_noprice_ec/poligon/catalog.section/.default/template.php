<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<script type="text/javascript" src="/bitrix/templates/poligon/js/ajax.js"></script>
<div class="catalog-section">
<?if($arParams["DISPLAY_TOP_PAGER"]):?>
	<?=$arResult["NAV_STRING"]?><br />
<?endif;?>
<?if (count($arResult["ITEMS"])):?>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
	<th width="70%">������������</th>
	<th width="10%">������.</th>
	<th width="10%" style="text-align:center">PDF</th>
	<th width="10%" style="text-align:center">�����</th>
</tr>
	<?foreach($arResult["ITEMS"] as $cell=>$arElement):?>
	<?if ($cell%2==1) $st = 'class="grey"'; else $st='';?>
	<?if ($arElement["DISPLAY_PROPERTIES"]["SPEC"]["VALUE"]==1) $st = 'class="spec"';?>
		<tr <?=$st?> >
			<td valign="top"><b><?=$arElement["NAME"]?> <? if ($arElement["DISPLAY_PROPERTIES"]["article"]["VALUE"]) echo '('.$arElement["DISPLAY_PROPERTIES"]["article"]["VALUE"].')';?></b>
			<br /><?=$arElement["PREVIEW_TEXT"]?>
			</td>
			<td align="center">
				<?if($arElement["DISPLAY_PROPERTIES"]["producer_full"]["DISPLAY_VALUE"])				
				echo $arElement["DISPLAY_PROPERTIES"]["producer_full"]["DISPLAY_VALUE"];
			 	else echo '&nbsp;';?>
				<?$res = CIBlockSection::GetByID($arElement["IBLOCK_SECTION_ID"]);
					if($ar_res = $res->GetNext())
					{
					  $section_name = $ar_res['NAME'];
					  if ($ar_res["IBLOCK_SECTION_ID"])
					  {
$rsSect1 = CIBlockSection::GetList(array("SORT"=>"ASC"), array("IBLOCK_ID"=>$ar_res["IBLOCK_ID"], "ID"=>$ar_res["IBLOCK_SECTION_ID"]), false, array("NAME","UF_PROIZV"));
		if ($arSect1 = $rsSect1->GetNext())
		{ 
			$proizv = $arSect1["UF_PROIZV"];
                        $section_name2 = $arSect1['NAME'];
                        $APPLICATION->SetTitle($section_name2.' '.$proizv.' > '.$section_name);
		}}
	        else $APPLICATION->SetTitle($section_name.' > '.$arElement["DISPLAY_PROPERTIES"]["producer_full"]["DISPLAY_VALUE"]);
				}
				?>
			</td>
			<td align="center">
				<?if($arElement["DISPLAY_PROPERTIES"]["pdf"]["DISPLAY_VALUE"]){?>				
				<a href="/PDF/<?=$arElement["DISPLAY_PROPERTIES"]["pdf"]["DISPLAY_VALUE"]?>"><img src="/images/pdf_doc.gif"></a>	<?} else{ echo '&nbsp;';}?>				
			</td>
			<td align="center">
				<?$db_res = CCatalogProduct::GetList(
					array(),
					array("ID" => $arElement["ID"]),
					false,
					array()
				    );
				if ($ar_res = $db_res->Fetch())
				{
				    if (!$ar_res["QUANTITY"]){
					if (!$arElement["DISPLAY_PROPERTIES"]["srok"]["DISPLAY_VALUE"])
						echo '<img src="/images/grey.gif" alt="��� ������" title="��� ������">';
					else echo $arElement["DISPLAY_PROPERTIES"]["srok"]["DISPLAY_VALUE"]; 					
					}
					else echo '<img src="/images/green.gif" alt="���� �� ������" title="���� �� ������">';
				}?>			
			</td>
	</tr>
	<?endforeach; // foreach($arResult["ITEMS"] as $arElement):?>
</table>
<?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
	<br /><?=$arResult["NAV_STRING"]?>
<?endif;?>
<?endif;?>
</div>
