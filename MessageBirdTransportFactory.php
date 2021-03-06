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

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class MessageBirdTransportFactory extends AbstractTransportFactory
{
    /**
     * @return MessageBirdTransport
     */
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();

        if ('messagebird' !== $scheme) {
            throw new UnsupportedSchemeException($dsn, 'messagebird', $this->getSupportedSchemes());
        }

        $authToken = $this->getUser($dsn);
        $originator = $dsn->getOption('originator');
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return (new MessageBirdTransport($authToken, $originator, $this->client, $this->dispatcher))->setHost($host)->setPort($port);
    }

    protected function getSupportedSchemes(): array
    {
        return ['messagebird'];
    }
}