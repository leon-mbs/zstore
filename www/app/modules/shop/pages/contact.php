<?php

namespace App\Modules\Shop\Pages;

use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;

class Contact extends Base
{

    public function __construct() {
        parent::__construct();
        $shop = \App\System::getOptions("shop");

        $this->_tvars['contact'] = base64_decode($shop['contact']);

        $this->add(new Form('messageform'))->onSubmit($this, 'msgOnClick');
        $this->messageform->add(new TextArea('smessage'));
        $this->messageform->add(new TextInput('scontact'));
        $this->messageform->add(new TextInput('sname'));
    }

    public function msgOnClick($sender) {
        $shop = \App\System::getOptions("shop");

        $message = "<br>Имя: " . $this->messageform->sname->getText();
        $message .= "<br>Контакт: " . $this->messageform->scontact->getText();
        $message .= "<br><br>Сообщение: " . $this->messageform->smessage->getText();
        $message .= "<br>";

        \App\Helper::sendLetter($message, $shop['email'], $shop['email'], 'Сообщение с каталога');

        $this->setSuccess('sent');
    }

}
