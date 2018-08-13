<?php

use JTL\Onetimelink\Authentication\AuthenticationInterface;
use JTL\Onetimelink\Authentication\BasicAuth;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\Storage\LocationDirectory;

return [
    "request" => function (): Request {
        return Request::createFromGlobals(true);
    },
    "auth" => function (Request $request): AuthenticationInterface {
        return new BasicAuth($request);
    },

    ###################################################################
    # Storage Settings
    ###################################################################

    # Path for File Storage
    "storage-directory" => '/var/run/otl',

    # Storage Engine
    "storage" => function (string $directory): DatabaseStorage {
        return new DatabaseStorage(new LocationDirectory($directory));
    },

    ###################################################################
    # END: Storage Settings
    ###################################################################

    "user" => [
        "ronny" => "098f6bcd4621d373cade4e832627b4f6"
    ],
    "admin" => [
        "ronny"
    ]
];