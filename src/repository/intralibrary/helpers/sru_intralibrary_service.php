<?php

require_once __DIR__ . '/abstract_intralibrary_service.php';

class sru_intralibrary_service extends abstract_intralibrary_service {

    private $token;

    protected function create_request(\IntraLibrary\Service\SRWResponse $srwResp) {

        $xsReq = new \IntraLibrary\Service\SRURequest($srwResp);

        if ($this->token) {
            $xsReq->setToken($this->token);
        }

        return $xsReq;
    }

    public function set_token($token) {
        $this->token = $token;
    }
}

