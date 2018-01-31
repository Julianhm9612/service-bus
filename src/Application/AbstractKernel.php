<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application;

use Desperado\Saga\Service\Exceptions as SagaServiceExceptions;
use Desperado\Saga\Service\SagaService;
use Desperado\ServiceBus\EntryPoint\EntryPointContext;
use Desperado\ServiceBus\Extensions\Logger\ServiceBusLogger;
use Desperado\ServiceBus\KernelEvents;
use Desperado\ServiceBus\MessageBus\MessageBus;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Desperado\ServiceBus\Services;
use Desperado\ServiceBus\Task\CompletedTask;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use React\Promise\RejectedPromise;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Base application kernel
 */
abstract class AbstractKernel
{
    /**
     * Message bus factory
     *
     * @var MessageBusBuilder
     */
    private $messageBusBuilder;

    /**
     * Sagas service
     *
     * @var SagaService
     */
    private $sagaService;

    /**
     * Message bus
     *
     * @var MessageBus
     */
    private $messageBus;

    /**
     * Event dispatcher
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param MessageBusBuilder $messageBusBuilder
     * @param SagaService       $sagaService
     * @param EventDispatcher   $dispatcher
     *
     * @throws Services\Exceptions\ServiceConfigurationExceptionInterface
     * @throws SagaServiceExceptions\ClosedMessageBusException
     */
    final public function __construct(
        MessageBusBuilder $messageBusBuilder,
        SagaService $sagaService,
        EventDispatcher $dispatcher
    )
    {
        $this->messageBusBuilder = $messageBusBuilder;
        $this->eventDispatcher = $dispatcher;
        $this->sagaService = $sagaService;

        $this->configureSagas();

        $this->messageBus = $messageBusBuilder->build();
    }

    /**
     * Handle message
     *
     * @param EntryPointContext        $entryPointContext
     * @param AbstractExecutionContext $executionContext
     *
     * @return PromiseInterface
     *
     * @throws \Throwable
     */
    final public function handle(EntryPointContext $entryPointContext, AbstractExecutionContext $executionContext): PromiseInterface
    {
        $this->eventDispatcher->dispatch(
            KernelEvents\MessageIsReadyForProcessingEvent::EVENT_NAME,
            new KernelEvents\MessageIsReadyForProcessingEvent($entryPointContext, $executionContext)
        );

        try
        {
            $promise = $this->messageBus->handle(
                $entryPointContext->getMessage(),
                $executionContext
            );

            if(false === ($promise instanceof PromiseInterface))
            {
                $promise = new  FulfilledPromise();
            }
        }
        catch(\Throwable $throwable)
        {
            $promise = new RejectedPromise($throwable);
        }

        return $promise
            ->then(
                function(array $promisesResult = null) use ($entryPointContext, $executionContext)
                {
                    if(null !== $promisesResult)
                    {
                        foreach($promisesResult as $completedTask)
                        {
                            /** @var CompletedTask $completedTask */

                            $completedTask
                                ->getTaskResult()
                                ->then(
                                    function() use ($completedTask, $entryPointContext, $executionContext)
                                    {
                                        return $completedTask->getContext()->getOutboundMessageContext();
                                    },
                                    function(\Throwable $throwable)
                                    {
                                        /** @todo: fix me */
                                        ServiceBusLogger::throwable('rejectedPromise', $throwable);
                                    }
                                );
                        }
                    }

                    $this->eventDispatcher->dispatch(
                        KernelEvents\MessageProcessingCompletedEvent::EVENT_NAME,
                        new KernelEvents\MessageProcessingCompletedEvent($entryPointContext, $executionContext)
                    );

                    return $executionContext->getOutboundMessageContext();
                },
                function(\Throwable $throwable) use ($entryPointContext, $executionContext)
                {
                    ServiceBusLogger::throwable('rejectedPromise', $throwable);

                    $this->eventDispatcher->dispatch(
                        KernelEvents\MessageProcessingFailedEvent::EVENT_NAME,
                        new KernelEvents\MessageProcessingFailedEvent($throwable, $entryPointContext, $executionContext)
                    );

                    return $throwable;
                }
            );
    }

    /**
     * Get sagas list
     *
     * [
     *     0 => 'someSagaNamespace',
     *     1 => 'someSagaNamespace',
     *     ....
     * ]
     *
     *
     * @return array
     */
    protected function getSagasList(): array
    {
        return [];
    }

    /**
     * Get message bus builder
     *
     * @return MessageBusBuilder
     */
    final protected function getMessageBusBuilder(): MessageBusBuilder
    {
        return $this->messageBusBuilder;
    }

    /**
     * Process saga configuration
     *
     * @return void
     *
     * @throws SagaServiceExceptions\ClosedMessageBusException
     */
    private function configureSagas(): void
    {
        foreach($this->getSagasList() as $saga)
        {
            $this->sagaService->configure($saga);

            /** Add saga listeners to message bus */

            foreach($this->sagaService->getSagaListeners($saga) as $listener)
            {
                $this->messageBusBuilder->pushMessageHandler(
                    Services\Handlers\Messages\MessageHandlerData::new(
                        $listener->getEventNamespace(),
                        $listener->getHandler(),
                        [],
                        new Services\Handlers\Messages\EventExecutionParameters('sagas')
                    )
                );
            }
        }
    }
}
