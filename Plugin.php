<?php namespace MG\EncryptEnv;

use MG\EncryptEnv\Traits\VendorFilesTrait;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    use VendorFilesTrait;

    public function pluginDetails()
    {
        return [
            'name'        => 'mg.encryptenv::lang.plugin.name',
            'description' => 'mg.encryptenv::lang.plugin.description',
            'author'      => 'Matthew Guillot',
            'icon'        => 'icon-lock'
        ];
    }
    public function boot()
    {
        //Check for required composer.json entries and for required files
        $this->checkRequiredFiles();
    }
    public function register()
    {
        $this->registerConsoleCommand('encryptenv:encrypt', 'MG\EncryptEnv\Command\EncryptEnvValues');
    }
}
