<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Application\DependencyInjection\Compiler;

use function ServiceBus\Common\canonicalizeFilesPath;
use function ServiceBus\Common\extractNamespaceFromFile;
use function ServiceBus\Common\searchFiles;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 *
 */
final class ImportMessageHandlersCompilerPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     *
     * @param ContainerBuilder $container
     *
     * @return void
     *
     * @throws \LogicException
     * @throws \ServiceBus\Common\Exceptions\File\LoadContentFailed
     * @throws \ServiceBus\Common\Exceptions\File\NonexistentFile
     */
    public function process(ContainerBuilder $container): void
    {
        if(true === self::enabled($container))
        {
            $excludedFiles = canonicalizeFilesPath(self::getExcludedFiles($container));

            $files = searchFiles(self::getDirectories($container), '/\.php/i');

            $this->registerClasses($container, $files, $excludedFiles);
        }
    }

    /**
     * @param ContainerBuilder         $container
     * @param \Generator<\SplFileInfo> $generator
     * @param array<int, string>       $excludedFiles
     *
     * @return void
     *
     * @throws \LogicException
     * @throws \ServiceBus\Common\Exceptions\File\LoadContentFailed
     * @throws \ServiceBus\Common\Exceptions\File\NonexistentFile
     */
    private function registerClasses(ContainerBuilder $container, \Generator $generator, array $excludedFiles): void
    {
        /**
         * @var \SplFileInfo $file
         */
        foreach($generator as $file)
        {
            $filePath = $file->getRealPath();

            if(true === \in_array($filePath, $excludedFiles, true))
            {
                continue;
            }

            $class = extractNamespaceFromFile($filePath);

            if(
                null !== $class &&
                true === self::isMessageHandler($filePath) &&
                false === $container->hasDefinition($class)
            )
            {
                $container->register($class, $class)->addTag('service_bus.service');
            }
        }
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private static function enabled(ContainerBuilder $container): bool
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return true === $container->hasParameter('service_bus.auto_import.handlers_enabled')
            ? (bool) $container->getParameter('service_bus.auto_import.handlers_enabled')
            : false;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     *
     * @throws \LogicException Error loading file contents
     */
    private static function isMessageHandler(string $filePath): bool
    {
        $fileContent = \file_get_contents($filePath);

        if(false !== $fileContent)
        {
            return false !== \strpos($fileContent, '@CommandHandler') ||
                false !== \strpos($fileContent, '@EventListener');
        }

        throw new \LogicException(
            \sprintf('Error loading "%s" file contents', $filePath)
        );
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param ContainerBuilder $container
     *
     * @return array<int, string>
     */
    private static function getDirectories(ContainerBuilder $container): array
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         * @var array<int, string> $directories
         */
        $directories = true === $container->hasParameter('service_bus.auto_import.handlers_directories')
            ? $container->getParameter('service_bus.auto_import.handlers_directories')
            : [];

        return $directories;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param ContainerBuilder $container
     *
     * @return array<int, string>
     */
    private static function getExcludedFiles(ContainerBuilder $container): array
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         * @var array<int, string> $excludedFiles
         */
        $excludedFiles = true === $container->hasParameter('service_bus.auto_import.handlers_excluded')
            ? $container->getParameter('service_bus.auto_import.handlers_excluded')
            : [];

        return $excludedFiles;
    }
}
