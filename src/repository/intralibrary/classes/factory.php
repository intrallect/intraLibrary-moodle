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
 * Authentication Factory
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
namespace repository_intralibrary;

class factory extends auth_base {

    /**
     * @var repository_intralibrary\auth
     */
    private $auth;

    public function __construct() {
        parent::__construct();
        $this->auth = new auth();
    }

    /**
     * Get the auth service used by the factory
     *
     * @return repository_intralibrary\auth
     */
    public function get_auth() {
        return $this->auth;
    }

    /**
     *
     * @throws Exception
     * @return abstract_intralibrary_service
     */
    public function build_intralibrary_service($options = array()) {
        switch ($this->authSetting) {
            case INTRALIBRARY_AUTH_OPEN:
            case INTRALIBRARY_AUTH_OPEN_TOKEN:
                require_once __DIR__ . '/../helpers/sru_intralibrary_service.php';
                $repo = new \sru_intralibrary_service();

                $token = get_config('intralibrary', 'token');
                if ($this->authSetting == INTRALIBRARY_AUTH_OPEN_TOKEN && $token) {
                    $repo->set_token($token);
                }

                break;
            case INTRALIBRARY_AUTH_SHARED:
                require_once __DIR__ . '/../helpers/xsearch_intralibrary_service.php';
                $repo = new \xsearch_intralibrary_service();

                list($username) = $this->auth->get_auth_username_password();
                $repo->set_username($username);

                if (isset($options['myresources']) && $options['myresources'] == 'yes') {
                    $repo->set_my_resources_only(TRUE);
                }

                break;
            default:
                $this->_throwUnknownException();
        }

        // check if custom query is set
        if (get_config("intralibrary", "customCQL")) {
            $repo->set_custom_cql(get_config("intralibrary", "customCQL_query"));
        }

        return $repo;
    }

    /**
     * Build a SWORDService object
     *
     * @param boolean $forceAdmin whether the admin username/password will be used
     * @return \IntraLibrary\Service\SWORDService
     */
    public function build_sword_service($forceAdmin = FALSE) {

        if ($forceAdmin) {
            list($username, $password) = $this->auth->get_admin_username_password();
        } else {
            list($username, $password) = $this->auth->get_auth_username_password();
        }

        return new \IntraLibrary\Service\SWORDService($username, $password);
    }

    /**
     * Build a set of used data used for VCards based on the current user
     *
     * @return repository_intralibrary_contributor_data
     */
    public function build_contributor_data() {

        require_once __DIR__ . '/../helpers/contributor_data.php';

        $data = new \repository_intralibrary_contributor_data();

        switch ($this->authSetting) {
            case INTRALIBRARY_AUTH_OPEN:
            case INTRALIBRARY_AUTH_OPEN_TOKEN:
                global $USER;
                $data->FullName     ="{$USER->firstname} {$USER->lastname}";
                $data->Email        = $USER->email;
                $data->Organisation = "";
                break;
            case INTRALIBRARY_AUTH_SHARED:
                $ssoUser = $this->auth->get_sso_user();
                $data->FullName     = $ssoUser->get_full_name();
                $data->Email        = $ssoUser->get_email();
                $data->Organisation = $ssoUser->get_organisation();
                break;
            default:
                $this->_throwUnknownException();
        }

        return $data;
    }
}
