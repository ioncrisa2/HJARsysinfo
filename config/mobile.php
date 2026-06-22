<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mobile App Access
    |--------------------------------------------------------------------------
    |
    | Only users with one of these roles may authenticate through the mobile API.
    | The role name for "pemimpin" in this codebase is "pimpinan".
    |
    */
    'allowed_roles' => [
        'data_contributor',
        'pimpinan',
        'surveyor',
    ],
];
