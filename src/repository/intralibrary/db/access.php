<?php
$capabilities = array(

        'repository/intralibrary:view' => array(
            'captype' => 'read', 'contextlevel' => CONTEXT_MODULE,
                'archetypes' => array(
                    'user' => CAP_ALLOW
                )
        )
);
