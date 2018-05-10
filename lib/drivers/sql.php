<?php

/*

 +-----------------------------------------------------------------------+
 | PostfixAdmin Aliases Plugin for RoundCube                             |
 | Version: 0.7.2                                                        |
 | Author: Gianluca Giacometti <php@gianlucagiacometti.it>               |
 | Copyright (C) 2012 Gianluca Giacometti                                |
 | License: GNU General Public License                                   |
 +-----------------------------------------------------------------------+

*/

function mail_alias($action, array &$data) {

	$rcmail = rcmail::get_instance();

	if ($dsn = $rcmail->config->get('aliases_sql_dsn')) {
		if (is_array($dsn) && empty($dsn['new_link'])) {
			$dsn['new_link'] = true;
			}
		else if (!is_array($dsn) && !preg_match('/\?new_link=true/', $dsn)) {
			$dsn .= '?new_link=true';
			}
		$db = rcube_db::factory($dsn, '', false);
		$db->set_debug((bool)$rcmail->config->get('sql_debug'));
		$db->db_connect('w');
		}
	else {
		$db = $rcmail->get_dbh();
		}

	if ($err = $db->is_error()) {
		return PLUGIN_ERROR_CONNECT;
		}

	$search = array(
			'%address',
			'%goto',
			'%newalias',
			'%domain',
			'%created',
			'%modified',
			'%active'
			);
	$replace = array(
			$db->quote($data['address']),
			$db->quote($data['goto']),
			$db->quote($data['newalias']),
			$db->quote($data['domain']),
			$db->quote($data['created']),
			$db->quote($data['modified']),
			$db->quote($data['active'])
			);
	$query = str_replace($search, $replace, $rcmail->config->get('aliases_sql_'.$action));

	$sql_result = $db->query($query);
	if ($err = $db->is_error()) {
		return PLUGIN_ERROR_PROCESS;
		}

	if (in_array($action, array("aliases", "allaliases"))) {
		$data['address'] = array();
		while ($row = $db->fetch_assoc($sql_result)) {
			$data['address'][] = $row;
			}
		}

	if ($action == "read") {
		$data = array();
		while ($row = $db->fetch_assoc($sql_result)) {
			$data[] = $row;
			}
		}

	return PLUGIN_SUCCESS;

	}

?>
