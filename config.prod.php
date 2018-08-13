<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 29.08.17
 */

use JTL\Onetimelink\Notification\Message\AbstractMessage;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\User;

return [

    # Timezone used in OTL
    # 'timezone' => 'Europe/Berlin',

    # Request Object
    # 'request' => function (): Request,

    # Authentication Object
    # 'auth' => function (Request $request): AuthenticationInterface,

    # User Interface URL - required when sending mails
    # 'user-interface-host' => 'http://127.0.0.1:3000',

    ###################################################################
    # Notification Settings
    ###################################################################

    # Email address used in from when sending notification mails
    # 'from' => 'otl-do-not-reply@jtl-software.com',

    # Notifier class used for sending mails
    # 'notifier' => function (string $mailFrom): NotifierInterface,

    ###################################################################
    # Messages
    ###################################################################

    # Message to be sent when a guest link was filled with content
    # 'message-guest-link-response' => function (User $user, $oneTimeLink, array $tags): AbstractMessage,

    # Message to be sent when a link was deleted
    # 'message-link-deleted' => function (User $user, array $tags, string $ip, string $useragent): AbstractMessage,

    # Password reset message
    # 'message-password-reset' => function (string $email, string $hash, string $resetLink): AbstractMessage,

    # Prompt user for activation message
    # 'activate-user' => function (string $email, string $activationUrl): AbstractMessage,

    # Account activation successful message
    # 'activated-user' => function (string $email): AbstractMessage,

    ###################################################################
    # Storage Settings
    ###################################################################

    # Attachment storage location
    # 'storage-directory' => '/',

    # Attachment storage location
    # 'tmp_dir' => '',

    # Storage Engine
    # 'storage' => function (string $directory): DatabaseStorage,

    ###################################################################
    # Logging
    ###################################################################

    # Log file location
    # 'log-file-path' => 'app.log',

    # Log format
    # 'log-format' => '[%extra.uid%][%datetime%][%level_name%]\t%extra.memory_usage% - %message% %context% %extra%\n',

    ###################################################################
    # Console
    ###################################################################

    # Attachment storage location (relative to the scripts/Console.php script)
    # 'console-storage-directory' => '/',
    
    # Directory where downloads are stored (relative to the scripts/Console.php script)
    # 'console-tmp-dir' => '',

    # php contrib/bin/createUser.php <user> <password> to create password for the user list
    # 'user' =>  '',

    # E-Mail whitelist regex. Only users with an email that matches this regex may register an account.
    # 'allow-user-with-mail' => '/^.*$/',

    # DSN used by RedBeanPHP to connect to the application database
    # 'db_dsn' =>  '',

    # DSN used by RedBeanPHP to connect to the application database (relative to the scripts/Console.php script)
    # 'console_db_dsn' =>  '',
];