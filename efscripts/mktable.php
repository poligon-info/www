<?php
$host = 'localhost';	  
// Соединение с базой данных
	// Создаём новую...
	mysql_query("CREATE TABLE `efind` (
						LEFT JOIN b_iblock_element_property AS biep_img ON (biep_img.iblock_element_id = bsc.item_id AND biep_img.iblock_property_id = '18')
						LEFT JOIN b_iblock_element_property AS biep_pdf ON (biep_pdf.iblock_element_id = bsc.item_id AND biep_pdf.iblock_property_id = '19')
						LEFT JOIN b_iblock_element_property AS biep_deliv ON (biep_deliv.iblock_element_id = bsc.item_id AND biep_deliv.iblock_property_id = '24')
						LEFT JOIN b_catalog_price AS bcp ON (bcp.product_id = bsc.item_id AND bcp.quantity_from = '1')
						LEFT JOIN b_catalog_product AS bcprod ON (bcprod.id = bsc.item_id)
					WHERE
?>