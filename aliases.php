<?php

/*

 +-----------------------------------------------------------------------+
 | PostfixAdmin Aliases Plugin for RoundCube                             |
 | Version: 0.7.2                                                        |
 | Author: Gianluca Giacometti <php@gianlucagiacometti.it>               |
 | Copyright (C) 2012 Gianluca Giacometti                                |
 | License: GNU General Public License                                   |
 +-----------------------------------------------------------------------+

 code structure based on:

 +-----------------------------------------------------------------------+
 | SieveRules Module for RoundCube                                       |
 | by Philip Weir                                                        |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+

*/

class aliases extends rcube_plugin {

	public $task = 'settings';
	private $alias;
	private $action;
	private $rc;

	function check_driver_error($ret) {
		switch ($ret) {
			case PLUGIN_ERROR_CONNECT:
				$this->rc->output->command('display_message', $this->gettext('aliasesdriverconnecterror'), 'error');
				$this->init_setup();
				return FALSE;
				break;
			case PLUGIN_ERROR_PROCESS:
				$this->rc->output->command('display_message', $this->gettext('aliasesdriverprocesserror'), 'error');
				$this->init_setup();
				return FALSE;
				break;
			case PLUGIN_SUCCESS:
			default:
				return TRUE;
				break;
			}
		}

	function init() {

		$rcmail = rcmail::get_instance();
		$this->rc = &$rcmail;
		$this->load_config();
		$this->require_plugin('jqueryui');

		$this->action = $this->rc->action;

		$this->add_texts('localization/', true);
		$this->rc->output->add_label('aliases');

		$this->include_script('aliases.js');

		$this->register_action('plugin.aliases', array($this, 'init_html'));
		$this->register_action('plugin.aliases.add', array($this, 'init_html'));
		$this->register_action('plugin.aliases.edit', array($this, 'init_html'));
		$this->register_action('plugin.aliases.save', array($this, 'save'));
		$this->register_action('plugin.aliases.delete', array($this, 'delete'));
		$this->register_action('plugin.aliases.update_list', array($this, 'gen_js_list'));
		$this->register_action('plugin.aliases.setup', array($this, 'init_setup'));

		}

	function init_html() {

		$this->_startup();

		$this->api->output->add_handlers(array(
			'aliaseslist' => array($this, 'gen_list'),
			'aliasform' => array($this, 'gen_form'),
			'aliasesframe' => array($this, 'aliases_frame'),
			));

		$this->api->output->include_script('list.js');


		if ($this->action == 'plugin.aliases.add') {
			$this->api->output->set_pagetitle($this->gettext('aliasesnewalias'));
			$this->api->output->send('aliases.editalias');
			}

		elseif ($this->action == 'plugin.aliases.edit') {
			$this->api->output->set_pagetitle($this->gettext('aliaseseditalias'));
			$this->api->output->send('aliases.editalias');
			}

		else {
			$this->api->output->set_pagetitle($this->gettext('aliases'));
			$this->api->output->send('aliases.aliases');
			}

		}

	function init_setup() {
		$this->_startup();
		$this->api->output->set_pagetitle($this->gettext('aliases'));
		$this->api->output->send('aliases.setupaliases');
		}

	function aliases_frame($attrib) {
		if (!$attrib['id']) {
			$attrib['id'] = 'rcmprefsframe';
			}
		$attrib['name'] = $attrib['id'];
		$this->api->output->set_env('contentframe', $attrib['name']);
		$this->api->output->set_env('blankpage', $attrib['src'] ? $this->api->output->abs_url($attrib['src']) : 'program/blank.gif');
		return html::iframe($attrib);
		}

	function gen_list($attrib) {

		$this->api->output->add_gui_object('aliases_list', 'aliases-table');

		$table = new html_table(array('id' => 'aliases-table', 'class' => 'records-table', 'cellspacing' => '0', 'cols' => 1));
		$table->add_header(null, $this->gettext('aliases'));

		if (sizeof($this->alias) == 0) {
			$table->add(null, rep_specialchars_output($this->gettext('aliasesnoalias')));
			$table->add_row();
			}
		else foreach($this->alias as $idx => $alias) {
			$table->set_row_attribs(array('id' => 'rcmrow' . $idx));
			$table->add(null, rcmail::Q($alias['name']));
			}

		return html::tag('div', array('id' => 'aliases-list-filters'), $table->show());

		}


	function gen_js_list() {

		$this->_startup();

		if (empty($this->alias)) {
			$this->api->output->command('aliases_update_list', 'add-first', -1, rep_specialchars_output($this->gettext('aliasesnoalias')));
			}
		else foreach($this->alias as $idx => $alias) {
			$alias_name = $alias['name'];
			$tmp_output = new rcube_template('settings');
			$this->api->output->command('aliases_update_list', $idx == 0 ? 'add-first' : 'add', 'rcmrow' . $idx, rcmail::Q($alias_name));
			}

		$this->api->output->send();

		}


	function gen_form($attrib) {

		$this->include_script('jquery.maskedinput.js');
		$this->api->output->add_label(
			'aliases.aliasesaliasdeleteconfirm',
			'aliases.aliasesaliasexists',
			'aliases.aliasesnoaliasname'
			);

		$iid = rcube_utils::get_input_value('_iid', RCUBE_INPUT_GPC);
		if ($iid == '') {
			$iid = sizeof($this->alias);
			}

		$cur_alias = $this->alias[$iid];
		$this->api->output->set_env('iid', $iid);
		if (isset($this->alias[$iid])) {
			$this->api->output->add_script("parent.". JS_OBJECT_NAME .".aliases_list.highlight_row(".$iid.");");
			}

		list($form_start, $form_end) = get_form_tags($attrib, 'plugin.aliases.save');

		$out = $form_start;

		$hidden_iid = new html_hiddenfield(array('name' => '_iid', 'value' => $iid));
		$out .= $hidden_iid->show();

		$field_id = 'rcmfd_name';
		$input_name = new html_inputfield(array('name' => '_name', 'id' => $field_id));

		$out .= html::label($field_id, rcmail::Q($this->gettext('aliasesaliasname')));
		$out .= "&nbsp;" . $input_name->show($cur_alias['name']);

		$out .= "<br /><br />";

		$out .= $form_end;

		return $out;

		}

	function save() {

		$this->_startup();

		$name = mb_strtolower(trim(rcube_utils::get_input_value('_name', rcube_utils::INPUT_POST, true)), 'UTF-8');
		$iid = trim(rcube_utils::get_input_value('_iid', rcube_utils::INPUT_POST));

		if (!preg_match('/[a-zA-Z0-9_.]/', $name)) {
			$this->api->output->command('display_message', $this->gettext('aliasesaliasnameerror'), 'error');
			$this->api->output->add_script("parent.". JS_OBJECT_NAME .".aliases_update_list('update', '0', '". rcmail::Q($name) ."');");
			$this->init_setup();
			return FALSE;
			}

		$driver = $this->home . '/lib/drivers/' . $this->rc->config->get('aliases_driver', 'sql').'.php';

		if (!is_readable($driver)) {
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: Unable to open driver file $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		require_once($driver);

		if (!function_exists('mail_alias')) {
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: function mail_alias_read not found in driver $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		$data = array();
		$data['goto'] = rcmail::get_instance()->user->get_username();
		$elements = explode("@", trim($data['goto']));
		$data['address'] = $name . "@" . $elements[1];
		$data['domain'] = $elements[1];

// check alias existence in domain
		$ret = mail_alias('allaliases', $data);
		if (!$this->check_driver_error($ret)) { return FALSE; }
		foreach ($data['address'] as $alias) {
			$elements = explode("@", trim($alias['address']));
			if ($elements[0] == $name) {
				$this->api->output->command('display_message', $this->gettext('aliasesaliasexistsindomain'), 'error');
				$this->api->output->add_script("parent.". JS_OBJECT_NAME .".aliases_update_list('update', '0', '". rcmail::Q($name) ."');");
				$this->init_setup();
				return FALSE;
				}
			}

		$data['goto'] = $elements[0] . "@" . $elements[1];
		$elements = explode("@", trim($data['goto']));
		$data['address'] = $name . "@" . $elements[1];
		$ret = mail_alias('read', $data);
		if (!$this->check_driver_error($ret)) { return FALSE; }

// create new alias
		if (!isset($this->alias[$iid]) && ($name != "") && empty($data)) {
			$data = array();
			$data['goto'] = rcmail::get_instance()->user->get_username();
			$elements = explode("@", trim($data['goto']));
			$data['address'] = $name . "@" . $elements[1];
			$data['domain'] = $elements[1];
			$data['created'] = date('Y-m-d H:i:s');
			$data['modified'] = date('Y-m-d H:i:s');
			$data['active'] = 'TRUE';
			$ret = mail_alias('create', $data);
			if (!$this->check_driver_error($ret)) { return FALSE; }
			$this->api->output->command('display_message', $this->gettext('aliasesaliascreated'), 'confirmation');
			$this->api->output->add_script("parent.". JS_OBJECT_NAME .".aliases_update_list('update', '0', '". rcmail::Q($name) ."');");
			$this->init_setup();
			}
// update existing alias
		else {
			$data = array();
			$data['goto'] = rcmail::get_instance()->user->get_username();
			$elements = explode("@", trim($data['goto']));
			$data['address'] = $this->alias[$iid]['name'] . "@" . $elements[1];
			$data['newalias'] = $name . "@" . $elements[1];
			$data['modified'] = date('Y-m-d H:i:s');
			$ret = mail_alias('update', $data);
			if (!$this->check_driver_error($ret)) { return FALSE; }
			$this->api->output->command('display_message', $this->gettext('aliasesaliasupdated'), 'confirmation');
			$this->api->output->add_script("parent.". JS_OBJECT_NAME .".aliases_update_list('update', '0', '". rcmail::Q($name) ."');");
			$this->init_setup();
			}

		$this->alias = array();
		$this->_startup();

		rcmail::get_instance()->overwrite_action('plugin.aliases.edit');
		$this->action = 'plugin.aliases.edit';
		$this->init_html();

		}

	function delete() {

		$this->_startup();

		$driver = $this->home . '/lib/drivers/' . $this->rc->config->get('aliases_driver', 'sql').'.php';

		if (!is_readable($driver)) {
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: Unable to open driver file $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		require_once($driver);

		if (!function_exists('mail_alias')) {
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: function mail_alias_read not found in driver $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		$data = array();
		$data['goto'] = rcmail::get_instance()->user->get_username();
		$elements = explode("@", trim($data['goto']));
		$ids = rcube_utils::get_input_value('_iid', RCUBE_INPUT_GET);
		$data['address'] = $this->alias[$ids]['name'] . "@" . $elements[1];

		$ret = mail_alias('delete', $data);
		if (!$this->check_driver_error($ret)) { return FALSE; }

		$this->alias = array();
		$this->_startup();

		$this->api->output->command('display_message', $this->gettext('aliasesaliasdeleted'), 'confirmation');
		$this->api->output->add_script("parent.". JS_OBJECT_NAME .".aliases_update_list('delete', ". $ids .");");

		if (isset($_GET['_framed']) || isset($_POST['_framed'])) {
			$this->api->output->add_script("parent.". JS_OBJECT_NAME .".show_contentframe(false);");
			}
		else {
			rcmail::get_instance()->overwrite_action('plugin.aliases.edit');
			$this->action = 'plugin.aliases.edit';
			$this->init_html();
			}

		}

	private function _startup() {

		$driver = $this->home . '/lib/drivers/' . $this->rc->config->get('aliases_driver', 'sql').'.php';

		if (!is_readable($driver)) {
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: Unable to open driver file $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		require_once($driver);

		if (!function_exists('mail_alias')) {
			raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: function mail_alias_read not found in driver $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		$data = array();
		$data['goto'] = rcmail::get_instance()->user->get_username();

		$ret = mail_alias('aliases', $data);
		if (!$this->check_driver_error($ret)) { return FALSE; }

		foreach ($data['address'] as $alias) {
			$elements = explode("@", trim($alias['address']));
			if ($elements[0] != "") {
				$this->alias[] = array("name" => $elements[0]);
				}
			}
		sort($this->alias);

		return TRUE;

		}

	}

?>
