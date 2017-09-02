<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Domain\Application;

use Desperado\Framework\Domain\Context\ContextInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;

/**
 * Kernel
 */
interface KernelInterface
{
    /**
     * Run application
     *
     * @param array $clients
     *
     * @return void
     */
    public function run(array $clients): void;

    /**
     * Terminate application
     *
     * @return void
     */
    public function terminate(): void;

    /**
     * Handle message
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return void
     */
    public function handleMessage(MessageInterface $message, ContextInterface $context): void;
}