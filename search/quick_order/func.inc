<?php
/**
 *	@author "������� ������" <afomich@rambler.ru>
 *	@modify "������ ��������" <vladimir@mashkov.com>
 *	@version 1.1.0
 */

/**
 *	��������� e-mail
 * 	@param String
 *	@return true/false
 */
	function validEmail($email)
	{
		return (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.'@'.'[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.'[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$',$email));
	}

/**
 *	��������� ����� ��������
 * 	@param String
 *	@return true/false
 */

	function isPhoneNumber($content) {
		return (ereg('[^a-zA-Z�-��-�]+', $content));
	}

/**
 *	�������������� html � unicode
 *	@param String
 *	@return String
 */
	function translateHtml($content, $additional = true)
	{
		$p = "";
		if ($additional)
		{
			false ? $p .= "<b>" : $p .= "";
			false ? $p .= "<i>" : $p .= "";
		}
		$content = strip_tags($content, $p);
		$content = ereg_replace("[ ]{2,}", " ", $content);
		$content = str_replace("\r\n", "\n", $content);
		$content = str_replace("\r", "\n", $content);
		$content = str_replace("\$", " &#36;", $content);
		$content = trim($content);
		$content = stripslashes($content);
		if ($additional)
		{
			$content = convertLink($content);
		}
		return $content;
	}

/**
 *	������������ ��� ������ � ����������
 *	@param String
 *	@return String
 */
	function convertLink($content)
	{
		global $constant;
		$content = preg_replace('/(http|ftp|news|https)(:\/\/)([^<> ])+/i', "<a href='\\0'>\\0</a>", $content);
		$content = preg_replace('/([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,3})(\]?))/i', "<a href='mailto:\\1'>\\1</a>", $content);
		return $content;
	}

	function br2nl($content)
	{
		return str_replace("<br/>", "\n", $content);
	}

/**
 *	�������������� ������ � ����
 *	@param String
 *	@param String ��-��������� 'mess-content.php'
 */
	function writeDataInFile($fileData,$fileName = "mess-content.txt")
	{
		@$openFile = fopen($fileName,"a") or die ("������ ���������. ���������� ����� �� ".$fileName." �������� � ������� \"chmod 666 ".$fileName."\"");
		if ($openFile && flock($openFile,LOCK_EX)) {
			@fwrite($openFile,$fileData);
		}
		fclose($openFile);
	}

/**
 *	���������� ������� ����������������� ����
 *	@param String ��-��������� 'ru'
 *	@return String
 */
	function getFullDate($timestamp, $locale = "ru")
	{
		setlocale (LC_ALL, $locale);
		$currDay = strftime ("%d",$timestamp);
		$currMonth = strftime ("%B",$timestamp);
		$currYear = strftime ("%Y",$timestamp);
		$currWeek = strftime ("%a",$timestamp);
		$currFullWeek = strftime ("%A",$timestamp);
		return $currDay." ".strtolower($currMonth).", ".ucfirst($currFullWeek).", ".$currYear;
	}

/**
 *	���������� ������� ����������������� �����
 *	@return String
 */
	function getQuestionTime($timestamp)
	{
		return strftime("%H:%M:%S", $timestamp);
	}

/**
 *	���������� ������ ������������ ������� ������������
 *	@param Array
 *	@param String
 *	@return String
 */
	function getSystem($arrSystem,$userAgent)
	{
		$system = 'Other';
		foreach($arrSystem as $key => $value)
		{
			if (strpos($userAgent, $key) !== false)
			{
				$system = $value;
				break;
			}
		}
		return $system;
	}

/**
 *	���������� ������ ������������ ������������
 *	@param Array
 *	@param String
 *	@return Associative Array
 */
	function getBrowser($arrBrowser,$userAgent)
	{
		$version = "";
		$browser = 'Other';
		if (($pos = strpos($userAgent, 'Opera')) !== false)
		{
			$browser = 'Opera';
			$pos += 6;
			if ((($posEnd = strpos($userAgent, ';', $pos)) !== false) || (($posEnd = strpos($userAgent, ' ', $pos)) !== false))
				$version = trim(substr($userAgent, $pos, $posEnd - $pos));
		}
		elseif (($pos = strpos($userAgent, 'MSIE')) !== false)
		{
			$browser = 'Internet Explorer';
			$posEnd = strpos($userAgent, ';', $pos);
			if ($posEnd !== false)
			{
				$pos += 4;
				$version = trim(substr($userAgent, $pos, $posEnd - $pos));
			}
		}
		elseif (((strpos($userAgent, 'Gecko')) !== false) && ((strpos($userAgent, 'Netscape')) === false))
		{
			$browser = 'Mozila';
			if (($pos = strpos($userAgent, 'rv:')) !== false)
			{
				$posEnd = strpos($userAgent, ')', $pos);
				if ($posEnd !== false)
				{
					$pos += 3;
					$version = trim(substr($userAgent, $pos, $posEnd - $pos));
				}
			}
		}
		elseif ((strpos($userAgent, ' I;') !== false) || (strpos($userAgent, ' U;') !== false) || (strpos($userAgent, ' U ;') !== false) || (strpos($userAgent, ' I)') !== false) || (strpos($userAgent, ' U)') !== false))
		{
			$browser = 'Netscape Navigator';
			if (($pos = strpos($userAgent, 'Netscape6')) !== false)
			{
				$pos += 10;
				$version = trim(substr($userAgent, $pos, strlen($userAgent) - $pos));
			}
			else
			{
				if (($pos = strpos($userAgent, 'Mozilla/')) !== false)
				{
					if (($posEnd = strpos($userAgent, ' ', $pos)) !== false)
					{
						$pos += 8;
						$version = trim(substr($userAgent, $pos, $posEnd - $pos));
					}
				}
			}
		}
		else
		{
			foreach($arrBrowser as $key => $value)
			{
				if (strpos($userAgent, $key) !== false)
				{
					$browser = $value;
					break;
				}
			}
		}
		$userAgentArr['browser'] = $browser;
		$userAgentArr['version'] = $version;
		return $userAgentArr;
	}

?>