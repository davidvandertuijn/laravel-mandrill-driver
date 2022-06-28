<?php

namespace Davidvandertuijn\LaravelMandrillDriver\app\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class MandrillError
{
    use Dispatchable;
    use SerializesModels;

    public $sentMessage;
    public $arguments;
    public $strerror;

    public function __construct($sentMessage, $arguments, $strerror)
    {
        $this->sentMessage = $sentMessage;
        $this->arguments = $arguments;
        $this->strerror = $strerror;
    }
}
