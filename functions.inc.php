<?php

function parse_uri() {	
	global $home_page, $pages, $base_url, $show_comments, $langs, $def_lang;
	
	$nh = preg_replace('#/\./#', '/', $base_url);
	$nh = preg_replace('#^http[s]*://[^/]+/#i', '/', $nh);
	$nh = preg_replace('#/[^/]+/\.\./#i', '/', $nh);
	$ru = trim(isset($_SERVER['REQUEST_URI']) ? urldecode($_SERVER['REQUEST_URI']) : '/');
	$ru = preg_replace('#'.preg_quote($nh).'#i', '', $ru, 1);
	list($ru) = explode('?', $ru, 2);
	
	if (isset($_GET['route'])) {
		$ru = trim($_GET['route']);
	}
	$ru = preg_split('#[\ \t]*[/]+[\ \t]*#i', $ru, -1, PREG_SPLIT_NO_EMPTY);
	$ru = array_map('trim', $ru);
	
	$cusr = null;
	if (strpos(ini_get('disable_functions'), 'get_current_user') === false) {
		$cusr = get_current_user();
	}
	if ($cusr && !empty($ru) && ($ru[0] == ('~'.$cusr) || $ru[0] == ($cusr.'~'))) {
		array_shift($ru);
	}
	
	if (isset($ru[0]) && preg_match('#^[a-z]{2}-[A-Z]{2}$#', $ru[0])) {
		array_shift($ru);
	}
	
	if (!count($ru)) {
		foreach ($pages as $idx => $pi) {
			if ($home_page == $pi['id']) return array($idx, $def_lang);
		}
		return array($home_page, $def_lang);
	}
	
	$show_comments = false;
	
	if ($ru[0] == 'news') {
		$pageIdx = getPageIndexById(isset($ru[1]) ? intval($ru[1]) : null);
		return array($pageIdx, $def_lang);
	}
	else if ($ru[0] == 'blog') {
		$pageIdx = getPageIndexById(isset($ru[1]) ? intval($ru[1]) : null);
		$show_comments = true;
		return array($pageIdx, $def_lang);
	}
	
	$ru_ = $lang = null;
	if (!$ru) {
		$ru_ = $ru;
		$lang = $def_lang;
	} else if (is_array($ru)) {
		if (count($ru) == 1) {
			if ($def_lang) {
				if (isset($langs[$ru[0]])) {
					$lang = $ru[0];
				} else {
					$lang = $def_lang;
					$ru_ = $ru[0];
				}
			} else {
				$ru_ = $ru[0];
			}
		} else {
			$lang = $ru[0];
			$ru_ = $ru[1];
		}
	}
	
	foreach ($pages as $idx => $pi) {
		if ($ru_ == $pi['id']) return array($idx, $lang);
	}
	if (!$ru_) {
		foreach ($pages as $idx => $pi) {
			if ($home_page == $pi['id']) return array($idx, $lang);
		}
		return array($home_page, $lang);
	}
	
	foreach ($pages as $idx => $pi) {
		if (is_array($pi['alias'])) {
			if ($lang && isset($pi['alias'][$lang]) && $ru_ == $pi['alias'][$lang]) {
				return array($idx, $lang);
			}
		} else if ($ru_ == $pi['alias']) {
			return array($idx, $lang);
		}
	}
	
	return array(-1, $lang);
}

function getPageIndexById($pageId) {
	global $pages;
	foreach ($pages as $id => $pi) {
		if ($pageId == $pi['id']) return $id;
	}
	return null;
}

function getPageUri($pageId, $lang = null) {
	global $pages, $def_lang;
	foreach ($pages as $id => $pi) {
		if ($pi['id'] != $pageId) continue;
		if (is_array($pi['alias'])) {
			if ($lang && isset($pi['alias'][$lang])) {
				return "$lang/{$pi['alias'][$lang]}";
			} else if ($def_lang && isset($pi['alias'][$def_lang])) {
				return "$def_lang/{$pi['alias'][$def_lang]}";
			}
		} else {
			return "/{$pi['alias']}";
		}
	}
}

function handleComments($pageId = null) {
	if (isset($_POST['postComment'])) {
		// message field is used as "Honney Pot" trap
		if ($pageId && isset($_POST['message']) && !$_POST['message']) {
			$file = dirname(__FILE__).'/'.$pageId.'.comments.dat';
			$data = is_file($file) ? file_get_contents($file) : null;
			$data = $data ? @json_decode($data) : array();
			if (trim($_POST['text'])) {
				$data[] = $comment = array(
					'date' => date('Y-m-d'),
					'time' => date('H:i'),
					'user' => ($_POST['name'] ? $_POST['name'] : 'anonymous'),
					'text' => substr($_POST['text'], 0, 200)
				);
				file_put_contents($file, json_encode($data));
				
				// post info to builder
				if (function_exists('curl_init')) {
					global $user_key, $user_hash, $comment_callback;
					$res = _http_get($comment_callback, array(
						'key'	=> $user_key,
						'hash'	=> md5($user_key.$user_hash),
						'id'	=> $pageId,
						'date'	=> base64_encode($comment['date']),
						'time'	=> base64_encode($comment['time']),
						'name'	=> base64_encode($comment['user']),
						'message'=> base64_encode($comment['text'])
					));
				}				
			}
		}
		list($ru) = explode('?', $_SERVER['REQUEST_URI']);
		list($ru) = explode('#', $ru);
		header('Location: '.$ru.'#wb_comment_box');
		exit();
	}
}

function renderComments($pageId = null) {
	$comments = array();
	$data = dirname(__FILE__).'/'.$pageId.'.comments.dat';
	$data = is_file($data) ? file_get_contents($data) : null;
	$data = $data ? @json_decode($data) : null;
	if ($data && is_array($data)) {
		$comments = array_reverse($data);
	}
	include dirname(__FILE__).'/comments.tpl.php';
}

function tr_($value, $lang = null, $default = null) {
	global $def_lang;
	if (!$lang) $lang = $def_lang;
	if ($lang) {
		if (is_array($value) && isset($value[$lang])) {
			return $value[$lang];
		}
	}
	return ($value) ? $value : $default;
}

function handleForms($page_id) {
	global $forms, $formErrors, $def_lang, $lang;
	$formErrors = new stdClass();
	// check to ensure that all parameters are ok as well as protect from bots
	// and hackers
	if (!isset($_POST['wb_form_id'])
		|| $_POST['message'] !== ''
		|| !isset($forms)
		|| !is_array($forms)
		|| !isset($page_id)
		|| !(isset($forms[$page_id]) || isset($forms['blog']))
		|| !(isset($forms[$page_id][$_POST['wb_form_id']]) || isset($forms['blog'][$_POST['wb_form_id']]))
		|| !(isset($forms[$page_id][$_POST['wb_form_id']]['fields']) || isset($forms['blog'][$_POST['wb_form_id']]['fields']))
		|| isset($_POST['forms'])
		|| isset($_GET['forms'])
	) return;
	
	if (!class_exists('PHPMailer')) {
		include dirname(__FILE__).'/phpmailer/class.phpmailer.php';
	}
	$form = isset($forms[$page_id][$_POST['wb_form_id']])
		? $forms[$page_id][$_POST['wb_form_id']] : $forms['blog'][$_POST['wb_form_id']];
	$fields = $form['fields'];
	$email_list = array_map('trim', explode(';', $form['email']));
	$mail_to = array();
	foreach ($email_list as $eml) {
		if (($m = is_mail($eml))) { $mail_to[] = $m; }
	}
	$mail_from = reset($mail_to);
	$mail_from_name = 'NoName';
	
	global $wb_form_send_state;
	$wb_form_send_state = false;
	
	$data = Array();
	foreach($fields as $idx => $field) {
		if (!isset($_POST["wb_input_$idx"])) {
			return; // all fields must be present
		}
		$max_len = ($field["type"]=="textarea")?65536:1024; // 65 kilobytes max for textarea and 1024 for other
		$value = $_POST["wb_input_$idx"];
		if (empty($value) && strlen($value) == 0) {
			if (!isset($formErrors->required)) $formErrors->required = array();
			$formErrors->required[] = "wb_input_$idx";
		}
		$value = htmlspecialchars($value);
		$value = @substr($value, 0, $max_len);
		if ($field["type"] == "select") {
			$options = explode(";", tr_($field["options"], $lang));
			$data[$idx] = trim($options[intval($value)]);
		} else
			$data[$idx] = $value;
		if ($field["fidx"] == 0) $mail_from_name = $value;
		if ($field["fidx"] == 1) $mail_from = is_mail($value);
	}
	
	$formErrors_t = (array) $formErrors;
	if (!empty($formErrors_t)) return; // must not have any errors
	
	if (!$mail_from_name) $mail_from_name = "Anonymous";
	if (!$mail_from) $mail_from = reset($mail_to);
	
	if (!empty($mail_to)) {
		$mailer = new PHPMailer();
		// $mailer->PluginDir = dirname(__FILE__) . "/phpmailer/";
		
		if (isset($form['smtpEnable']) && $form['smtpEnable']) {
			include dirname(__FILE__).'/phpmailer/class.smtp.php';
			
			$mailer->isSMTP();
			$mailer->Host = ((isset($form['smtpHost']) && $form['smtpHost']) ? $form['smtpHost'] : 'localhost');
			$mailer->Port = ((isset($form['smtpPort']) && intval($form['smtpPort'])) ? intval($form['smtpPort']) : 25);
			$mailer->SMTPSecure = ((isset($form['smtpEncryption']) && $form['smtpEncryption']) ? $form['smtpEncryption'] : '');
			if (isset($form['smtpUsername']) && $form['smtpUsername'] && isset($form['smtpPassword']) && $form['smtpPassword']) {
				$mailer->SMTPAuth = true;
				$mailer->Username = ((isset($form['smtpUsername']) && $form['smtpUsername']) ? $form['smtpUsername'] : '');
				$mailer->Password = ((isset($form['smtpPassword']) && $form['smtpPassword']) ? $form['smtpPassword'] : '');
			}
		}

		$style = "* { font: 12px Arial; }\nstrong { font-weight: bold; }";
		foreach ($mail_to as $eml) {
			$mailer->AddAddress($eml);
		}
		$mailer->SetFrom($mail_from, $mail_from_name);
		$mailer->CharSet = 'utf-8';
		//$mailer->MsgHTML(preg_replace('/([\x{80}-\x{FFFFFF}])/ue', "mb_convert_encoding('$1', 'HTML-ENTITIES', 'UTF-8')", $tpl->getHTML()));
		$message = '<table cellspacing="5" cellpadding="0">';
		foreach ($fields as $idx => $field) {
			$name = tr_($field["name"]);
			$value = $data[$idx];
			if ($field["type"] == "textarea")
				$message .= "<tr><td colspan=\"2\"><strong>$name: </strong></td></tr>\n<tr><td colspan=\"2\">" . nl2br($value) . "</td></tr>\n";
			else
				$message .= "<tr><td><strong>$name: </strong></td><td>" . nl2br($value) . "</td></tr>\n";
  		}
		$message .= '</table>';
		
		$html =
'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>' . $form["subject"] . '</title>
		<meta http-equiv=Content-Type content="text/html; charset=utf-8">
		' . ($style?"<style><!--\n$style\n--></style>\n\t\t":"") . '</head>
	<body>' . $message . '</body>
</html>';
		$mailer->MsgHTML($html);
		$mailer->AltBody = strip_tags(str_replace("</tr>", "</tr>\n", $message));
		$mailer->Subject = $form["subject"];
		ob_start();
		$res = $mailer->Send();
		$err = ob_get_clean();
		if ($res) {
			$wb_form_send_state = (isset($form['sentMessage']) && $form['sentMessage']) ? $form['sentMessage'] : 'Form was sent.';
		} else {
			$wb_form_send_state = 'Form sending failed.';
		}
	} else {
		$wb_form_send_state = 'Form configuration error.';
	}
}

function is_mail($mail) {
	if (preg_match("/^[0-9a-zA-Z\.\-\_]+\@[0-9a-zA-Z\.\-\_]+\.[0-9a-zA-Z\.\-\_]+$/is", trim($mail)))
		return $mail;
	return "";
}

function mini_text($text) {
	return trim(substr(strip_tags($text), 0, 100), " \n\r\t\0\x0B.").'...';
}

$Wildfire_header_sent = false;
$Wildfire_msg_idx = false;
	
function wf_log($msg, $type = 'LOG') {
	global $Wildfire_header_sent, $Wildfire_msg_idx;
	$types = Array('LOG', 'INFO', 'WARN', 'ERROR');
	$type = in_array(strtoupper($type), $types) ? strtoupper($type) : $types[0];
	$escape = "\"\0\n\r\t\\";
	
	$trs = debug_backtrace();
	$last = Array();
	foreach ($trs as $li) {
		if (isset($li['class']) && $li['class'] == __CLASS__) { $last = $li; continue; }
		$last = $li;
		break;
	}
	
	$message = '[{"Type":"'.$type.'","File":"'.addcslashes($last['file'], $escape).'",'.
		'"Line":'.$last['line'].'},"'.addcslashes($msg, $escape).'"]';
	if ($Wildfire_msg_idx === false) $Wildfire_msg_idx = 0;
	if (!$Wildfire_header_sent) {
		$Wildfire_header_sent = true;
		header('X-Wf-Protocol-1: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
		header('X-Wf-1-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3');
		header('X-Wf-1-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
	}
	$count = ceil(strlen($message) / 5000);
	for ($i = 0; $i < $count; $i++) {
		$Wildfire_msg_idx++;
		$part = substr($message, ($i * 5000), 5000);
		header('X-Wf-1-1-1-'.$Wildfire_msg_idx.': '.(($i == 0) ? strlen($message) : '').
			'|'.$part.'|'.(($i < ($count - 1)) ? '\\' : ''));
	}
}

if (!function_exists('trace')) {
function trace($var, $return = false) {
	$code = '';
	if (is_array($var) || is_object($var)) {
		$code .= '<pre style="margin: 0;">'.print_r($var, true).'</pre>';
	} else {
		$code .= $var;
	}
	if (is_bool($var)) $code = '<span style="color: #0000ff; font-weight: bold; font-style: normal; font-family: Courier New; font-size: 12px;">'.($var ? 'TRUE' : 'FALSE').'</span>';
	if (is_null($var)) $code = '<span style="color: #0000ff; font-weight: bold; font-style: normal; font-family: Courier New; font-size: 12px;">NULL</span>';
	
	$code = '<div style="padding: 0px; margin: 4px 0; position: relative; float: none; clear: both; border: 1px dashed #e5e09b;">'.
			'<a style="display: block; position: absolute; right: 3px; top: 2px; font-weight: bold; text-decoration: none; line-height: 14px; color: #676767; font-family: arial,sans-serif; font-size: 19px;" href="#" onclick="this.parentNode.style.display = \'none\'; return false;" title="Hide">'.
				'&times;'.
			'</a>'.
			'<div id="FormMessages_message" style="padding: 17px 20px; margin: 0; float: none; background: #fffde0; color: #000000; font-family: Arial; font-size: 13px; font-style: italic;">'.$code.'</div>'.
		'</div>';
	if ($return) return $code; else echo $code;
}
}

function _http_get($url, $post_vars = false) {
	$post_contents = '';
	if ($post_vars) {
		if (is_array($post_vars)) {
			foreach($post_vars as $key => $val) {
				$post_contents .= ($post_contents ? '&' : '').urlencode($key).'='.urlencode($val);
			}
		} else {
			$post_contents = $post_vars;
		}
	}

	$uinf = parse_url($url);
	$host = $uinf['host'];
	$path = $uinf['path'];
	$path .= (isset($uinf['query']) && $uinf['query']) ? ('?'.$uinf['query']) : '';
	$headers = array(
		($post_contents ? 'POST' : 'GET')." $path HTTP/1.1",
		"Host: $host",
	);
	if ($post_contents) {
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		$headers[] = 'Content-Length: '.strlen($post_contents);
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 600);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	if ($post_contents) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_contents);
	}

	$data = curl_exec($ch);
	if (curl_errno($ch)) {
		return false;
	}
	curl_close($ch);

	return $data;
}

if (!function_exists('file_put_contents')) {
	if (!defined('FILE_USE_INCLUDE_PATH'))
		define('FILE_USE_INCLUDE_PATH', 1);
	if (!defined('FILE_APPEND'))
		define('FILE_APPEND', 8);
	if (!defined('LOCK_EX'))
		define('LOCK_EX', 2);
	function file_put_contents($filename, $data, $flags = 0, $context = false) {
		if (is_array($data))
			$data = implode('', $data);
		$res = false;
		if ($fh = fopen($filename, ($flags & FILE_APPEND) ? 'a' : 'w',
			($flags & FILE_USE_INCLUDE_PATH) ? true : false)) {
			$res = fwrite($fh, $data);
			fclose($fh);
		}
		return $res;
	}
}

if (!function_exists('json_decode')) {
	require_once dirname(__FILE__).'/class.json.php';
	global $_json_service;
	$_json_service = new Services_JSON();
	function json_decode($json, $assoc = false) {
		global $_json_service;
		$_json_service->use = $assoc ? SERVICES_JSON_LOOSE_TYPE : 0;
		return $_json_service->decode($json);
	}
	function json_encode($data, $assoc = false) {
		global $_json_service;
		$_json_service->use = $assoc ? SERVICES_JSON_LOOSE_TYPE : 0;
		return $_json_service->encodeUnsafe($data);
	}
}

?>