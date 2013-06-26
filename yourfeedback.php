<?php
/***************************************************************************
 *   copyright				: (C) 2008 - 2013 WeBid
 *   site					: http://www.webidsupport.com/
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

include 'common.php';

// if user is not logged in redirect to login page
	if (!$user->is_logged_in()){
		$_SESSION['REDIRECT_AFTER_LOGIN'] = 'yourfeedback.php';
		header('location: user_login.php');
		exit;
	}

// whats the page number
	$page_on = (!isset($_GET['p']) || $_GET['p'] == 0) ? 1:$_GET['p'];

// count the pages
	$query = "SELECT COUNT(rated_user_id) FROM " . $DBPrefix . "feedbacks WHERE rated_user_id = " . $user->user_data['id'];
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$total = mysql_result($res, 0);

// get this page of data
	$offset = ($page_on - 1) * $system->SETTINGS['perpage'];
	$offset = ($offset < 0) ? 0 : $offset;
	$query = "SELECT f.*, a.title FROM " . $DBPrefix . "feedbacks f
				LEFT OUTER JOIN " . $DBPrefix . "auctions a
				ON a.id = f.auction_id
				WHERE rated_user_id = " . $user->user_data['id'] . "
				ORDER by feedbackdate DESC
				LIMIT " . intval($offset) . "," . $system->SETTINGS['perpage'];
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);

$i = 0;
$feed_disp = array();
while ($arrfeed = mysql_fetch_assoc($res))
{
	$query = "SELECT id, rate_num, rate_sum FROM " . $DBPrefix . "users WHERE nick = '" . $arrfeed['rater_user_nick'] . "'";
	$result = mysql_query($query);
	$system->check_mysql($result, $query, __LINE__, __FILE__);
	$usarr = mysql_fetch_array($result);
	$j = 0;
	foreach ($memtypesarr as $k => $l)
	{
		if ($k >= $usarr['rate_sum'] || $j++ == (count($memtypesarr) - 1))
		{
			$usicon = '<img src="' . $system->SETTINGS['siteurl'] . 'images/icons/' . $l['icon'] . '" alt="' . $l['icon'] . '" class="fbstar">';
			break;
		}
	}
	switch ($arrfeed['rate'])
	{
		case 1: $uimg = $system->SETTINGS['siteurl'] . 'images/positive.png';
			break;
		case - 1: $uimg = $system->SETTINGS['siteurl'] . 'images/negative.png';
			break;
		case 0: $uimg = $system->SETTINGS['siteurl'] . 'images/neutral.png';
			break;
	}
	$template->assign_block_vars('fbs', array(
			'BGCOLOUR' => (!(($i + 1) % 2)) ? '' : 'class="alt-row"',
			'IMG' => $uimg,
			'USFLINK' => 'profile.php?user_id=' . $usarr['id'] . '&auction_id=' . $arrfeed['auction_id'],
			'USERNAME' => $arrfeed['rater_user_nick'],
			'USFEED' => $usarr['rate_sum'],
			'USICON' => (isset($usicon)) ? $usicon : '',
			'FBDATE' => FormatDate($arrfeed['feedbackdate']),
			'AUCTIONURL' => ($arrfeed['title']) ? '<a href="item.php?id=' . $arrfeed['auction_id'] . '">' . $arrfeed['title'] . '</a>' : $MSG['113'] . $arrfeed['auction_id'],
			'FEEDBACK' => nl2br(stripslashes($arrfeed['feedback']))
			));

	$i++;
}

include $include_path . 'membertypes.inc.php';
foreach ($membertypes as $idm => $memtypearr){
	$memtypesarr[$memtypearr['feedbacks']] = $memtypearr;
}
ksort($memtypesarr, SORT_NUMERIC);

$i = 0;
foreach ($memtypesarr as $k => $l){
	if ($k >= $user->user_data['rate_sum'] || $i++ == (count($memtypesarr) - 1)){
		$TPL_rate_ratio_value = '<img src="' . $system->SETTINGS['siteurl'] . 'images/icons/' . $l['icon'] . '" alt="' . $l['icon'] . '" class="fbstar">';
		break;
	}
}


$template->assign_vars(array(
		'USERNICK' => $user->user_data['nick'],
		'USERFB' => $user->user_data['rate_sum'],
		'USERFBIMG' => (isset($TPL_rate_ratio_value)) ? $TPL_rate_ratio_value : '',
		'PAGINATION' => pagination($page_on,$system->SETTINGS['perpage'],$total,'yourfeedback.php?p=%1$s'),
		'BGCOLOUR' => (!(($i + 1) % 2)) ? '' : 'class="alt-row"'
		));

include 'header.php';
$TMP_usmenutitle = $MSG['25_0223'];
include $include_path . 'user_cp.php';
$template->set_filenames(array(
		'body' => 'yourfeedback.tpl'
		));
$template->display('body');
include 'footer.php';
?>
