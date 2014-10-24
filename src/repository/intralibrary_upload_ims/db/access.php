<?php
$capabilities = array(
        'repository/intralibrary_upload_ims:view' => array(
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array(
                        'user' => CAP_ALLOW
                )
        )
);
