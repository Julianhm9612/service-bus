<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Infrastructure\MessageSerialization;

use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 * Decode a message string into an object
 */
interface MessageDecoder
{
    /**
     * Restore message from string
     *
     * @param string $serializedMessage
     *
     * @return Message
     *
     * @throws \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed
     */
    public function decode(string $serializedMessage): Message;
}
