<?
class xredaktor_render
{

	private static $renderOfflinePages = false;
	private static $disableClientInfos = false;

	public static function getCurrentFace()
	{
		@session_start();
		if (is_numeric($_REQUEST['xr_face'])) {
			$_SESSION['XR_FACE'] = intval($_REQUEST['xr_face']);
		} else
		{

			if (!isset($_SESSION['XR_FACE_CHECK']))
			{
				$_SESSION['XR_FACE_CHECK'] = false;
			}

			if (!$_SESSION['XR_FACE_CHECK'])
			{
				require_once(Ixcore::htdocsRoot .'/xgo/xcore/libs/Mobile-Detect-2.8.15/Mobile_Detect.php');

				$detect 	= new Mobile_Detect();
				$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
				
				if($deviceType == 'tablet')
				{
					$deviceType = 'phone';
				}
								
				switch ($deviceType)
				{
					case 'phone':
						/* $f_id = 1; */
						$f_id = 4;
						break;
					case 'computer':
						/* $f_id = 3; */
						$f_id = 4;
						break;
					case 'tablet':
					default: 
					$f_id = 0;
					break;
				}

				$_SESSION['XR_FACE'] = $f_id;
				$_SESSION['XR_FACE_CHECK'] = true;

			}

		}

		if (intval($_SESSION['XR_FACE'])) return intval($_SESSION['XR_FACE']);
		return 0;
	}

	public static function setCurrentFace($faceId)
	{
		@session_start();
		$_SESSION['XR_FACE'] = intval($faceId);
		return true;
	}

	public static function renderHtmlEditor($value)
	{

		return xredaktor_xr_html::toStaticHtml($value);


		// CACHEING !! NOCH OFFEN
		if (strpos($value,"href")===false) return $value;

		$html = new DOMDocument();
		$html = new DOMDocument();
		$html->loadHTML('<?xml version="1.0" encoding="utf-8"?>'.($value));
		$xpath = new DomXPath($html);
		$links = $xpath->query('//a');

		$valueAddon = "";

		for ($i=0;$i<$links->length;$i++) {

			$url	= $links->item($i)->getAttribute('href');
			$target	= $links->item($i)->getAttribute('target');
			$title	= $links->item($i)->getAttribute('title');
			$class	= "";

			$tagger = "#XR_2LINK";
			
		
			
			if (strpos($url,$tagger)!==false)
			{
				
				list($crap,$json) = explode($tagger,$url);
				$settings = json_decode(urldecode($json),true);

				/*************
				* OLD STUFF BEGIN
				*****************/

				if (!isset($settings['choose']))
				{

					$target = $settings['target'];
					$title 	= $settings['title'];

					if (trim($settings['action'])=="")
					{
						$class 	= 'xr_noAction';
					} else
					{
						$class 	= 'xr_'.$settings['action'];
					}

					if ($settings['type']=='external')
					{
						$url = $settings['external'];
					} else
					{
						if (!is_numeric($settings['internal'])) $settings['internal'] = 1;


						$cfg = array(
						'p_id'=>intval($settings['internal']),
						'lang'=>xredaktor_pages::getFrontEndLang()
						);

						if ($cfg['p_id'] == 0)
						{
							$psa_p_id 		= xredaktor_render::getPSARecordById($psa_id);
							$cfg['p_id'] 	= xredaktor_niceurl::getStartPageById($psa_p_id);
						}


						$url = xredaktor_niceurl::genUrl($cfg);
					}

					$links->item($i)->removeAttribute('id');
					$links->item($i)->setAttribute('title',	$title);
					$links->item($i)->setAttribute('target',$target);
					$links->item($i)->setAttribute('href',	$url);
					$links->item($i)->setAttribute('class',	$class);

					/*************
					* OLD STUFF END
					*****************/
				} else
				{
					switch ($settings['choose'])
					{
						case 'EMAIL':

							$email_to = $settings['email_to'];
							$check = array();
							$check['subject'] 	= urlencode($settings['email_subject']);
							$check['cc'] 		= urlencode($settings['email_cc']);
							$check['body'] 		= urlencode($settings['email_body']);

							$url = "mailto:$email_to?d=1";

							foreach ($check as $k => $v)
							{
								if (trim($v) != "")
								{
									$url .= "&$k=$v";
								}
							}

							$links->item($i)->removeAttribute('id');
							$links->item($i)->setAttribute('title',	$email_subject);
							$links->item($i)->setAttribute('href',	$url);
							break;

						case 'FA':

							$s_id 			= intval($settings['filearchiv']);
							$FA_MODE 		= $settings['FA_MODE'];
							$FA_RESIZE 		= $settings['FA_RESIZE'];
							$FA_RESIZE_W 	= $settings['FA_RESIZE_W'];
							$FA_RESIZE_H 	= $settings['FA_RESIZE_H'];

							$downloadCfg 	= array(
							's_id' 		=> $s_id,
							'mode' 		=> $FA_MODE,
							'resize' 	=> $FA_RESIZE,
							'w' 		=> $FA_RESIZE_W,
							'h' 		=> $FA_RESIZE_H,
							);

							if ($s_id == 0)
							{
								$links->item($i)->removeAttribute('id');
								$links->item($i)->removeAttribute('href');
							} else
							{
								$download = xredaktor_storage::download($downloadCfg);
								$links->item($i)->removeAttribute('id');
								$links->item($i)->setAttribute('title',	$download['title']);
								$links->item($i)->setAttribute('href',	$download['href']);
								$links->item($i)->setAttribute('target',$download['target']);
							}
							break;

						case 'LB':
							$idOfContentDiv = "lb_content_".$psa_id.'_'.$i;
							$valueAddon .= "<div class='xr_lightbox_content'><div id='$idOfContentDiv'>"."<h1>".$settings['lb_title']."</h1>".$settings['lb_html']."</div></div>";
							$links->item($i)->removeAttribute('id');
							$links->item($i)->setAttribute('title',	$settings['lb_title']);
							$links->item($i)->setAttribute('target','');
							$links->item($i)->setAttribute('href',	'#'.$idOfContentDiv);
							$links->item($i)->setAttribute('class',	'xr_lightbox');
							break;
						case 'LINK':
						default:

							$type		= $settings['type'];
							$target 	= $settings['target'];
							$title 		= $settings['title'];
							$external 	= $settings['external'];
							$internal 	= intval($settings['internal']);

							switch ($type)
							{
								case 'internal':
									if ($internal==0)
									{
										$psa_p_id 		= xredaktor_render::getPSARecordById($psa_id);
										$start_p_id 	= xredaktor_niceurl::getStartPageById($psa_p_id);
										$internal = $start_p_id;
									}
									$cfg = array(
									'p_id' => $internal,
									'lang' => xredaktor_pages::getFrontEndLang()
									);
									$url = xredaktor_niceurl::genUrl($cfg);
									break;
								case 'external':
								default:
									$url = $external;
							}

							$links->item($i)->removeAttribute('id');
							$links->item($i)->setAttribute('title',	$title);
							$links->item($i)->setAttribute('target',$target);
							$links->item($i)->setAttribute('href',	$url);
							$links->item($i)->setAttribute('class',	$class);
					}
				}

			}
		}

		$body  = $xpath->query('/html/body');
		$value = $html->saveXml($body->item(0));
		$value = substr($value,strlen("<body>"),-strlen("</body>"));

		//$value = ($html->saveHTML());
		return $value.$valueAddon;
	}

	public static function psa_value_renderer($psa_id,$lang,$as,$value,$htmlChars)
	{
		if ($as['as_type'] != 'HTML')
		{
			return $value;
		}

		// CACHEING !! NOCH OFFEN
		if (strpos($value,"href")===false) return $value;

		$html = new DOMDocument();
		$html->loadHTML('<?xml version="1.0" encoding="utf-8"?>'.($value));
		$xpath = new DomXPath($html);
		$links = $xpath->query('//a');

		$valueAddon = "";

		for ($i=0;$i<$links->length;$i++) {

			$url	= $links->item($i)->getAttribute('href');
			$target	= $links->item($i)->getAttribute('target');
			$title	= $links->item($i)->getAttribute('title');
			$class	= "";

			$tagger = "#XR_LINK";
			if (strpos($url,$tagger)!==false)
			{
				list($crap,$json) = explode($tagger,$url);
				$settings = json_decode(urldecode($json),true);

				/*************
				* OLD STUFF BEGIN
				*****************/

				if (!isset($settings['choose']))
				{

					$target = $settings['target'];
					$title 	= $settings['title'];

					if (trim($settings['action'])=="")
					{
						$class 	= 'xr_noAction';
					} else
					{
						$class 	= 'xr_'.$settings['action'];
					}

					if ($settings['type']=='external')
					{
						$url = $settings['external'];
					} else
					{
						if (!is_numeric($settings['internal'])) $settings['internal'] = 1;


						$cfg = array(
						'p_id'=>intval($settings['internal']),
						'lang'=>xredaktor_pages::getFrontEndLang()
						);

						if ($cfg['p_id'] == 0)
						{
							$psa_p_id 		= xredaktor_render::getPSARecordById($psa_id);
							$cfg['p_id'] 	= xredaktor_niceurl::getStartPageById($psa_p_id);
						}


						$url = xredaktor_niceurl::genUrl($cfg);
					}

					$links->item($i)->removeAttribute('id');
					$links->item($i)->setAttribute('title',	$title);
					$links->item($i)->setAttribute('target',$target);
					$links->item($i)->setAttribute('href',	$url);
					$links->item($i)->setAttribute('class',	$class);

					/*************
					* OLD STUFF END
					*****************/
				} else
				{
					switch ($settings['choose'])
					{
						case 'EMAIL':

							$email_to = $settings['email_to'];
							$check = array();
							$check['subject'] 	= urlencode($settings['email_subject']);
							$check['cc'] 		= urlencode($settings['email_cc']);
							$check['body'] 		= urlencode($settings['email_body']);

							$url = "mailto:$email_to?d=1";

							foreach ($check as $k => $v)
							{
								if (trim($v) != "")
								{
									$url .= "&$k=$v";
								}
							}

							$links->item($i)->removeAttribute('id');
							$links->item($i)->setAttribute('title',	$email_subject);
							$links->item($i)->setAttribute('href',	$url);
							break;

						case 'FA':

							$s_id 			= intval($settings['filearchiv']);
							$FA_MODE 		= $settings['FA_MODE'];
							$FA_RESIZE 		= $settings['FA_RESIZE'];
							$FA_RESIZE_W 	= $settings['FA_RESIZE_W'];
							$FA_RESIZE_H 	= $settings['FA_RESIZE_H'];

							$downloadCfg 	= array(
							's_id' 		=> $s_id,
							'mode' 		=> $FA_MODE,
							'resize' 	=> $FA_RESIZE,
							'w' 		=> $FA_RESIZE_W,
							'h' 		=> $FA_RESIZE_H,
							);

							if ($s_id == 0)
							{
								$links->item($i)->removeAttribute('id');
								$links->item($i)->removeAttribute('href');
							} else
							{
								$download = xredaktor_storage::download($downloadCfg);
								$links->item($i)->removeAttribute('id');
								$links->item($i)->setAttribute('title',	$download['title']);
								$links->item($i)->setAttribute('href',	$download['href']);
								$links->item($i)->setAttribute('target',$download['target']);
							}
							break;

						case 'LB':
							$idOfContentDiv = "lb_content_".$psa_id.'_'.$i;
							$valueAddon .= "<div class='xr_lightbox_content'><div id='$idOfContentDiv'>"."<h1>".$settings['lb_title']."</h1>".$settings['lb_html']."</div></div>";
							$links->item($i)->removeAttribute('id');
							$links->item($i)->setAttribute('title',	$settings['lb_title']);
							$links->item($i)->setAttribute('target','');
							$links->item($i)->setAttribute('href',	'#'.$idOfContentDiv);
							$links->item($i)->setAttribute('class',	'xr_lightbox');
							break;
						case 'LINK':
						default:

							$type		= $settings['type'];
							$target 	= $settings['target'];
							$title 		= $settings['title'];
							$external 	= $settings['external'];
							$internal 	= intval($settings['internal']);

							switch ($type)
							{
								case 'internal':
									if ($internal==0)
									{
										$psa_p_id 		= xredaktor_render::getPSARecordById($psa_id);
										$start_p_id 	= xredaktor_niceurl::getStartPageById($psa_p_id);
										$internal = $start_p_id;
									}
									$cfg = array(
									'p_id' => $internal,
									'lang' => xredaktor_pages::getFrontEndLang()
									);
									$url = xredaktor_niceurl::genUrl($cfg);
									break;
								case 'external':
								default:
									$url = $external;
							}

							$links->item($i)->removeAttribute('id');
							$links->item($i)->setAttribute('title',	$title);
							$links->item($i)->setAttribute('target',$target);
							$links->item($i)->setAttribute('href',	$url);
							$links->item($i)->setAttribute('class',	$class);
					}
				}

			}
		}

		$body  = $xpath->query('/html/body');
		$value = $html->saveXml($body->item(0));
		$value = substr($value,strlen("<body>"),-strlen("</body>"));

		//$value = ($html->saveHTML());
		return $value.$valueAddon;
	}


	/**
		 * 
		 * WARTUNGSMODUS UND OFFLINE CHECKEN !!!!
		 * 
		 * **/


	public static function get404PageId()
	{
		return xredaktor_niceurl::guessErrorPage();
	}

	public static function processRequest()
	{
		$p_id = frontcontrollerx::getInt('p_id');
		if ($p_id === false) {
			$p_id = xredaktor_niceurl::guessStartPage();
		}
		self::renderPage($p_id);
	}

	public static function getPageById($p_id)
	{
		frontcontrollerx::isInt($p_id,"getPageById_[".$p_id."]");
		$p = dbx::query("select * from pages where p_id = $p_id and p_del = 'N'");
		return $p;
	}

	public static function getFrameByPageId($p_id)
	{
		frontcontrollerx::isInt($p_id,'getFrameByPageId');
		$p = dbx::query("select * from pages where p_id = $p_id");
		$p_frameid = $p['p_frameid'];
		$f = dbx::query("select * from atoms where a_id = $p_frameid");
		return $f;
	}

	public static function getAtom($a_id)
	{
		$a = dbx::query("select * from atoms where a_id = $a_id");
		return $a;
	}
	public static function getAtomHTML($a_id,$face_id=false)
	{
		$a = dbx::query("select * from atoms where a_id = $a_id");

		if (($face_id === false) || (!is_numeric($face_id)))
		{
			$face_id = self::getCurrentFace();
		}

		switch ($face_id)
		{
			case '0':
				return $a['a_content'];
				break;
			default:
				return $a['a_content_'.$face_id];
		}
	}

	public static function getContainers($a_id)
	{
		$containers = dbx::queryAll("select * from atoms_settings where as_a_id = $a_id and as_type = 'CONTAINER'");
		if (!is_array($containers)) $containers = array();
		return $containers;
	}

	public static function getASsMultiLangRecordsByIDAId($a_id)
	{
		$ass = dbx::queryAll("select * from atoms_settings where as_a_id = $a_id and as_type_multilang='Y'");
		if ($ass === false) $ass = array();
		return $ass;
	}

	public static function getASsNonMultiLangHTMLRecordsByIDAId($a_id)
	{
		$ass = dbx::queryAll("select * from atoms_settings where as_a_id = $a_id and as_type_multilang='N' and as_type='HTML'");
		if ($ass === false) $ass = array();
		return $ass;
	}


	public static function getASsSettingsComboCheckRadioRecordsByIDAId($a_id)
	{
		$ass = dbx::queryAll("select * from atoms_settings where as_a_id = $a_id and (as_type = 'COMBO' or as_type = 'CHECKBOX' or as_type = 'RADIO')");
		if ($ass === false) $ass = array();
		return $ass;
	}

	public static function getJSON_Fields($a_id)
	{
		$ass = dbx::queryAll("select * from atoms_settings where as_a_id = $a_id and (as_type = 'LINK' OR as_type='ATOMLIST')");
		if ($ass === false) $ass = array();
		$ret = array();

		foreach ($ass as $as)
		{
			$ret[] = $as['as_name'];
		}

		return $ret;
	}

	public static function getATOMLIST_Fields($a_id)
	{
		$ass = dbx::queryAll("select * from atoms_settings where as_a_id = $a_id and (as_type = 'ATOMLIST')");

		if ($ass === false) $ass = array();
		$ret = array(
		'check' => array(),
		'settings' => array(),
		);

		foreach ($ass as $as)
		{
			$ret['check'][] = $as['as_name'];
			$ret['settings'][$as['as_name']] = $as;
		}

		return $ret;
	}


	/****************************************************************************
	*
	*	REC-RENDERER
	*
	*/

	public static function getContainersContent($psa_fid,$as_id)
	{
		$containers = dbx::queryAll("select * from pages_settings_atoms where psa_fid=$psa_fid and psa_as_id=$as_id and psa_del = 'N' order by psa_sort");
		if (!is_array($containers)) $containers = array();
		return $containers;
	}

	public static function getMainSettings($p_id,$a_id,$psa_fid)
	{
		$settings = dbx::query("select * from pages_settings_atoms where psa_as_id = 0 and psa_p_id = $p_id and psa_a_id = $a_id and psa_fid=$psa_fid");
		return $settings;
	}


	public static function renderContainerInlines($p_id,$psa_fid,$as_id,$extraAssigns)
	{
		$_html = "";
		$_html .= self::injectContainerDivStart($p_id,$psa_fid,$as_id);

		$atomsInContainer = self::getContainersContent($psa_fid,$as_id);
		foreach ($atomsInContainer as $atomx)
		{
			$psa_id_of_atom		= $atomx['psa_id'];
			$psa_inline_a_id 	= $atomx['psa_inline_a_id'];
			$inline_atom_cfg 	= $atomx['psa_json_cfg'];
			$_html .= self::injectAtomDivStart($atomx);
			$_html .= self::renderAtom($p_id,$psa_inline_a_id,$psa_id_of_atom,array(),$extraAssigns);
			$_html .= self::injectAtomDivEnd($atomx);
		}

		$_html		.= self::injectContainerDivEnd($p_id,$psa_fid,$as_id);
		return $_html;
	}

	public static function checkContainer($p_id,$a_id,$as_id,$psa_fid)
	{
		if (count($ids)==0) $ids[] = -1;
		$ids = implode(",",$ids);

		$present = dbx::query("select * from pages_settings_atoms where psa_p_id = $p_id and psa_a_id = $a_id and psa_as_id = $as_id and psa_fid = $psa_fid");
		if ($present === false)
		{
			dbx::insert('pages_settings_atoms',array(
			'psa_p_id' 		=> $p_id,
			'psa_fid' 		=> $psa_fid,
			'psa_a_id' 		=> $a_id,
			'psa_as_id' 	=> $as_id,
			'psa_created' 	=> 'NOW()',
			'psa_sort'		=> dbx::queryAttribute("select max(psa_sort)+1 as county from pages_settings_atoms where psa_fid = $psa_fid",'county')
			));
			return dbx::getLastInsertId();
		} else
		{
			return $present['psa_id'];
		}
	}

	public static function getAtomCacheFileNameByFaceId($a_id,$face_id=false)
	{
		if ($face_id === false) $face_id = self::getCurrentFace();
		switch ($face_id)
		{
			case '0':
				$atom_cache_file_on_disk = dirname(__FILE__).'/../smarty/atom_cache/'.$a_id.'.cache.html';
				break;
			default:
				$atom_cache_file_on_disk = dirname(__FILE__).'/../smarty/atom_cache/'.$a_id.'.cache-'.$face_id.'.html';
		}

		return $atom_cache_file_on_disk;
	}

	public static function atom_cache_check($a_id,$renew=false,$showFace=false)
	{
		if ($_REQUEST['ACACHE']=='NO') $renew = true;

		if ($showFace !== false && is_numeric($showFace))
		{
			$face_id = $showFace;
		}
		else {
			$face_id = self::getCurrentFace();	
		}
		$atom_cache_file_on_disk = self::getAtomCacheFileNameByFaceId($a_id,$face_id);

		if ((!file_exists($atom_cache_file_on_disk)) || ($renew))
		{
			$faces = xredaktor_core::getFaces();
			foreach ($faces as $f)
			{
				$f_id = intval($f['f_id']);
				if ($f_id == 0) continue;

				$fileOnDisk	= self::getAtomCacheFileNameByFaceId($a_id,$f_id);
				$html 		= self::getAtomHTML($a_id,$f_id);
				hdx::fwrite($fileOnDisk,$html);
				//echo "[RESET] $fileOnDisk<br>";
			}

			$fileOnDisk	= self::getAtomCacheFileNameByFaceId($a_id,0);
			$html 		= self::getAtomHTML($a_id,0);
			//echo "[RESET] $fileOnDisk<br>";

			hdx::fwrite($fileOnDisk,$html);
		}

		//echo "[WANT] $fileOnDisk<br>";
		return $atom_cache_file_on_disk;
	}

	static $runningValues = array();



	public static function xr_mb_str_replace($search, $replace, $subject, &$count=0) {
		if (!is_array($search) && is_array($replace)) {
			return false;
		}
		if (is_array($subject)) {
			// call mb_replace for each single string in $subject
			foreach ($subject as &$string) {
				$string = &self::xr_mb_str_replace($search, $replace, $string, $c);
				$count += $c;
			}
		} elseif (is_array($search)) {
			if (!is_array($replace)) {
				foreach ($search as &$string) {
					$subject = self::xr_mb_str_replace($string, $replace, $subject, $c);
					$count += $c;
				}
			} else {
				$n = max(count($search), count($replace));
				while ($n--) {
					$subject = self::xr_mb_str_replace(current($search), current($replace), $subject, $c);
					$count += $c;
					next($search);
					next($replace);
				}
			}
		} else {
			$parts = mb_split(preg_quote($search), $subject);
			$count = count($parts)-1;
			$subject = implode($replace, $parts);
		}
		return $subject;
	}



	// THIS SHOULD BE FIXED

	public static function cleanNastyThings($str)
	{
		return str_replace(array("\xe2\x80\x8b","<br>"), array('','<br />'), $str);
	}

	public static function xr_chars($ret)
	{
		$ret = htmlspecialchars($ret);
		return $ret;
	}

	public static function xr_htmlSpecialChars($ret)
	{
		return htmlspecialchars($ret);
	}

	public static function isCMS_MODE()
	{
		return isset($_REQUEST['cms']);
	}

	public static function isCMS_MODE_ENTRY()
	{
		return false;
		return isset($_REQUEST['cms2']);
	}

	public static function handleValueByType($as,$value,$htmlChars=true)
	{
		/* TYPE SPECIFIC VALUE=="" ZUSAMMMEN LEGEN MIT WIZARDS */
		switch ($as['as_type'])
		{
			case 'TEXT':
			case 'TEXTAREA':
				if ($htmlChars) {
					$value = self::xr_chars($value);
				}
				break;
			case 'HTML':
				if (trim($value)=="<br>")	$value = "";
				if (trim($value)=="<br />") $value = "";
				$value = self::cleanNastyThings($value);
				break;
			case 'LINK':
				if (strpos($value,'"type":null')!==false) $value = "";
				break;
			default: break;
		}
		/* TYPE SPECIFIC VALUE=="" */
		return $value;
	}



	public static function getMultiLangValInclFailOverValueByASandRecord($as,$record,$isWizardData=false,$lang=false,$htmlChars=true)
	{
		$midKey = "";
		if ($lang===false) $lang = xredaktor_pages::getFrontEndLang();
		$langFailOver = xredaktor_pages::getLangFailOverOrder();

		if ($isWizardData) $midKey = "wz_";
		$as_name = $as['as_name'];


		if ($as['as_type_multilang'] == 'N')
		{
			switch ($as['as_type'])
			{
				case 'CHECKBOX':

					$langUP = strtoupper($lang);
					$as_config = json_decode($as['as_config'],true);
					$segments = array();

					foreach ($as_config['l'] as $l)
					{
						$_k = $l['v'];
						$onOff = ($record[$midKey.$as_name.'_'.$_k] == "on");
						if ($onOff)
						{
							$value = trim($as_config['a'][$_k][$langUP]);
							if ($value == "")
							{
								foreach ($langFailOver as $flang)
								{
									$flang = strtoupper($flang);
									$value = trim($as_config['a'][$_k][$flang]);
									if ($value != "") break;
								}
								if ($value == "") $value = trim($as_config['a'][$_k]['g']);
								if ($value == "") $value = trim($as_config['a'][$_k]['v']);
							}
							$segments[] = $value;
						}
					}

					$value = implode('-',$segments);

					break;

				case 'COMBO':
					$key = $record[$midKey.$as_name];
					$as_config = json_decode($as['as_config'],true);
					$value = trim($as_config['a'][$key][strtoupper($lang)]);

					if ($value == "")
					{

						$found = false;
						foreach ($langFailOver as $i=>$flang)
						{
							$flang 		= strtoupper($flang);
							$value = trim($as_config['a'][$key][$flang]);
							if ($value != "")
							{
								$found = true;
								break;
							}
						}

						if (!$found)
						{
							$value  	= trim($as_config['a'][$key]['g']);
						}

					}

					break;
				default:
					$value = $record[$midKey.$as_name];
			}

		} else
		{

			// TYPES wie COMBO etc ....

			$multiKey		= '_'.strtoupper($lang).'_'.$midKey.$as_name;
			$value  		= trim($record[$multiKey]);
			$value 			= self::handleValueByType($as,$value,$htmlChars);

			if ($value == "")
			{
				$found = false;
				$langFailOver = xredaktor_pages::getLangFailOverOrder();
				foreach ($langFailOver as $i=>$flang)
				{
					$flang 		= strtoupper($flang);
					$multiKey	= '_'.$flang.'_'.$midKey.$as_name;


					$value  	= trim($record[$multiKey]);
					$value 		= self::handleValueByType($as,$value,$htmlChars);
					if ($value != "")
					{
						$found = true;
						break;
					}
				}
				if (!$found)
				{
					$value  	= self::handleValueByType($as,$record[$as_name]);
				}
			}
		}

		/* FIXING ARRAY RESULTING VALUES*/

		switch ($as['as_type'])
		{
			case 'LINK':
				$value = json_decode($value,true);
				break;
			default: break;
		}

		return $value;
	}


	public static function getMultiLangValInclFailOverValueByASandRecord_____Old($as,$record,$isWizardData=false,$lang=false,$htmlChars=true)
	{
		$midKey = "";
		if ($lang===false) $lang = xredaktor_pages::getFrontEndLang();
		if ($isWizardData) $midKey = "wz_";
		$as_name = $as['as_name'];

		if ($as['as_type_multilang'] == 'N')
		{
			$value = $record[$midKey.$as_name];
		} else
		{
			$multiKey		= '_'.strtoupper($lang).'_'.$midKey.$as_name;
			$value  		= trim($record[$multiKey]);
			$value 			= self::handleValueByType($as,$value,$htmlChars);

			if ($value == "")
			{
				$found = false;
				$langFailOver = xredaktor_pages::getLangFailOverOrder();
				foreach ($langFailOver as $i=>$flang)
				{
					$flang 		= strtoupper($flang);
					$multiKey	= '_'.$lang.'_'.$midKey.$as_name;
					$value  	= trim($record[$multiKey]);
					$value 		= self::handleValueByType($as,$value,$htmlChars);
					if ($value != "")
					{
						$found = true;
						break;
					}
				}
				if (!$found)
				{
					$value  	= self::handleValueByType($as,$record[$as_name]);
				}
			}
		}

		/* FIXING ARRAY RESULTING VALUES*/

		switch ($as['as_type'])
		{
			case 'LINK':
				$value = json_decode($value,true);
				break;
			default: break;
		}

		return $value;
	}

	public static function renderSoloAtom($a_id,$assign=array())
	{
		if (!is_numeric($a_id)) die('renderSoloAtom NO-ID');

		self::$atomsUsed[$a_id] = 1;

		if (!isset($assign['P_LANG']))
		{
			$assign['P_LANG'] = xredaktor_pages::getFrontEndLang();
		}
		
		$showFace = false;
		if (isset($assign['showFace']))
		{
			$showFace = $assign['showFace'];
		}

		global $currentSiteId;
		$currentSiteId = xredaktor_atoms::getSiteIdByID($a_id);

		$file = self::atom_cache_check($a_id);
		//$assign['SITESETTINGS']	= site::getSiteSettings();

		$html =  templatex::render(self::atom_cache_check($a_id, false, $showFace),$assign,self::getSmartyAddOnsDir(),self::getSmartyTemplatesDir());
		return $html;
	}

	public static $atomsUsed = array();

	public static function renderAtom($p_id,$a_id,$psa_fid,$assign=array(),$extraAssigns=array())
	{
		self::$atomsUsed[$a_id] = 1;

		if (!is_array($assign)) 		$assign = array();
		if (!is_array($extraAssigns)) 	$extraAssigns = array();


		/***********************************************************
		* MAIN_SETTINGS OF ATOM
		*/

		$cfg = false;

		if ($psa_fid == 0)
		{
			$main_settings = self::getMainSettings($p_id,$a_id,$psa_fid);
		} else
		{
			if (!is_array($psa_fid))
			{
				$main_settings = dbx::query("select * from pages_settings_atoms where psa_id = $psa_fid");
			} else
			{
				$cfg = $main_settings = $psa_fid;
			}
		}

		if (is_array($main_settings))
		{
			if ($cfg === false) $cfg = json_decode($main_settings['psa_json_cfg'],true); // Patch für ATOM LIST
			$lang						= strtoupper(xredaktor_pages::getFrontEndLang());
			$langFailOver 				= xredaktor_pages::getLangFailOverOrder();
			$ass 						= self::getASsMultiLangRecordsByIDAId($a_id);
			$nonLangSpecificHtmlFields	= self::getASsNonMultiLangHTMLRecordsByIDAId($a_id);
			$checkComboRadio_settings 	= self::getASsSettingsComboCheckRadioRecordsByIDAId($a_id);
			$jsonFields 				= self::getJSON_Fields($a_id);
			$atomListFields 			= self::getATOMLIST_Fields($a_id);

			/*
			VALUES SET
			*/

			foreach ($cfg as $k => $value)
			{
				$assign[$k] = $value;

				if (in_array($k,$jsonFields)) {
					$assign[$k] = json_decode($value,true);
				}

				if (in_array($k,$atomListFields['check'])) {

					$retAtomList = array();

					$_a_id 		= $atomListFields['settings'][$k]['as_config'];
					$loopConfig = json_decode($value,true);
					$loopConfig = $loopConfig['l'];


					foreach ($loopConfig as $lc)
					{
						$retAtomList[] = self::renderAtom($p_id,$_a_id,$lc['atom_cfg']);
					}

					$assign[$k] = $retAtomList;
				}

				if ($_REQUEST['xms2'])
				{
					echo "<pre>";
					echo "$k => LEN:".strlen($value)."-".htmlspecialchars($value);
					echo "<pre>";
				}

			}

			foreach ($nonLangSpecificHtmlFields as $as)
			{
				$as_name 			= $as['as_name'];
				$assign[$as_name] 	= self::psa_value_renderer($psa_fid,'',$as,$assign[$as_name],true);
			}

			/*
			VALUES PATCHED BY LANG
			*/

			foreach ($ass as $as)
			{
				$as_type = $as['as_type'];


				switch ($as_type)
				{
					case 'ATOMLIST':
						echo "<pre>";
						print_r($as);
						echo "</pre>";
						break;
					default:

						$as_name 		= $as['as_name'];
						$doJsonDecode	= in_array($as_name,$jsonFields);
						$multiKey		= '_'.$lang.'_'.$as_name;
						$value  		= trim($cfg[$multiKey]);

						$value 			= self::psa_value_renderer($psa_fid,'',$as,$value,true);
						//						$value 			= self::handleValueByType($as,$value,true);

						if ($value == "")
						{
							$found = false;
							foreach ($langFailOver as $i=>$flang)
							{
								$flang 		= strtoupper($flang);
								$multiKey	= '_'.$flang.'_'.$as_name;
								$value  	= trim($cfg[$multiKey]);
								$value 			= self::psa_value_renderer($psa_fid,'',$as,$value);
								//$value 		= self::handleValueByType($as,$value);

								if ($value != "")
								{
									$found = true;
									break;
								}
							}
							if (!$found)
							{
								$value  	= self::handleValueByType($as,$cfg[$as_name]);
							}
						}

						if ($doJsonDecode)
						{
							$value = json_decode($value,true);
						}

						$assign[$as_name] = $value;
				}
			}

			/*

			INJECT SETTINGS OF COMBOS, CHECKBOX, RADIOS

			*/

			foreach ($checkComboRadio_settings as $setting)
			{
				$as_type_multilang 	= $setting['as_type_multilang'];
				$as_type 			= $setting['as_type'];
				$as_name 			= $setting['as_name'];
				$as_config 			= $setting['as_config'];
				$tmp  				= json_decode($as_config,true);

				$assoz 						= $tmp['a'];
				$linearSorted				= $tmp['l'];
				$assign['CFG_'.$as_name] 	= $tmp;

				switch ($as_type)
				{
					case 'CHECKBOX':

						$reassign = array('a'=>array(),'l'=>array());

						foreach ($linearSorted as $checkSets)
						{
							$preKey = "";
							if ($as_type_multilang == 'Y')
							{
								$preKey = '_'.$lang.'_';
							}

							$keyNacked	= $as_name.'_'.$checkSets['v'];
							$key 		= $preKey.$keyNacked;
							$checked 	= isset($assign[$key]);

							if ($checked)
							{

								$label = $checkSets[$lang];

								if (trim($label)=="")
								{
									$found = false;
									foreach ($langFailOver as $i=>$flang)
									{
										$flang	= strtoupper($flang);
										$label 	= $checkSets[$flang];
										if (trim($label) != "")
										{
											$found = true;
											break;
										}
									}
									if (!$found)
									{
										$label = $checkSets['g'];
									}
								}

								$assign[$keyNacked.'_label'] = $label;
								$reassign['a'][$checkSets['v']] = $label;
								$reassign['l'][] 				= array('k'=>$checkSets['v'],'v'=>$label);
							}

						}

						$assign[$as_name] = $reassign;

						break;
					default:
						if ($as_type_multilang == 'Y')
						{
							$multiKey = '_'.$lang.'_'.$as_name;
							$label = $tmp['a'][$assign[$multiKey]][$lang];
							if (trim($label)=="")
							{
								$found = false;
								foreach ($langFailOver as $i=>$flang)
								{
									$flang 		= strtoupper($flang);
									$multiKey	= '_'.$flang.'_'.$as_name;
									$label = $tmp['a'][$assign[$multiKey]][$flang];
									if ($value != "")
									{
										$found = true;
										break;
									}
								}
								if (!$found)
								{
									$label = $tmp['a'][$assign[$multiKey]]['g'];
								}
							}
						} else
						{
							$label = $tmp['a'][$assign[$as_name]][$lang];
							// ÜBERLAUF FEHLT HIER
						}
						$assign['LABEL_'.$as_name] 	= $label;
				}
			}

			/*

			CRAP

			*/

			if (isset($assign['HEADLINE']))
			{
				self::setRunningValue($p_id,'AHEADLINE',$assign['HEADLINE'],false);
			}
		}

		if (is_array($psa_fid)) return $assign; // PATCH FOR ATOMLIST

		if ($_REQUEST['xms'])
		{
			echo "<pre>";
			echo print_r($assign);
			echo "<pre>";
		}


		/***********************************************************
		* CONTAINERS OF ATOM
		*/

		$containers  = self::getContainers($a_id);
		foreach ($containers as $container)
		{
			$as_id 		= $container['as_id'];
			$as_name 	= $container['as_name'];

			$psa_id_container 	= self::checkContainer($p_id,$a_id,$as_id,$psa_fid);
			$_html 				= self::renderContainerInlines($p_id,$psa_id_container,$as_id,$extraAssigns);
			$assign[$as_name] = $_html;
		}


		$protectedValues = array('P_ID','REQUEST','PSA_ID','CMS');
		foreach (self::$runningValues[$p_id] as $k => $v)
		{
			$assign[$k] = $v;
		}

		if (self::isCMS_MODE())
		{
			$assign['CMS'] = self::injectEditHtml($p_id);
		}

		$assign['GA'] 			= $extraAssigns;

		if (self::$disableClientInfos)
		{
			$assign['IS_USER_BE'] 	= false;
			$assign['IS_DEV'] 		= false;
		} else
		{
			$assign['IS_USER_BE'] 	= xredaktor_core::isBackendEndUserLoggedIn();
			$assign['IS_DEV'] 		= libx::isDeveloper();
		}

		//$assign['SITESETTINGS']	= site::getSiteSettings();
		$assign['P_ID'] 		= $p_id;
		$assign['REQUEST']		= $_REQUEST;
		$assign['PSA_ID'] 		= $psa_fid;
		$assign['P_LANG'] 		= xredaktor_pages::getFrontEndLang();
		$assign['XR_IS_MOBILE'] = libx::isMobileBrowser();
		$assign['XR_FACE'] 		= xredaktor_render::getCurrentFace();
		$assign['XR_ATOMS_IN_USER'] = json_encode(self::$atomsUsed);

		$html =  templatex::render(self::atom_cache_check($a_id),$assign,self::getSmartyAddOnsDir(),self::getSmartyTemplatesDir());
		return $html;
	}

	public static function setRunningValue($p_id,$k,$v,$overRideIfPresent)
	{
		if ((isset(self::$runningValues[$p_id][$k])) && !$overRideIfPresent) return;
		self::$runningValues[$p_id][$k] = $v;
	}

	public function getSmartyTemplatesDir()
	{
		return dirname(__FILE__).'/../../../../xstorage/';
		return dirname(__FILE__).'/../smarty';
	}

	public function getSmartyAddOnsDir()
	{
		return dirname(__FILE__).'/../smarty';
	}

	/****************************************************************************
	*
	*	MARKERS
	*
	*/


	public static function injectContainerDivStart($p_id,$psa_id,$as_id)
	{
		if (!self::isCMS_MODE()) return "";
		$psa 		= self::getPSARecordById($psa_id);
		$container 	= xredaktor_atoms_settings::getById($psa['psa_as_id']);
		$as_name 	= $container['as_name'];
		return "<div class='xc_container_start' rel='$psa_id' as_name='$as_name' psa_id='$psa_id' as_id='$as_id'></div>";
	}

	public static function injectContainerDivEnd($p_id,$psa_id,$as_id)
	{
		if (!self::isCMS_MODE()) return "";
		$psa 		= self::getPSARecordById($psa_id);
		$container 	= xredaktor_atoms_settings::getById($psa['psa_as_id']);
		$as_name 	= $container['as_name'];
		return "<div class='xc_container_end' rel='$psa_id' as_name='$as_name' psa_id='$psa_id' as_id='$as_id'></div>";
	}



	public static function injectAtomDivStart($atomx)
	{
		if (!self::isCMS_MODE()) return "";
		$psa_id 			= $atomx['psa_id'];
		$psa_inline_a_id 	= $atomx['psa_inline_a_id'];
		$atom = self::getAtom($psa_inline_a_id);
		$a_name = $atom['a_name'];
		return "<div class='xc_atom_start' rel='$psa_id' psa_id='$psa_id' a_name='$a_name'></div>";
	}

	public static function injectAtomDivEnd($atomx)
	{
		if (!self::isCMS_MODE()) return "";
		$psa_id 			= $atomx['psa_id'];
		$psa_inline_a_id 	= $atomx['psa_inline_a_id'];
		$atom = self::getAtom($psa_inline_a_id);
		$a_name = $atom['a_name'];
		return "<div class='xc_atom_end' rel='$psa_id' psa_id='$psa_id' a_name='$a_name'></div>";
	}


	public static function injectEditHtml($p_id)
	{
		$html	= "";
		$assign = array('p_id'=>$p_id,'project_id' => xredaktor_niceurl::getSiteIdViaPageId($p_id));
		$html = templatex::render(dirname(__FILE__).'/../fly3/tpl/inject.tpl',$assign,self::getSmartyAddOnsDir(),self::getSmartyTemplatesDir());
		return $html;
	}


	public static function jump2nice404($p_id)
	{
		if ($p_id == self::get404PageId())
		{
			frontcontrollerx::header404();
		}

		header("HTTP/1.1 301 Moved Permanently");
		header("Location: ".xredaktor_niceurl::genUrl(array(
		'p_id' 	=> self::get404PageId(),
		'lang'	=> xredaktor_pages::getFrontEndLang()
		)));
		die();
	}

	public static function renderPageByVID($p_vid,$return=false,$extraAssigns=array())
	{
		$page = dbx::query("select * from pages where p_vid = $p_vid");
		$p_id = $page['p_id'];
		return self::renderPage($p_id,$return,$extraAssigns);
	}

	public static function renderPageEvenItIsOffline()
	{
		self::$renderOfflinePages = true;
	}

	public static function renderPageNoClientInfos()
	{
		self::$disableClientInfos = true;
	}

	public static function renderPage($p_id,$return=false,$extraAssigns=array(),$header404Check=true)
	{

		libx::turnOnErrorReporting();
		if ($p_id == 0) die('-');



		$p_id 	= frontcontrollerx::isInt($p_id,'PAGE_ID_NOT_NUMERIC');
		$p 		= self::getPageById($p_id);

		if ($p === false)
		{
			self::jump2nice404($p_id);
		}

		if ($header404Check)
		{
			if ($p_id == self::get404PageId())
			{
				header("HTTP/1.0 404 Not Found");
			}
		}

		if (($p['p_isOnline'] == 'N') && ($p_id != self::get404PageId()))
		{
			if (!self::$renderOfflinePages)
			{
				frontcontrollerx::header404();
			}
		}

		$a 		= self::getFrameByPageId($p_id);
		if (!is_numeric($a['a_id'])) die('Topelement nicht ');


		if (!is_array($extraAssigns)) $extraAssigns = array();

		$assign = array(
		'P_ID' => $p_id,
		'REQUEST' => $_REQUEST
		);

		self::$runningValues[$p_id] = array();

		if (self::isCMS_MODE_ENTRY())
		{
			/*
			$project_id = 1;
			$project_id = xredaktor_niceurl::getSiteIdViaHttpHost();
			$assign = array('url'=>'/xgo/xplugs/xredaktor/ajax/render/page?p_id='.$p_id.'&cms2=1','p_id'=>$p_id,'project_id'=>$project_id);
			$html = templatex::render(dirname(__FILE__).'/../fly2/tpl/wrapper.tpl',$assign,self::getSmartyAddOnsDir(),self::getSmartyTemplatesDir());
			*/
		} else
		{
			$html = self::renderAtom($p_id,$a['a_id'],0,$assign,$extraAssigns);
		}


		if ($return) return $html;

		if (isset($_REQUEST['XR_MAIL2']) && libx::isDeveloper())
		{
			$to = trim($_REQUEST['XR_MAIL2']);
			$cfg = xredaktor_niceurl::getSiteConfigViaPageId($p_id);
			$storage = dirname(xredaktor_storage::getDirOfStorageScope($cfg['s_s_storage_scope']));


			if (isset($cfg['s_mail_smtp_server']))
			{
				$s_mail_reply_name 	= $cfg['s_mail_reply_name'];
				$s_mail_reply_email = $cfg['s_mail_reply_email'];
				$s_mail_from_name 	= $cfg['s_mail_from_name'];
				$s_mail_from_email 	= $cfg['s_mail_from_email'];
				$s_mail_smtp_server = $cfg['s_mail_smtp_server'];
				$s_mail_smtp_user 	= $cfg['s_mail_smtp_user'];
				$s_mail_smtp_pwd 	= $cfg['s_mail_smtp_pwd'];
			}

			if (trim($s_mail_reply_name) == "") 	$s_mail_reply_name 	= $s_mail_from_name;
			if (trim($s_mail_reply_email) == "") 	$s_mail_reply_email = $s_mail_from_email;

			$mailCfg = array(
			'to'						=> array('email' => $to,					'name' => $to),
			'from'						=> array('email' => $s_mail_from_email ,	'name' => $s_mail_from_name ),
			'reply'						=> array('email' => $s_mail_reply_name ,	'name' => $s_mail_reply_email ),
			'html'						=> $html,
			'txt'						=> '',
			'subject'					=> "PAGE2EMAIL VIA XGO [$p_id]",
			'priority'					=> mailx::PRIO_NORMAL,
			'imageProcessing' 			=> true,
			'imageProcessing_type' 		=> 'embedd',
			'imageProcessing_location' 	=> $storage,
			'smtp_settings'				=> array(
			'smtp_server'	=> $s_mail_smtp_server,
			'smtp_user'		=> $s_mail_smtp_user,
			'smtp_pwd'		=> $s_mail_smtp_pwd,
			)
			);

			if (!mailx::sendMail($mailCfg))
			{
			}
		}
		die($html);
	}

	public static function getPSARecordById($psa_id)
	{
		return dbx::query("select * from pages_settings_atoms where psa_id = $psa_id");
	}

	/****************************************************************************
	*
	*	EDIT-FUNCTIONS
	*
	*/


	public static function atomAppend($cfg)
	{

		$psa_inline_a_id 	= $cfg['psa_inline_a_id'];
		$psa_fid 			= $cfg['psa_fid'];

		$father = self::getPSARecordById($psa_fid);
		$p_id = $cfg['psa_p_id'] = $father['psa_p_id'];
		$psa_as_id = $cfg['psa_as_id'];

		$psa_sort = dbx::queryAttribute("select max(psa_sort)+1 as county from pages_settings_atoms where psa_fid = $psa_fid ","county");
		$cfg['psa_sort'] 	= $psa_sort;
		$cfg['psa_created']	= 'NOW()';
		dbx::insert('pages_settings_atoms',$cfg);
		$psa_id = dbx::getLastInsertId();


		return array(self::getPSARecordById($psa_id),self::renderContainerInlines($p_id,$psa_fid,$psa_as_id));
	}

	public static function atomRemove($cfg)
	{
		$psa_id 	= $cfg['psa_id'];
		$psa 		= self::getPSARecordById($psa_id);
		$psa_fid 	= $psa['psa_fid'];
		$p_id 		= $psa['psa_p_id'];
		$psa_as_id 	= $psa['psa_as_id'];

		dbx::update('pages_settings_atoms',array('psa_del'=>'Y'),array('psa_id'=>$psa_id));
		xredaktor_storage::fixFileUsage($psa_id);
		return array(self::renderContainerInlines($p_id,$psa_fid,$psa_as_id));
	}

	public static function atomMoveUp($cfg)
	{
		$psa_id 			= $cfg['psa_id'];

		$psa = self::getPSARecordById($psa_id);

		$psa_p_id 	= $psa['psa_p_id'];
		$psa_a_id 	= $psa['psa_a_id'];
		$psa_fid 	= $psa['psa_fid'];
		$psa_as_id 	= $psa['psa_as_id'];
		$psa_sort 	= $psa['psa_sort'];

		$atom2change = dbx::query("select * from pages_settings_atoms where psa_p_id = $psa_p_id and psa_a_id = $psa_a_id and psa_as_id = $psa_as_id and psa_fid = $psa_fid and psa_sort < $psa_sort and psa_del = 'N' order BY psa_sort DESC");
		if ($atom2change !== false)
		{
			$psa_id_2 	= $atom2change['psa_id'];
			$psa_sort_2 = $atom2change['psa_sort'];
			dbx::update('pages_settings_atoms',array('psa_sort'=>$psa_sort),	array('psa_id'=>$psa_id_2));
			dbx::update('pages_settings_atoms',array('psa_sort'=>$psa_sort_2),	array('psa_id'=>$psa_id));
		}

		return array(self::getPSARecordById($psa_id),self::renderContainerInlines($psa_p_id,$psa_fid,$psa_as_id));
	}

	public static function atomMoveDown($cfg)
	{
		$psa_id 			= $cfg['psa_id'];

		$psa = self::getPSARecordById($psa_id);

		$psa_p_id 	= $psa['psa_p_id'];
		$psa_a_id 	= $psa['psa_a_id'];
		$psa_fid 	= $psa['psa_fid'];
		$psa_as_id 	= $psa['psa_as_id'];
		$psa_sort 	= $psa['psa_sort'];

		$atom2change = dbx::query("select * from pages_settings_atoms where psa_p_id = $psa_p_id and psa_a_id = $psa_a_id and psa_as_id = $psa_as_id and psa_fid = $psa_fid and psa_sort > $psa_sort and psa_del = 'N' order BY psa_sort ASC");
		if ($atom2change !== false)
		{
			$psa_id_2 	= $atom2change['psa_id'];
			$psa_sort_2 = $atom2change['psa_sort'];
			dbx::update('pages_settings_atoms',array('psa_sort'=>$psa_sort),	array('psa_id'=>$psa_id_2));
			dbx::update('pages_settings_atoms',array('psa_sort'=>$psa_sort_2),	array('psa_id'=>$psa_id));
		}

		return array(self::getPSARecordById($psa_id),self::renderContainerInlines($psa_p_id,$psa_fid,$psa_as_id));
	}

	public static function atomInsertBefore($cfg)
	{
		$psa_id 			= $cfg['psa_id'];
		$psa_inline_a_id 	= $cfg['psa_inline_a_id'];

		$psa = self::getPSARecordById($psa_id);

		$psa_p_id 	= $psa['psa_p_id'];
		$psa_a_id 	= $psa['psa_a_id'];
		$psa_fid 	= $psa['psa_fid'];
		$psa_as_id 	= $psa['psa_as_id'];
		$psa_sort 	= $psa['psa_sort'];

		dbx::insert('pages_settings_atoms',array(
		'psa_fid' 			=> $psa_fid,
		'psa_p_id' 			=> $psa_p_id,
		'psa_a_id' 			=> $psa_a_id,
		'psa_as_id' 		=> $psa_as_id,
		'psa_sort'			=> $psa_sort,
		'psa_inline_a_id' 	=> $psa_inline_a_id,
		'psa_created'		=> 'NOW()'
		));

		$psa_id_new = dbx::getLastInsertId();

		dbx::query("update pages_settings_atoms set psa_sort=psa_sort+1 where psa_a_id = $psa_a_id and psa_as_id = $psa_as_id and psa_p_id = $psa_p_id and psa_id != $psa_id_new and psa_sort >= $psa_sort");

		return array(self::getPSARecordById($psa_id_new),self::renderContainerInlines($psa_p_id,$psa_fid,$psa_as_id));
	}

	public static function smartyTester()
	{
		return "HELLO SMARTY!";
	}
}