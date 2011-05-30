/**
 * the AJAX library used to create forms dynamically
 *
 * This file extends prototype, etc., to enhance interactions with the end-user
 *
 * @author Bernard Paques
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

var Forms = {

	/**
	 * initialize the engine
	 */
	onWindowLoad: function() {
		Forms.i18n = {
			check: 'Check',
			drop: 'Drop',
			field: 'Field',
			label: 'Label',
			list: 'List',
			listDefault: '/1/ A first option' + "\n" + '/2/ A second option' + "\n",
			name: 'Name',
			password: 'Password',
			radio: 'Radio',
			text: 'Text',
			textarea: 'Textarea',
			title: 'Title',
			type: 'Type'
		};
	},

	/**
	 * used internally to finalize an item insertion
	 *
	 * @param the id of the new item
	 * @param the content of the new item
	 */
	append: function(id, text) {

		// append the item at the bottom of the list
                $("#form_panel").append(text);

		// some commands will appear on hovering
		Yacs.addOnDemandTools(id);

		// flash the new item
                $("#" + id).effect("highlight",{},3000);

		// drag and drop is allowed to re-order the list
                $("#form_panel").sortable({
                    items: '.sortable',
                    axis:'y',
                    handle: '.drag_handle'
                });
                            
	},

	/**
	 * display some text
	 */
	appendLabel: function(options) {

		// compute a unique id for this item
		if(!Forms.fieldCounter)
			Forms.fieldCounter = 1;
		else
			Forms.fieldCounter += 1;

		// set default options
		if(typeof options == 'undefined')
			options = { };

		if(!options.text)
			options.text = Forms.i18n.label;

		if(!options.type)
			options.type = 'title';
		options.option_title = '';
		if(options.type == 'title') {
			options.option_title = ' selected="selected"';
			options.preview = '<h2>' + options.text + '</h2>';
		}
		options.option_subtitle = '';
		if(options.type == 'subtitle') {
			options.option_subtitle = ' selected="selected"';
			options.preview = '<h3>' + options.text + '</h3>';
		}
		options.option_raw = '';
		if(options.type == 'raw') {
			options.option_raw = ' selected="selected"';
			options.preview = options.text;
		}

		// content of the new item
		var content = '<div id="field_' + Forms.fieldCounter + '" class="sortable mutable">'
			+ '<div class="preview">' + options.preview + '</div>'
			+ '<div class="properties" style="display: none">'
			+ '<table class="form">'
			+ '<tr class="odd"><td>' + Forms.i18n.text + '</td><td><textarea class="text" rows="3" cols="50">' + options.text + '</textarea></td></tr>'
			+ '<tr class="even"><td>' + Forms.i18n.type + '</td><td><select class="type"><option value="title"' + options.option_title + '>' + Forms.i18n.title + '</option>'
			+ '<option value="subtitle"' + options.option_subtitle + '>Sub-title</option>'
			+ '<option value="raw"' + options.option_raw + '>Some text</option>'
			+ '</select></td></tr>'
			+ '<tr class="odd"><td colspan="2">'
			+ '<a href="#" onclick="Forms.saveLabel(\'#field_' + Forms.fieldCounter + '\'); return false"><img src="' + url_to_root + 'skins/_reference/ajax/accept.png" width="16" height=16" /></a>'
			+ '<a href="#" onclick="Forms.restoreLabel(\'#field_' + Forms.fieldCounter + '\'); return false"><img src="' + url_to_root + 'skins/_reference/ajax/cancel.png" width="16" height=16" /></a>'
			+ '</td></tr>'
			+ '</table>'
			+ '</div>'
			+ '<div class="state" style="display: none">'
			+ '<div class="class">label</div>'
			+ '<div class="text">' + options.text + '</div>'
			+ '<div class="type">' + options.type + '</div>'
			+ '</div>'
			+ '</div>';

		// update the list on screen
		Forms.append("field_" + Forms.fieldCounter, content);

	},

	/**
	 * the user has cancelled his update
	 */
	restoreLabel: function(handle) {
		handle = $(handle);

		// restore from the store
                var store = handle.children('.state');
                var properties = handle.children('.properties');

                properties.find('textarea').val(store.children('.text').html());
                properties.find('select').val(store.children('.type').html());

		// close properties
		properties.toggle('slide');
	},

	/**
	 * the user has validated his update
	 */
	saveLabel: function(handle) {                
		handle = $(handle);

		// save in the store
		var store = handle.children('.state');
                var properties = handle.children('.properties');
                
                var itemText = properties.find('textarea').val();
                var itemType = properties.find('select').val();
		store.html('<div class="class">label</div>' + '<div class="text">' + itemText + '</div>' + '<div class="type">' + itemType + '</div>');
                                
		// also update the preview
                var preview = handle.children('.preview');
		if(itemType == 'title') 
			preview.html('<h2>' + itemText + '</h2>');
		if(itemType == 'subtitle')
			preview.html('<h3>' + itemText + '</h3>');
		if(itemType == 'raw')
                        preview.html(itemText);

		// close properties
                properties.toggle('slide');
	},

	/**
	 * extract the first option
	 */
	getFirstOption: function(text) {
		text = text.split("\n")[0];
		text = text.replace(/^\/.+\/\s*/, '');
		return text;
	},

	/**
	 * upload a file
	 */
	appendFileInput: function(options) {

		// compute a unique id for this item
		if(!Forms.fieldCounter)
			Forms.fieldCounter = 1;
		else
			Forms.fieldCounter += 1;

		// set default options
		if(typeof options == 'undefined')
			options = { };

		options.preview = '<input type="file" disabled="disabled"/>';

		if(!options.name)
			options.name = 'field_' + Forms.fieldCounter;

		// content of the new item
		var content = '<div id="field_' + Forms.fieldCounter + '" class="sortable mutable">'
			+ '<div class="preview">' + options.preview + '</div>'
			+ '<div class="properties" style="display: none">'
			+ '<table class="form">'
			+ '<tr class="odd"><td>' + Forms.i18n.name + '</td><td><input type="text" class="name" value="' + options.name + '" /></td></tr>'
			+ '<tr class="even"><td colspan="2">'
			+ '<a href="#" onclick="Forms.saveFileInput(\'#field_' + Forms.fieldCounter + '\'); return false"><img src="' + url_to_root + 'skins/_reference/ajax/accept.png" width="16" height=16" /></a>'
			+ '<a href="#" onclick="Forms.restoreFileInput(\'#field_' + Forms.fieldCounter + '\'); return false"><img src="' + url_to_root + 'skins/_reference/ajax/cancel.png" width="16" height=16" /></a>'
			+ '</td></tr>'
			+ '</table>'
			+ '</div>'
			+ '<div class="state" style="display: none">'
			+ '<div class="class">file</div>'
			+ '<div class="name">' + options.name + '</div>'
			+ '</div>'
			+ '</div>';

		// update the list on screen
		Forms.append("field_" + Forms.fieldCounter, content);

	},

	/**
	 * the user has cancelled his update
	 */
	restoreFileInput: function(handle) {
		handle = $(handle);

		// restore from the store
                var store = handle.children('.state');
                var properties = handle.children('.properties');

                properties.find('.name').val(store.children('.name').html());

		// close properties
                properties.toggle('slide');
	},

	/**
	 * the user has validated his update
	 */
	saveFileInput: function(handle) {
		handle = $(handle);

		// save in the store
		var store = handle.children('.state');
                var properties = handle.children('.properties');

                var itemName = properties.find('.name').val();
		store.html('<div class="class">file</div>' + '<div class="name">' + itemName + '</div>');

		// also update the preview
		var preview = handle.children('.preview');
		preview.html('<input type="file" disabled="disabled" />');

		// close properties
		properties.toggle('slide');
	},

	/**
	 * display some text
	 */
	appendListInput: function(options) {

		// compute a unique id for this item
		if(!Forms.fieldCounter)
			Forms.fieldCounter = 1;
		else
			Forms.fieldCounter += 1;

		// set default options
		if(typeof options == 'undefined')
			options = { };

		if(!options.text)
			options.text = Forms.i18n.listDefault;

		if(!options.type)
			options.type = 'radio';
		options.option_radio = '';
		if(options.type == 'radio') {
			options.option_radio = ' selected="selected"';
			options.preview = '<input type="radio" disabled="disabled"/>' + Forms.getFirstOption(options.text);
		}
		options.option_check = '';
		if(options.type == 'check') {
			options.option_check = ' selected="selected"';
			options.preview = '<input type="checkbox" disabled="disabled"/>' + Forms.getFirstOption(options.text);
		}
		options.option_drop = '';
		if(options.type == 'drop') {
			options.option_drop = ' selected="selected"';
			options.preview = '<select disabled="disabled"><option>' + Forms.getFirstOption(options.text) + '</option></select>';
		}

		if(!options.name)
			options.name = 'field_' + Forms.fieldCounter;

		// content of the new item
		var content = '<div id="field_' + Forms.fieldCounter + '" class="sortable mutable">'
			+ '<div class="preview">' + options.preview + '</div>'
			+ '<div class="properties" style="display: none">'
			+ '<table class="form">'
			+ '<tr class="odd"><td>' + Forms.i18n.text + '</td><td><textarea class="text" rows="3" cols="50">' + options.text + '</textarea></td></tr>'
			+ '<tr class="even"><td>' + Forms.i18n.type + '</td><td><select class="type"><option value="radio"' + options.option_radio + '>' + Forms.i18n.radio + '</option>'
			+ '<option value="check"' + options.option_check + '>' + Forms.i18n.check + '</option>'
			+ '<option value="drop"' + options.option_drop + '>' + Forms.i18n.drop + '</option>'
			+ '</select></td></tr>'
			+ '<tr class="odd"><td>' + Forms.i18n.name + '</td><td><input type="text" class="name" value="' + options.name + '" /></td></tr>'
			+ '<tr class="even"><td colspan="2">'
			+ '<a href="#" onclick="Forms.saveListInput(\'#field_' + Forms.fieldCounter + '\'); return false"><img src="' + url_to_root + 'skins/_reference/ajax/accept.png" width="16" height=16" /></a>'
			+ '<a href="#" onclick="Forms.restoreListInput(\'#field_' + Forms.fieldCounter + '\'); return false"><img src="' + url_to_root + 'skins/_reference/ajax/cancel.png" width="16" height=16" /></a>'
			+ '</td></tr>'
			+ '</table>'
			+ '</div>'
			+ '<div class="state" style="display: none">'
			+ '<div class="class">list</div>'
			+ '<div class="text">' + options.text + '</div>'
			+ '<div class="type">' + options.type + '</div>'
			+ '<div class="name">' + options.name + '</div>'
			+ '</div>'
			+ '</div>';

		// update the list on screen
		Forms.append("field_" + Forms.fieldCounter, content);

	},

	/**
	 * the user has cancelled his update
	 */
	restoreListInput: function(handle) {
		handle = $(handle);

		// restore from the store
		var store = handle.children('.state');
                var properties = handle.children('.properties');

                properties.find('textarea').val(store.children('.text').html());
                properties.find('select').val(store.children('.type').html());
                properties.find('.name').val(store.children('.name').html());

		// close properties
		properties.toggle('slide');
	},

	/**
	 * the user has validated his update
	 */
	saveListInput: function(handle) {
		handle = $(handle);

		// save in the store
		var store = handle.children('.state');
                var properties = handle.children('.properties');

		var itemText = properties.find('textarea').val();
                var itemType = properties.find('select').val();
		var itemName = properties.find('.name').val();
		store.html('<div class="class">list</div>' + '<div class="text">' + itemText + '</div>' + '<div class="type">' + itemType + '</div>' + '<div class="name">' + itemName + '</div>');

		// also update the preview
		var preview = handle.children('.preview');
		if(itemType == 'radio')
			preview.html('<input type="radio" disabled="disabled"/>' + Forms.getFirstOption( itemText ));
		if(itemType == 'check')
			preview.html('<input type="checkbox" disabled="disabled"/>' + Forms.getFirstOption( itemText ));
		if(itemType == 'drop')
			preview.html('<select disabled="disabled"><option>' + Forms.getFirstOption( itemText ) + '</option></select>');

		// close properties
		properties.toggle('slide');
	},

	/**
	 * capture some text
	 */
	appendTextInput: function(options) {

		// compute a new item id
		if(!Forms.fieldCounter)
			Forms.fieldCounter = 1;
		else
			Forms.fieldCounter += 1;

		// set default options
		if(typeof options == 'undefined')
			options = { };

		if(!options.type)
			options.type = 'text';
		options.option_text = '';
		if(options.type == 'text') {
			options.option_text = ' selected="selected"';
			options.preview = Forms.i18n.text;
		}
		options.option_password = '';
		if(options.type == 'password') {
			options.option_password = ' selected="selected"';
			options.preview = Forms.i18n.password;
		}
		options.option_textarea = '';
		if(options.type == 'textarea') {
			options.option_textarea = ' selected="selected"';
			options.preview = Forms.i18n.textarea;
		}

		if(!options.name)
			options.name = 'field_' + Forms.fieldCounter;

		// content of the new item
		var content = '<div id="field_' + Forms.fieldCounter + '" class="sortable mutable">'
			+ '<div class="preview"><input type="text" value="' + options.preview + '" disabled="disabled" /></div>'
			+ '<div class="properties" style="display: none">'
			+ '<table class="form">'
			+ '<tr class="odd"><td>' + Forms.i18n.type + '</td><td><select class="type"><option value="text"' + options.option_text + '>' + Forms.i18n.text + '</option>'
			+ '<option value="password"' + options.option_password + '>' + Forms.i18n.password + '</option>'
			+ '<option value="textarea"' + options.option_textarea + '>' + Forms.i18n.textarea + '</option>'
			+ '</select></td></tr>'
			+ '<tr class="even"><td>' + Forms.i18n.name + '</td><td><input type="text" class="name" value="' + options.name + '" /></td></tr>'
			+ '<tr class="odd"><td colspan="2">'
			+ '<a href="#" onclick="Forms.saveTextInput(\'#field_' + Forms.fieldCounter + '\'); return false"><img src="' + url_to_root + 'skins/_reference/ajax/accept.png" width="16" height=16" /></a>'
			+ '<a href="#" onclick="Forms.restoreTextInput(\'#field_' + Forms.fieldCounter + '\'); return false"><img src="' + url_to_root + 'skins/_reference/ajax/cancel.png" width="16" height=16" /></a>'
			+ '</td></tr>'
			+ '</table>'
			+ '</div>'
			+ '<div class="state" style="display: none">'
			+ '<div class="class">text</div>'
			+ '<div class="type">' + options['type'] + '</div>'
			+ '<div class="name">' + options.name + '</div>'
			+ '</div>'
			+ '</div>';

		// update the list on screen
		Forms.append("field_" + Forms.fieldCounter, content);

	},

	/**
	 * the user has cancelled his update
	 */
	restoreTextInput: function(handle) {
		handle = $(handle);

		// restore from the store
		var store = handle.children('.state');
                var properties = handle.children('.properties');

                properties.find('select').val(store.children('.type').html());
                properties.find('.name').val(store.children('.name').html());

		// close properties
		properties.toggle('slide');
	},

	/**
	 * the user has validated his update
	 */
	saveTextInput: function(handle) {
		handle = $(handle);

		// save in the store
		var store = handle.children('.state');
                var properties = handle.children('.properties');

		var itemType = properties.find('select').val();
		var itemName = properties.find('.name').val();
		$(store).html('<div class="class">text</div>' + '<div class="type">' + itemType + '</div>' + '<div class="name">' + itemName + '</div>');

		// also update the preview
		var preview = handle.children('.preview');
		if(itemType == 'text')
			preview.html('<input type="text" value="' + Forms.i18n.text + '" disabled="disabled" />');
		if(itemType == 'password')
			preview.html('<input type="text" value="' + Forms.i18n.password + '" disabled="disabled" />');
		if(itemType == 'textarea')
			preview.html('<input type="text" value="' + Forms.i18n.textarea + '" disabled="disabled" />');

		// close properties
		properties.toggle('slide');
	},

	/**
	 * load a form based on JSON input
	 *
	 * @param the DOM element that contains field descriptions
	 * @param a list of items to load
	 */
	fromJSON: function(handle, items) {

		$.each(items, function() {
			var item = $(this)[0];

			if(item['class'] == 'file')
				Forms.appendFileInput(item);

			if(item['class'] == 'label')
				Forms.appendLabel(item);

			if(item['class'] == 'list')
				Forms.appendListInput(item);

			if(item['class'] == 'text')
				Forms.appendTextInput(item);

		});
	},

	/**
	 * build a JSON representation of form fields
	 *
	 * @param the DOM element that contains fields escriptions
	 * @return a JSON string
	 */
	toJSON: function(handle) {
		var nodes = $(handle + ' .state');
		if(nodes.length < 1) {return '[]'};
		var buffer = '';
                var index = 0;

                nodes.each(function() {
                    var node = $(this);
                    var nodeClass = node.children('.class').text();

                    if(index)
				buffer += ",\n";

                    if(nodeClass == 'file') {
				buffer += '{ "class": "file"'
					+ ', "name": "'+ node.children('.name').text().toJSON()+'" }';
                    }

                    if(nodeClass == 'label') {
				buffer += '{ "class": "label"'
					+ ', "text": "'+ node.children('.text').text().toJSON()
					+ '", "type": "'+ node.children('.type').text().toJSON()+'" }';
                    }

                    if(nodeClass == 'list') {
                                var list_text = node.children('.text').text();
                                // quote newlines in text
				list_text = $.quoteString(list_text);

                                buffer += '{ "class": "list"'
					+ ', "text": '+ list_text.toJSON()
					+ ', "type": "'+ node.children('.type').text().toJSON()
					+ '", "name": "'+ node.children('.name').text().toJSON()+'" }';
                    }

                    if(nodeClass == 'text') {
				buffer += '{ "class": "text"'
					+ ', "type": "'+ node.children('.type').text().toJSON()
					+ '", "name": "'+ node.children('.name').text().toJSON()+'" }';
                    }

                    index += 1;
                });
                return '[' + buffer + ']';
	}
}

// ready to receive new notifications
$(document).ready(Forms.onWindowLoad);

