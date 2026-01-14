<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DeprecationSuppressor implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 2048],
        ];
    }

    public function onKernelRequest(KernelEvent $event): void
    {
        // Suppress PHP 8.4 deprecation warnings
        @ini_set('display_errors', '0');
        @ini_set('assert.warning', '0');
        
        // Set error handler to filter deprecation warnings
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // Suppress E_DEPRECATED, E_USER_DEPRECATED, and E_STRICT warnings
            if (in_array($errno, [E_DEPRECATED, E_USER_DEPRECATED, E_STRICT], true)) {
                return true; // Suppress the warning
            }
            return false; // Let other errors through
        }, E_ALL);
    }
}
