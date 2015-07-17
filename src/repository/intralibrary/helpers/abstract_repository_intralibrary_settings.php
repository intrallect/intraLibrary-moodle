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
 *
 * IntraLibrary repository settings helper class
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

abstract class abstract_repository_intralibrary_settings {

    protected static $PLUGIN_NAME = NULL;

    /**
     * @param unknown $identifier the property
     * @param string $replace     replacement string/object/array
     * @return Ambigous <string, lang_string, unknown, mixed>
     */
    protected static function get_string($identifier, $replace = NULL) {
        return get_string($identifier, self::get_plugin_name(), $replace);
    }

    /**
     * Return the name of the plugin (used for get_string calls)
     *
     * @return string
     */
    private static function get_plugin_name() {
        if (empty(static::$PLUGIN_NAME)) {
            throw new Exception("Plugin name must be configured in subclass");
        }
        return static::$PLUGIN_NAME;
    }

    /**
     * @var repository_intralibrary\data_service
     */
    protected $data_service;

    protected $isEditing = FALSE;

    public function __construct(repository_intralibrary\data_service $data) {

        $this->data_service = $data;

        if (isset($_POST['action'], $_POST['submitbutton'])
                && $_POST['action'] == 'edit' && $_POST['submitbutton'] == 'Save') {
            $this->isEditing = TRUE;
        }
    }

    /**
     *
     * @param MoodleQuickForm $mform
     * @param string $name
     * @param string $type
     * @param string $label
     * @param string $required
     */
    protected function add_element($mform, $name, $type, $required = FALSE, $label = NULL) {

        $label      = $this->get_label($name, $label);
        $element    = $mform->addElement($type, $name, $label);

        $this->set_required($mform, $name, $required);

        return $element;
    }

    protected function add_select($mform, $name, $options, $required = FALSE, $label = NULL) {

        $label      = $this->get_label($name, $label);
        $element    = $mform->addElement('select', $name, $label, $options);

        $this->set_required($mform, $name, $required);

        return $element;
    }

    private function get_label($name, $label = NULL) {
        if ($label === NULL) {
            $label = self::get_string("setting_$name");
        }
        return $label;
    }

    private function set_required($mform, $name, $required) {
        if ($required) {
            $validation = $required === TRUE ? 'server' : $required;
            $mform->addRule($name, 'Required', 'required', NULL, $validation);
        }
    }
}
