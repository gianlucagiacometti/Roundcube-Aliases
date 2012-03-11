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

rcube_webmail.prototype.aliases_select = function(list) {
	var id;
	if (this.aliases_timer) {
		clearTimeout(rcmail.aliases_timer);
		}
	if (id = list.get_single_selection()) {
		rcmail.aliases_timer = window.setTimeout(function() { rcmail.aliases_load(id, 'plugin.aliases.edit'); }, 200);
		}
	}

rcube_webmail.prototype.aliases_keypress = function(list) {
	if (list.key_pressed == list.DELETE_KEY) {
		rcmail.command('plugin.aliases.delete');
		}
	else if (list.key_pressed == list.BACKSPACE_KEY) {
		rcmail.command('plugin.aliases.delete');
		}
	}

rcube_webmail.prototype.aliases_mouse_up = function(e) {
	if (rcmail.aliases_list) {
		if (!rcube_mouse_is_over(e, rcmail.aliases_list.list)) {
			rcmail.aliases_list.blur();
			}
		}
	}

rcube_webmail.prototype.aliases_load = function(id, action) {

	if (action == 'plugin.aliases.edit' && (!id || id==rcmail.env.iid)) {
		return false;
		}

	var add_url = '';
	var target = window;
	if (rcmail.env.contentframe && window.frames && window.frames[rcmail.env.contentframe]) {
		add_url = '&_framed=1';
		target = window.frames[rcmail.env.contentframe];
		rcube_find_object(rcmail.env.contentframe).style.visibility = 'inherit';
		}

	if (action && (id || action == 'plugin.aliases.add')) {
		rcmail.set_busy(true);
		target.location.href = rcmail.env.comm_path+'&_action='+action+'&_iid='+id+add_url;
		}

	return true;

	}

rcube_webmail.prototype.aliases_update_list = function(action, param1, param2, param3, param4) {

	var selection;
	var rows = rcmail.aliases_list.rows;
	var rules = Array();

	switch(action) {
		case 'add-first':
			rcmail.aliases_list.clear();
		case 'add':
			if (rows.length == 1 && rows[0].obj.cells[0].innerHTML == rcmail.gettext('loading','')) {
				rcmail.aliases_list.remove_row(0);
				}
			var newrow = document.createElement('tr');
			if (param1 == -1) {
				var cell = document.createElement('td');
				cell.appendChild(document.createTextNode(param2));
				newrow.appendChild(cell);
				}
			else {
				newrow.id = param1;
				var cell = document.createElement('td');
				cell.appendChild(document.createTextNode(param2));
				newrow.appendChild(cell);
				}
			rcmail.aliases_list.insert_row(newrow);
			break;
		case 'update':
			rcmail.http_request('plugin.aliases.update_list', '', false);
			break;
		case 'delete':
			rcmail.aliases_list.clear_selection();
		case 'reload':
			rcmail.aliases_list.clear();
			var newrow = document.createElement('tr');
			var cell = document.createElement('td');
			cell.appendChild(document.createTextNode(rcmail.gettext('loading','')));
			newrow.appendChild(cell);
			rcmail.aliases_list.insert_row(newrow);
			rcmail.http_request('plugin.aliases.update_list', '', false);
			break;
		}

	}

rcube_webmail.prototype.aliases_get_index = function(list, value, fallback) {
	fallback = fallback || 0;
	for (var i = 0; i < list.length; i++) {
		if (list[i].value == value) {
			return i;
			}
		}
	return fallback;
	}

rcube_webmail.prototype.aliases_load_setup = function() {
	var add_url = '';
	var target = window;
	if (rcmail.env.contentframe && window.frames && window.frames[rcmail.env.contentframe]) {
		add_url = '&_framed=1';
		target = window.frames[rcmail.env.contentframe];
		rcube_find_object(rcmail.env.contentframe).style.visibility = 'inherit';
		}
	target.location.href = rcmail.env.comm_path+'&_action=plugin.aliases.setup' + add_url;
	}

$(document).ready(function() {

	if (window.rcmail) {

		rcmail.addEventListener('init', function(evt) {

			if (rcmail.env.action == 'plugin.aliases.add' || rcmail.env.action == 'plugin.aliases.edit' || rcmail.env.action == 'plugin.aliases.setup' || rcmail.env.action == 'plugin.aliases.advanced') {
				var tab = $('<span>').attr('id', 'settingstabpluginaliases').addClass('tablink-selected');
				}
			else {
				var tab = $('<span>').attr('id', 'settingstabpluginaliases').addClass('tablink');
				}

			var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.aliases').attr('title', rcmail.gettext('aliasesmanagealiases', 'aliases')).html(rcmail.gettext('aliases','aliases')).appendTo(tab);

			// add button and register command
			rcmail.add_element(tab, 'tabs');

			if ((rcmail.env.action == 'plugin.aliases' || rcmail.env.action == 'plugin.aliases.advanced') && !rcmail.env.aliaseserror) {

				if (rcmail.gui_objects.aliases_list) {
					rcmail.aliases_list = new rcube_list_widget(rcmail.gui_objects.aliases_list, {multiselect:false, draggable:true, keyboard:true});
					rcmail.aliases_list.addEventListener('select', function(o) { rcmail.aliases_select(o); });
					rcmail.aliases_list.addEventListener('keypress', function(o) { rcmail.aliases_keypress(o); });
					document.onmouseup = function(e) { return rcmail.aliases_mouse_up(e); };
					rcmail.aliases_list.init();
					rcmail.aliases_list.focus();

					if (rcmail.env.iid && rcmail.env.iid < rcmail.aliases_list.rows.length && !rcmail.env.eid) {
						rcmail.aliases_list.select_row(rcmail.env.iid, false, false);
						}
					}

				if (rcmail.env.action == 'plugin.aliases') {
					rcmail.register_command('plugin.aliases.add', function(id) {
							if (rcmail.aliases_examples) { rcmail.aliases_examples.clear_selection(); }
							rcmail.aliases_list.clear_selection();
							var add_url = '';
							var target = window;
							if (rcmail.env.contentframe && window.frames && window.frames[rcmail.env.contentframe]) {
								add_url = '&_framed=1';
								target = window.frames[rcmail.env.contentframe];
								rcube_find_object(rcmail.env.contentframe).style.visibility = 'inherit';
								}
							target.location.href = rcmail.env.comm_path+'&_action=plugin.aliases.add' + add_url;
						}, true);
					}

				}
			else if (rcmail.env.action == 'plugin.aliases.setup') {
				rcmail.register_command('plugin.aliases.import', function(props) {
					var add_url = '';
					var target = window;
					if (rcmail.env.framed)
						target = window.parent;
					target.location.href = './?_task=settings&_action=plugin.aliases.import&' + props;
					}, true);
				}

			if (rcmail.env.action == 'plugin.aliases.add' || rcmail.env.action == 'plugin.aliases.edit') {

				rcmail.register_command('plugin.aliases.save', function() {
					var rows;
					if (rcmail.env.framed) {
						rows = parent.rcmail.aliases_list.rows;
						}
					else {
						rows = rcmail.aliases_list.rows;
						}
					var input_name = rcube_find_object('_name');
					if (input_name && input_name.value == '') {
						alert(rcmail.gettext('aliasesnoaliasname','aliases'));
						input_name.focus();
						return false;
						}
					for (var i = 0; i < rows.length; i++) {
						if (input_name.value == rows[i].obj.cells[0].innerHTML && i != rcmail.env.iid) {
							alert(rcmail.gettext('aliasesaliasexists','aliases'));
							input_name.focus();
							return false;
							}
						}
					rcmail.gui_objects.editform.submit();
					}, true);

				rcmail.register_command('plugin.aliases.delete', function(id) {
					if (confirm(rcmail.gettext('aliasesaliasdeleteconfirm','aliases'))) {
						var add_url = '';
						var target = window;
						if (rcmail.env.contentframe && window.frames && window.frames[rcmail.env.contentframe]) {
							add_url = '&_framed=1';
							target = window.frames[rcmail.env.contentframe];
							rcube_find_object(rcmail.env.contentframe).style.visibility = 'inherit';
							}
						target.location.href = rcmail.env.comm_path+'&_action=plugin.aliases.delete&_iid=' + rcmail.env.iid;
						}
					}, true);

				}

			});

		}

	});
