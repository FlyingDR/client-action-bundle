<?php

namespace Flying\Bundle\ClientActionBundle\State;

/**
 * Interface for structure components that supports
 * exporting their contents to client side of application
 */
interface ClientExportInterface
{
    /**
     * Get structure component representation suitable for client side of application
     *
     * @return mixed
     */
    public function toClient();
}
