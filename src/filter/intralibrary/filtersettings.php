<?php

$settings->add(new admin_setting_configtext(
    'filter_intralibrary/detect_embed_code',
    get_string('detect_embed_code', 'filter_intralibrary'),
    get_string('detect_embed_code_desc', 'filter_intralibrary'),
    '<script',
    PARAM_RAW));