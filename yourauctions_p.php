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

// If user is not logged in redirect to login page
	if (!$user->is_logged_in()){
		$_SESSION['REDIRECT_AFTER_LOGIN'] = 'yourauctions_p.php';
		header('location: user_login.php');
		exit;
	}

// whats the page number
	$page_on = (!isset($_GET['p']) || $_GET['p'] == 0) ? 1:$_GET['p'];


$NOW = time();
$NOWB = gmdate('Ymd');

// DELETE OR CLOSE OPEN AUCTIONS
if (isset($_POST['action']) && $_POST['action'] == 'delopenauctions')
{
	if (is_array($_POST['O_delete']) && count($_POST['O_delete']) > 0)
	{
		$removed = 0;
		foreach ($_POST['O_delete'] as $k => $v)
		{
			$v = intval($v);
			// Pictures Gallery
			if ($dir = @opendir($upload_path . '/' . $v))
			{
				while ($file = readdir($dir))
				{
					if ($file != '.' && $file != '..')
					{
						@unlink($upload_path . '/' . $v . $file);
					}
				}
				closedir($dir);
				@rmdir($upload_path . '/' . $v);
			}

			// Delete Invited Users List and Black Lists associated with this auction
			$query = "DELETE FROM " . $DBPrefix . "auccounter WHERE auction_id = " . $v;
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
			// Auction
			$query = "DELETE FROM " . $DBPrefix . "auctions WHERE id = " . $v;
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			$removed++;
		}

		$query = "UPDATE " . $DBPrefix . "counters SET auctions = (auctions - " . $removed . ")";
		$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
	}

	if (is_array($_POST['startnow']))
	{
		foreach ($_POST['startnow'] as $k => $v)
		{
			$query = "SELECT duration FROM " . $DBPrefix . "auctions WHERE id = " . $v;
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			$data = mysql_fetch_assoc($res);

			$ends = $NOW + ($data['duration'] * 24 * 60 * 60);

			// Update end time to "now"
			$query = "UPDATE " . $DBPrefix . "auctions SET starts = '" . $NOW . "', ends = '" . $ends . "' WHERE id = " . intval($v);
			$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
		}
	}
}
// Retrieve active auctions from the database
	$query = "SELECT count(id) AS COUNT FROM " . $DBPrefix . "auctions WHERE user = " . $user->user_data['id'] . " and starts > " . $NOW . " AND suspended = 0";
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$total = mysql_result($res, 0, 'COUNT');


// Handle columns sorting variables
if (!isset($_SESSION['pa_ord']) && empty($_GET['pa_ord']))
{
	$_SESSION['pa_ord'] = 'title';
	$_SESSION['pa_type'] = 'asc';
}
elseif (!empty($_GET['pa_ord']))
{
	$_SESSION['pa_ord'] = mysql_escape_string($_GET['pa_ord']);
	$_SESSION['pa_type'] = mysql_escape_string($_GET['pa_type']);
}
elseif (isset($_SESSION['pa_ord']) && empty($_GET['pa_ord']))
{
	$_SESSION['pa_nexttype'] = $_SESSION['pa_type'];
}

if (!isset($_SESSION['pa_nexttype']) || $_SESSION['pa_nexttype'] == 'desc')
{
	$_SESSION['pa_nexttype'] = 'asc';
}
else
{
	$_SESSION['pa_nexttype'] = 'desc';
}

if (!isset($_SESSION['pa_type']) || $_SESSION['pa_type'] == 'desc')
{
	$_SESSION['pa_type_img'] = '<img src="images/arrow_up.gif" align="center" hspace="2" border="0" />';
}
else
{
	$_SESSION['pa_type_img'] = '<img src="images/arrow_down.gif" align="center" hspace="2" border="0" />';
}
// get this page of data
	$offset = ($page_on - 1) * $system->SETTINGS['perpage'];
	$offset = ($offset < 0) ? 0 : $offset;
	$query = "SELECT * FROM " . $DBPrefix . "auctions au
				WHERE user = " . $user->user_data['id'] . " AND starts > '" . $NOW . "' AND suspended = 0
				ORDER BY " . $_SESSION['pa_ord'] . " " . $_SESSION['pa_type'] . " LIMIT $offset, " . $system->SETTINGS['perpage'];
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);

$i = 0;
while ($item = mysql_fetch_array($res))
{
	$template->assign_block_vars('items', array(
			'BGCOLOUR' => (!($i % 2)) ? '' : 'class="alt-row"',
			'ID' => $item['id'],
			'TITLE' => $item['title'],
			'STARTS' => FormatDate($item['starts']),
			'ENDS' => FormatDate($item['ends']),

			'B_HASNOBIDS' => ($item['current_bid'] == 0)
			));
	$i++;
}


$template->assign_vars(array(
		'BGCOLOUR' => (!($i % 2)) ? '' : 'class="alt-row"',
		'ORDERCOL' => $_SESSION['pa_ord'],
		'ORDERNEXT' => $_SESSION['pa_nexttype'],
		'ORDERTYPEIMG' => $_SESSION['pa_type_img'],
		'PAGINATION' => pagination($page_on,$system->SETTINGS['perpage'],$total,'yourauctions_p.php?p=%1$s'),

		'B_AREITEMS' => ($i > 0)
		));

include 'header.php';
$TMP_usmenutitle = $MSG['25_0115'];
include $include_path . 'user_cp.php';
$template->set_filenames(array(
		'body' => 'yourauctions_p.tpl'
		));
$template->display('body');
include 'footer.php';
?>
