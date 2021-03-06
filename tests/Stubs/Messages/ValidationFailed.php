<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Stubs\Messages;

use ServiceBus\Services\Contracts\ValidationFailedEvent;

/**
 *
 */
final class ValidationFailed implements ValidationFailedEvent
{
    /**
     * Request Id
     *
     * @var string
     */
    private $correlationId;

    /**
     * List of validate violations
     *
     * [
     *    'propertyPath' => [
     *        0 => 'some message',
     *        ....
     *    ]
     * ]
     *
     * @var array<string, array<int, string>>
     */
    private $violations;

    /**
     * @inheritDoc
     */
    public static function create(string $correlationId, array $violations): ValidationFailedEvent
    {
        $self = new self();

        $self->correlationId = $correlationId;
        $self->violations    = $violations;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function correlationId(): string
    {
        return $this->correlationId;
    }

    /**
     * @inheritDoc
     */
    public function violations(): array
    {
        return $this->violations;
    }
}
