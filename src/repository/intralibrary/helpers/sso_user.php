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
 * IntraLibrary SSO user interface
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

interface repository_intralibrary_sso_user {

    /**
     * Determines whether this user is a staff member
     *
     * @return boolean
     */
    public function is_staff();

    /**
     * Get the intraLibrary username
     *
     * @return string
     */
    public function get_username();

    /**
     * Get the intraLibrary password
     *
     * @return string
     */
    public function get_password();

    /**
     * Get the first name of the user
     *
     * @return string
     */
    public function get_first_name();

    /**
     * Get the last name of the user
     *
     * @return string
     */
    public function get_last_name();

    /**
     * Get the full name of the user
     *
     * @return string
     */
    public function get_full_name();

    /**
     * Get the email of the user
     *
     * @return string
     */
    public function get_email();

    /**
     * Get the faculty of the user
     *
     * @return string
     */
    public function get_faculty();

    /**
     * Get the person type of the user
     *
     * @return string
     */
    public function get_person_type();

    /**
     * Get the organisation of this user
     *
     * @return string
     */
    public function get_organisation();

    /**
     * Get SWORD deposit URL
     *
     * @return string
     */
    public function get_deposit_url();
}