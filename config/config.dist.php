<?php

use JTL\Onetimelink\Authentication\AuthenticationInterface;
use JTL\Onetimelink\Authentication\BasicAuth;
use JTL\Onetimelink\Notification\Mail;
use JTL\Onetimelink\Notification\Message\AbstractMessage;
use JTL\Onetimelink\Notification\Message\HTMLMessage;
use JTL\Onetimelink\Notification\NotifierInterface;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\Storage\LocationDirectory;
use JTL\Onetimelink\User;
use PHPMailer\PHPMailer\PHPMailer;

return [

    # Timezone used in OTL
    'timezone' => 'Europe/Berlin',

    # Request Object
    'request' => function (): Request {
        return Request::createFromGlobals(true);
    },

    # Authentication Object
    'auth' => function (Request $request): AuthenticationInterface {
        return new BasicAuth($request);
    },

    # User Interface URL - required when sending mails
    'user-interface-host' => 'http://127.0.0.1:3000',

    ###################################################################
    # Notification Settings

    # Email address used in from when sending notification mails
    'from' => 'otl-do-not-reply@jtl-software.com',

    'notifier' => function (string $mailFrom): NotifierInterface {
        $mailer = new PHPMailer(true);
        $mailer->isMail();
        $mailer->isHTML(true);
        $mailer->CharSet = 'UTF-8';
        return new Mail($mailer, $mailFrom);
    },

    # Messages
    'message-guest-link-response' => function (User $user, $oneTimeLink, array $tags): AbstractMessage {
        $directory = new LocationDirectory(__DIR__ . '/../Resources/EmailTemplate/');

        $data = new \stdClass();
        $data->user = (string)$user;
        $data->yourLink = $oneTimeLink;
        $data->tags = $tags;

        $subjectExt = '';
        if(!empty($tags)){
            $subjectExt = '['.implode('][', $tags).'] ';
        }
        $subjectExt = mb_convert_encoding($subjectExt, 'Windows-1252', 'auto');

        return new HTMLMessage(
            $user->getEmail(),
            $subjectExt. 'Der Gastlink wurde mit Inhalt belegt',
            (string)$directory . 'GuestLinkResponse_de.php',
            $data
        );
    },

    'message-link-deleted' => function (User $user, array $tags, string $ip, string $useragent): AbstractMessage {
        $directory = new LocationDirectory(__DIR__ . '/../Resources/EmailTemplate/');

        $data = new \stdClass();
        $data->user = (string)$user;
        $data->useragent = $useragent;
        $data->tags = $tags;
        $data->ip = $ip;

        $subjectExt = '';
        if(!empty($tags)){
            $subjectExt = '['.implode('][',$tags).'] ';
        }

        return new HTMLMessage(
            $user->getEmail(),
            $subjectExt. 'Ein Link wurde abgeholt',
            (string)$directory . 'LinkDeleted_de.php',
            $data
        );
    },

    'message-password-reset' => function (string $email, string $hash, string $resetLink): AbstractMessage {
        $directory = new LocationDirectory(__DIR__ . '/../Resources/EmailTemplate/');

        $data = new stdClass();
        $data->hash = $hash;
        $data->resetLink = $resetLink;

        return new HTMLMessage(
            $email,
            'Ihr OTL-Passwort wurde zurückgesetzt!',
            (string)$directory . 'ResetPassword_de.php',
            $data
        );
    },

    'activate-user' => function (string $email, string $activationUrl): AbstractMessage {
        $directory = new LocationDirectory(__DIR__ . '/../Resources/EmailTemplate/');

        $data = new \stdClass();
        $data->activationUrl = $activationUrl;

        return new HTMLMessage(
            $email,
            'OneTimeLink E-Mail-Freigabe',
            (string)$directory . 'ActivateNewUser_de.php',
            $data
        );
    },

    'activated-user' => function (string $email): AbstractMessage {
        $directory = new LocationDirectory(__DIR__ . '/../Resources/EmailTemplate/');

        $data = new \stdClass();

        return new HTMLMessage(
            $email,
            'OneTimeLink-Konto wurde aktiviert',
            (string)$directory . 'ActivatedUser_de.php',
            $data
        );
    },

    # Notification Settings
    ###################################################################

    ###################################################################
    # Storage Settings

    # Path for File Storage
    'storage-directory' => __DIR__ . '/../../data',

    # Storage Engine
    'storage' => function (string $directory): DatabaseStorage {
        return new DatabaseStorage(new LocationDirectory($directory));
    },

    # END: Storage Settings
    ###################################################################

    ###################################################################
    # Logging

    # application log file
    'log-file-path' => __DIR__ . '/../../log/app.log',

    # log format
    'log-format' => '[%extra.uid%][%datetime%][%level_name%]\t%extra.memory_usage% - %message% %context% %extra%\n',

    # END: Logging
    ###################################################################

    ###################################################################
    # Garbage Collection

    'console-storage-directory' => __DIR__ . '/../var/data',
    'console-tmp-dir' => __DIR__ . '/../var/tmp',

    # END: Logging
    ###################################################################

    ###################################################################
    # Unsorted Stuff goes here- someone need to clean up
    ###################################################################

    # ensure file is writable
    'user' =>  __DIR__ . '/../../users/user.json',
    'db_dsn' =>  'sqlite:/application/db/database.db',
    'console_db_dsn' =>  'sqlite:' . __DIR__ . '/../var/db/database.db',

    'allow-user-with-mail' => '/.*@(jtl-software|example)\.com$/',

    'tmp_dir' => '/application/data',
    'user-activation-secret' => '##secret##',


];