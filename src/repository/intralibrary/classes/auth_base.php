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
 * Base for classes working with auth settings
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
namespace repository_intralibrary;

abstract class auth_base {

    protected $authSetting;

    public function __construct() {
        $this->authSetting = get_config('intralibrary', 'authentication');
    }

    protected function _throwUnknownException() {
        intralibrary_add_moodle_log("view", "Unknown authentication setting: $this->authSetting");
        throw new \Exception("Unknown authentication setting: $this->authSetting - please contact your administrator");
    }

    public function is($auth_setting) {
        return $this->authSetting == $auth_setting;
    }
}
