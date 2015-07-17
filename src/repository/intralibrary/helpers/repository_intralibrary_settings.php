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
 * IntraLibrary plugin settings page helper class
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

use \IntraLibrary\Configuration;
use \IntraLibrary\Service\SRURequest;
use \IntraLibrary\Service\SRWResponse;

require_once __DIR__ . '/abstract_repository_intralibrary_settings.php';

class repository_intralibrary_settings extends abstract_repository_intralibrary_settings {

    protected static $PLUGIN_NAME = 'repository_intralibrary';

    /**
     * (non-PHPdoc)
     *
     * @see repository::type_config_form()
     *
     * @param MoodleQuickForm $mform
     */
    public function type_config_form(MoodleQuickForm $mform) {

        $currAuth = get_config('intralibrary', 'authentication');
        $postAuth = isset($_POST['authentication']) ? $_POST['authentication'] : NULL;
        $sharedAuthSettings = $postAuth == INTRALIBRARY_AUTH_SHARED || (!$postAuth && $currAuth == INTRALIBRARY_AUTH_SHARED);

        $this->add_element($mform, 'hostname', 'text', TRUE);
        $mform->setType('hostname', PARAM_RAW);
        $this->add_element($mform, 'admin_username', 'text', TRUE);
        $mform->setType('admin_username', PARAM_RAW);
        $this->add_element($mform, 'admin_password', 'text', TRUE);
        $mform->setType('admin_password', PARAM_RAW);

        $this->add_select($mform, 'authentication', array(
                0 => self::get_string('setting_category_select'),
                INTRALIBRARY_AUTH_OPEN => self::get_string('settings_user_auth_open'),
                INTRALIBRARY_AUTH_OPEN_TOKEN => self::get_string('settings_user_auth_open_token'),
                INTRALIBRARY_AUTH_SHARED => self::get_string('settings_user_auth_shared')
        ), TRUE);

        if ($postAuth == INTRALIBRARY_AUTH_OPEN_TOKEN || (!$postAuth && $currAuth == INTRALIBRARY_AUTH_OPEN_TOKEN)) {
            $mform->addElement('text', 'token', self::get_string('settings_user_auth_token'));
            $mform->setType('token', PARAM_RAW);
        }

        if ($sharedAuthSettings) {
            $mform->addElement('text', 'sso_user_class', self::get_string('settings_user_auth_shared_class'), 'size="40"');
            $mform->setType('sso_user_class', PARAM_RAW);
        }

        $mform->addElement('text', 'search_limit', self::get_string('settings_search_limit'), 'size="5"');
        $mform->setType('search_limit', PARAM_INT);

        $this->add_select($mform, 'category', array_merge(
                array(self::get_string('setting_category_select')),
                repository_intralibrary::data_service()->get_category_sources()
        ), TRUE);

        $mform->addElement('checkbox', 'customCQL', self::get_string('settings_customCQL'));
        if (get_config("intralibrary", "customCQL") || isset($_POST["customCQL"])) {
            $textareaAttrs = 'rows="5" cols="40"';
            $mform->addElement('textarea', 'customCQL_query', self::get_string('settings_customCQL_query'), $textareaAttrs);
            $mform->addElement('static', 'customCQL_label', NULL, '<i>' . self::get_string('settings_customCQL_desc') . '</i>');
        }

        $this->add_element($mform, 'kaltura_url', 'text', FALSE);
        $mform->setType('kaltura_url', PARAM_RAW);

        if (!$sharedAuthSettings) {
            $mform->addElement('header', 'col_header', self::get_string('settings_user_collections'));
            $this->add_collections($mform);
        }

        $mform->closeHeaderBefore('logenabled');

        $this->add_element($mform, 'logenabled', 'checkbox');
        $this->add_element($mform, 'logfile', 'text');
        $mform->setType('logfile', PARAM_RAW);

        $mform->closeHeaderBefore('optional_field_my_resources');
        $mform->addElement('header', 'fields_header', self::get_string('settings_enabled_query_fields'));
        $this->add_element($mform, 'optional_field_my_resources', 'checkbox', FALSE, self::get_string('search_myresources'));
        $this->add_element($mform, 'optional_field_collection', 'checkbox', FALSE, self::get_string('search_collection'));
        $this->add_element($mform, 'optional_field_file_type', 'checkbox', FALSE, self::get_string('search_filetype'));
        $this->add_element($mform, 'optional_field_star_rating', 'checkbox', FALSE, self::get_string('search_starrating'));

        $this->add_element($mform, 'optional_field_resource_type', 'checkbox', FALSE, self::get_string('search_resourcetype'));
        if (get_config('intralibrary', 'optional_field_resource_type') || isset($_POST['optional_field_resource_type'])) {
            $vocabularies = repository_intralibrary::data_service()->get_all_vocabularies();
            $this->add_select($mform, 'resource_type_vocabulary_id', array_merge(
                    array(self::get_string('setting_resource_type_vocabulary_select')),
                    $vocabularies
            ), TRUE);
        }

        $this->add_element($mform, 'optional_field_category', 'checkbox', FALSE, self::get_string('search_category'));

    }

    /**
     * @param MoodleQuickForm $mform
     * @param array $data
     * @param array $errors
     * @param string $name
     */
    public function type_form_validation($mform, $data, $errors) {

        // cache original configuration
        $config = Configuration::get();

        // configure IntraLibrary with new options
        Configuration::set($data);

        try {
            // test whether the supplied user has admin access
            $req = new \IntraLibrary\Service\RESTRequest();
            $req->setLogin($data['admin_username'], $data['admin_password']);
            // Explicilty NOT an 'adminGet' request so that any existing cookie data is not used.
            $resp = $req->get('Test/authorization', array(
                    'authaction' => 'VIEW_ADMIN_AREA'
            ));

            $status = $req->getLastResponseCode();
            if ($status == 401 || $status == 403) {
                $errors['admin_password'] = $errors['admin_username'] = self::get_string('setting_error_credentials');
            } else if ($resp->getError()) {
                $errors['hostname'] = self::get_string('setting_error_url') . $data['hostname'];
            } else {
                // Now that we know this is a valid user, save the internal user id
                $req = new \IntraLibrary\Service\RESTRequest();
                $userData = $req->adminGet('User/show/' . $data['admin_username'])->getData();
                set_config('admin_user_id', $userData['user']['id'], "intralibrary");
            }
        } catch (Exception $ex) {
            $errors['hostname'] = $ex->getMessage();
        }

        if (empty($data['category'])) {
            $errors['category'] = self::get_string('setting_category_select_missing');
        }

        if (empty($data['authentication'])) {
            $errors['authentication'] = self::get_string('settings_user_auth_error');
        }

        if ($data['authentication'] == INTRALIBRARY_AUTH_OPEN_TOKEN && empty($data['token'])) {
            $errors['token'] = self::get_string('settings_user_auth_token_missing');
        }

        if (empty($data['search_limit']) || !is_numeric($data['search_limit'])) {
            $errors['search_limit'] = self::get_string('settings_search_limit_invalid');
        }

        if ($data['authentication'] == INTRALIBRARY_AUTH_SHARED) {
            if (empty($data['sso_user_class'])) {
                $errors['sso_user_class'] = self::get_string('settings_user_auth_shared_class_missing');
            } else {
                try {
                    $authHelper = new repository_intralibrary\auth();
                    $authHelper->validate_sso_user($data['sso_user_class']);
                } catch (Exception $ex) {
                    $errors['sso_user_class'] = $ex->getMessage();
                }
            }
        }

        if (!empty($data['logfile'])) {
            $can_write = repository_intralibrary_can_write_to_file($data['logfile']);
            if ($can_write !== TRUE) {
                $errors['logfile'] = $can_write;
            }
        }

        // Validate the rest, if intralibrary looks to be configured OK
        if (empty($errors)) {
            $errors = $this->type_form_additional_validation($mform, $data, $errors);
        }

        // if there were errors restore original configuration
        if ($errors) {
            Configuration::set($config);
        }

        return $errors;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param unknown $mform
     * @param unknown $data
     * @param unknown $errors
     * @return Ambigous <Ambigous, string, lang_string, unknown, mixed>
     */
    private function type_form_additional_validation($mform, $data, $errors) {

        $currAuth = get_config('intralibrary', 'authentication');
        $postAuth = isset($_POST['authentication']) ? $_POST['authentication'] : NULL;
        $sharedAuthSettings = $postAuth == INTRALIBRARY_AUTH_SHARED || (!$postAuth && $currAuth == INTRALIBRARY_AUTH_SHARED);

        if (!$sharedAuthSettings) {
            $collections = array();
            foreach ($data as $key => $value) {
                if (strpos($key, 'col_') === 0 && $value === "1") {
                    $collections[] = substr($key, 4); // Strip out 'col_'
                }
            }

            if (empty($collections)) {
                $errors["col_error"] = self::get_string('settings_user_collections_error');
            } else {
                set_config('enabled_collections', implode(',', $collections), 'intralibrary');
            }
        }

        //Custom CQL validation
        if (!empty($data['customCQL']) && isset($data['customCQL_query'])) {

            try {
                $service = new repository_intralibrary\sru_service();
                $service->set_custom_cql($data['customCQL_query']);

                $SRWResp = $service->get_records(array('searchterm' => 'test'));
                if ($SRWResp->getError()) {
                    $errors['customCQL_query'] = self::get_string('settings_customCQL_error2');
                }
            } catch (Exception $ex) {
                $errors['customCQL_query'] = $ex->getMessage();
            }
        }

        return $errors;
    }

    private function add_collections($mform) {
        if ($collections = $this->data_service->get_all_collections()) {
            $mform->addElement('static', 'col_error', NULL, NULL);
            foreach ($collections as $colId => $collection) {
                $checkbox = new HTML_QuickForm_checkbox('col_' . $colId, $collection, '');
                $checkbox->setChecked($this->is_collection_enabled($colId));
                $mform->addElement($checkbox);
            }
        } else {
            $mform->addElement('static', 'col_error', NULL, self::get_string('settings_user_collections_info'));
        }
    }

    private function is_collection_enabled($collectionId) {
        static $collections;
        if (!isset($collections)) {
            $collections = $this->data_service->get_available_collections();
        }
        return isset($collections[$collectionId]);
    }

    /**
     * Options for all instances
     *
     * @return array
     */
    public static function get_type_option_names() {
        return array(
                'pluginname',
                'hostname',
                'admin_username',
                'admin_password',
                'authentication',
                'token',
                'kaltura_url',
                'sso_user_class',
                'category',
                'customCQL',
                'customCQL_query',
                'logenabled',
                'logfile',
                'search_limit',
                'optional_field_my_resources',
                'optional_field_collection',
                'optional_field_file_type',
                'optional_field_star_rating',
                'optional_field_resource_type',
                'resource_type_vocabulary_id',
                'optional_field_category'
        );
    }
}
