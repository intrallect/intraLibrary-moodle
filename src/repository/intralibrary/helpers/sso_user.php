<?php
/**
 * intraLibrary SSO user interface
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