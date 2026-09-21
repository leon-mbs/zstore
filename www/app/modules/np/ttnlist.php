<?php

namespace App\Modules\NP;

use App\System;
use App\Helper as H;
use App\Entity\Doc\Document;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use App\Application as App;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;

/**
 * Стан доставок: ТТН з експрес-накладною Нової пошти.
 * Список береться з бази одразу (останній відомий стан), «Оновити» питає НП і міняє стан документів.
 */
class TTNList extends \App\Pages\Base
{
    public $_doclist = array();   //показані рядки
    public $_all = array();       //усі ТТН за режимом, фільтр лише ховає зайві

    //коди статусів НП
    public const NP_DELIVERED = array(9, 10, 11, 106);
    public const NP_INWAY     = array(4, 5, 6, 7, 8, 41, 101);
    public const NP_PROBLEM   = array(102, 103, 104, 108, 105, 2, 3);

    /**
     * @param string $mode   'pdf' - віддати маркування ТТН $docid (посилання з рядка журналу)
     */
    public function __construct($mode = '', $docid = 0) {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'np') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        if ($mode == 'pdf') {
            $this->printMarking(intval($docid));
            return;
        }

        $this->add(new ClickLink('refresh', $this, 'onRefresh'));
        $this->add(new Form('searchform'))->onSubmit($this, 'onFilter');
        $this->searchform->add(new TextInput('searchnumber'));
        $this->searchform->add(new DropDownChoice('searchcust'));
        $this->searchform->add(new DropDownChoice('searchmode', array('active' => 'В доставці та готові до відправки', 'recent' => 'Усі за 30 днів'), 'active'))->onChange($this, 'onMode');
        $this->searchform->add(new ClickLink('reset', $this, 'onReset'));
        $this->add(new Label('summary'));

        $this->add(new DataView('doclist', new ArrayDataSource($this, '_doclist'), $this, 'doclistOnRow'));

        $this->loadList();
    }

    /**
     * ТТН з бази за режимом: проблемні першими, далі новіші.
     */
    private function loadList() {
        $where = "content like '%<ship_number>%' and meta_name = 'TTN' and ";
        if ($this->searchform->searchmode->getValue() == 'recent') {
            $where .= " state in (11,14,20) and document_date >= " . \ZDB\DB::getConnect()->DBDate(strtotime('-30 day'));
        } else {
            $where .= " state in (11,20) ";
        }
        $this->_all = array();
        foreach (Document::find($where, "document_date desc") as $doc) {
            if (strlen($doc->headerdata['ship_number'] ?? '') > 0) {
                $this->_all[$doc->document_id] = $doc;
            }
        }
        uasort($this->_all, function ($a, $b) {
            $pa = self::isProblem($a) ? 0 : 1;
            $pb = self::isProblem($b) ? 0 : 1;
            return $pa == $pb ? $b->document_date <=> $a->document_date : $pa <=> $pb;
        });

        $c = array();
        foreach ($this->_all as $d) {
            $c[$d->customer_id] = $d->customer_name;
        }
        $this->searchform->searchcust->setOptionList($c);

        $this->applyFilter();
    }

    private static function isProblem($doc) {
        return in_array(intval($doc->headerdata['sn_code'] ?? 0), self::NP_PROBLEM);
    }

    private function applyFilter() {
        $cust = $this->searchform->searchcust->getValue();
        $n = trim($this->searchform->searchnumber->getText());
        $this->_doclist = array();
        foreach ($this->_all as $d) {
            if ($cust > 0 && $d->customer_id <> $cust) {
                continue;
            }
            if (strlen($n) > 0 && strpos($d->headerdata['ship_number'], $n) === false && strpos($d->document_number, $n) === false) {
                continue;
            }
            $this->_doclist[$d->document_id] = $d;
        }
        $this->doclist->Reload();

        $problems = count(array_filter($this->_all, function ($d) { return self::isProblem($d); }));
        $text = "ТТН: " . count($this->_doclist) . (count($this->_doclist) != count($this->_all) ? " з " . count($this->_all) : "");
        if ($problems > 0) {
            $text .= ", проблемних: {$problems}";
        }
        $this->summary->setText($text);
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();
        $code = intval($doc->headerdata['sn_code'] ?? 0);
        $num = $doc->headerdata['ship_number'];

        //позначка стану: не фон рядка, щоб читалось і в темній темі
        $badge = '';
        if (self::isProblem($doc)) {
            $badge = '<span class="badge bg-danger">проблема</span>';
        } elseif ($doc->state == Document::STATE_DELIVERED || in_array($code, self::NP_DELIVERED)) {
            $badge = '<span class="badge bg-success">доставлено</span>';
        }

        $row->add(new BookmarkableLink('document_number', "/index.php?p=App/Pages/Register/GIList&arg={$doc->document_id}"))->setValue($doc->document_number);
        $row->add(new Label('document_date', H::fd($doc->document_date)));
        $row->add(new BookmarkableLink('ship_number', "https://novaposhta.ua/tracking/?cargo_number=" . urlencode($num)))->setValue($num);
        $row->add(new Label('customer_name', $doc->customer_name));
        $row->add(new Label('amount', H::fa($doc->amount)));
        $row->add(new Label('badge', $badge, true))->setVisible($badge != '');
        $row->add(new Label('state', trim(preg_replace('/\s+/u', ' ', $doc->headerdata['sn_state'] ?? ''))));
        $checked = intval($doc->headerdata['sn_checked'] ?? 0);
        $row->add(new Label('checked', $checked > 0 ? 'перевірено ' . date('d.m H:i', $checked) : 'ще не перевірялось'));
        $plan = strtotime($doc->headerdata['sn_plan'] ?? '');   //НП віддає «22-09-2026 12:00:00»
        $row->add(new Label('plan', $plan > 0 && !$badge ? date('d.m H:i', $plan) : ''));

        $row->add(new BookmarkableLink('print', "/index.php?p=App/Modules/NP/TTNList&arg=pdf/{$doc->document_id}"));
    }

    /**
     * Маркування 100x100 з НП. Ключ API лишається на сервері: раніше він був у посиланні на сторінці.
     */
    private function printMarking($docid) {
        $doc = Document::load($docid);
        if ($doc == null || $doc->meta_name != 'TTN' || strlen($doc->headerdata['ship_number'] ?? '') == 0) {
            http_response_code(404);
            die('ТТН не знайдено');
        }
        $modules = System::getOptions("modules");
        $url = "https://my.novaposhta.ua/orders/printMarking100x100/orders[]/" . urlencode($doc->headerdata['ship_number']) . "/type/pdf/apiKey/" . $modules['npapikey'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $pdf = curl_exec($ch);
        curl_close($ch);

        if (!is_string($pdf) || substr($pdf, 0, 4) !== '%PDF') {
            http_response_code(502);
            die('Нова пошта не віддала маркування для ' . $doc->headerdata['ship_number']);
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $doc->headerdata['ship_number'] . '.pdf"');
        echo $pdf;
        die;
    }

    //обновление  статусов
    public function onRefresh($sender) {
        $api = new \App\Modules\NP\Helper();
        $tracks = array();
        foreach ($this->_all as $ttn) {
            if (in_array($ttn->state, array(Document::STATE_INSHIPMENT, Document::STATE_READYTOSHIP))) {
                $tracks[] = $ttn->headerdata['ship_number'];
            }
        }
        if (count($tracks) == 0) {
            $this->setInfo('Немає ТТН для перевірки');
            return;
        }

        $statuses = $api->check($tracks);
        if (count($statuses) == 0) {
            $this->setError('Нова пошта не відповіла, спробуйте пізніше');
            return;
        }

        $cnt = 0;
        $changed = 0;
        foreach ($this->_all as $ttn) {
            $decl = $ttn->headerdata['ship_number'];
            if (!isset($statuses[$decl])) {
                continue;
            }
            $code = intval($statuses[$decl]['StatusCode']);
            $ttn->headerdata['sn_state'] = $statuses[$decl]['Status'];
            $ttn->headerdata['sn_code'] = $code;
            $ttn->headerdata['sn_checked'] = time();
            $ttn->headerdata['sn_plan'] = $statuses[$decl]['ScheduledDeliveryDate'] ?? '';
            $cnt++;

            $newstate = 0;
            if (in_array($code, self::NP_DELIVERED)) {
                $newstate = Document::STATE_DELIVERED;
            }
            if (in_array($code, self::NP_INWAY)) {
                $newstate = Document::STATE_INSHIPMENT;
            }
            if ($newstate > 0 && $newstate != $ttn->state) {
                $ttn->updateStatus($newstate);   //зберігає і стан, і headerdata
                $changed++;
            } else {
                $ttn->save();
            }
        }

        $this->setSuccess("Перевірено {$cnt} ТТН, змінився стан у {$changed}");
        $this->loadList();
    }

    public function onFilter($sender) {
        $this->applyFilter();
    }

    public function onMode($sender) {
        $this->loadList();
    }

    public function onReset($sender) {
        $this->searchform->searchnumber->setText('');
        $this->searchform->searchcust->setValue(0);
        $this->applyFilter();
    }

}
