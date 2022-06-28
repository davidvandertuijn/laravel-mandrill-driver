<?php

namespace Davidvandertuijn\LaravelMandrillDriver\app\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class MandrillMessageSent
{
    use Dispatchable;
    use SerializesModels;

    public $sentMessage;
    public $arguments;
    public $response;

    public function __construct($sentMessage, $arguments, $response)
    {
        $this->sentMessage = $sentMessage;
        $this->arguments = $arguments;
        $this->response = $response;
    }
}
