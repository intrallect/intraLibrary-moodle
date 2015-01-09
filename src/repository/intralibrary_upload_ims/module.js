(function(M) {

	var compatible_repos = {},
		prepare_form_class = 'intralibrary-preupload-form',
		prepare_button_text = 'Prepare Package',
		processing = false;

	function is_compatible(repo_id) {
		return typeof compatible_repos[repo_id] != 'undefined';
	}

	var ims_upload = {

		augment_upload_form: function(Y, form_id, client_id) {

			var iframe_name = 'intralibrary-uploadframe-' + client_id,
				form_div = Y.one('#' + form_id),
				upload_button_container = Y.one('.upload-button-container');

			// hide the form and the submit button
			form_div.setStyle('display', 'none');
			upload_button_container.setStyle('display', 'none');

			var preupload_iframe = Y.Node.create('<iframe></iframe>')
					.set('name', iframe_name)
					.addClass('intralibrary-preupload-iframe'),
				file_input = Y.Node.create('<input></input>')
					.set('type', 'file')
					.set('name', 'package'),
				file_input_description = Y.Node.create('<p></p>')
					.setHTML('Upload a package to read its metadata and prepare it for deposit.'),
				file_input_label = Y.Node.create('<label></label>')
					.setHTML('Select package file:')
					.addClass('control-label'),
				file_input_container = Y.Node.create('<div></div>')
					.addClass('controls')
					.setStyle('width', '325px')
					.append(file_input)
					.append(file_input_description)
				file_input_row = Y.Node.create('<div></div>')
					.addClass('fp-saveas')
					.addClass('control-group')
					.addClass('clearfix')
					.addClass('fp-file')
					.append(file_input_label)
					.append(file_input_container),
				client_id_input = Y.Node.create('<input></input>')
					.set('type', 'hidden')
					.set('name', 'client_id')
					.set('value', client_id),
				submit_button = Y.Node.create('<input></input>')
					.set('type', 'submit')
					.set('value', prepare_button_text)
				submit_container = Y.Node.create('<p></p>')
					.setStyle('text-align','center')
					.append(submit_button),
				preupload_container = Y.Node.create('<div></div>')
					.addClass('fp-formset')
					.setStyle('margin-left','auto')
					.setStyle('margin-right','auto')
					.append(file_input_row)
					.append(client_id_input)
					.append(submit_container),
				preupload_form = Y.Node.create('<form></form>')
					.addClass(prepare_form_class)
					.addClass('form-horizontal')
					.set('id', 'filepicker-' + client_id + ' .' + prepare_form_class)
					.set('action', '/repository/intralibrary_upload_ims/prepare_imscp.php')
					.set('target', iframe_name)
					.set('method', 'post')
					.set('enctype', 'multipart/form-data')
					.append(preupload_container);

			processing = false;

			preupload_form.on('submit', function(evt) {
				if (processing || preupload_form.one('input[type=file]').get('value') == '') {
					evt.preventDefault();
				} else {
					processing = true;
					preupload_form.one('input[type=submit]').set('value', 'Loading...').set('disabled', 'disabled');
				}
			});

			form_div.ancestor().append(preupload_form).append(preupload_iframe);
		},

		prepare_filepicker: function(filepicker, Y) {

			var create_upload_form = filepicker.create_upload_form;
			filepicker.create_upload_form = function(data) {

				// create original upload form
				create_upload_form.apply(filepicker, arguments);

				if (is_compatible(data.repo_id)) {
					ims_upload.augment_upload_form(Y, data.upload.id + '_' + this.options.client_id, this.options.client_id);
				}
			};
		}
	};

	M.repository_intralibrary_upload_ims = {

		init: function(Y, repository_id, pluginname) {
			// enable customised upload form
			M.repository_intralibrary_upload.set_compatible_repo(repository_id, pluginname);
			// disable for any instances of editor
			M.repository_intralibrary_filepicker.disable_in_env('editor', repository_id);

			// tag this repository as compatible
			compatible_repos[repository_id] = pluginname;

			// attach the upload filepicker
			M.repository_intralibrary_filepicker.add_hook(function(filepicker, Y) {
				ims_upload.prepare_filepicker(filepicker, Y);
			});
		},

		/**
		 *
		 * @param data
		 */
		load_upload_fields: function(data) {
			YUI().use('node', function(Y) {

				var client_id = data.client_id,
					prepare_form = Y.one('#filepicker-' + client_id + ' .' + prepare_form_class);

				processing = false;

				if (data.metadata) {

					prepare_form.remove();

					var upload_form = Y.one('#' + data.form_prefix + '_' + client_id),
						file_input = upload_form.one('input[name=repo_upload_file]');

					// send a reference to the processed file and hide this input field
					file_input.set('type', 'hidden').set('value', data.filename);
					file_input.ancestor('.fp-file').hide();

					upload_form.one('input[name=title]').set('value', data.metadata.title);
					upload_form.one('textarea[name=description]').set('value', data.metadata.description);
					upload_form.one('input[name=keywords]').set('value', data.metadata.keywords.join(', '));
					upload_form.one('input[name=upload_type]').set('value', 'contentPackage');

					upload_form.show();
					upload_form.next().show();

				} else {

					prepare_form.all('input').removeAttribute('disabled');
					prepare_form.one('input[type=submit]').set('value', prepare_button_text);

					if (data.error) {
						M.core_filepicker.instances[data.client_id].print_msg(data.error, 'error');
					}
				}
			});
		},

		augment_upload_form: function(Y, form_id, client_id) {
			ims_upload.augment_upload_form(Y, form_id, client_id);
		}
	};

})(M);
