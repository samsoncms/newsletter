<?php

namespace samsoncms\newsletter\field;

use samsoncms\field\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;

/**
 * Overridden control field
 * @package samsoncms\app\user
 */
class Status extends Generic
{
    /** @var string Path to field view file */
    protected $innerView = 'www/field/status';

    /**
     * Render collection entity field inner block
     * @param RenderInterface $renderer
     * @param QueryInterface $query
     * @param mixed $object Entity object instance
     * @return string Rendered entity field
     */
    public function render(RenderInterface $renderer, QueryInterface $query, $object)
    {
        $status = '';
        switch ($object->status) {
            case 0:
                $status = 'New';
                break;
            case 1:
                $status = 'Delivered';
                break;
            case 2:
                $status = 'Declined';
                break;
            case 3:
                $status = 'Deleted';
                break;
        }
        // Render input field view
        return $renderer
            ->view($this->innerView)
            ->set('class', $this->css)
            ->set($object, 'item')
            ->set('status', $status)
            ->output();
    }
}
