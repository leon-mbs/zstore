<?php

namespace App\Modules\Rozetka;

use App\Application as App;
use App\System;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Link\ClickLink;

/**
 * Налаштування модуля Rozetka
 */
class Options extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (!Helper::allowed()) {
            System::setErrorMsg('Немає права доступу до сторінки');
            App::RedirectError();
            return;
        }

        $form = $this->add(new Form('cform'));

        $form->add(new TextInput('login', Helper::opt('login')));
        $form->add(new TextInput('password', ''));

        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), Helper::opt('pricetype', 'price1')));
        $form->add(new DropDownChoice('defstore', \App\Entity\Store::getList(), intval(Helper::opt('store', 0))));
        $form->add(new DropDownChoice('defmf', \App\Entity\MoneyFund::getList(), intval(Helper::opt('mf', 0))));

        $pt = [];
        $pt[1] = 'Оплата на стороні маркетплейсу';
        $pt[2] = 'Постоплата';
        $pt[3] = 'Оплата касовим чеком, РФ або ВН';
        $form->add(new DropDownChoice('defpaytype', $pt, intval(Helper::opt('paytype', 2))));

        $form->add(new DropDownChoice('salesource', \App\Helper::getSaleSources(), Helper::opt('salesource', 0)));
        $form->add(new CheckBox('insertcust', intval(Helper::opt('insertcust', 0)) == 1));
        $form->add(new CheckBox('setprocessing', intval(Helper::opt('setprocessing', 0)) == 1));
        $form->add(new CheckBox('ssl', intval(Helper::opt('ssl', 1)) == 1));
        $form->add(new TextInput('vendor', Helper::opt('vendor')));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');

        $this->add(new ClickLink('genkey'))->onClick($this, 'genkeyOnClick');
        $this->add(new ClickLink('checkconn'))->onClick($this, 'checkOnClick');

        $this->_tvars['feedurl'] = Helper::feedUrl();
        $this->_tvars['hasfeed'] = strlen(Helper::feedUrl()) > 0;
    }

    public function saveOnClick($sender) {
        $login = trim($this->cform->login->getText());
        $password = $this->cform->password->getText();
        $pricetype = $this->cform->defpricetype->getValue();
        $paytype = intval($this->cform->defpaytype->getValue());
        $mf = intval($this->cform->defmf->getValue());

        if (strlen($login) == 0) {
            $this->setError('Не вказано логін');
            return;
        }
        if (strlen($pricetype ?? '') < 2) {
            $this->setError('Не вказано тип ціни');
            return;
        }
        if ($paytype == 0) {
            $this->setError('Не вказано тип оплати');
            return;
        }
        if ($paytype == 1 && $mf == 0) {
            $this->setError('Для оплати на стороні маркетплейсу потрібно вказати касу');
            return;
        }

        $m = System::getOptions('modules');
        $changed = ($m['rozlogin'] ?? '') !== $login || strlen($password) > 0;
        $m['rozlogin'] = $login;
        if (strlen($password) > 0) {
            $m['rozpassword'] = $password;
        }
        $m['rozpricetype'] = $pricetype;
        $m['rozstore'] = intval($this->cform->defstore->getValue());
        $m['rozmf'] = $mf;
        $m['rozpaytype'] = $paytype;
        $m['rozsalesource'] = $this->cform->salesource->getValue();
        $m['rozinsertcust'] = $this->cform->insertcust->isChecked() ? 1 : 0;
        $m['rozsetprocessing'] = $this->cform->setprocessing->isChecked() ? 1 : 0;
        $m['rozssl'] = $this->cform->ssl->isChecked() ? 1 : 0;
        $m['rozvendor'] = trim($this->cform->vendor->getText());
        System::setOptions('modules', $m);

        if ($changed) {
            Api::forgetToken();
        }
        $this->cform->password->setText('');
        $this->setSuccess('Збережено');
    }

    public function checkOnClick($sender) {
        if (strlen(Helper::opt('password')) == 0) {
            $this->setError('Спершу збережіть логін і пароль');
            return;
        }
        try {
            $n = Api::ping();
        } catch (\Exception $e) {
            $this->setError("З'єднання не вдалося: " . $e->getMessage());
            return;
        }
        $this->setSuccess("З'єднання успішне. Нових замовлень на Rozetka: {$n}");
    }

    public function genkeyOnClick($sender) {
        $m = System::getOptions('modules');
        $m['rozfeedkey'] = bin2hex(random_bytes(16));
        System::setOptions('modules', $m);
        $this->_tvars['feedurl'] = Helper::feedUrl();
        $this->_tvars['hasfeed'] = true;
        $this->setSuccess('Нову адресу прайсу створено. Вкажіть її в кабінеті продавця Rozetka');
    }
}
