# Laravel Mandrill Driver

<a href="https://packagist.org/packages/davidvandertuijn/laravel-mandrill-driver"><img src="https://poser.pugx.org/davidvandertuijn/laravel-mandrill-driver/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/davidvandertuijn/laravel-mandrill-driver"><img src="https://poser.pugx.org/davidvandertuijn/laravel-mandrill-driver/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/davidvandertuijn/laravel-mandrill-driver"><img src="https://poser.pugx.org/davidvandertuijn/laravel-mandrill-driver/license.svg" alt="License"></a>

![Laravel Mandrill Driver](https://cdn.davidvandertuijn.nl/github/laravel-mandrill-driver.png)

This library adds Mandrill support to Laravel and the ability to capture the Mandrill response via an Event.

## Install

```
composer require davidvandertuijn/laravel-mandrill-driver
```

Add the MAIL_MAILER and MANDRILL_SECRET environment variables:

```
MAIL_MAILER=mandrill
MANDRILL_SECRET=your-api-key
```

 Add mandrill config to the config/services.php file:

```
'mandrill' => [
    'secret' => env('MANDRILL_SECRET'),
],
```

Add mandrill option to the config/mail.php "mailers" array:

```
'mandrill' => [
    'transport' => 'mandrill',
],
```
Publish config:

```
php artisan vendor:publish --provider="Davidvandertuijn\LaravelMandrillDriver\MandrillServiceProvider"
```

## Mandrill Message Sent Event (optional)

The Event should be registered in the App\Providers\EventServiceProvider.php $listen Array:

```
use App\Listeners\Mandrill\MessageSent as MandrillMessageSentListener;
use Davidvandertuijn\LaravelMandrillDriver\app\Events\MandrillMessageSent as MandrillMessageSentEvent;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MandrillMessageSentEvent::class => [
            MandrillMessageSentListener::class
        ],
    ];
}
```

Define the listener in App\Listeners\Mandrill\MessageSent.php:

```
namespace App\Listeners\Mandrill;

class MessageSent
{
    public function handle($event)
    {
        // Mandrill ID
        // $event->response[0]->_id
    }
}
```
