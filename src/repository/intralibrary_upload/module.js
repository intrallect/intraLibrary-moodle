(function(M) {

	function _get_string(id) {
		return M.util.get_string(id,'repository_intralibrary_upload');
	}

	function is_compatible(repo_id) {
		return typeof upload._compatible_repos[repo_id] != 'undefined';
	}

	var upload = {
		NAME: 'repo_upload_file', // that's a standard moodle name,
		_course_shortname: null,
		_course_fullname: null,
		_compatible_repos: {},
		_categories: {},
		_sub_categories: {},
		_accepted_mimetype: '*',
		_settings: {},
		_current_form: null,
		_kaltura_extensions: [],

		// standard input fields
		get_input_fields: function(options) {
			// use the course fullname & shortname as autokeywords
			var auto_keywords = [];
			if (this._course_shortname) {
				auto_keywords.push(this._course_shortname);
			}
			if (this._course_fullname) {
				auto_keywords.push(this._course_fullname);
			}

			var is_url = options.env == 'url';
			var input_fields = [ {
				label: is_url ? 'Enter URL' : 'Choose file',
				help: is_url ? 'Enter the (fully qualified) URL you want to deposit' : 'Click on Browse... and choose the file you want to upload.',
				type: is_url ? 'text' : 'file',
				required: true,
				name: this.NAME,
				title: 'File',
				value: is_url ? 'http://' : '',
				row_class: 'fp-file' // required my moodle's form validation
			}, {
				label: _get_string('upload_title'),
				help: _get_string('upload_title_help'),
				type: 'text',
				required: true,
				name: 'title'
			}, {
				label: _get_string('upload_description'),
				help: _get_string('upload_description_help'),
				type: 'textarea',
				required: true,	name: 'description'
			}, {
				label: _get_string('upload_category'),
				type: 'select',
				help: _get_string('upload_category_help'),
				required: true,
				name: 'category',
				options: this._categories,
				add_empty: true
			}, {
				label: _get_string('upload_subcategory'),
				type: 'select',
				help: _get_string('upload_subcategory_help'),
				name: 'subcategory',
				options: [],
				multiple: true
			}, {
				label: _get_string('upload_keywords'),
				type: 'text',
				help: _get_string('upload_keywords_help'),
				name: 'keywords',
				value: auto_keywords.join(', ')
			}];

			for (var i = 1; i < 3; i++){
				var optionalfield = this.get_optional_deposit(i);
				if (optionalfield != null){
					input_fields.push(optionalfield);
					if(optionalfield.additional_content){
						input_fields.push(optionalfield.additional_content);
					}
				}
			}

			return input_fields;
		},

		/**
		 * Generates the optional fields
		 * @param int;
		 * @return object;
		 */
		get_optional_deposit: function (num) {
			var needed = this._settings['optional_deposit_' + num];
			if(needed) {
				var because = {
						name: 'optional_' + num + '_reason',
						label: " " + this._settings['optional_' + num + '_extra_info_description'],
						type: 'text',
						hidden: true
				};

				var field_properties = {
						label: this._settings['optional_' + num + '_title'],
						type: 'checkbox',
						help: this._settings['optional_' + num + '_label'],
						name: "optional_" + num,
						row_class: "optional_" + num
						};

				// check if extra information field required
				if (this._settings['optional_' + num + '_extra_info']) {
					field_properties.additional_content = because;
				}
				return field_properties;
			}
			return null;
		},

		prepare_filepicker: function(filepicker, Y) {

			filepicker.validate_upload_form = function(form) {

				if (typeof form == 'string') {
					form = Y.one('#' + form);
				}

				var errors = [];
				form.all('[required]').each(function(a, b, c) {
					if (!this.get('value')) {
						var label = form.one('[for=' + this.get('id') + ']'),
								error = this.get('title');
						if (!error) {
							error = label ? label.get('text').replace(' *', '') : this.get('name');
						}
						errors.push(error);
					}
				});

				// validate file extension
				var filepath = form.one('[name=repo_upload_file]').get('value').toLowerCase();
				var accepeted_types = filepicker.options["accepted_types"];
				if (typeof(accepeted_types) != "undefined" && _.indexOf(accepeted_types, ("." + (/[^.]+$/.exec(filepath))[0])) == -1 && accepeted_types.length != 0) {
					this.print_msg(_get_string('upload_invalid_ext'));
					return false;
				}

				if (errors.length) {
					var message = _get_string('upload_missing');
					if (errors.length != 1) message += 's';
					this.print_msg(message + ': ' + errors.join(', '), 'error');
					return false;
				} else {
					return true;
				}
			};

			var request = filepicker.request;
			filepicker.request = function(args, redraw) {

				// only process compatible repos
				if (args.repository_id
						&& is_compatible(args.repository_id)
						&& this.fpnode) {

					var form_id = args.form && args.form.upload && args.form.id,
						loading_text;

					// otherwise, when this request is an upload add some loading text
					if (args.action == 'upload' && form_id) {

						if (!this.validate_upload_form(form_id)) {
							return;
						}

						var ext = Y.one('#' + form_id).one('input[name=repo_upload_file]').get('value').split('.').pop();
						if (_.indexOf(upload._kaltura_extensions, ext) !== -1) {
							loading_text = _get_string('upload_kaltura');
						} else {
							loading_text = _get_string('upload_kaltura_patient');
						}
					}

					request.apply(this, arguments);

					if (loading_text) {
						this.fpnode.one('.fp-content-loading .fp-content-center').append('<p>' + loading_text + '</p>');
					}
				} else {
					request.apply(this, arguments);
				}
			};

			// reset the .fp-content's node height before displaying any results
			var display_response = filepicker.display_response;
			filepicker.display_response = function() {
				filepicker.fpnode.one('.fp-content').setStyle('height', null);
				display_response.apply(this, arguments);
			};

			var create_upload_form = filepicker.create_upload_form;
			filepicker.create_upload_form = function(data) {

				// create the normal form..
				create_upload_form.apply(this, arguments);

				// only process compatible repos
				if (is_compatible(data.repo_id)) {
					var formId = data.upload.id + '_' + this.options.client_id;
					var current_form = Y.one('#' + formId);
					upload._accepted_mimetype = data.ext;

					// add uploadType for form creating options
					this.options.uploadType = data.uploadType;

					// create upload form
					upload.create_upload_form(current_form, this.options);
					var upload_div = this.fpnode.one('.fp-content .fp-upload-form');
					upload_div.addClass('intralibrary-upload');

					// modify upload button
					var uploadButton = this.fpnode.one('.fp-upload-btn');
					uploadButton.set('text', 'Upload');
					uploadButton.set('type', 'submit');
					uploadButton.setStyle('margin',0);

					var buttonContainer = current_form.siblings('div.mdl-align').item(0);
					buttonContainer.addClass('upload-button-container');
					current_form.all('.help').setStyle("margin-bottom","3px");
				}
			};
		},

		create_upload_form: function(currentForm, options) {

			this.current_filepicker_id = options.client_id;
			// grab a reference to the form itself
			this._current_form = currentForm;

			// remove file and title, and hide author and license inputs
			var file_selector = 'input[name=repo_upload_file]';
			this.hide_input_row(file_selector, true);
			this.hide_input_row('input[name=title]', true);
			this.hide_input_row('input[name=author]');
			this.hide_input_row('select[name=license]');

			// add custom fields
			for (var i = 0, fields = this.get_input_fields(options); i < fields.length; i++) {
				this.add_input_row(fields[i]);
			}

			function bind_optional_checkbox(i, current_form, fpId, otherbox) {
				var optional = current_form.one('[name=optional_'+ i +']');
				var other = null;
				var reason = null;
				var reason_label = null;
				var other_reason = null;
				var other_reason_label = null;
				var node_name = null;
				var other_node_name = null;
				var label_id = null;
				var other_label_id =null;
				
				if (optional) {
					optional.on('click', function() {
						
						//sets the checkbox and the required flag
						if (optional.get('checked')) {
							if (parseInt(upload._settings['optional_' + i + '_extra_info'])){
								node_name = 'optional_'+ i + '_reason';
								label_id = node_name + '_label_' + fpId;
								this.get('form').one('[name=optional_'+ i + '_reason]').set('required', this.get('checked'));
								reason = current_form.one('[name=' + node_name + ']'),
								reason_label = current_form.one('#' + label_id);
								reason.show();
								reason_label.show();
								}
							//disable other
							if (upload._settings['optional_deposit_' + otherbox]) {
								other = current_form.one('[name=optional_'+ otherbox +']');
								other.set('checked', false);
								other_node_name = 'optional_'+ otherbox + '_reason';
								other_label_id = other_node_name + '_label_' + fpId;
								if (parseInt(upload._settings['optional_' +  otherbox + '_extra_info'])){
									this.get('form').one('[name=optional_'+  otherbox + '_reason]').set('required', other.get('checked'), false);
									other_reason = current_form.one('[name=' + other_node_name + ']'),
									other_reason_label = current_form.one('#' + other_label_id);
									other_reason.hide();
									other_reason_label.hide();
									}
							}
						} else if (upload._settings['optional_' + i + '_extra_info']){
								reason.hide();
								reason_label.hide();
						}
					});
				}
			}

			// create a hidden input fild for the upload format (singleFile / contentPackage)
			this._current_form.appendChild(this.create_input({name: 'upload_type', type: 'hidden',
				value: (options.env == 'url' ? 'url' : options.uploadType) }));

			// make "requires approval" reason required if the checkbox is ticked
			bind_optional_checkbox(1, this._current_form, this.current_filepicker_id, 2);
			bind_optional_checkbox(2, this._current_form, this.current_filepicker_id, 1);

			// fix the file input id
			this._current_form.one(file_selector).set('id', this._current_form.getAttribute("id") + '_file');
			this._current_form.one(file_selector).set('accept', upload._accepted_mimetype);

			// category / subcategory names
			this._current_form.appendChild(this.create_input({name: 'category_name', type: 'hidden'}));
			this._current_form.appendChild(this.create_input({name: 'category_value', type: 'hidden'}));
			this._current_form.appendChild(this.create_input({name: 'subcategory_name', type: 'hidden'}));
			this._current_form.appendChild(this.create_input({name: 'subcategory_value', type: 'hidden'}));

			// subcategory data
			this._current_form.one('select[name=category]').on('change', function() {
				upload.set_select_options(
						upload._sub_categories[this.get('value')] || [],
						upload._current_form.one('select[name=subcategory]'));

				upload._current_form.one('input[name=subcategory_name]').set('value', '');
				upload._current_form.one('input[name=subcategory_value]').set('value', '');
			});

			// setting category / subcategory names
			this._current_form.all('select').on('change', function(event) {

				var name = event.target.get('name'),
					hidden_name = upload._current_form.one('input[name=' + name + '_name]'),
					hidden_value = upload._current_form.one('input[name=' + name + '_value]'),
					name = [],
					value = [];

				// collect all of the names and values
				event.target.all('option').each(function(node) {
					if (node.get('selected')) {
						name.push(node.get('text'));
						value.push(node.get('value'));
					}
				});

				hidden_name.set('value', name.join(','));
				hidden_value.set('value', value.join(','));
			});
		},

		hide_input_row: function(selector, remove) {
			var input = this._current_form.one(selector);
			if (input) {
				var row = input.ancestor('div.control-group');
				if (remove) {
					row.remove();
				} else {
					row.setStyle('display', 'none');
				}
			}
		},

		add_row: function(label_node, input_node, input_data) {
			var row = this.Y.Node.create('<div class="fp-saveas control-group clearfix" style="margin-bottom:5px;"></div>');

			row.appendChild(label_node);
			row.appendChild(input_node);

			if (!_.isUndefined(input_data) && input_data.row_class) {
				row.addClass(input_data.row_class);
			}

			this._current_form.one('div.fp-formset').appendChild(row);
		},

		add_input_row: function(input_data) {
			var input_node = this.create_input(input_data),
				label_node = this.create_label(input_data);

			if (label_node && input_node) {
				this.add_row(label_node, input_node, input_data);
			}
		},

		set_select_options: function(options, select_node) {

			// clear away any existing options
			select_node.all('option').remove();

			for (var o, i = 0, length = options.length; i < length; i++) {
			    o = options[i];
				select_node.appendChild('<option value="' + o.refId + '">' + o.name + '</option>');
			}
		},

		create_label: function(data) {

			var node = this.Y.Node.create('<label class="control-label"></label>'),
				nodeHTML = '';

			if (data.name) {
				node.set('for', data.name + '-' + this.current_filepicker_id);
				node.set('id', data.name + '_label_' + this.current_filepicker_id);
			}

			if (data.hidden)
				node.hide();

			if (data.help)
				nodeHTML += '<img src="/repository/intralibrary_upload/pix/info.png" class="help" title="' + data.help + '" /> ';

			nodeHTML += data.label;

			if (data.required)
				nodeHTML += ' <span style="cursor:help;" title="' + _get_string('upload_required') + '">*</span>';

			node.set('innerHTML', nodeHTML);

			return node;
		},

		create_input: function(data) {
			var node;

			switch (data.type) {
			case 'select':

				node = this.Y.Node.create('<select></select>');

				if (data.multiple) {
					node.set('multiple', 'multiple');
				}

				this.set_select_options(data.options, node);

				if (data.add_empty) {
					var empty_option = this.Y.Node.create('<option value="" selected="selected"></option>');
					empty_option.set('text', '-- Choose ' + data.label + ' --');
					node.prepend(empty_option);
				}
				node.setStyle('width', '289px');

				break;
			case 'textarea':
				node = this.Y.Node.create('<textarea>' + ( data.value || '' ) + '</textarea>');
				delete data.value;
				node.setStyle('width', '275px');
				break;
			case 'file':
			case 'hidden':
			case 'text':
				node = this.Y.Node.create('<input type="' + data.type + '" />');
				node.setStyle('width', '275px');
				if(data.value){
					node.set('value', data.value);
				}
				break;
			case 'checkbox':
				node = this.Y.Node.create('<input type="' + data.type + '" />');
				break;
			default:
				return null;
			}

			node.set('name', data.name);
			node.set('id', data.name + '-' + this.current_filepicker_id);

			if (data.required)
				node.set('required', 'required');

			if (data.value)
				node.set('value', data.value);

			if (data.title)
				node.set('title', data.title);

			if (data.hidden)
				node.hide();

			var container = this.Y.Node.create('<div class="controls"></div>');
			container.append(node);

			return container;
		}
	};


	M.repository_intralibrary_upload = {
		/**
		 * Initialise upload features for the filepicker
		 */
		init: function(Y, repository_id, pluginname) {

			upload.Y = Y;

			this.set_compatible_repo(repository_id, pluginname);

			// attach the upload filepicker
			M.repository_intralibrary_filepicker.add_hook(function(filepicker, Y) {
				upload.prepare_filepicker(filepicker, Y);
			});
		},

		/**
		 * Set a compatible upload repository
		 *
		 * @param repository_id
		 * @param pluginname
		 */
		set_compatible_repo: function(repository_id, pluginname) {
			// tag this repository as compatible
			upload._compatible_repos[repository_id] = pluginname;
		},

		/**
		 * Set Categories for the file picker
		 */
		set_categories: function(Y, categories) {
			upload._categories = categories;
		},
		/**
		 * Set optional deposit fields if required for the file picker
		 */
		set_settings_variables: function(Y, value) {
			upload._settings = value;
		},

		/**
		 * Set Sub Categories for the file picker
		 */
		set_sub_categories: function(Y, sub_categories) {
			upload._sub_categories = sub_categories;
		},

		/**
		 * Set the Kaltura file extensions
		 */
		set_kaltura_extensions: function(Y, extensions) {
			upload._kaltura_extensions = extensions;
		},

		/**
		 * Set the course short/full names
		 * @param Y
		 * @param shortname
		 * @param fullname
		 */
		set_course: function(Y, shortname, fullname) {
			upload._course_shortname = shortname;
			upload._course_fullname = fullname;
		},

		/**
		 * Create an upload form
		 * @param Y
		 * @param form_id
		 * @param options
		 */
		create_upload_form: function(Y, form, options) {
			upload.create_upload_form(form, options);
		}
	};

})(M);
