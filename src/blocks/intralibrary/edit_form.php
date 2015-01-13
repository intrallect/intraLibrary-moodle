<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings page for the plugin
 *
 * This plugin enables users to quickly deposit
 * files into the associated IntraLibrary repository.
 *
 * @package    block_intralibrary
 * @category   block
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_intralibrary_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG;
        $path = $CFG->wwwroot;
        $link = $path.'/blocks/intralibrary/file_for_sharing.php';
        $name = trim(get_string('pluginname', 'repository_intralibrary'), get_string('plugin', 'block_intralibrary'));
        if ($this->block->config->title == "") {
            $this->block->config->title = get_string('uploadto', 'block_intralibrary')." ".$name;
        }
        if ($this->block->config->blockbody == "") {
            $this->block->config->blockbody = get_string('default_body', 'block_intralibrary', $link);
        }

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('static', 'description', "", get_string('settings_suggestion_empty_field', 'block_intralibrary'));
         // A sample string variable with a default value.
        $mform->addElement('text', 'config_title', get_string('optional_title', 'block_intralibrary'), 'size="60"');
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('textarea', 'config_blockbody', get_string('optional_body', 'block_intralibrary'),
                'wrap="virtual" rows="10" cols="58"');
        $mform->setType('config_blockbody', PARAM_RAW);

        $mform->addElement('static', 'description', "", get_string('settings_suggestion_no_link', 'block_intralibrary', $link));

    }
}