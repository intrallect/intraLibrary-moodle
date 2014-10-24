<?php

require_once __DIR__ . '/auth_base.php';

class repository_intralibrary_auth extends repository_intralibrary_auth_base {

    const DEPOSIT_POINT_FROM_SSO = 'deposit-point-from-sso';

    private $sso_user;

    /**
     * Get the username and password based on the auth settings
     *
     * @return array
     */
    public function get_auth_username_password() {
        switch ($this->authSetting) {
            case INTRALIBRARY_AUTH_OPEN:
            case INTRALIBRARY_AUTH_OPEN_TOKEN:
                return $this->get_admin_username_password();
                break;
            case INTRALIBRARY_AUTH_SHARED:
                $username = $this->get_intralibrary_username();
                $password = $this->get_sso_user()->get_password();
                break;
            default:
                $this->_throwUnknownException();
        }

        return array($username, $password);
    }

    /**
     * Get the configured admin username and password
     *
     * @return array
     */
    public function get_admin_username_password() {
        $username = get_config('intralibrary', 'admin_username');
        $password = get_config('intralibrary', 'admin_password');

        return array($username, $password);
    }

    /**
     * Get the intralibrary username for the current user
     * Creates an intralibrary user if they don't exist,
     * based on sso user data.
     *
     * @return string
     */
    public function get_intralibrary_username() {
        global $USER;

        if (!isset($USER->intralibrary_username)) {

            $ssoUser = $this->get_sso_user();

            $req = new \IntraLibrary\Service\RESTRequest();
            $resp = $req->adminGet('User/createBcuUser',
                array(
                    'username' =>   $ssoUser->get_username(),
                    'Password' =>   $ssoUser->get_password(),
                    'FirstName' =>  $ssoUser->get_first_name(),
                    'LastName' =>   $ssoUser->get_last_name(),
                    'Email' =>      $ssoUser->get_email(),
                    'Faculty' =>    $ssoUser->get_faculty(),
                    'PersonType' => $ssoUser->get_person_type()
                ));

            if ($resp->getError()) {
                // No recovering if there was an error..
                $data = $resp->getData();
                repository_intralibrary_log("Failed to create an IntraLibrary with User/createBcuUser");
                repository_intralibrary_log($data['exception']['stackTrace']);
                throw new Exception("Unable to retrieve IntraLibrary username");
            }

            $USER->intralibrary_username = $ssoUser->get_username();
        }

        return $USER->intralibrary_username;
    }

    /**
     * Get get the sso user
     *
     * @throws Exception
     * @return repository_intralibrary_sso_user
     */
    public function get_sso_user() {

        if (!isset($this->sso_user)) {

            $filename = get_config('intralibrary', 'sso_user_class');
            $this->validate_sso_user($filename);

            $class_name = $this->get_sso_user_class_name($filename);
            $this->sso_user = new $class_name();
        }

        return $this->sso_user;
    }

    /**
     *
     * @return boolean true if the SSO user is of PersonType 'staff'
     */
    public function is_staff() {
        switch ($this->authSetting) {
            case INTRALIBRARY_AUTH_OPEN:
            case INTRALIBRARY_AUTH_OPEN_TOKEN:
                // TODO: how do we want to determine whether normal users are staff?
                return FALSE;
            case INTRALIBRARY_AUTH_SHARED:
                return $this->get_sso_user()->is_staff();
            default:
                $this->_throwUnknownException();
        }
    }

    /**
     * Ensure that a file contains an sso user class
     *
     * @param string $filename
     * @throws Exception if there is an issue
     */
    public function validate_sso_user($filename) {
        $this->_validate_sso_user_file($filename);
        $this->_validate_sso_user_class($filename);
    }

    /**
     * Get the sso user class name based on filename
     *
     * @param string $filename
     * @return string
     */
    public function get_sso_user_class_name($filename) {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * Ensure the file exists
     *
     * @param string $filename
     * @throws Exception
     */
    private function _validate_sso_user_file($filename) {
        if (!$filename) {
            throw new moodle_exception('settings_user_auth_shared_class_missing', 'repository_intralibrary');
        }

        if (!file_exists($filename)) {
            throw new moodle_exception('settings_user_auth_shared_class_missing', 'repository_intralibrary');
        }
    }

    /**
     * Ensure the file contains a valid sso_user class
     *
     * @param string $filename
     * @throws Exception
     * @return string the name of the class
     */
    private function _validate_sso_user_class($filename) {

        require_once __DIR__ . '/sso_user.php';
        require_once $filename;

        $class_name = $this->get_sso_user_class_name($filename);

        if (!class_exists($class_name)) {
            throw new moodle_exception('settings_user_auth_shared_class_bad_class', 'repository_intralibrary', NULL, array(
                'path' => $filename,
                'class_name' => $class_name
            ));
        }

        $interface = 'repository_intralibrary_sso_user';
        $implements = class_implements($class_name);
        if (!in_array($interface, $implements)) {
            throw new moodle_exception('settings_user_auth_shared_class_no_iterface', 'repository_intralibrary', NULL, array(
                'class_name' => $class_name,
                'interface' => $interface
            ));
        }

        return $class_name;
    }
}
