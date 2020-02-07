<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System;


/**
 * Dependency injection container.
 * 
 * This class extends on Pimple ( https://github.com/silexphp/Pimple ).
 *
 * @since 0.1
 * @link https://pimple.symfony.com/ Document.
 * @link https://github.com/silexphp/Pimple Document.
 */
final class Container extends \Pimple\Container implements \Psr\Container\ContainerInterface
{

    /**
     * {@inheritDoc}
     */
    public function __construct(array $items = [])
    {
        parent::__construct($items);
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        return $this->offsetGet($id);
    }// get


    /**
     * {@inheritDoc}
     */
    public function has($id): bool
    {
        return $this->offsetExists($id);
    }// has


}
