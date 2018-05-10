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

rcube_webmail.prototype.aliases_select = function(list) {
	if (this.aliases_timer)
		clearTimeout(rcmail.aliases_timer);

	var id;
	if (id = list.get_single_selection())
		rcmail.aliases_timer = window.setTimeout(function(id) { rcmail.aliases_load(id, 'plugin.aliases.edit'); }, 200, id);
}

rcube_webmail.prototype.aliases_keypress = function(list) {
	if (list.key_pressed == list.DELETE_KEY)
		rcmail.command('plugin.aliases.delete');
	else if (list.key_pressed == list.BACKSPACE_KEY)
		rcmail.command('plugin.aliases.delete');
}

rcube_webmail.prototype.aliases_mouse_up = function(e) {
	if (rcmail.aliases_list) {
		if (!rcube_mouse_is_over(e, rcmail.aliases_list.list))
			rcmail.aliases_list.blur();
	}
};

rcube_webmail.prototype.aliases_load = function(id, action) {
	if (action == 'plugin.aliases.edit' && (!id || id == rcmail.env.iid))
		return false;

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

rcube_webmail.prototype.aliases_ready = function(id) {
	if (id.substring(0, 2) != 'ex')
		rcmail.enable_command('plugin.aliases.delete', true);

	rcmail.aliases_list.highlight_row(id);
	rcmail.env.iid = id;

	return true;
}

rcube_webmail.prototype.aliases_get_index = function(list, value, fallback) {
	fallback = fallback || 0;

	for (var i = 0; i < list.length; i++) {
		if (list[i].value == value)
			return i;
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
			if ((rcmail.env.action == 'plugin.aliases') && !rcmail.env.aliaseserror) {
				if (rcmail.gui_objects.aliases_list) {
					rcmail.aliases_list = new rcube_list_widget(rcmail.gui_objects.aliases_list, {multiselect:false, draggable:true, keyboard:true});

					// override blur function to prevent current alias being deselected
					rcmail.aliases_list.blur = function() {}

					rcmail.aliases_list.addEventListener('select', function(o) { rcmail.aliases_select(o); });
					rcmail.aliases_list.addEventListener('keypress', function(o) { rcmail.aliases_keypress(o); });
					document.onmouseup = function(e) { return rcmail.aliases_mouse_up(e); };
					rcmail.aliases_list.init();
					rcmail.aliases_list.focus();

					if (rcmail.env.iid && rcmail.env.iid < rcmail.aliases_list.rowcount && !rcmail.env.eid)
						rcmail.aliases_list.select_row(rcmail.env.iid, false, false);
				}

				if (rcmail.env.action == 'plugin.aliases') {
					rcmail.register_command('plugin.aliases.add', function(id) {
							rcmail.aliases_list.clear_selection();
							rcmail.env.iid = null;
							rcmail.enable_command('plugin.aliases.delete', false);

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

				rcmail.register_command('plugin.aliases.delete', function(id) {
					if (confirm(rcmail.get_label('aliasesaliasdeleteconfirm','aliases'))) {
						var add_url = '';

						var target = window;
						if (rcmail.env.contentframe && window.frames && window.frames[rcmail.env.contentframe]) {
							add_url = '&_framed=1';
							target = window.frames[rcmail.env.contentframe];
							rcube_find_object(rcmail.env.contentframe).style.visibility = 'inherit';
						}

						target.location.href = rcmail.env.comm_path+'&_action=plugin.aliases.delete&_iid=' + rcmail.env.iid + add_url;
						rcmail.enable_command('plugin.aliases.delete', false);
					}
				}, false);
			}

			if (rcmail.env.action == 'plugin.aliases.add' || rcmail.env.action == 'plugin.aliases.edit') {
				rcmail.register_command('plugin.aliases.save', function() {
					var rows, rowcount;

					if (rcmail.env.framed) {
						rows = parent.rcmail.aliases_list.rows;
						rowcount = parent.rcmail.aliases_list.rowcount;
					}
					else {
						rows = rcmail.aliases_list.rows;
						rowcount = rcmail.aliases_list.rowcount;
					}

					var input_name = rcube_find_object('_name');

					if (input_name && input_name.value == '') {
						alert(rcmail.get_label('aliasesnovalidalias','aliases'));
						input_name.focus();
						return false;
					}

					for (var i = 0; i < rowcount; i++) {
						if (rows[i] && input_name.value == rows[i].obj.cells[0].innerHTML && i != rcmail.env.iid) {
							alert(rcmail.get_label('aliasesaliasexists','aliases'));
							input_name.focus();
							return false;
						}
					}
					rcmail.gui_objects.editform.submit();
				}, true);

			}

		});

	}
});