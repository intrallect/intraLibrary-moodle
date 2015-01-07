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
        if (intralibrary_isEditor()) {
            if ($this->content !== NULL) {
                return $this->content;
            }
            $this->title = "Intralibrary Quick Deposit ";
        }
    }

    public function get_content() {
        if (intralibrary_isEditor()) {
            global $CFG;
            if ($this->content !== NULL) {
                return $this->content;
            }

            //set tilte content
            if (! empty($this->config->title)) {
                $this->title = $this->config->title;
            } else {
                $name = trim(get_string('pluginname', 'repository_intralibrary'), "Plugin");
                $this->title = get_string('uploadto', 'block_intralibrary')." ".$name;
            }

            //set body content
            $this->content = new stdClass;
            if (! empty($this->config->blockbody)) {
                $this->content->text = $this->config->blockbody;
            } else {
                $path = $CFG->wwwroot;
                $link = $path.'/blocks/intralibrary/file_for_sharing.php';
                $this->content->text = 'To deposit resources, click <a href="'.$link.'">here</a>.';
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
     * @return boolean
     */
    public function _self_test() {
        return TRUE;
    }
}
