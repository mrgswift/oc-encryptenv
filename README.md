## **oc-encryptenv**

[![GitHub license](https://img.shields.io/badge/License-MIT-green.svg)](https://github.com/mrgswift/laravel-encryptenv/blob/master/LICENSE)

This OctoberCMS plugin is a wrapper for the composer package mrgswift/encryptenv which allows you to encrypt your environment variables in your .env file

This is accomplished through the added helper function `secEnv()` to replace `env()` when using
an encrypted value in your environment file. 

Also included is a console command to encrypt 
the values of all flagged .env  variables.

## IMPORTANT
**This plugin requires that you have write access to your apache/nginx configuration files and requires at least basic knowledge of how your apache/nginx configuration files work.  Issues/questions regarding configuration of apache/nginx or any other web service daemon are beyond the scope of plugin support.**


Some setup/configuration is required to get everything working correctly.

## Install

###### Since some components in this package are needed early on in composer's autoload execution, installation steps must be done in this order (below).  If you don't follow this order, composer may throw an error and things will break.

#### Because the dependency package `mrgswift/laravel-encryptenv` must be available early on in composer's autoload execution (before OctoberCMS), this composer dependency must be installed manually. Use composer to install the package
```console
$ composer require mrgswift/laravel-encryptenv
```
#

#### Files automatically copied from mrgswift/encryptenv dependency package

The default configuration file from the composer package mrgswift/encryptenv is automatically copied to the config path on first run

```
config/encryptenv.php
```

#

#### An autoload block with a files property will automatically be added to your `composer.json` file

By default the composer.json file that is included with OctoberCMS does not have an autoload block. The following autoload block (below) will automatically be added to your composer.json file on first run of the plugin.  This adds the helper function `secEnv` early on in composer's autoload execution to ensure decryption happens early enough to prevent errors.  If the plugin throws the exception `Unable to automatically add file autoloader to composer.json file` this means you either already have an autoload block or the plugin was unable to automatically add this autoload block (file permissions?).  In this case you will need to add this manually to your composer.json

```
    "autoload": {
        "files": ["app/Helpers/secEnv.php"]
    },
```
Re-generate your autoload files, otherwise the new autoload entry you added to `composer.json` will not be seen by composer
```console
$ composer dump-autoload
```
**NOTE: If your OctoberCMS application only returns a blank white screen, you most likely need to run** `composer dump-autoload`

#

#### Update `config/encryptenv.php` with desired settings

Required Settings in `config/encryptenv.php`

****cipher (default: AES-128-CBC)****
```php
    /*
    |--------------------------------------------------------------------------
    | Encryption Cipher
    |--------------------------------------------------------------------------
    |
    | This package uses Laravel's built-in Encryption API
    | Laravel Encrypter supports either AES-128-CBC or AES-256-CBC as a cipher.
    |
    | If you are concerned about the performance and scalability of your
    | application, AES-128-CBC should be more then sufficient to protect your
    | environment variables.  If you are more paranoid, you can use AES-256-CBC
    |
    | More on this here:
    | https://blog.1password.com/guess-why-were-moving-to-256-bit-aes-keys/
    |
    | Change 'cipher' below to AES-128-CBC OR AES-256-CBC to encrypt your environment
    | variables in your .env or custom config file (set custom config below)
    |
    */

    'cipher' => 'AES-128-CBC',
```

****encrypt_flag (default: !ENC:)**** 
```php
    /*
    |--------------------------------------------------------------------------
    | Encrypt Value Flag
    |--------------------------------------------------------------------------
    |
    | This will tell the EncryptEnv console command what flag to look for at the
    | beginning of each environment variable value, to trigger encrypting the value.
    |
    | For best results, use a string that has little probability of being inside of
    | an actual variable value. Though this package does only check the beginning of
    | each variable value, it is still possible to mistakenly choose an encrypt_flag
    | that is contained at the beginning of an actual variable value. If you make
    | this mistake, this package will partially encrypt the variable value causing
    | unexpected results and most likely making the variable unreadable
    |
    | The default included encrypt_flag should suffice for most setups
    |
    | NOTE:  This cannot be 'ENC:' but you can put anything else here
    |
    */

    'encrypt_flag' => '!ENC:',
```
#
Other (Unsupported) Settings in `config/encryptenv.php` 

****custom_config_file**** 
```php
    /*
    |--------------------------------------------------------------------------
    | Custom Config File
    |--------------------------------------------------------------------------
    |
    | Set this if you would rather use your own config file and not docroot/.env
    | Otherwise leave this blank.  Any custom config file must be located in
    | the laravel's default config path, which is docroot/config for most Laravel
    | environments. Laravel has the helper function config_path() to return this
    | path if you are unsure what this path is.
    |
    | NOTE: If you set this, your .env file will be completely ignored by this
    | package
    }
    */

    'custom_config_file' => '',
```
****custom_config_output (default: env)**** 

```php
    /*
    |--------------------------------------------------------------------------
    | Custom Config File Output Format
    |--------------------------------------------------------------------------
    |
    | This is only applied if custom_config_file (above) is set to a non-blank
    | value/filename. Valid output formats are 'env' OR 'array'.  Setting to
    | 'env' outputs variables in valid .env file syntax, while 'array' outputs
    | an array usable by Laravel's Config helper class
    |
    */

    'custom_config_output' => 'env'
```
#

#### Configure your web service (apache/nginx) to pass your CONFIGKEY (encryption key) to PHP

This package requires that the server environment variable CONFIGKEY be passed by your web service to your 
OctoberCMS application.  Your CONFIGKEY is either 16 characters (for AES-128-CBC cipher) or 32 characters long 
(for AES-256-CBC cipher)

For best security practices, this should be set up as a conditional pass based on what script is making the request. 
Ideally this should only be `public/index.php`.  By limiting the passing of CONFIGKEY to only index.php, it limits the 
ability of non-privileged users to retrieve the CONFIGKEY and your encrypted config values.  You should also make index.php read-only to non-privileged users.  These measures are obviously not full-proof, but it most likely will buy 
you more time to mitigate a disaster in the event your server is hacked or a malicious user attempts to retrieve the 
CONFIGKEY or one of your environment variables.

Your web service configuration files (nginx.conf, /etc/nginx/sites-available/*, httpd.conf, etc) should only be readable 
by the root user, otherwise your CONFIGKEY can be retrieved by a non-privileged shell user by simply opening the config file. Most apache and nginx setups run their parent process as root, so there should not be an issue of 
the configuration files being readable by apache/nginx.

An example configuration for nginx and apache are below.  Configuration of nginx and apache are beyond the scope 
of this README.  For help with nginx or apache, or for more information refer to:

[Apache Documentation Website](http://httpd.apache.org/docs/)

[Nginx Documentation Website](https://docs.nginx.com/nginx/admin-guide/)

****Apache Configuration Example****

VirtualHost block example with example CONFIGKEY
```
<VirtualHost *:80>
    DocumentRoot "/path/to/octobercms/docroot/public"
    ServerName yourlaravelapp.tld
    <If "%{SCRIPT_FILENAME} == '/path/to/octobercms/docroot/public/index.php'">
       SetEnv CONFIGKEY "51TMszQEvpAlVxbe"
    </If>
    ...
</VirtualHost>
```

****Nginx Configuration Example (using php-fpm)****

PHP location block example with example CONFIGKEY

```
    location ~ \.php$ {
        set $script_filename $document_root$fastcgi_script_name;
        set $configkey "";
        if ($script_filename = "/path/to/octobercms/docroot/public/index.php") {
          set $configkey "51TMszQEvpAlVxbe";
        }
        try_files $uri /index.php =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        ...
        fastcgi_param SCRIPT_FILENAME $script_filename;
        fastcgi_param CONFIGKEY $configkey;
        include fastcgi_params;
    }

```

The two examples above will pass CONFIGKEY into PHP's $_SERVER array as $_SERVER['CONFIGKEY'].

****What about IIS?****

Regarding IIS setups.  I have no idea how to do this in IIS, though I suspect it is possible.  If anyone
knows how to accomplish the same thing with the same level of security in an IIS server environment, feel free to do a pull 
request. Thanks!

## Documentation

1. [Preparing for Encryption](#preparing-for-encryption)
2. [Using the Encryption flag](#using-the-encryption-flag)
3. [Running the Console Command](#running-the-console-command)
4. [File Permissions](#file-permissions)


### Preparing for Encryption

#### 1. Backup your existing environment variables file

To prepare for encryption you should first backup your existing .env file, as this process will overwrite your file with the
encrypted values.  By design and for security reasons, it is not convenient to decrypt these values after the fact, as 
there is no console command to do so, and ideally the CONFIGKEY (encryption key) is not even available for decryption

#### 2. Ready your configuration files by using the secEnv helper function for config values you plan to encrypt

Update your OctoberCMS and package-specific config files and change the values of each variable or property that you
want to be encrypted to use the secEnv helper function (works exactly like the env() helper function)

Usage: secEnv('name','fallback_value')

Example mail.php config file:
```php
return [
    'driver' => env('MAIL_DRIVER', 'smtp'),
    'host' => secEnv('MAIL_HOST', 'smtp.somehost.com'),
    'port' => env('MAIL_PORT', 587),
    'username' => secEnv('MAIL_USERNAME'),
    'password' => secEnv('MAIL_PASSWORD'),
    ...
];
```
In the above example, the the use of the secEnv helper function in the host, username, and password values indicates that
the secEnv function should be used to retrieve the values of each of these keys, checking if the value in your .env or 
custom config file should be decrypted.  In the case of 'host', the value of MAIL_HOST in your environment file 
will be checked for an encrypted value.  If it is encrypted, the value will be decrypted to be readable by Laravel's 
Config class.  If it cannot find an encrypted value, it will assign the fallback value (smtp.somehost.com in the example).  
Just like the env() helper function, the fallback value for secEnv is optional.



### Using the Encryption Flag

Edit the environment variables file and add the encryption flag defined in 
`config/encryptenv.php` as a prefix to each value you want to encrypt

Example .env File with default Encrypt Flag !ENC:
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=!ENC:65UMszZRvpAPVxba

MYSQL_HOST=db.somehost.net
MYSQL_USER=!ENC:webapp_mysql
MYSQL_PASS=!ENC:Secure.P4ssW0rd!

SERVICE_API_KEY=!ENC:qZXFuZWh0NFE9PSIsInZhbHVlIjoidmNpRUV5em

```

The values for APP_KEY, MYSQL_USER, MYSQL_PASS, and SERVICE_API_KEY are flagged for encryption
and will be replaced with the encrypted string when running the console command (below).

### Running the Console Command

To run the encryption sequence in your environment variables file, execute the artisan console command included with this plugin

The artisan console command `encryptenv:encrypt` has one optional argument `configkey`.  Having the config key as an optional
argument allows you to add this console command to your own scripts for things like automation in your deployment process.
If you do use the configkey argument, it is recommended that you include safeguards to prevent this console command from 
being recorded in your shell's history (to protect your Config Key).     

More on this here:
https://stackoverflow.com/questions/6475524/how-do-i-prevent-commands-from-showing-up-in-bash-history

##### Generating a new CONFIGKEY (encryption key)

You will need to generate a new CONFIGKEY if you don't already have one.

If you put `generate-key` in the optional configkey argument, the encryptenv:encrypt artisan command will automatically
generate a new CONFIGKEY and encrypt the flagged values in your environment variables file.  

Upon completion of the encryption, it will display your new CONFIGKEY (See example below)

```console
$ php artisan encryptenv:encrypt generate-key
Done!

Your new generated CONFIGKEY is: UQvq72E7ZFXE2sUvW2QsaXGCEgXav2jK

DO NOT lose this key if you want to use the encrypted config values you just encrypted.
You will need to update your web service configuration file with this new CONFIGKEY
Refer to the Install [Configure your web service] section in the README for more info
```

##### Running Console Command With An Existing CONFIGKEY

If you already have a CONFIGKEY set up and configured for your web service, simply run the encryptenv:encrypt artisan
command as follows:

_Without the optional configkey argument_
```console
$ php artisan encryptenv:encrypt
```

_With the optional configkey argument_
```console
$ php artisan encryptenv:encrypt UQvq72E7ZFXE2sUvW2QsaXGCEgXav2jK
```

If you do not provide the configkey argument, you will be prompted for your Config Key,
either 16 characters or 32 characters long depending on which cipher you defined in `config/encryptenv.php`  

Enter your Config Key into the prompt and press Enter when you are ready to start the encryption sequence

Example with Config Key prompt:
```console
 Config Key (16 char key):
 > 95UMleZOvpAPVyba

Done!
```

When the command has completed the encryption sequence it will display "Done!"

Check your environment variables file to make sure the values you want encrypted are as you expect.  

Your .env file will look similar to this:
```php
APP_ENV=production
APP_DEBUG=false
APP_KEY=ENC:eyJpdiI6ImpBMlE0Q1VNK2J3MEdlWU9peSs0TFE9PSIsInZhbHVlIjoiNUpsbDNzVUw2RWpWUE1rXC9xQTliNjltT3hLZWNZS1JqTVNRWGZ6cjBNaUFQc3FrUVJObENWNW1SaTlOaTVKdVUiLCJtYWMaUFQc3FrUVJObENWNWDJhNjZhMmE3NmUyYmJkYjQ3ODMxZmFiNmQ0ZTgxZTkxZDA5N2RhMjk2MGZmYzM5NTY4ZjcyIn0=

MAILCHIMP_API_KEY=ENC:eyJpdiI6IkUzK0c2QmdIMlkwQW56MEtYd3o5ZGc9PSIsInZhbHVlIjoiTWRhZ1gxenZzUENaTFZCcVFJWmZIeTJ6NnpjZitzODVYMjROd2xyR295UTFXNhMWU3Y2xyR295UTFXbWkxZ3YrRnExNyIsIm1hYyI6IjM4N2MxZjM5MjIyMjRlZjAxZjc4xMnZ4TmRIODVYMjROd2xyR295UTFXNhMWU3YzZiZmNmY2JiYTQwNDQ1NmRjOTI4OTEifQ==

MAIL_USERNAME=ENC:eyJpdiMjkzMDkxOTY4MzY5OTUxMmFlMmYSIsInZhbHVlIjoieEo5ZjBzckRORnBQRlFTMDlQRkxlUXpXWjg0ME8zUFZvRHBCekhBXC9rXC9NPSIsIm1hYyI6IjFkZmJlMjQ5OWUxNGI0NWVkMTg4Yzk1ODE2OWU0YTJhZjQzMjkzMDkxOTY4MzY5OTUxMmFlMmY0MmMyZmIzNzMifQ==
MAIL_PASSWORD=ENC:eyJpdiI6IlU4a2lhMEFqa3hlcWZyQTlyOXd1c2c9PSIsInZhbHVlIjoiVENPRFwvUldBNDRMUzZGeHNvT1lsSlQzcU41bWsyZ25HcW8zdTJYKytkTVU9IiwibWFjIjoiYWY5MGIwMGJjMDk5MTJiNTcxYzQzODIyODZiMjEyOXd1c2c9PSIsInZhbHVlIjoiVENPRFwvU0OTMyMjUyZGVjOWUyNyJ9
```

If you set everything up correctly, OctoberCMS should now be working with your encrypted environment variable values.

Note: You should run `php artisan config:clear` to clear your config cache just to be sure everything is truly working.
