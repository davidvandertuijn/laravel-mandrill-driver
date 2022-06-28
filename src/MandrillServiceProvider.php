<?php

namespace Davidvandertuijn\LaravelMandrillDriver;

use GuzzleHttp\Client;
use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Mailchimp\Transport\MandrillTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class MandrillServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Register paths to be published by the publish command.

        $this->publishes([
            __DIR__.'/config/mandrill.php' => config_path('mandrill.php'),
        ]);

        // Register a new resolving callback.

        $this->app->resolving(MailManager::class, function (MailManager $manager) {

            //  Register a custom transport creator Closure.

            $manager->extend('mandrill', function () {
                $config = $this->app['config']->get('services.mandrill', []);

                // Create a new Mandrill transport instance.

                return new MandrillTransport(
                    new Client($config),
                    $config['secret']
                );
            });
        });
    }
}
