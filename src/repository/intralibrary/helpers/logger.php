<?php
class repository_intralibrary_logger {
    public function log() {
        call_user_func_array('repository_intralibrary_log', func_get_args());
    }
}

