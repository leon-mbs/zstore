<?php

namespace App\Modules\Rozetka;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;

/**
 * Замовлення Rozetka: імпорт у Zippy і повернення статусів
 */
class Orders extends \App\Pages\Base
{
    public $_neworders = [];
    public $_eorders   = [];

    public function __construct() {
        parent::__construct();

        if (!Helper::allowed()) {
            System::setErrorMsg('Немає права доступу до сторінки');
            App::RedirectError();
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('types', Helper::typesList(), Api::TYPES_NEW));

        $this->add(new DataView('neworderslist', new ArrayDataSource(new Prop($this, '_neworders')), $this, 'noOnRow'));
        $this->add(new ClickLink('importbtn'))->onClick($this, 'onImport');

        $this->add(new ClickLink('refreshbtn'))->onClick($this, 'onRefresh');
        $this->add(new Form('updateform'))->onSubmit($this, 'exportOnSubmit');
        $this->updateform->add(new DataView('orderslist', new ArrayDataSource(new Prop($this, '_eorders')), $this, 'expRow'));
        $this->updateform->add(new DropDownChoice('estatus', Helper::sellerStatusList(), Helper::ST_PROCESSING));

        $this->_tvars['configured'] = strlen(Helper::opt('login')) > 0 && strlen(Helper::opt('password')) > 0;
    }

    // ----------------------------------------------------------- import

    public function filterOnSubmit($sender) {
        $this->_neworders = [];
        try {
            $list = Api::allOrders(intval($this->filter->types->getValue()));
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            $this->neworderslist->Reload();
            return;
        }

        $skipped = 0;
        $warn = [];
        foreach ($list as $roz) {
            if (Helper::findImported($roz['id'])) {
                $skipped++;
                continue;
            }
            $doc = Helper::buildOrder($roz, $warn);
            if ($doc == null) {
                continue;
            }
            $this->_neworders[intval($roz['id'])] = $doc;
        }
        foreach (array_unique($warn) as $w) {
            $this->setWarn($w);
        }
        if (count($list) == 0) {
            $this->setInfo('На Rozetka немає замовлень у цій групі');
        } elseif (count($this->_neworders) == 0) {
            $this->setInfo('Нових замовлень немає' . ($skipped > 0 ? " (уже імпортовано: {$skipped})" : ''));
        }
        $this->neworderslist->Reload();
    }

    public function noOnRow($row) {
        $order = $row->getDataItem();
        $row->add(new Label('number', $order->headerdata['rozorder']));
        $row->add(new Label('date', H::fdt($order->document_date)));
        $row->add(new Label('customer', $order->headerdata['rozclient']));
        $row->add(new Label('rstatus', Helper::statusName($order->headerdata['rozstatus'])));
        $row->add(new Label('amount', H::fa($order->payamount)));
        $row->add(new Label('comment', $order->notes));
    }

    public function onImport($sender) {
        if (count($this->_neworders) == 0) {
            $this->setError('Спершу завантажте замовлення');
            return;
        }
        $setproc = intval(Helper::opt('setprocessing', 0)) == 1;
        $n = 0;
        $errors = [];
        foreach ($this->_neworders as $rozId => $doc) {
            if (Helper::findImported($rozId)) {
                continue;   // хтось імпортував паралельно
            }
            Helper::importOrder($doc);
            $n++;
            if ($setproc && intval($doc->headerdata['rozstatus']) == Helper::ST_NEW) {
                try {
                    Api::updateOrder($rozId, Helper::ST_PROCESSING);
                    $doc->headerdata['rozstatus'] = Helper::ST_PROCESSING;
                    $doc->save();
                } catch (\Exception $e) {
                    $errors[] = "Замовлення {$rozId}: " . $e->getMessage();
                }
            }
        }
        $this->_neworders = [];
        $this->neworderslist->Reload();
        $this->setInfo("Імпортовано замовлень: {$n}");
        foreach ($errors as $e) {
            $this->setWarn($e);
        }
    }

    // ----------------------------------------------------------- export

    public function onRefresh($sender) {
        $this->_eorders = Helper::pendingExport();
        foreach ($this->_eorders as $o) {
            $o->rozttn = $o->headerdata['ttn'] ?? $o->headerdata['ship_number'] ?? '';
        }
        $this->updateform->orderslist->Reload();
    }

    public function expRow($row) {
        $order = $row->getDataItem();
        $row->add(new CheckBox('ch', new Prop($order, 'ch')));
        $row->add(new Label('number2', $order->document_number));
        $row->add(new Label('number3', $order->headerdata['rozorder']));
        $row->add(new Label('date2', H::fdt($order->document_date)));
        $row->add(new Label('customer2', $order->headerdata['rozclient']));
        $row->add(new Label('amount2', H::fa($order->payamount)));
        $row->add(new Label('state', Document::getStateName($order->state)));
        $row->add(new Label('rstatus2', Helper::statusName($order->headerdata['rozstatus'])));
        $row->add(new TextInput('ttn', new Prop($order, 'rozttn')));
    }

    public function exportOnSubmit($sender) {
        $st = intval($this->updateform->estatus->getValue());
        $needTtn = in_array($st, [Helper::ST_SHIPPED, Helper::ST_TOCARRIER]);

        $elist = [];
        foreach ($this->_eorders as $order) {
            if ($order->ch == true) {
                $elist[] = $order;
            }
        }
        if (count($elist) == 0) {
            $this->setError('Не обрано замовлення');
            return;
        }

        $n = 0;
        foreach ($elist as $order) {
            $ttn = trim($order->rozttn ?? '');
            if ($needTtn && strlen($ttn) == 0) {
                $this->setWarn("Замовлення {$order->document_number}: для статусу «" . Helper::statusName($st) . "» потрібна ТТН");
                continue;
            }
            try {
                Api::updateOrder($order->headerdata['rozorder'], $st, $ttn);
            } catch (\Exception $e) {
                $this->setWarn("Замовлення {$order->document_number}: " . $e->getMessage());
                continue;
            }
            $order->headerdata['rozorderback'] = 1;
            $order->headerdata['rozstatus'] = $st;
            if (strlen($ttn) > 0) {
                $order->headerdata['ttn'] = $ttn;
            }
            $order->save();
            $n++;
        }
        if ($n > 0) {
            $this->setSuccess("Оновлено на Rozetka: {$n}");
        }
        $this->onRefresh(null);
    }
}
