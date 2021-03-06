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
 * The core of the IntraLibrary Block plugin
 *
 * @package    block_intralibrary
 * @category   block
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once __DIR__ . '/../../repository/intralibrary/abstract_repository_intralibrary.php';
class block_intralibrary extends block_base {

    public function init() {
        if ($this->content !== NULL) {
            return $this->content;
        }
        $this->title = "IntraLibrary Block";
    }

    public function get_content() {
        if ($this->_self_test()) {
            global $CFG;
            global $USER;

            if ($this->content !== NULL) {
                return $this->content;
            }

            // set tilte content
            if (!empty($this->config->title)) {
                $this->title = $this->config->title;
            } else {
                $name = trim(get_string('pluginname', 'repository_intralibrary'), "Plugin");
                $this->title = get_string('uploadto', 'block_intralibrary') . " " . $name;
            }

            // set body content
            $this->content = new stdClass();
            if (!empty($this->config->blockbody)) {
                $this->content->text = $this->config->blockbody;
            } else {
                $path = $CFG->wwwroot;
                $link = $path . '/blocks/intralibrary/file_for_sharing.php';
                $this->content->text = get_string('default_body', 'block_intralibrary', $link);
            }

            if ($this->_is_admin() && !intralibrary_isEditor()) {
                $this->content->text .= '<br /><p><i>' . get_string('admin_only_hint', 'block_intralibrary') . '</i></p>';
            }

            return $this->content;
        }
    }

    /**
     * Bence:
     * If we don't have a user context or the current user is not a teacher, the block plugin doesn't have a title which
     * makes moodle to hide the block.
     * During the update procedure moodle will check all plugins, it looks for sufficent content, title, etc. At that time
     * the site doesn't have a user context which makes the block not to display it's title. As Moodle doesn't find the
     * title, it marks it as faulty.
     *
     * To get around of this check we need to implement this method here. Unforunatly not much documentation online, I found
     * this procedure in reverse engineering the upgradelib.php
     *
     * @return boolean
     */
    public function _self_test() {
        global $USER;
        if ($this->_is_admin()) {
            return TRUE;
        } else {
            return intralibrary_isEditor();
        }
    }

    public function _is_admin() {
        global $USER;
        $admins = get_admins();
        $isadmin = FALSE;
        foreach ($admins as $admin) {
            if ($USER->id == $admin->id) {
                $isadmin = TRUE;
                break;
            }
        }
        return $isadmin;
    }
}
