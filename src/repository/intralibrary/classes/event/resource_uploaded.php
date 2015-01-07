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
 * General log entry type of the IntraLibrary plugin family
 *
 * This event is used to log any special event, including exceptions,
 * errors, configuration changes.
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
namespace repository_intralibrary\event;

defined('MOODLE_INTERNAL') || die();

class resource_uploaded extends \core\event\base {

    /**
     * These array elements are mandatory, must be the same for all instances of the class
     */
    protected function init() {
        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Provide the localised name for the admin log screen, should be
     * the same for all instances
     *
     * @return string
     */
    public static function get_name() {
        return get_string('upload_event_name', 'repository_intralibrary');
    }

    /**
     * The description dynamically generated for each event instance based on the "other" data
     * field, it is shown on the admin log view.
     * In this case, it reads the title of the resource from the "other" data variable's title field.
     *
     * @return string
     */
    public function get_description() {
        return '"'. $this->other['title'] .'" has been uploaded to IntraLibrary successfully.';
    }

    /**
     * This function provides the intralibrary url of the resource.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url($this->other['url']);
    }
}