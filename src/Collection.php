<?php

namespace samsoncms\email;

use samson\activerecord\dbQuery;
use samsoncms\field\Generic;
use samsonframework\orm\QueryInterface;
use wundermodel\cms\newsletter\field\Status;
use wundermodel\cms\newsletter\field\Template;
use wundermodel\cms\newsletter\field\Control;

class Collection extends \samsoncms\Collection
{
    /** {@inheritdoc} */
    public function __construct($renderer, $query = null, $pager = null)
    {
        // Fill default column fields for collection
        $this->fields = array(
            new Generic('distribution_id', t('#', true), 0, '', 0),
            new Template('template_id', t('Template', true), 0),
            new Generic('recipient_count', t('Recipients count', true), 0, '', 0),
            new Generic('open_count', t('Opens count', true), 0, '', 0),
            new Generic('click_count', t('Clicks count', true), 0, '', 0),
            new Status('status', t('Status', true), 0),
            new Generic('ts', t('Created', true), 10, '', 0),
            new Generic('finished', t('Finished', true), 10, '', 0),
            new Control(),
        );

        // Call parents
        parent::__construct($renderer, $query, $pager);

        $this->fill();
    }
}
