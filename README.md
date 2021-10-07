OneTimeLink
===========

OneTimeLink enables you to exchange sensitive data online via a uniquely valid link.
Simply upload the file, generate the link and send it to the recipient.
After the recipient has received the data it will be irrevocably deleted.

# How-To Install OTL (without docker)

Requirements: nginx, >= PHP 7.1

Copy `.env.dist` to `.env` and enter valid user credentials.

Setup Document Root to `./web` directory and point all requests to `index.php`

    location / {
        try_files $uri $uri/index.html @rewrite_index;
    }

    location @rewrite_index {
        rewrite ^.*$ /index.php last;
    }

Setup ENV `ENVIRONMENT_CONFIG_PATH` - pointing to the correct `config.php` path. Otherwise the default config will be used.

    fastcgi_param  ENVIRONMENT_CONFIG_PATH /var...

If you want a fresh configuration copy *config.prod.php* into the config/ directory and configure the application from scratch.

Setup X-ACCL-REDIRECT for controlled Downloads - create a internal location pointing to the `data` directory

    location /application/data/ {
        internal;
        alias /application/data/;
    }
    
See `docker-compose.yml` for more information

# How-To Contribute

Checkout the source code and run a `./composer install` direct in project root. Once composer has installed 
all required dependencies you can start all required container by running `docker-compose up -d`.

Now you are ready to execute the application. 

`./vendor/bin/codeception run` will execute all Integration Tests
`./vendor/bin/phpunit` will execute all UnitTests 

OTL is now accessible through [localhost:8080](http://localhost:8080)

## Dependencies

  * \>= PHP 7.1
  * (development-only) Docker engine v1.13 or higher. 
  * (development-only) Docker compose v1.12 pr higher. See [docs.docker.com/compose/install](https://docs.docker.com/compose/install/).
  * PHP-Sqlite with the extension 'pdo_sqlite3' enabled

## Services exposed outside your environment

Service|Address outside containers
------|---------
Webserver|[localhost:8080](http://localhost:8080)
MailHog|[localhost:8081](http://localhost:8001)

## Hosts within your environment

You'll need to configure your application to use any services you enabled:

Service|Hostname|Port number
------|---------|-----------
php-fpm|php-fpm|9000

### Docker compose cheatsheet

**Note:** you need to cd first to where your docker-compose.yml file lives.

  * Start containers in the background: `docker-compose up -d`
  * Start containers on the foreground: `docker-compose up`. You will see a stream of logs for every container running.
  * Stop containers: `docker-compose stop`
  * Kill containers: `docker-compose kill`
  * View container logs: `docker-compose logs`
  * Execute command inside of container: `docker-compose exec SERVICE_NAME COMMAND` where `COMMAND` is whatever you want to run. Examples:
        * Shell into the PHP container, `docker-compose exec php-fpm bash`
        * Run symfony console, `docker-compose exec php-fpm bin/console`
        * Open a mysql shell, `docker-compose exec mysql mysql -uroot -pCHOSEN_ROOT_PASSWORD`

# Codeception tests failing? Read this!

Completely empty the directories `var/data` and `var/tmp` and remove the
existing database.db file (backup anything important):
`# rm -rf var/data/* var/tmp/* var/db/database.db`

Set the users.json permissions to rwx for all users:
`# chmod a+rwx var/users/user.json`

The codeception tests should work now (unless you broke something of course)