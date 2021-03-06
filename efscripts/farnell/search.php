<?php

    $host = 'localhost';       # Адрес MySQL сервера, например: mysql.mysite.com
    $user = 'poliinfo_bitrix';       # Имя пользователя базы данных, например: user_stock
    $pass = 'Y2Gd75q';       # Пароль пользователя, например: MswZ2Qs
    $base = 'poliinfo_bitrix';       # Название базы данных, например: store
    $table = 'efind_farnell';      # Название таблицы с данными, например: stock

    # Проверка наличия параметра search
    if(isset($_REQUEST['search']) && $_REQUEST['search'] != '')
    {
    # Соединение с базой данных
    if($dbh = mysql_connect($host, $user, $pass))
    {
        # Установка текущей базы данных
        mysql_select_db($base, $dbh);
        
        # Запрашиваем в таблице все позиции, названия которых содержат запрос
        $sth = mysql_query( "SELECT * FROM `".$table."` ".
                "WHERE `NAME_ORDERCODE` LIKE '%".addslashes($_REQUEST['search'])."%' ".
                "LIMIT 0,20", $dbh );
        
        # Если при запросе возникла ошибка, выводим соответствующее сообщение и выходим
        if(mysql_errno() > 0)
        {
        header("HTTP/1.1 500 Internal Server Error");
        print    "<h1>500 Internal Server Error</h1>".
            "Query Error";
        exit;
        }
        
        ob_start();
        print "<data>\n";
        if(mysql_num_rows($sth) > 0)
        {
        # Идем по каждой найденой записи
        while($row = mysql_fetch_array($sth, MYSQL_ASSOC))
        {
            print "<line>\n";
            
            # Название позиции
            print "    <part>".$row['NAME_ORDERCODE']."</part>\n";
            
            # Производитель
            if($row['MNFR'] != '')
            print "    <mfg>".$row['MNFR']."</mfg>\n";
            
            # Описание
            if($row['DESC'] != '')
            print "    <note>".$row['DESC']."</note>\n";

            # Ссылка на PDF
            if($row['PDF_LINK'] != '')
            print "    <pdf>".$row['PDF_LINK']."</pdf>\n";

            # Ссылка на изображение
            if($row['POLI_LINK'] != '')
            print "    <img>".$row['POLI_LINK']."</img>\n";
            
            # Валюта цен: доллар
            if(!empty($row['CURRENCY']))
            print "    <cur>".$row['CURRENCY']."</cur>\n";
            
            # Розничная цена
            if($row['PRICE1'] > 0)
            print "    <p1>".$row['PRICE1']."</p1>\n";
            
            # Мелкооптовая цена
            if($row['PRICE2'] > 0)
            print "    <p2>".$row['PRICE2']."</p2>\n";
            
            # Оптовая цена
            if($row['PRICE3'] > 0)
            print "    <p3>".$row['PRICE3']."</p3>\n";
            
            # Состояние склада
            print "    <stock>".$row['STOCK_DELIVERY']."</stock>\n";
            
            # Если в состояние не цифра (а, например, срок поставки,
            # наличие на складе партнеров и т.д.), указываем явно,
            # что данной позиции нет на складе
            if(!preg_match("/^\d+$/", trim($row['STOCK_DELIVERY']))) 
            print "    <instock>0</instock>";
            print "</line>\n";
        }
        mysql_free_result($sth);
        
        
        }
        print "</data>\n";
        $content = ob_get_contents();
        ob_clean();
        
        header("Content-type: application/xml");
        print '<?xml version="1.0" encoding="windows-1251" ?>'."\n".
          $content;        
        
        mysql_close($dbh);
    } else
    # Если не удалось соединиться с базой данных, выводим ошибку и выходим
    {
            header("HTTP/1.1 500 Internal Server Error");
        print "<h1>500 Internal Server Error</h1>".
          "Could not connecto to database";
        exit;
    }
    } else
    # Если параметр search не задан, вывести ошибку и выйти
    {
        header("HTTP/1.1 500 Internal Server Error");
    print "<h1>500 Internal Server Error</h1>".
          "Request is not set";
    exit;
    }

?> 
