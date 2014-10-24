<?php
if (!isset($string)) {
    $string = array();
}

$string = array_merge($string,
        array(
    'pluginname' => 'Intralibrary Block',
    'intralibrary:addinstance' => 'Add the intralibrary custom upload',
    'intralibrary:addmyinstance' => 'Add the intralibray plugin to my moodle page',
    'uploadto' => "File upload to",
    'optional_title' => 'Block Title',
    'optional_body' => 'Block Body'
));
