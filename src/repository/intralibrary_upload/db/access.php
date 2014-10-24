<?php
$capabilities = array(
        'repository/intralibrary_upload:view' => array(
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array(
                        'user' => CAP_ALLOW
                )
        )
);
