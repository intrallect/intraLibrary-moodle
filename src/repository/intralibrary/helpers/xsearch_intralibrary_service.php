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
 * X-Search Request Service
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once __DIR__ . '/abstract_intralibrary_service.php';

class xsearch_intralibrary_service extends abstract_intralibrary_service {

    private $username;
    private $myresources_only;

    protected function create_request(\IntraLibrary\Service\SRWResponse $srwResp) {

        if (!$this->username) {
            throw new Exception("XSearch requires a username");
        }

        $xsReq = new \IntraLibrary\Service\XSearchRequest($srwResp);
        $xsReq->setXSearchUsername($this->username);

        if ($this->myresources_only) {
            $xsReq->setShowUnpublished(TRUE);
        }

        return $xsReq;
    }

    protected function build_query($options) {
        $query = parent::build_query($options);

        if ($this->myresources_only && $this->username) {
            $query .= " AND rec.username={$this->username}";
        }

        return $query;
    }

    public function set_username($username) {
        $this->username = $username;
    }

    public function set_my_resources_only($enabled) {
        $this->myresources_only = (boolean) $enabled;
    }
}

