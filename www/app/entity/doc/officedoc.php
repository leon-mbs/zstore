<?php

namespace App\Entity\Doc;

use App\Entity\Pay;
use App\Helper as H;

/**
 * Класс-сущность офисный документ
 *
 */
class OfficeDoc extends Document
{
    public function Execute() {
        $emp = intval($this->headerdata['employee'] ?? 0);
        $cust = intval($this->headerdata['customer'] ?? 0);
       
        $bonus = $this->headerdata['bonus'];
        $fine = $this->headerdata['fine'];
 
        if ($bonus > 0 && $emp > 0) {
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::BONUS;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $emp;
            $ua->amount = $bonus;
            $ua->save();
        }
        if ($fine > 0 && $emp > 0) {
            $ua = new \App\Entity\EmpAcc();
            $ua->optype = \App\Entity\EmpAcc::FINE;
            $ua->document_id = $this->document_id;
            $ua->emp_id = $emp;
            $ua->amount = 0 - $fine;
            $ua->save();
        }
    }

    public function generateReport() {
        $d = $this->unpackDetails('detaildata');

        $header = array(

            "content" => $d['data'] ?? ''

        );
        $report = new \App\Report('doc/officedoc.tpl');

        $html = $report->generate($header);

        return $html;
    }

    protected function getNumberTemplate() {
        return 'ОФ-000000';
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF);
    }

    protected function onState($state, $oldstate) {

        if ($state == Document::STATE_FINISHED) {
            $this->Execute();
        }

    }

    /**
     * права  из  списка
     *
     */
    public function checkShow($user, $showerror = true) {
        if ($user->user_id == $this->user_id || $user->user_id == $this->headerdata['author']) {
            return true;
        }
        if ($user->rolename == 'admins') {
            return true;
        }

        $d = $this->unpackDetails('accessdata');

        if (is_array($d['showlist']) && count($d['showlist']) > 0) {
            foreach ($d['showlist'] as $u) {
                if ($user->user_id == $u->user_id) {
                    return true;
                }
            }
            if ($showerror) {
                \App\System::setErrorMsg("Немає права перегляду");
            }
            return false; //если  список  непустой  и  не  найден в  списке

        } else {
            return true;
        }


    }

    public function checkEdit($user, $showerror = true) {
        if ($user->user_id == $this->user_id || $user->user_id == $this->headerdata['author']) {
            return true;
        }
        if ($user->rolename == 'admins') {
            return true;
        }

        $d = $this->unpackDetails('accessdata');
        if (is_array($d['editlist']) && count($d['editlist']) > 0) {
            foreach ($d['editlist'] as $u) {
                if ($user->user_id == $u->user_id) {
                    return true;
                }
            }
            if ($showerror) {
                \App\System::setErrorMsg("Немає права редагування");
            }

            return false; //если  список  непустой  и  не  найден в  списке

        } else {
            return true;
        }

    }

    public function checkApprove($user) {
        $d = $this->unpackDetails('accessdata');
        if (is_array($d['apprlist']) && count($d['apprlist']) > 0) {
            foreach ($d['apprlist'] as $u) {
                if ($user->user_id == $u->user_id) {
                    return true;
                }
            }
            return false; //если  список  непустой  и  не  найден в  списке

        } else {
            return false;
        }


    }

    /**
     * отметка  что  подписан
     *
     * @param mixed $user
     */
    public function sign($user_id) {
        $d = $this->unpackDetails('accessdata');
        $tmp = [];
        foreach ($d['apprlist'] as $u) {
            if ($user_id == $u->user_id) {
                $u->signed = true;
            }
            $tmp[$u->user_id] = $u;
        }

        $d['apprlist'] = $tmp;
        $this->packDetails('accessdata', $d);

    }

    /**
     * состояние  подписи  сколько  подписали сколько  еше  нет
     *
     */
    public function signed() {


        $d = $this->unpackDetails('accessdata');
        if (!is_array($d['apprlist'])) {
            return [[], []];
        }
        if (count($d['apprlist']) == 0) {
            return [[], []];
        }

        $a = [];
        $wa = [];
        foreach ($d['apprlist'] as $u) {

            if ($u->signed) {
                $a[$u->user_id] = $u->username;
            } else {
                $wa[$u->user_id] = $u->username;

            }
        }
        return [$a, $wa];

    }


}
