<?php

/**
 * Class Redkiwi_Rkvatfallback_Model_DiContainer
 * should implement PSR11 ContainerInterface, not so due to backwards compatibility
 */
class Redkiwi_Rkvatfallback_Model_DiContainer
{
    /**
     * @var array
     */
    protected $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws Exception Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new \Exception('Error while retrieving the entry');
        }

        return $this->services[$id];
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->services[$id]);
    }
}