<?php
defined('MOODLE_INTERNAL') || die();

$plugin->version = 2014102400;
$plugin->requires = 2014051200;
$plugin->component = 'filter_intralibrary';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0';

$plugin->dependencies = array(
        'repository_intralibrary' => 2014102400
);
