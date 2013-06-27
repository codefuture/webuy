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

if (!defined('InWeBid')) exit();

function browseItems($result, $feat_res)
{
	global $system, $uploaded_path, $DBPrefix, $MSG, $ERR_114;
	global $template;

	$feat_items = false;
	if ($feat_res != false)
	{
		$i = 0;
		while ($row = mysql_fetch_assoc($feat_res))
		{
			// get the data we need
			$row = build_items($row);

			// time left till the end of this auction
			$difference = $row['ends'] - time();

			$template->assign_block_vars('featured_items', array(
				'ODD_EVEN' => !($i++ % 2) ? 'odd' : 'even',
				'HIGHLIGHTED' => $row['highlighted'] == 'y' ? 'highlighted' : '',
				'ID' => $row['id'],
				'IMAGE' => $row['pict_url'],
				'TITLE' => $row['title'],
				'SUBTITLE' => $row['subtitle'],
				'BUY_NOW' => ($difference < 0) ? '' : $row['buy_now'],
				'BID' => $row['current_bid'],
				'BIDFORM' => $system->print_money($row['current_bid']),
				'CLOSES' => ArrangeDateNoCorrection($row['ends']),
				'NUMBIDS' => sprintf($MSG['950'], $row['num_bids']),

				'B_BOLD' => ($row['bold'] == 'y')
			));
			$feat_items = true;
		}
	}

	$i = 0;
	while ($row = mysql_fetch_assoc($result))
	{
		// get the data we need
		$row = build_items($row);

		// time left till the end of this auction 
		$difference = $row['ends'] - time();

		$template->assign_block_vars('items', array(
			'ODD_EVEN' => !($i++ % 2) ? 'odd' : 'even',
			'HIGHLIGHTED' => $row['highlighted'] == 'y' ? 'highlighted' : '',
			'ID' => $row['id'],
			'IMAGE' => $row['pict_url'],
			'TITLE' => $row['title'],
			'SUBTITLE' => $row['subtitle'],
			'BUY_NOW' => ($difference < 0) ? '' : $row['buy_now'],
			'BID' => $row['current_bid'],
			'BIDFORM' => $system->print_money($row['current_bid']),
			'CLOSES' => ArrangeDateNoCorrection($row['ends']),
			'NUMBIDS' => sprintf($MSG['950'], $row['num_bids']),

			'B_BOLD' => ($row['bold'] == 'y')
		));
	}

	$template->assign_vars(array(
		'B_FEATURED_ITEMS' => $feat_items,
		'B_SUBTITLE' => ($system->SETTINGS['subtitle'] == 'y'),
	));
}

function build_items($row)
{
	global $system, $uploaded_path;

	// image icon
	if (!empty($row['pict_url']))
	{
		$row['pict_url'] = $system->SETTINGS['siteurl'] . 'getthumb.php?w=' . $system->SETTINGS['thumb_list'] . '&fromfile=' . $uploaded_path . $row['id'] . '/' . $row['pict_url'];
	}
	else
	{
		$row['pict_url'] = get_lang_img('nopicture.gif');
	}

	if ($row['current_bid'] == 0)
	{
		$row['current_bid'] = $row['minimum_bid'];
	}

	if ($row['buy_now'] > 0 && $row['bn_only'] == 'n' && ($row['num_bids'] == 0 || ($row['reserve_price'] > 0 && $row['current_bid'] < $row['reserve_price'])))
	{
		$row['buy_now'] = '<a href="' . $system->SETTINGS['siteurl'] . 'buy_now.php?id=' . $row['id'] . '"><img src="' . get_lang_img('buy_it_now.gif') . '" border=0 class="buynow"></a>' . $system->print_money($row['buy_now']);
	}
	elseif ($row['buy_now'] > 0 && $row['bn_only'] == 'y')
	{
		$row['current_bid'] = $row['buy_now'];
		$row['buy_now'] = '<a href="' . $system->SETTINGS['siteurl'] . 'buy_now.php?id=' . $row['id'] . '"><img src="' . get_lang_img('buy_it_now.gif') . '" border=0 class="buynow"></a>' . $system->print_money($row['buy_now']) . ' <img src="' . get_lang_img('bn_only.png') . '" border="0" class="buynow">';
	}
	else
	{
		$row['buy_now'] = '';
	}

	return $row;
}
?>