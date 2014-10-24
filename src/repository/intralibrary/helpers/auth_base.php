<?php

/**
 * Base for classes working with auth settings
 */
abstract class repository_intralibrary_auth_base {

    protected $authSetting;

    public function __construct() {
        $this->authSetting = get_config('intralibrary', 'authentication');
    }

    protected function _throwUnknownException() {
        intralibrary_add_moodle_log("view", "Unknown authentication setting: $this->authSetting");
        throw new Exception("Unknown authentication setting: $this->authSetting - please contact your administrator");
    }

    public function is($auth_setting) {
        return $this->authSetting == $auth_setting;
    }
}
