<?php

namespace samsoncms\email\field;


use samsoncms\field\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;

class Template extends Generic
{
    public $innerView = 'www/field/template';
}
