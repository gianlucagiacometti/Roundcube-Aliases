<?php

/*

 +-----------------------------------------------------------------------+
 | PostfixAdmin Aliases Plugin for RoundCube                             |
 | Version: 1.3.6                                                        |
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

class aliases extends rcube_plugin
{
	public $task = 'settings';

	protected $alias = array();
	protected $action;
	protected $rc;

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

	function init()
	{
		$rcmail = rcube::get_instance();
		$this->load_config();
		$this->add_texts('localization/');

		// load required plugin
		$this->require_plugin('jqueryui');

		$this->action = $rcmail->action;

		$this->include_stylesheet($this->local_skin_path() . '/tabstyles.css');
		$this->add_hook('settings_actions', array($this, 'settings_tab'));

		// register internal plugin actions
		$this->register_action('plugin.aliases', array($this, 'init_html'));
		$this->register_action('plugin.aliases.add', array($this, 'init_html'));
		$this->register_action('plugin.aliases.edit', array($this, 'init_html'));
		$this->register_action('plugin.aliases.setup', array($this, 'init_setup'));
		$this->register_action('plugin.aliases.move', array($this, 'move'));
		$this->register_action('plugin.aliases.save', array($this, 'save'));
		$this->register_action('plugin.aliases.delete', array($this, 'delete'));
		$this->register_action('plugin.aliases.check', array($this, 'check_alias_domain'));

	}

	function settings_tab($p)
	{
		$p['actions'][] = array('action' => 'plugin.aliases', 'class' => 'aliases', 'label' => 'aliases.aliases', 'title' => 'aliases.aliases', 'role' => 'button', 'aria-disabled' => 'false', 'tabindex' => '0');

		return $p;
	}

	function init_html()
	{
		// create aliases UI
		$rcmail = rcube::get_instance();
		$this->_startup();
		$this->include_script('aliases.js');

		// add handlers for the various UI elements
		$this->api->output->add_handlers(array(
			'aliaseslisttitle' => array($this, 'gen_list_title'),
			'aliaseslist' => array($this, 'gen_list'),
			'aliasessetup' => array($this, 'gen_setup'),
			'aliasform' => array($this, 'gen_form'),
			'aliasesframe' => array($this, 'aliases_frame'),
		));

		$this->api->output->include_script('list.js');

		if ($this->action == 'plugin.aliases.add') {
			// show add alias
			$this->api->output->set_pagetitle($this->gettext('aliasesnewalias'));
			$this->api->output->send('aliases.editalias');
		}
		if ($this->action == 'plugin.aliases.edit') {
			// show edit alias
			$this->api->output->set_pagetitle($this->gettext('aliaseseditalias'));
			$this->api->output->send('aliases.editalias');
		}
		else {
			// show main UI
			$this->api->output->set_pagetitle($this->gettext('aliases'));
			$this->api->output->send('aliases.aliases');
		}
	}

	function init_setup()
	{
		// redirect setup UI, see gen_setup()
		$this->_startup();
		$this->include_script('aliases.js');

		$this->api->output->add_handlers(array('aliasessetup' => array($this, 'gen_setup')));
		$this->api->output->set_pagetitle($this->gettext('aliases'));
		$this->api->output->send('aliases.setupaliases');
	}

	function aliases_frame($attrib)
	{
		if (!$attrib['id'])
			$attrib['id'] = 'rcmprefsframe';

		return $this->api->output->frame($attrib, true);
	}

	function gen_list_title($attrib)
	{
		$title = $this->gettext('aliases');
		return $title;
	}


	function gen_list($attrib)
	{
		// create alias list for UI
		$this->api->output->add_label('loading', 'aliases.aliasesaliasdeleteconfirm');
		$this->api->output->add_gui_object('aliases_list', 'aliases-table');

		$table = new html_table($attrib + array('cols' => 2));

		if (!$attrib['noheader']) {
			$table->add_header(null, $this->gen_list_title($attrib));
		}

		if (sizeof($this->alias) == 0) {
			// no alias exist
			$table->add(array('colspan' => '2'), rcube_utils::rep_specialchars_output($this->gettext('aliasesnoaliases')));
		}
		else foreach($this->alias as $idx => $aliasx) {
			$args = rcube::get_instance()->plugins->exec_hook('aliases_list_aliases', array('idx' => $idx, 'name' => $aliasx['name'], 'enable' => $aliasx['enable']));
			$table->set_row_attribs(array('id' => 'rcmrow' . $idx));
			$table->add(null, rcmail::Q($aliasx['name']));
			$content = $aliasx['active'] == false ? "(".$this->gettext('aliasesaliasdisabled').")" : "&nbsp;";
			$table->add(null, $content);
		}

		return html::tag('div', array('id' => 'aliases-list-aliases'), $table->show($attrib));
	}

	function gen_setup()
	{
		$rcmail = rcube::get_instance();

		if (isset($_GET['_framed']) || isset($_POST['_framed'])) {
			$this->api->output->add_script("parent.". rcmail_output::JS_OBJECT_NAME .".goto_url('plugin.aliases');");
		}
		else {
			// go to aliases page
			$rcmail->overwrite_action('plugin.aliases');
			$this->api->output->send('aliases.aliases');
		}
	}

	function gen_form($attrib)
	{
		$rcmail = rcube::get_instance();
		$this->include_script('jquery.maskedinput.min.js');
		$this->api->output->add_label(
			'aliases.aliasesnovalidalias', 'aliases.aliasesaliasexists', 'aliases.actiondeleteconfirm');


		$iid = rcube_utils::get_input_value('_iid', rcube_utils::INPUT_GPC);
		if ($iid == '')
			$iid = sizeof($this->alias);

		$cur_script = $this->alias[$iid];
		$this->api->output->set_env('iid', $iid);

		if (isset($this->alias[$iid]))
			$this->api->output->add_script("parent.". rcmail_output::JS_OBJECT_NAME .".aliases_ready('".$iid."');");

		list($iid, $cur_script) = array_values($rcmail->plugins->exec_hook('aliases_init', array('id' => $iid, 'script' => $cur_script)));

		list($form_start, $form_end) = get_form_tags($attrib, 'plugin.aliases.save');

		$out = $form_start;

		$hidden_iid = new html_hiddenfield(array('name' => '_iid', 'value' => $iid));
		$out .= $hidden_iid->show();

		// alias name input
		$field_id = 'rcmfd_name';
		$input_name = new html_inputfield(array('name' => '_name', 'id' => $field_id, 'required' => 'required'));

		$out .= html::label($field_id, rcmail::Q($this->gettext('aliasesaliasname')));
		$out .= "&nbsp;" . $input_name->show($cur_script['name']);

		// alias active input
		$field_id = 'rcmfd_active';
		$input_active = new html_select(array('name' => '_active', 'id' => $field_id));
		$input_active->add(array($this->gettext('aliasestrue'),$this->gettext('aliasesfalse')), array('TRUE','FALSE'));

		$content = $cur_script['active'] == false ? "FALSE" : "TRUE";
		$out .= html::span('enableLink', html::label($field_id, rcmail::Q($this->gettext('aliasesaliasenabled')))
				. "&nbsp;" . $input_active->show($content));

		$out .= "<br /><br />";

		$out .= $form_end;

		return $out;
	}

	function check_alias_domain()
	{
		$rcmail = rcmail::get_instance();
		$driver = $this->home . '/lib/drivers/' . $rcmail->config->get('aliases_driver', 'sql').'.php';
		$new_alias = rcube_utils::get_input_value('_newalias', rcube_utils::INPUT_POST, true);

		$this->api->output->add_label('aliases.aliasesaliasexistsindomain');

		if (!is_readable($driver)) {
			rcube::raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: Unable to open driver file $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		require_once($driver);

		if (!function_exists('mail_alias')) {
			rcube::raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: function mail_alias_read not found in driver $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		$data = array();
		$data['goto'] = rcmail::get_instance()->user->get_username();
		$elements = explode("@", trim($data['goto']));
		$data['address'] = $name . "@" . $elements[1];
		$data['domain'] = $elements[1];

		$ret = mail_alias('allaliases', $data);
		if (!$this->check_driver_error($ret)) { return FALSE; }

		$error = "";
		foreach ($data['address'] as $alias) {
			$element = explode("@", trim($alias['address']));
			if ($element[0] == $new_alias) {
				$error = $this->gettext('aliasesaliasexistsindomain');
				}
			}

		$rcmail->output->command('plugin.aliases.checkaliasdomain', $error);
	}

	function save()
	{
		$rcmail = rcube::get_instance();
		$this->_startup();

		$name = mb_strtolower(trim(rcube_utils::get_input_value('_name', rcube_utils::INPUT_POST, true)), 'UTF-8');
		$active = trim(rcube_utils::get_input_value('_active', rcube_utils::INPUT_POST));
		$iid = trim(rcube_utils::get_input_value('_iid', rcube_utils::INPUT_POST));

		if (!preg_match('/[a-zA-Z0-9_.]/', $name)) {
			$this->api->output->command('display_message', $this->gettext('aliasesaliasnameerror'), 'error');
			$this->init_setup();
			return FALSE;
			}

		$driver = $this->home . '/lib/drivers/' . $rcmail->config->get('aliases_driver', 'sql').'.php';

		if (!is_readable($driver)) {
			rcube::raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: Unable to open driver file $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		require_once($driver);

		if (!function_exists('mail_alias')) {
			rcube::raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: function mail_alias_read not found in driver $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		$data = array();
		$data['goto'] = rcmail::get_instance()->user->get_username();
		$elements = explode("@", trim($data['goto']));
		$data['address'] = $name . "@" . $elements[1];
		$data['domain'] = $elements[1];
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
			$data['active'] = $active;
			$ret = mail_alias('create', $data);
			if (!$this->check_driver_error($ret)) { return FALSE; }
			$this->api->output->command('display_message', $this->gettext('aliasesaliascreated'), 'confirmation');
			$this->gen_setup();
		}
// update existing alias
		else {
			$data = array();
			$data['goto'] = rcmail::get_instance()->user->get_username();
			$elements = explode("@", trim($data['goto']));
			$data['address'] = $this->alias[$iid]['name'] . "@" . $elements[1];
			$data['newalias'] = $name . "@" . $elements[1];
			$data['modified'] = date('Y-m-d H:i:s');
			$data['active'] = $active;
			$ret = mail_alias('update', $data);
			if (!$this->check_driver_error($ret)) { return FALSE; }
			$this->api->output->command('display_message', $this->gettext('aliasesaliasupdated'), 'confirmation');
			$this->gen_setup();
		}

	}

	function delete()
	{
		$rcmail = rcube::get_instance();
		$this->_startup();

		$driver = $this->home . '/lib/drivers/' . $rcmail->config->get('aliases_driver', 'sql').'.php';

		if (!is_readable($driver)) {
			rcube::raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: Unable to open driver file $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		require_once($driver);

		if (!function_exists('mail_alias')) {
			rcube::raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: function mail_alias_read not found in driver $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		$data = array();
		$data['goto'] = rcmail::get_instance()->user->get_username();
		$elements = explode("@", trim($data['goto']));
		$ids = rcube_utils::get_input_value('_iid', rcube_utils::INPUT_GET);
		$data['address'] = $this->alias[$ids]['name'] . "@" . $elements[1];

		$ret = mail_alias('delete', $data);
		if (!$this->check_driver_error($ret)) { return FALSE; }

		$this->api->output->command('display_message', $this->gettext('aliasesaliasdeleted'), 'confirmation');

		$this->gen_setup();

	}

	protected function _startup()
	{
		$rcmail = rcube::get_instance();
		$driver = $this->home . '/lib/drivers/' . $rcmail->config->get('aliases_driver', 'sql').'.php';

		if (!is_readable($driver)) {
			rcube::raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: Unable to open driver file $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		require_once($driver);

		if (!function_exists('mail_alias')) {
			rcube::raise_error(array('code' => 600, 'type' => 'php', 'file' => __FILE__, 'message' => "aliases plugin: function mail_alias_read not found in driver $driver"), true, false);
			return $this->gettext('aliasesinternalerror');
			}

		$data = array();
		$data['goto'] = rcmail::get_instance()->user->get_username();
		$elements = explode("@", trim($data['goto']));
		$data['domain'] = $elements[1];

		$ret = mail_alias('aliases', $data);
		if (!$this->check_driver_error($ret)) { return FALSE; }

		foreach ($data['address'] as $alias) {
			$active = $alias['active'];
			$elements = explode("@", trim($alias['address']));
			if ($elements[0] != "") {
				$this->alias[] = array("name" => $elements[0], "domain" => $elements[1], "active" => $active);
				}
			}
		sort($this->alias);

		return TRUE;
	}

}

?>
