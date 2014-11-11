<?php
if (!isset($string)) {
    $string = array();
}

$string['pluginname'] = 'intralibrary Block';  // required for moodle plugin directory validation

$string = array_merge($string,
    array(
        'intralibrary:addinstance' => 'Add the intralibrary custom upload',
        'intralibrary:addmyinstance' => 'Add the intralibray plugin to my moodle page',
        'uploadto' => "File upload to",
        'optional_title' => 'Block Title',
        'optional_body' => 'Block Body'
    ));
