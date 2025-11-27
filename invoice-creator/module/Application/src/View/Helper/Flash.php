<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Session\Container;

class Flash extends AbstractHelper
{
    /**
     * Return and clear queued flash messages from session.
     * Each message is an array with keys: 'type' and 'text'.
     * Example: ['type' => 'success', 'text' => 'Saved']
     *
     * @return array<int, array{type:string,text:string}>
     */
    public function __invoke(): array
    {
        $container = new Container('flash');
        $messages = [];
        if ($container->offsetExists('messages')) {
            $messages = (array)$container->messages;
            // clear after fetching
            unset($container->messages);
        }
        return $messages;
    }
}
