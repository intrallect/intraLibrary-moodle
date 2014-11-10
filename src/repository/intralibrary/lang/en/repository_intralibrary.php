<?php
if (!isset($string)) {
    $string = array();
}

$string['pluginname'] = 'IntraLibrary Plugin'; // required for moodle plugin directory validation

$string = array_merge($string,
        array(
            'intralibrary:view' => 'View IntraLibrary Repository',

            'configplugin' => 'IntraLibrary Plugin Configuration',
            'pluginname_help' => 'IntraLibrary Plugin: help',

            // Search
            'search_back' => 'Back',
            'search_query' => 'Search for',
            'search_myresources' => 'Only my resources:',
            'search_yes' => 'Yes',
            'search_no' => 'No',
            'search_selectone' => '-- Any --',

            'search_collection' => 'Collection',

            'search_filetype' => 'File Type',
            'search_word' => 'Word',
            'search_pdf' => 'PDF',
            'search_image' => 'Image',

            'search_starrating' => 'Star Rating',
            'search_star4' => '4 or more',
            'search_star3' => '3 or more',
            'search_star2' => '2 or more',
            'search_star1' => '1 or more',

            'search_category' => 'Category',
            'search_kaltura_error' => 'You cannot attach a Kaltura video here, try embedding it using the media icon in the text editor instead.',

            // Settings
            'setting_hostname' => 'IntraLibrary URL',
            'setting_admin_username' => 'IntraLibrary Admin Username',
            'setting_admin_password' => 'IntraLibrary Admin Password',
            'setting_logenabled' => 'Log Enabled',
            'setting_logfile' => 'Log File',
            'setting_error_credentials' => 'Invalid Admin Username and/or Password',
            'setting_error_url' => 'Invalid Intralibrary URL: ',
            'setting_memcache' => 'Memcache server (host:port)',
            'setting_memcache_invalid' => 'Unable to connect to memcache server',

            // categoty settings
            'setting_category' => 'IntraLibrary Category Source',
            'setting_category_select' => '-- Select One --',
            'setting_category_select_missing' => 'Select a Category Source',
            'setting_category_info' => 'You must configure IntraLibrary before you can select a category',

            // authentication settings
            'setting_authentication' => 'User Authentication Method',
            'settings_user_auth_error' => 'Please select a User Authentication Method',
            'settings_user_auth_open' => 'Open intraLibrary',
            'settings_user_auth_open_token' => 'Open intraLibrary with Collection Token',
            'settings_user_auth_token' => 'Authentication token',
            'settings_user_auth_token_missing' => 'Missing authentication token',
            'settings_user_auth_shared' => 'Shared authentication',
            'settings_user_auth_shared_class' => 'SSO User class<br/>(path to .php file)',
            'settings_user_auth_shared_class_missing' => 'Missing SSO User class (is the supplied path valid?)',
            'settings_user_auth_shared_class_bad_class' => '"{$a->path}" does not contains the called "{$a->class_name}"',
            'settings_user_auth_shared_class_no_iterface' => 'SSO User class ("{$a->class_name}") must implement the "{$a->interface}" interface',
            'settings_user_collections' => 'Enabled Collections',
            'settings_user_collections_info' => 'You must properly configure intraLibrary before selecting collections',
            'settings_user_collections_error' => 'Please select at least one collection',

            // addition search CQL settings
            'settings_customCQL' => 'Addition search CQL',
            'settings_customCQL_query' => 'Query',
            'settings_customCQL_desc' => 'Please note, your custom query will be added after each form generated query.',
            'settings_customCQL_error' => 'Missing custom CQL',
            'settings_customCQL_error2' => 'Syntax error, please verify your custom CQL',
            'cachedef_accces_cache' => 'IntraLibrary session cache',
            'cachedef_data_cache' => 'IntraLibrary data cache',

            // events
            'upload_event_name' => 'IntraLibrary Upload',
            'general_event_name' => 'IntraLibrary Log'
        ));
