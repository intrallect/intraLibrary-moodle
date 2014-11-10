<?php
if (!isset($string)) {
    $string = array();
}

$string['pluginname'] = 'IntraLibrary Upload IMS Plugin'; // required for moodle plugin directory validation

$string = array_merge($string,
        array(
            'intralibrary_upload_ims:view' => 'View IntraLibrary Upload IMS Repository',
            'configplugin' => 'IntraLibrary Upload IMS Plugin Configuration',
            'pluginname_help' => 'IntraLibrary Upload IMS Plugin: help'
        ));
