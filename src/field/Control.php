<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 21.04.2015
 * Time: 14:52
 */
namespace samsoncms\email\field;

use samsoncms\field\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;

/**
 * Overridden control field
 * @package samsoncms\app\user
 */
class Control extends Generic
{
    /** @var string Path to field view file */
    protected $innerView = 'www/field/control';

    /**  Overload parent constructor and pass needed params there */
    public function __construct()
    {
        parent::__construct('control', t('Управление', true), 0, 'control', false);
    }

    /**
     * Render collection entity field inner block
     * @param RenderInterface $renderer
     * @param QueryInterface $query
     * @param mixed $object Entity object instance
     * @return string Rendered entity field
     */
    public function render(RenderInterface $renderer, QueryInterface $query, $object)
    {
        // Render input field view
        return $renderer
            ->view($this->innerView)
            ->set('class', $this->css)
            ->set($object, 'item')
            ->output();
    }
}
