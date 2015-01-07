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
 * IntraLibrary Upload Plugin settings page helper class
 *
 * It provides deposit functionality though
 * the moodle user interface.
 *
 * @package    repository_intralibrary_upload
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once __DIR__ . '/../../intralibrary/helpers/abstract_repository_intralibrary_settings.php';

class repositroy_intralibrary_upload_settings extends abstract_repository_intralibrary_settings {

    protected static $PLUGIN_NAME = 'repository_intralibrary_upload';

    /**
     * Create the settings form
     *
     * @param MoodleQuickForm $mform
     */
    public function type_config_form($mform) {

        $locations = $this->data_service->get_availabe_locations();

        $this->add_select($mform, 'default_deposit_point', $locations);
        $mform->addElement('static', 'sso_deposit_description', NULL, '*' . self::get_string('settings_sso_deposit_description'));

        // kaltura settings block
        $mform->addElement('header', 'header', "Kaltura ".self::get_string("settings"));
        $this->add_element($mform, 'kaltura_enabled', 'checkbox');
        if (get_config("intralibrary_upload", "kaltura_enabled") || isset($_POST["kaltura_enabled"])) {
            $this->add_element($mform, 'kaltura_url', 'text');
            $mform->setType('kaltura_url', PARAM_RAW);
            $this->add_element($mform, 'kaltura_partner_id', 'text');
            $mform->setType('kaltura_partner_id', PARAM_RAW);
            $this->add_element($mform, 'kaltura_admin_secret', 'text');
            $mform->setType('kaltura_admin_secret', PARAM_RAW);
        }
        $mform->closeHeaderBefore('optional1');

        // Optional deposit method #1 block
        $this->add_optional_deposit($mform, $locations, 1);
        $this->add_optional_deposit($mform, $locations, 2);
    }

    private function add_optional_deposit($mform, $locations, $index) {

        $mform->addElement('header', "header$index", self::get_string("settings_optional_deposit_box", $index));
        $mform->addElement('checkbox', "optional_deposit_$index", self::get_string("settings_optional_deposit_box_enabled"));

        // Add text fields if box is ticked
        if (get_config("intralibrary_upload", "optional_deposit_$index") || isset($_POST["optional_deposit_$index"])) {
            $mform->addElement('text', "optional_{$index}_title", self::get_string("upload_title"), 'size="35"');
            $mform->setType("optional_{$index}_title", PARAM_RAW);
            $mform->addElement('text', "optional_{$index}_label", self::get_string("upload_label"), 'size="35"');
            $mform->setType("optional_{$index}_label", PARAM_RAW);

            // Add Extra information drop down-box and label if it is turned on
            $mform->addElement('selectyesno', "optional_{$index}_extra_info", self::get_string("settings_extra_information"));

            if (get_config("intralibrary_upload", "optional_{$index}_extra_info")
                    || !empty($_POST["optional_{$index}_extra_info"])) {
                $mform->addElement('text', "optional_{$index}_extra_info_description",
                self::get_string("settings_extra_info_description"));
                $mform->setType("optional_{$index}_extra_info_description", PARAM_RAW);
            }
            $mform->addElement('select', "optional_{$index}_alter_collection",
                self::get_string("settings_alterative_collection"), $locations);
            $mform->setType("optional_{$index}_alter_collection", PARAM_RAW);
        }
    }

    /**
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param moodleform $mform
     * @param array $data
     * @param array $errors
     * @return array an array of errors
     */
    public function type_form_validation($mform, $data, $errors) {

        if (!empty($data['kaltura_enabled'])) {
            $serviceUrl     = isset($data['kaltura_url'])           ? $data['kaltura_url'] : NULL;
            $partnerId      = isset($data['kaltura_partner_id'])    ? $data['kaltura_partner_id'] : NULL;
            $adminSecret    = isset($data['kaltura_admin_secret'])  ? $data['kaltura_admin_secret'] : NULL;
            $this->_validate_kaltura_settings($serviceUrl, $partnerId, $adminSecret, $errors);
        }

        if (empty($data['default_deposit_point'])) {
            $errors['default_deposit_point'] = self::get_string('settings_optional_deposit_url_missing');
        }

        // check the set of custom deposit fields
        $this->_validate_optional_deposit($data, $errors, 1);
        $this->_validate_optional_deposit($data, $errors, 2);

        return $errors;
    }

    private function _validate_kaltura_settings($serviceUrl, $partnerId, $adminSecret, &$errors) {

        try {
            // create a session with the Kaltura server
            require_once __DIR__ . '/kaltura.php';
            $kHelper = new intralibrary_kaltura_helper($serviceUrl, $partnerId);
            $kHelper->startSession($adminSecret);
        } catch (Exception $ex) {
            $code = (string) $ex->getCode();
            $msg = $ex->getMessage();

            if (stristr($code, 'session') || stristr($msg, 'session')) {
                $key = 'kaltura_admin_secret';
            } else if (stristr($code, 'partner') || stristr($msg, 'partner')) {
                $key = 'kaltura_partner_id';
            } else {
                $key = 'kaltura_url';
            }

            $errors[$key] = $msg;
        }
    }

    private function _validate_optional_deposit($data, &$errors, $index) {
        if (!empty($data["optional_deposit_".$index])) {
            if (empty($data["optional_{$index}_title"])) {
                $errors["optional_{$index}_title"] = self::get_string('settings_optional_deposit_title_missing');
            }
            if (empty($data["optional_{$index}_label"])) {
                $errors["optional_{$index}_label"] = self::get_string('settings_optional_deposit_label_missing');
            }
            if (!empty($data["optional_{$index}_extra_info"]) && empty($data["optional_{$index}_extra_info_description"])) {
                $errors["optional_{$index}_extra_info_description"] = self::get_string('settings_optional_deposit_desc_missing');
            }
            if (empty($data["optional_{$index}_alter_collection"])) {
                $errors["optional_{$index}_alter_collection"] = self::get_string('settings_optional_deposit_url_missing');
            }
        }
    }


    /**
     *
     * @return array
     */
    public static function get_type_option_names() {
        return array(
                'pluginname',
                'kaltura_enabled',
                'kaltura_url',
                'kaltura_partner_id',
                'kaltura_admin_secret',
                'optional_deposit_1',
                'optional_1_title',
                'optional_1_label',
                'optional_1_extra_info',
                'optional_1_extra_info_description',
                'optional_1_alter_collection',
                'optional_deposit_2',
                'optional_2_title',
                'optional_2_label',
                'optional_2_extra_info',
                'optional_2_extra_info_description',
                'optional_2_alter_collection',
                'default_deposit_point'
        );
    }
}
