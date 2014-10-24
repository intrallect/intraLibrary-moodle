(function(M) {

	var file_for_sharing = {
			upload_form: 		null, // YUI node of the upload form
			Y: 					null, // instance of YUI's Y
			upload_repo_id: 	null, // id of intralibrary_upload repository
			upload_ims_repo_id: null, // id of intralibrary_upload_ims repository
			filepicker:			null  // filepicker instance (to leverage 'request' function)
	};

	function goHome() {
		window.location.href = M.cfg.wwwroot;
	}

	/**
	 * Toggles the upload form between a disabled & low opacity state
	 */
	function toggleForm() {

		var uploadButton = file_for_sharing.Y.one('.intralibrary-upload .fp-upload-btn'),
			loading = file_for_sharing.Y.one('.intralibrary-upload .fp-content-loading');

		if (uploadButton.get('disabled')) {
			uploadButton.set('disabled', null);
			loading.hide();
			uploadButton.ancestor().setStyle('opacity', 1);
			file_for_sharing.upload_form.setStyle('opacity', 1);
		} else {
			uploadButton.set('disabled', 'disabled');
			loading.show();
			uploadButton.ancestor().setStyle('opacity', 0.2);
			file_for_sharing.upload_form.setStyle('opacity', 0.2);
		}
	}

	/**
	 * Process the upload form
	 */
	function doUpload() {

		var scope = file_for_sharing.filepicker,
				form = file_for_sharing.upload_form,
				id = form.get('id'),
				client_id = file_for_sharing.filepicker.options.client_id,
				selected_env = file_for_sharing.Y.one('.intralibrary-upload-types input:checked').get('value');

		if (!scope.validate_upload_form(form)) {
			return;
		}

		// re-use the Moodle filepicker 'request' function to process uploads
		// in order to remain as true to the filepicker interface & process as possible
		scope.request({
			scope: scope,
			action: 'upload',
			client_id: client_id,
			params: {
				savepath: scope.options.savepath,
				accepted_types: ['*'],
				env: selected_env,
				upload_to_moodle: false
			},
			// the selected environment dictates which repository to upload to
			repository_id: selected_env == 'ims' ? file_for_sharing.upload_ims_repo_id : file_for_sharing.upload_repo_id,
			form: { id: id, upload: true },
			onerror: function(id, o, args) {
				toggleForm();
			},
			callback: function(id, o, args) {

				// replace the file/url input element
				var file_input = file_for_sharing.Y.one('.file-picker input[name=repo_upload_file]'),
					value = file_input.get('value');

				if (selected_env != 'url') {
					// parse out the filename (strip directories etc.)
					var startIndex = (value.indexOf('\\') >= 0 ? value.lastIndexOf('\\') : value.lastIndexOf('/')),
						value = value.substring(startIndex);

					if (value.indexOf('\\') === 0 || value.indexOf('/') === 0) {
						value = value.substring(1);
					}
				}

				showDialog({
					message: "You have uploaded '" + value + "'\n\nDo you want to upload another file?",
					actionYes: function() {
						var new_input = file_for_sharing.Y.Node.create('<input />');
						new_input.set('type', file_input.get('type'));
						new_input.set('id', file_input.get('id'));
						new_input.set('name', file_input.get('name'));
						new_input.set('required', file_input.get('required'));

						file_input.replace(new_input);
					},
					actionNo: goHome
				});

				toggleForm();
			}
		});

		toggleForm();
	}

	function showDialog(options) {

	    YUI().use("panel", function (Y) {
	        new Y.Panel({
	            contentBox : Y.Node.create('<div></div>'),
	            bodyContent: '<div class="message">' + options.message + '</div>',
	            width      : 410,
	            zIndex     : 6,
	            centered   : true,
	            modal      : true, // modal behavior
	            render     : true,
	            buttons    : {
	                footer: [
	                    {
	                        name  : 'no',
	                        label : 'No',
	                        action: function(e) {
	                            e.preventDefault();
	                            this.hide();
	                            options.actionNo(e);
	                        }
	                    },

	                    {
	                        name     : 'yes',
	                        label    : 'Yes',
	                        action   : function(e) {
	                            e.preventDefault();
	                            this.hide();
	                            options.actionYes(e);
	                        }
	                    }
	                ]
	            }
	        });
	    });
	}

	/**
	 * Create the upload form based on the selection of the
	 */
	function createUploadForm(env) {

		var form = file_for_sharing.upload_form,
			Y = file_for_sharing.Y,
			form_id = form.get('id'),
			client_id = file_for_sharing.filepicker.options.client_id,
			buttonContainer = Y.one('.buttonContainer');

		form.empty();
		form.append('<div class="fp-formset" style="margin-left: auto; margin-right: auto;"></div>');

		Y.all('.intralibrary-preupload-form').remove();
		Y.all('.intralibrary-preupload-iframe').remove();

		form.show();

		M.repository_intralibrary_upload.create_upload_form(
				Y, form,
				{ env: env, client_id: client_id });

		if (env == 'ims') {
			M.repository_intralibrary_upload_ims.augment_upload_form(
					Y, form_id, client_id);
			buttonContainer.hide();
		} else {
			buttonContainer.show();
		}
	}

	M.repository_intralibrary_upload.file_for_sharing = function(Y, upload_form_id, client_id, upload_repo_id, upload_ims_repo_id) {

		// create a filepicker to help us with the upload process
		M.core_filepicker.init(Y, { client_id: client_id });

		file_for_sharing.Y = Y;
		file_for_sharing.upload_form = Y.one('#' + upload_form_id),
		file_for_sharing.upload_repo_id = upload_repo_id;
		file_for_sharing.upload_ims_repo_id = upload_ims_repo_id;
		file_for_sharing.filepicker = M.core_filepicker.instances[client_id];
		file_for_sharing.filepicker.print_msg = function(message) { alert(message); };

		// re-render the form whenever one of the radio buttons is selected
		Y.all('.intralibrary-upload-types input').on('change', function(event) {
			this.each(function(node) {
				node.ancestor().removeClass('selected');
			});
			event.target.ancestor().addClass('selected');
			createUploadForm(event.target.get('value'));
		});
		Y.one('.intralibrary-upload-types input[value=file]').simulate('change');

		// process uploads here
		Y.one('.intralibrary-upload .fp-upload-btn').on('click', doUpload);
		Y.one('.intralibrary-upload .fb-cancel-btn').on('click', goHome);
	};
})(M);
