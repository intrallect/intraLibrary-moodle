(function(M, document) {

	var disabled_in_env = {},
		hooks = [];

	M.repository_intralibrary_filepicker = {

		/**
		 * Add a hook to execute on new filepickers
		 */
		add_hook: function(callback) {
			hooks.push(callback);
		},

		/**
		 * Disable a particular repository for an environment
		 *
		 * @param env
		 * @param repository_id
		 */
		disable_in_env: function(env, repository_id) {

			if (!disabled_in_env[env]) {
				disabled_in_env[env] = {};
			}

			disabled_in_env[env][repository_id] = true;
		},
		
		disable_in_env_from_server: function(Y, env, repository_id) {
			this.disable_in_env(env, repository_id);
		},

		/**
		 * Hook our module into the moodle filepicker
		 */
		hook_into_filepicker: function() {

			if (_.isUndefined(M.core_filepicker) ||
					_.isUndefined(M.core_filepicker.init)) {
				return false;
			}

			// has it already been hooked into?
			if (M.core_filepicker.init_original) {
				return true;
			}

			// override the init function
			var core_filepicker_init = M.core_filepicker.init;
			M.core_filepicker.init = function(Y, options) {

				// run original initialisation
				core_filepicker_init.apply(M.core_filepicker, arguments);

				// and prepare it
				var filepicker = M.core_filepicker.instances[options.client_id];
				if (filepicker) {
					for (var i = 0; i < hooks.length; i++) {
						hooks[i](filepicker, Y);
					}
				}
			};

			var core_filepicker_show = M.core_filepicker.show;
			M.core_filepicker.show = function(Y, options) {

				// disable repositories that don't want to show up in certain
				// environments
				var to_disable = disabled_in_env[options.env] || {};
				for (var repo_id in to_disable) {
					if (options.repositories[repo_id]) {
						delete options.repositories[repo_id];
					}
				}

				core_filepicker_show.apply(M.core_filepicker, arguments);
			};

			return true;
		}
	};

	// attach css
	var filepickerCss = M.cfg.wwwroot + '/repository/intralibrary/filepicker.css';
    var isLoaded = _.find(document.styleSheets, function(stylesheet) {
        return stylesheet.href
                ? stylesheet.href.indexOf(filepickerCss) != -1
                : false;
    });

	if (!isLoaded) {
		if (document.createStyleSheet) {
			document.createStyleSheet(filepickerCss);
		} else {
			var newSS = document.createElement('link');
			newSS.rel = 'stylesheet';
			newSS.type = 'text/css';
			newSS.href = filepickerCss;
			document.getElementsByTagName("head")[0].appendChild(newSS);
		}
	}
})(M, document);
