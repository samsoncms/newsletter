<?php

namespace samsoncms\email;

use Aws\Common\Credentials\Credentials;
use Aws\Ses\SesClient;
use samson\activerecord\client;
use samson\activerecord\email_distribution;
use samson\activerecord\email_letter;
use samson\activerecord\email_template;
use samson\core\SamsonLocale;
use samson\instagram\Instagram;
use samsoncms\api\OutfitQuery;
use samsoncms\api\Product;
use samsoncms\api\ProductQuery;
use samsonframework\orm\Relation;
use samsonphp\event\Event;
use samson\activerecord\dbQuery;

require_once 'Collection.php';
require_once 'field/Template.php';
require_once 'field/Control.php';
require_once 'field/Status.php';

class Application extends \samsoncms\Application
{
    /** @var string Module identifier */
    protected $id = 'distribution';

    /** @var string Entity class name */
    protected $entity = '\samson\activerecord\email_distribution';

    /** Application name */
    public $name = 'Email';

    /** Application description */
    public $description = 'Email distribution management';

    /** @var string $icon Icon class */
    public $icon = 'envelope';

    public $hide = true;

    public function prepare()
    {
        db()->execute(
            "
                CREATE TABLE IF NOT EXISTS `email_distribution` (
                  `distribution_id` int(11) NOT NULL AUTO_INCREMENT,
                  `template_id` int(11) NOT NULL,
                  `status` int(11) DEFAULT '0',
                  `recipient_count` int(11) DEFAULT '0',
                  `bounce_count` int(11) DEFAULT '0',
                  `open_count` int(11) DEFAULT '0',
                  `click_count` int(11) DEFAULT '0',
                  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `finished` datetime NOT NULL,
                  `active` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`distribution_id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
            "
        );

        db()->execute(
            "
                CREATE TABLE IF NOT EXISTS `email_letter` (
                  `letter_id` int(11) NOT NULL AUTO_INCREMENT,
                  `distribution_id` int(11) NOT NULL,
                  `template_id` int(11) NOT NULL,
                  `recipient` varchar(50) NOT NULL,
                  `status` int(11) DEFAULT '0',
                  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`letter_id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
            "
        );

        db()->execute(
            "
                CREATE TABLE IF NOT EXISTS `email_template` (
                  `template_id` int(11) NOT NULL AUTO_INCREMENT,
                  `content` text NOT NULL,
                  `locale` varchar(5) NOT NULL DEFAULT 'en',
                  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`template_id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
            "
        );

        return parent::prepare();
    }

    /**
     * Get list of users that are subscribed for newsletters
     */
    protected function getSubscribedUsers()
    {
        return dbQuery('user')->exec();
    }

    /**
     * Universal controller action.
     * Entity collection rendering
     */
    public function __template()
    {
        $description = t($this->description, true);
        $name = t($this->description, true);

        $clients = $this->getSubscribedUsers();

        $selectClient = '<select multiple id="emailClientSelect" name="recipients">';
        foreach ($clients as $client) {
            $selectClient .= '<option value="'.$client->id.'">'.$client->email.'</option>';
        }
        $selectClient .= '</select>';

        $subscribersCount = sizeof($clients);

        $letter = $this->view('letter/index')->outfits('')->letterHeader(t('Preview', true))->letterMessage(t('Preview', true))->output();

        // Prepare view
        $this->title($description)
            ->view('form')
            ->set('name', $name)
            ->set('icon', $this->icon)
            ->set('letter', $letter)
            ->subscribersCount($subscribersCount)
            ->clientSelect($selectClient)
            ->set('description', $description)
        ;
    }

    public function __openaction($distribution_id)
    {
        /** @var email_distribution $distribution */
        $distribution = null;
        if (dbQuery('email_distribution')->where('distribution_id', $distribution_id)->first($distribution)) {
            $distribution->open_count ++;
            $distribution->save();
        }
    }

    public function __clickaction($distribution_id, $params = '')
    {
        /** @var email_distribution $distribution */
        $distribution = null;
        if (dbQuery('email_distribution')->where('distribution_id', $distribution_id)->first($distribution)) {
            $distribution->click_count ++;
            $distribution->save();

            $params = explode('-', $params);
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/';
            if (sizeof($params)) {
                foreach ($params as $param) {
                    $url .= $param.'/';
                }
            }

            header('Location: '.$url);die;
        }
    }

    public function __async_templates($id)
    {
        /** @var $this->entity $outfit */
        $template = null;
        $response = array('status' => 0, 'popup' => '');
        if (dbQuery('email_template')->where('template_id', $id)->first($template)) {
            $response['popup'] = $this->view('template/popup')->content($template->content)->output();
            $response['status'] = 1;
        }

        return $response;
    }

    public function createLetter($header, $message)
    {
        return '<h1>'.$header.'</h1><br><div>'.$message.'</div>';
    }

    /**
     * @param int $preview Flag - send letters or no
     * @return array
     */
    public function __async_make($preview = 0)
    {
        /** @var client[] $recipients */
        $recipients = null;
        // Check recipient count
        if (isset($_POST['allRecipients']) && $_POST['allRecipients'] == 'on') {
            $recipients = $this->getSubscribedUsers();
        } else {
            $recipients = explode(',', $_POST['_orderrecipients']);
            array_pop($recipients);
            $recipients = dbQuery('user')->where('user_id', $recipients)->exec();
        }

        $letters = array();
        foreach (SamsonLocale::$locales as $locale) {
            if (isset($_POST['header-'.$locale]) && isset($_POST['message-'.$locale])) {
                $letters[$locale] = $this->createLetter($_POST['header-'.$locale], $_POST['message-'.$locale]);
            }
        }

        if (!$preview) {
            $templates = array();

            foreach ($letters as $locale => $letter) {
                $template = new email_template();
                $template->content = $letter;
                $template->locale = $locale;
                $template->save();
                $templates[$locale] = $template;
            }

            $distribution = new email_distribution();
            $distribution->template_id = $templates[SamsonLocale::$defaultLocale]->template_id;
            $distribution->recipient_count = sizeof($recipients);
            $distribution->active = 1;
            $distribution->save();
            // Send emails to all users from POST array
            foreach ($recipients as $recipient) {
                $emailLetter = new email_letter();
                $emailLetter->distribution_id = $distribution->distribution_id;
                $emailLetter->recipient = $recipient->email;
                $emailLetter->template_id = $templates[SamsonLocale::$defaultLocale]->template_id;
                $emailLetter->save();
            }
        }

        return array('status' => 1, 'preview' => $letters[SamsonLocale::$defaultLocale]);
    }

    /**
     * Send manual newsletters
     */
    public function __manual()
    {
        /** @var email_letter[] $letters */
        $letters = dbQuery('email_letter')->where('status', 0)->exec();

        foreach ($letters as $letter) {
            $template = dbQuery('email_template')->where('template_id', $letter->template_id)->first();
            $message = $template->content;
            $img = '<img width="1" height="1" src="'.str_replace('wundermodel.local', 'wundermodel.local.samsonos.com', url()->build('newsletter/openaction/'.$letter->distribution_id)).'">';
            $message .= $img;
            $message = str_replace('clickaction/', 'clickaction/'.$letter->distribution_id.'/', $message);
            $this->sendEmail($message, $letter->recipient);
            $letter->status = 1;
            $letter->save();
        }
    }

    /**
     * @param string $letter Letter html content
     * @param string $recipient email recipient
     */
    public function sendEmail($letter, $recipient = 'onysko@samsonos.com')
    {
        $client = SesClient::factory(array(
            'credentials' => new Credentials('AKIAJWWRY2GTVOEJAYLA', 'HHXrswBKtG9jmj5ky7W9ZYPHOWYQOe8e99rM+dRO'),
            'region' => 'eu-west-1'
        ));

        $result = $client->sendEmail(array(
            // Source is required
            'Source' => 'Name <noreply@wundermodel.com>',
            // Destination is required
            'Destination' => array(
                'ToAddresses' => array($recipient),
            ),
            // Message is required
            'Message' => array(
                // Subject is required
                'Subject' => array(
                    // Data is required
                    'Data' => 'WUNDERMODEL',
                ),
                // Body is required
                'Body' => array(
                    'Html' => array(
                        // Data is required
                        'Data' => $letter,
                    ),
                ),
            ),
        ));
    }
}
