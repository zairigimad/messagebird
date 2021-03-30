<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\MessageBird;

use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class MessageBirdTransport extends AbstractTransport
{
    protected const HOST = 'rest.messagebird.com';

    private $authToken;
    private $originator;

    public function __construct(string $authToken, string $originator, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->authToken = $authToken;
        $this->originator = $originator;

        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        return sprintf('messagebird://%s@default?originator=%s', $this->getEndpoint(), $this->originator);
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $response = $this->client->request('POST', 'https://'.$this->getEndpoint().'/messages', [
            'json' => [
                'originator' => $this->originator,
                'recipients' => $message->getPhone(),
                'body' => $message->getSubject(),
            ],
            'headers' => [
                'Authorization' => sprintf('AccessKey %s', $this->authToken),
            ],
        ]);

        if (201 !== $response->getStatusCode()) {
            $error = $response->toArray(false);

            throw new TransportException(sprintf('Unable to send the SMS: "%s".', $error['message']), $response);
        }

        $success = $response->toArray(false);

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($success['id']);

        return $sentMessage;
    }
}