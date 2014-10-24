<?php

require_once __DIR__ . '/auth_base.php';

class repository_intralibrary_factory extends repository_intralibrary_auth_base {

    /**
     * @var repository_intralibrary_auth
     */
    private $auth;

    public function __construct() {
        parent::__construct();
        $this->auth = new repository_intralibrary_auth();
    }

    /**
     * Get the auth service used by the factory
     *
     * @return repository_intralibrary_auth
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
                require_once __DIR__ . '/sru_intralibrary_service.php';
                $repo = new sru_intralibrary_service();

                $token = get_config('intralibrary', 'token');
                if ($this->authSetting == INTRALIBRARY_AUTH_OPEN_TOKEN && $token) {
                    $repo->set_token($token);
                }

                break;
            case INTRALIBRARY_AUTH_SHARED:
                require_once __DIR__ . '/xsearch_intralibrary_service.php';
                $repo = new xsearch_intralibrary_service();

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

        require_once __DIR__ . '/contributor_data.php';

        $data = new repository_intralibrary_contributor_data();

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
