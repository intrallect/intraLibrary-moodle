<?php

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

