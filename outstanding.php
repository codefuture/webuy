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
		$_SESSION['REDIRECT_AFTER_LOGIN'] = 'outstanding.php';
		header('location: user_login.php');
		exit;
	}

// whats the page number
	$page_on = (!isset($_GET['p']) || $_GET['p'] == 0) ? 1:$_GET['p'];

// count the pages
	$query = "SELECT COUNT(*) FROM " . $DBPrefix . "winners WHERE paid = 0 AND winner = " . $user->user_data['id'];
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$total = mysql_result($res, 0);

// get this page of data
	$offset = ($page_on - 1) * $system->SETTINGS['perpage'];
	$offset = ($offset < 0) ? 0 : $offset;
	$query = "SELECT w.auction As id, w.id As winid, a.title, a.shipping, a.shipping_cost, w.bid, w.qty, a.shipping_cost_additional
				FROM " . $DBPrefix . "winners w
				LEFT JOIN " . $DBPrefix . "auctions a ON (a.id = w.auction)
				WHERE w.paid = 0 AND w.winner = " . $user->user_data['id'] . "
				LIMIT " . intval($offset) . "," . $system->SETTINGS['perpage'];
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);

	$i=0;
while ($row = mysql_fetch_assoc($res))
{
	$template->assign_block_vars('to_pay', array(
			'ODD_EVEN' => !($i++ % 2) ? 'odd' : 'even',
			'URL' => $system->SETTINGS['siteurl'] . 'item.php?id=' . $row['id'],
			'TITLE' => $row['title'],
			'SHIPPING' => ($row['shipping'] == 1) ? $system->print_money($row['shipping_cost']+($row['shipping_cost_additional'] * ($row['qty'] - 1))) : $system->print_money(0),
			'ADDITIONAL_SHIPPING_COST' => $system->print_money($row['shipping_cost_additional'] * ($row['qty'] - 1)),
			'ADDITIONAL_SHIPPING' => $system->print_money($row['shipping_cost_additional']),
			'ADDITIONAL_SHIPPING_QUANTITYS' => $row['qty'] - 1,
			'QUANTITY' => $row['qty'],
			'BID' => $system->print_money($row['bid'] * $row['qty']),
			'TOTAL' => $system->print_money($row['shipping_cost'] + ($row['bid'] * $row['qty']) + ($row['shipping_cost_additional'] * ($row['qty'] - 1))),
			'ID' => $row['id'],
			'WINID'=> $row['winid'],

			'B_NOTITLE' => (empty($row['title']))
			));
}


$query = "SELECT balance FROM " . $DBPrefix . "users WHERE id = " . $user->user_data['id'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$user_balance = mysql_result($res, 0);

$_SESSION['INVOICE_RETURN'] = 'outstanding.php';
$template->assign_vars(array(
		'USER_BALANCE' => $system->print_money($user_balance),
		'PAY_BALANCE' => $system->print_money_nosymbol(($user_balance < 0) ? 0 - $user_balance : 0),
		'CURRENCY' => $system->SETTINGS['currency'],
		'PAGINATION' => pagination($page_on,$system->SETTINGS['perpage'],$total,'outstanding.php?p=%1$s')
		));

include 'header.php';
$TMP_usmenutitle = $MSG['422'];
include $include_path . 'user_cp.php';
$template->set_filenames(array(
		'body' => 'outstanding.tpl'
		));
$template->display('body');
include 'footer.php';
?>
