<?php
if (!isset($string)) {
    $string = array();
}

$string['pluginname'] = 'IntraLibrary Upload Plugin'; // required for moodle plugin directory validation

$string = array_merge($string,
        array(
            'intralibrary_upload:view' => 'View IntraLibrary Upload Repository',

             // settings page
            'configplugin' => 'IntraLibrary Upload Plugin Configuration', 'pluginname_help' => 'IntraLibrary Upload Plugin: help',
            'settings' => 'settings',
            'setting_kaltura_url' => 'Kaltura URL', 'setting_kaltura_admin_secret' => 'Kaltura Admin Secret',
            'setting_kaltura_partner_id' => 'Kaltura Partner ID',
            'setting_kaltura_enabled' => 'Enable Kaltura Support',
            'setting_approval' => 'Approval might be requested',
            'setting_non_discoverable' => 'Uploaded file might be hidden',
            'settings_optional_deposit_box' => 'Optional Deposit Point {$a}',
            'settings_optional_deposit_box_enabled' => 'Enabled',
            'settings_extra_information' => 'Ask for additional desciption',
            'settings_alterative_collection' => 'Deposit Group &amp; Workflow',
            'settings_extra_info_description' => 'Additional description prompt',

            'settings_optional_deposit_label_missing' => "Please insert a hint",
            'settings_optional_deposit_title_missing' => "Please insert a title",
            'settings_optional_deposit_desc_missing' => "Please insert description",
            'settings_optional_deposit_url_missing' => "Please select a Group and Workflow",
            'setting_default_deposit_point' => "Default deposit point",
            'settings_default_deposit_missing' => 'Default deposit point is not configured',
            'settings_optional_deposit_missing' => 'Optional deposit point {$a} is not configured',

            'settings_sso_deposit_description' => 'The SSO User class will determine the deposit URL',

            // upload form
            'upload_title' => 'Title',
            'upload_label' => 'Hint',
            'upload_title_help' => 'This title will be shown when you search for resources so it should be short and as descriptive as possible. It will be searched when people look for resources.',
            'upload_description' => 'Description',
            'upload_description_help' => 'This description will be used in the repository to provide more detail about the resource. It will be searched when people look for resources.',
            'upload_category' => 'Category',
            'upload_category_help' => 'You must choose one category to help make this file more discoverable. It can be used to filter resources when searching.',
            'upload_subcategory' =>'Sub-Category',
            'upload_subcategory_help' => 'You may choose none, one or more sub-categories to show the areas in which this resource can be useful.',
            'upload_autokeywords' => 'Auto-Keywords',
            'upload_autokeywords_help' => 'These keywords will be included automatically unless you remove them.',
            'upload_keywords' => 'Keywords',
            'upload_keywords_help' => 'You may add as many keywords or phrases as you wish, separated by commas. These will help to make the resource discoverable, particularly if the keywords are not already in the title or description. These are searched when people look for resources.',
            'upload_missing'=>'Missing required field',
            'upload_required' => 'Required Field',
            'upload_invalid_ext'=>'Invalid file extension',

            // kaltura upload
            'upload_kaltura' => 'Uploading media to Kaltura / IntraLibrary... please be patient with large files.',
            'upload_kaltura_patient' => 'Uploading to IntraLibrary... please be patient with large files.'
    )
);

