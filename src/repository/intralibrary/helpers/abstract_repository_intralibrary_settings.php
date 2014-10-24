<?php
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
     * @var repository_intralibrary_data_service
     */
    protected $data_service;

    protected $isEditing = FALSE;

    public function __construct(repository_intralibrary_data_service $data) {

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
