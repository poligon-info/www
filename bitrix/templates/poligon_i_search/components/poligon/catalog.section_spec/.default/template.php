<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<script type="text/javascript" src="/bitrix/templates/poligon/js/ajax.js"></script>
<div class="catalog-section">
<?if($arParams["DISPLAY_TOP_PAGER"]):?>
	<?=$arResult["NAV_STRING"]?><br />
<?endif;?>
<?if (count($arResult["ITEMS"])):?>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
	<th width="60%">������������</th>
	<th width="50">������.</th>
	<th width="50" style="text-align:center">PDF</th>
	<th width="50" style="text-align:center">�����</th>
	<th width="80" style="text-align:center">����</th>
	<th width="50">&nbsp;</th>
</tr>
	<?foreach($arResult["ITEMS"] as $cell=>$arElement):?>
	<?if ($cell%2==1) $st = 'class="grey"'; else $st='';?>
	<?//if ($arElement["DISPLAY_PROPERTIES"]["SPEC"]["VALUE"]==1) $st = 'class="spec"';?>
		<tr <?=$st?> >
			<td valign="top"><a href="<?=$arElement["DETAIL_PAGE_URL"]?>"><b><?=$arElement["NAME"]?><? if ($arElement["DISPLAY_PROPERTIES"]["article"]["VALUE"]) echo ' ('.$arElement["DISPLAY_PROPERTIES"]["article"]["VALUE"].')';?></b></a>
				<?/*foreach($arElement["DISPLAY_PROPERTIES"] as $pid=>$arProperty):?>
					<?=$arProperty["NAME"]?>:&nbsp;<?
					if(is_array($arProperty["DISPLAY_VALUE"]))
						echo implode("&nbsp;/&nbsp;", $arProperty["DISPLAY_VALUE"]);
					else
						echo $arProperty["DISPLAY_VALUE"];?><br />
				<?endforeach*/?>
				<br />
				<?=$arElement["PREVIEW_TEXT"]?>
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
					  		$res1 = CIBlockSection::GetByID($ar_res["IBLOCK_SECTION_ID"]);
							if($ar_res1 = $res1->GetNext()) $section_name2 = $ar_res1['NAME'];
							$APPLICATION->SetTitle($section_name2.' > '.$section_name.' > '.$arElement["DISPLAY_PROPERTIES"]["producer_full"]["DISPLAY_VALUE"]);
							
					  }
					  else $APPLICATION->SetTitle($section_name.' > '.$arElement["DISPLAY_PROPERTIES"]["producer_full"]["DISPLAY_VALUE"]);
					}
				?>
			</td>
			<td align="center">
				<?if($arElement["DISPLAY_PROPERTIES"]["pdf"]["DISPLAY_VALUE"]){?>				
				<a href="<?=$arElement["DISPLAY_PROPERTIES"]["pdf"]["DISPLAY_VALUE"]?>"><img src="/images/pdf_doc.gif"></	a>	<?} else{ echo '&nbsp;';}?>				
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
			<td align="center">&nbsp;
			<span class="catalog-price"><nobr><?=FormatCurrency($arElement["PRICE_MATRIX"]["MATRIX"][1][0]["PRICE"], $arElement["PRICE_MATRIX"]["MATRIX"][1][0]["CURRENCY"]);?></nobr></span>
			<?foreach($arElement["PRICES"] as $code=>$arPrice):?>
				<?if($arPrice["CAN_ACCESS"]):?>
					<?//=$arResult["PRICES"][$code]["TITLE"];?>
					<?if($arPrice["DISCOUNT_VALUE"] < $arPrice["VALUE"]):?>
						<s><?=$arPrice["PRINT_VALUE"]?></s> <span class="catalog-price"><?=$arPrice["PRINT_DISCOUNT_VALUE"]?></span>
					<?else:?><span class="catalog-price">&nbsp;<nobr><?=$arPrice["PRINT_VALUE"]?></nobr></span><?endif;?>
								

				<?endif;?>
			<?endforeach;?>
			</td>
			<td width="30">
			<?if($arElement["CAN_BUY"]):?>
			<center>
				<a href="javascript:void(0)<?//echo $arElement['ADD_URL']?>" onclick="run(<?=$arElement['ID']?>)"><img src="/bitrix/templates/poligon/images/basket.gif"><?//echo GetMessage("CATALOG_ADD")?></a>
			</center>
			<?elseif((count($arResult["PRICES"]) > 0) || is_array($arElement["PRICE_MATRIX"])):?>
				<?=GetMessage("CATALOG_NOT_AVAILABLE")?>
			<?endif?>
			</td>
	</tr>
	<?endforeach; // foreach($arResult["ITEMS"] as $arElement):?>
</table>
<?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
	<br /><?=$arResult["NAV_STRING"]?>
<?endif;?>
<?endif;?>
</div>
