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
 * The core of the IntraLibrary Filter plugin
 *
 * This filter plugin renders Kaltura  videos sourced
 * from the IntraLibrary repository. The filter must
 * be turned on in order to display Kaltura videos.
 *
 * @package    filter_intralibrary
 * @category   filter
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_intralibrary;

class fiexception extends \moodle_exception {

    public function __construct($errorcode, $link='', $a = null, $debuginfo = null) {
        parent::__construct($errorcode, 'filter_intralibrary', $link, $a, $debuginfo);
    }
}
