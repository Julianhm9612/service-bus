<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\ArgumentResolvers;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Messages\Message;
use ServiceBus\Common\MessageHandler\MessageHandlerArgument;

/**
 *
 */
final class MessageArgumentResolver implements ArgumentResolver
{
    /**
     * @inheritdoc
     */
    public function supports(MessageHandlerArgument $argument): bool
    {
        return $argument->isA(Message::class);
    }

    /**
     * @inheritdoc
     *
     * @return Message
     */
    public function resolve(Message $message, ServiceBusContext $context, MessageHandlerArgument $argument): Message
    {
        return $message;
    }
}
