<?php

namespace Davidvandertuijn\LaravelMandrillDriver;

use App\Models\Mandrill\Log as MandrillLogModel;
use Davidvandertuijn\LaravelMandrillDriver\App\Events\MandrillError;
use Davidvandertuijn\LaravelMandrillDriver\App\Events\MandrillMessageSent;
use Exception;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mime\Part\DataPart;

class MandrillTransport extends AbstractTransport
{
    // Priority

    public const PRIORITY_HIGHEST = 1;
    public const PRIORITY_HIGH = 2;
    public const PRIORITY_NORMAL = 3;
    public const PRIORITY_LOW = 4;
    public const PRIORITY_LOWEST = 5;

    /**
     * Guzzle client instance.
     * @var ClientInterface
     */
    public $client;

    /**
     * The Mandrill API key.
     * @var string
     */
    public $key;

    /**
     * Create a new Mandrill transport instance.
     * @param \GuzzleHttp\ClientInterface $client
     * @param string $key
     */
    public function __construct(ClientInterface $client, $key)
    {
        $this->key = $key;
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return 'mandrill';
    }

    /**
     * Do Send.
     * @param SentMessage $message
     */
    public function doSend(SentMessage $message): void
    {
        $this->sendMandrillRequest($message);
    }

    /**
     * Fetch To.
     * @param SentMessage $sentMessage
     * @return array
     */
    public function fetchTo(SentMessage $sentMessage): array
    {
        $a = [];

        // TO

        $to = $sentMessage->getOriginalMessage()->getTo();

        if ($to && is_array($to) && count($to) > 0) {
            foreach ($to as $o) {
                $a[] = [
                    'email' => $o->getAddress(),
                    'name' => $o->getName(),
                    'type' => 'to'
                ];
            }
        }

        // CC

        $cc = $sentMessage->getOriginalMessage()->getCc();

        if ($cc && is_array($cc) && count($cc) > 0) {
            foreach ($cc as $o) {
                $a[] = [
                    'email' => $o->getAddress(),
                    'name' => $o->getName(),
                    'type' => 'to'
                ];
            }
        }

        // BCC

        $bcc = $sentMessage->getOriginalMessage()->getBcc();

        if ($bcc && is_array($bcc) && count($bcc) > 0) {
            foreach ($bcc as $o) {
                $a[] = [
                    'email' => $o->getAddress(),
                    'name' => $o->getName(),
                    'type' => 'to'
                ];
            }
        }

        return $a;
    }

    /**
     * Get all the addresses this message should be sent to.
     * Note that Mandrill still respects CC, BCC headers in raw message itself.
     * @param SentMessage $sentMessage
     * @return array
     */
    public function getTo(SentMessage $sentMessage): array
    {
        $to = [];

        if ($sentMessage->getOriginalMessage()->getTo()) {
            $to = array_merge($to, array_keys($sentMessage->getOriginalMessage()->getTo()));
        }

        if ($sentMessage->getOriginalMessage()->getCc()) {
            $to = array_merge($to, array_keys($sentMessage->getOriginalMessage()->getCc()));
        }

        if ($sentMessage->getOriginalMessage()->getBcc()) {
            $to = array_merge($to, array_keys($sentMessage->getOriginalMessage()->getBcc()));
        }

        return $to;
    }

    /**
     * Send Mandrill Request.
     * @see https://mandrillapp.com/api/docs/messages.curl.html
     * @param SentMessage $sentMessage
     * @throws Exception
     */
    public function sendMandrillRequest(SentMessage $sentMessage)
    {
        // URL
        $url = Config::get('mandrill.url').'/messages/send.json';

        // Arguments

        $arguments['key'] = $this->key;
        $arguments['message']['html'] = $sentMessage->getOriginalMessage()->getHtmlBody();
        $arguments['message']['subject'] = $sentMessage->getOriginalMessage()->getSubject();
        $arguments['message']['from_email'] = env('MAIL_FROM_ADDRESS');
        $arguments['message']['from_name'] = env('MAIL_FROM_NAME') ?? 'Mandrill Mailer.';
        $arguments['message']['to'] = $this->fetchTo($sentMessage);
        $arguments['async'] = true;

        // Reply-To

        $replyTo = $sentMessage->getOriginalMessage()->getReplyTo();

        if ($replyTo && is_array($replyTo) && count($replyTo) > 0) {
            $replyTo = array_key_first($replyTo);
            $arguments['message']['headers']['Reply-To'] = $replyTo;
        }

        // Important

        if (in_array($sentMessage->getOriginalMessage()->getPriority(), [
            self::PRIORITY_HIGHEST,
            self::PRIORITY_HIGH
        ])) {
            $arguments['message']['important'] = true;
        }

        // Attachments

        $rawAttachments = $this->fetchAttachments($sentMessage);

        $attachments = [];

        foreach ($rawAttachments as $rawAttachment) {
            $attachments[] = [
                'type' => $rawAttachment['type'],
                'name' => $rawAttachment['filename'],
                'content' => $rawAttachment['content']
            ];
        }

        if ($attachments) {
            $arguments['message']['attachments'] = $attachments;
        }

        // CURL Request

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arguments));

        $response = curl_exec($ch);

        if ($errno = curl_errno($ch)) {
            $strerror = curl_strerror($errno);

            // Event
            event(new MandrillError($sentMessage, $arguments, $strerror));

            throw new Exception($strerror);
        } else {
            // Event
            event(new MandrillMessageSent($sentMessage, $arguments, json_decode($response)));

            return json_decode($response);
        }
    }

    /**
     * Fetch Attachments.
     * @param SentMessage $sentMessage
     * @return array $attachments
     */
    public function fetchAttachments(SentMessage $sentMessage): array
    {
        $attachments = [];

        foreach ($sentMessage->getOriginalMessage()->getAttachments() as $attachment) {
            if (get_class($attachment) === DataPart::class) {
                $attachments[] = [
                    'content' => base64_encode($attachment->getBody()),
                    'filename' => $attachment->getFilename(),
                    'type' => $attachment->getContentType()
                ];
            }
        }

        return $attachments;
    }
}
