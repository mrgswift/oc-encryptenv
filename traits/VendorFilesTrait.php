<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace MG\EncryptEnv\Traits;

use October\Rain\Exception\ApplicationException;

trait VendorFilesTrait
{
    protected function checkRequiredFiles()
    {
        //Check for required files and copy from vendor package if they don't exist

        //Config file (if not exists assume first execution)
        if (!file_exists(base_path().'/config/encryptenv.php')) {

            if (!file_exists(base_path('/vendor/mrgswift/laravel-encryptenv'))) {
                throw new ApplicationException('Vendor package mrgswift/laravel-encryptenv not found in vendor path.  Did you install it with composer?');
            }

            copy(base_path('/vendor/mrgswift/laravel-encryptenv/config/encryptenv.php'), base_path('/config/encryptenv.php'));

            $composerjson_path = base_path('/composer.json');

            //Attempt to add autoload block with file entry for secEnv helper
            $composerobj = json_decode(file_get_contents($composerjson_path));
            if (empty($composerobj->autoload) && empty($composerobj->autoload->files)) {

                //Add autoload block
                $composerobj->autoload = (object)Array();
                $composerobj->autoload->files = ['plugins/mg/encryptenv/helpers/secEnv.php'];

                //Remove any escape characters from paths in new composer.json file
                $composerjson = str_replace('\/','/', json_encode($composerobj, JSON_PRETTY_PRINT));

                //Backup existing composer.json (just in case)
                copy($composerjson_path, base_path('/composer.json.orig'));

                //Write new composer.json file with added autoload block
                file_put_contents($composerjson_path, $composerjson);
            } else {
                $throwexception = true;

                //Check if required autoload block already exists
                if (!empty($composerobj->autoload->files) && is_array($composerobj->autoload->files)) {
                    foreach ($composerobj->autoload->files as $file) {
                        $file == 'plugins/mg/encryptenv/helpers/secEnv.php' && $throwexception = false;
                    }
                }

                if ($throwexception) { throw new ApplicationException('Unable to automatically add file autoloader to composer.json file.  Refer to documentation for manual installation.'); }
            }
        }
    }
}
