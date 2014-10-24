(function(M) {

	var reference_only = false,
		intralibrary_ready = false,
		intralibrary_url = null,
		search_parameters = {}, // the latest search parameters
		compatible_repos = {}; // compatible repositories

	function _get_string(id) {
		return M.util.get_string(id,'repository_intralibrary');
	}

	function is_compatible(repo_id) {
		return typeof compatible_repos[repo_id] != 'undefined';
	}

	var prepare_filepicker = function(filepicker, Y) {

		// hook into repository options to store response data
		var parse_repository_options = filepicker.parse_repository_options;
		filepicker.parse_repository_options = function(data) {

			if (is_compatible(data.repo_id)) {

				// store the search parameters
				if (data.parameters) {
					search_parameters = data.parameters;
				}
			}

			parse_repository_options.apply(filepicker, arguments);
		};

		// hook into select_file to show previews
		var select_file = filepicker.select_file;
		filepicker.select_file = function(args) {

			var is_compat = is_compatible(this.active_repo.id),
				env = this.options.env,
				return_types = this.options.return_types;

			if (is_compat) {
				if (args.type == 'kaltura' && env == 'filemanager') {
					// files added via the kaltura file picker should be linked by reference
					this.options.return_types = 4; // FILE_REFERENCE
					//this.print_msg(_get_string('search_kaltura_error'));
					//return;
				} else if (env == 'editor' || env == 'url') {
					this.options.return_types = 1; // FILE_EXTERNAL
				} else if (reference_only) {
					this.options.return_types = 4; // FILE_REFERENCE
				}
			}

			// call original function & restore return types
			select_file.apply(filepicker, arguments);
			this.options.return_types = return_types;

			// only process compatible repos
			if (is_compat) {

				var client_id = this.options.client_id,
					select_file_form = Y.one('#filepicker-select-' + client_id + ' form'),
					newinfo_div = select_file_form.one('.intralibrary-info'),
					select_button = this.selectnode.one('button.fp-select-confirm'),
					select_button_text = select_button.get('text');

				// This is copied from moodle's /repository/filepicker.js line 1112
				// since the corresponding event doesn't get fired in Firefox
				// This also (intentionally) forces the fields be hidden for FILE_INTERNAL types
				this.selectnode.all('.fp-setauthor,.fp-setlicense,.fp-saveas').each(function(node){
					node.addClass('uneditable');
					node.all('input,select').set('disabled', 'disabled');
				});

				if (newinfo_div) {
					newinfo_div.empty();
				} else {
					newinfo_div = Y.Node.create('<div></div>').addClass('intralibrary-info');
					select_file_form.appendChild(newinfo_div);
				}

				newinfo_div.appendChild(Y.Node.create('<h3>' + args.title + '</h3>'));
				if (args.description) {
					newinfo_div.appendChild(Y.Node.create('<p>' + args.description + '</p>'));
				}

				newinfo_div.appendChild(Y.Node.create('<a target="_blank" href="' + args.url + '">Preview</a>'));

				// update the hidden "name" field to contain the actual filename in the LOR
				// this is nice to have, but also required by Moodle's "accepted_types" validation
				if (args.type == 'kaltura') {

					// kaltura videos will use the supplied/guessed file extension
					// just to pass Moodle's 'accepted types' validation
					if (env != 'filemanager') {
						select_file_form.one('.fp-saveas input').set('value', args.title + '.' + args.fileext);
					}

				} else {

					// all other types should request the filename from the server
					select_button.set('disabled', 'disabled');
					select_button.set('text', 'Please wait ...');
					this.request({
						action: 'download',
						client_id: client_id,
						repository_id: this.active_repo.id,
						params: {
							accepted_types: ['*'],
							linkexternal: 'yes',
							source: Y.one('#filesource-' + client_id).get('value'),
							get_original_filename: true
						},
						callback: function(id, obj) {
							select_button.set('disabled', false);
							select_button.set('text', select_button_text);
							select_file_form.one('.fp-saveas input').set('value', obj.url);
						}
					});
				}
			}
		};

		// hook into print_header to update the "manage" link
		var print_header = filepicker.print_header;
		filepicker.print_header = function() {

			// ensure the browse link is disabled if it exists
			var browse = this.fpnode.one('.fp-tb-browse');
			if (browse) {
				browse.addClass('disabled');
			}

			print_header.apply(filepicker, arguments);

			// only process compatible repos
			if (!is_compatible(this.active_repo.id)) {
				return;
			}

			// update "Manage" link text
			var repo_name = compatible_repos[this.active_repo.id],
				icon_url = '/repository/intralibrary/pix/icon.png',
				manage_text = 'Search "' + search_parameters.searchterm + '" in ' + repo_name,
				manage_link = this.active_repo.manage;

			if (manage_link && this.fpnode.one('.fp-tb-manage a')) {
				this.fpnode.one('.fp-tb-manage a').replace(Y.Node.create(
						'<a target="_blank" href="' + manage_link + '" class="browse-icon-desc"><img class="browse-icon" src="' + icon_url + '" /> ' + manage_text + '</a>'
				));

				var logOutNode = this.fpnode.one('div.fp-tb-logout');
				logOutNode.one('a').set('title', 'New search')
				logOutNode.one('img').set('src','/pix/a/search.png')

			}

			// create a "Browse" link
			if (!browse && intralibrary_url) {
				browse = Y.Node.create('<div class="fp-tb-manage fp-tb-browse disabled"></div>');
				browse.setStyle('width','auto');
				browse_link = Y.Node.create('<a target="_blank" class="browse-icon-desc"></a>');
				browse_link.set('href', intralibrary_url + '/IntraLibrary?command=browse-taxonomy');
				browse_link.set('title', 'Browse ' + repo_name);
				browse_link.setContent('<img class="browse-icon" src="' + icon_url + '" />Browse ' + repo_name);
				browse.append(browse_link);

				this.fpnode.one('.fp-navbar .fp-toolbar').prepend(browse);
			}
		};

		// hook into print_login...
		var print_login = filepicker.print_login;
		filepicker.print_login = function(data) {

			// store the intralibrary url for print_header
			if (data.intralibrary_url) {
				intralibrary_url = data.intralibrary_url;
			}

			// create the normal form..
			print_login.apply(filepicker, arguments);

			// only process compatible repos
			if (!is_compatible(data.repo_id)) {
				return;
			}

			var login_form = Y.one('#fp-form-' + this.options.client_id);

			if (data.intralibrary_url && !intralibrary_ready) {
				// initiate an IntraLibrary session to allow thumbnails to load without SSO redirects
				Y.Node.create('<iframe seamless="seamless" scrolling="no" class="intralibrary-pixel-iframe"></iframe>')
						 .set('src', data.intralibrary_url + '/IntraLibrary?command=get-news-feeds')
						 .appendTo(Y.one('body'));
				intralibrary_ready = true;
			}

			if (data.error_message) {
				this.print_msg(data.error_message, 'error');
			}

			// enable the browse link
			this.fpnode.one('.fp-navbar .fp-toolbar').removeClass('empty');
			this.fpnode.one('.fp-tb-browse').removeClass('disabled').addClass('enabled');

			// append "*" to the "Search for" label
			login_form.all('label').each(function() {
				var text = this.get('text');
				if (text == 'Search for') {
					this.set('text', text + ' *:');
				}
			});

			// restore search parameters into form
			for (var i = 0, d; i < this.logindata.length; i++) {
				d = this.logindata[i];
				if (d.type == 'checkbox' && d.checked) {
					login_form.one('input[name=' + d.name + ']').set('checked', 'checked');
				} else if (d.type == 'select' && d.value) {
					login_form.one('select[name=' + d.name + '] option[value=' + d.value + ']').set('selected', 'selected');
				} else if (d.value) {
					login_form.one('input[name=' + d.name + ']').set('value', d.value);
				}
			}
			var submitButton = Y.one('button.fp-login-submit');
			submitButton.addClass('mb-10');
			Y.Node.create('<input type="reset" value="Reset" class="fp-login-submit">').appendTo(submitButton.get('parentNode'));
		};
	};

	M.repository_intralibrary = {
		init: function(Y, repository_id, pluginname) {

			// tag this repository as compatible
			compatible_repos[repository_id] = pluginname;

			// attach the upload filepicker
			M.repository_intralibrary_filepicker.add_hook(prepare_filepicker);
		},

		set_reference_only: function(Y, ref_only) {
			reference_only = ref_only;
		}
	};

})(M);

