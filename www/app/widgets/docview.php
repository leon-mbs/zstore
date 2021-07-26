<?php

namespace App\Widgets;

use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\RedirectLink;

/**
 * Виджет для  просмотра  документов
 */
class DocView extends \Zippy\Html\PageFragment
{

    private $_doc;
    private $_p         = null;
    public  $_reldocs   = array();
    public  $_statelist = array();
    public  $_fileslist = array();
    public  $_msglist   = array();
    public  $_paylist   = array();
    public  $_itemlist  = array();

    public function __construct($id) {
        parent::__construct($id);

        $this->add(new BookmarkableLink('print', ""));
        $this->add(new RedirectLink('printmob', ""));
        $this->add(new RedirectLink('html', ""))->setVisible(false);
        $this->add(new RedirectLink('word', ""));
        $this->add(new RedirectLink('excel', ""));
        $this->add(new RedirectLink('pdf', ""));
        $this->add(new BookmarkableLink('pos', ""));
        $this->add(new RedirectLink('posmob', ""));
        $this->add(new ClickLink('email', $this, "onMail"));

        $this->add(new Label('preview'));
        $this->add(new Label('previewpos'));

        $this->add(new Label('hmessages'));
        $this->add(new Label('hdocs'));
        $this->add(new Label('hfiles'));
        $this->add(new \Zippy\Html\Image('scanimage'))->setVisible(false);

        $this->add(new DataView('dw_statelist', new ArrayDataSource(new Prop($this, '_statelist')), $this, 'stateListOnRow'));

        $this->add(new DataView('dw_paylist', new ArrayDataSource(new Prop($this, '_paylist')), $this, 'payListOnRow'));

        $this->add(new DataView('dw_itemlist', new ArrayDataSource(new Prop($this, '_itemlist')), $this, 'itemListOnRow'));

        $this->add(new DataView('reldocs', new ArrayDataSource(new Prop($this, '_reldocs')), $this, 'relDoclistOnRow'));

        $this->add(new ClickLink('pdoc'))->onClick($this, 'parentDocOnClick');
        $this->add(new Form('addrelform'))->onSubmit($this, 'OnReldocSubmit');
        $this->addrelform->add(new AutocompleteTextInput('addrel'))->onText($this, 'OnAddDoc');

        $this->add(new Form('addfileform'))->onSubmit($this, 'OnFileSubmit');
        $this->addfileform->add(new \Zippy\Html\Form\File('addfile'));
        $this->addfileform->add(new TextInput('adddescfile'));
        $this->add(new DataView('dw_files', new ArrayDataSource(new Prop($this, '_fileslist')), $this, 'fileListOnRow'));

        $this->add(new Form('addmsgform'))->onSubmit($this, 'OnMsgSubmit');
        $this->addmsgform->add(new TextArea('addmsg'));
        $this->add(new DataView('dw_msglist', new ArrayDataSource(new Prop($this, '_msglist')), $this, 'msgListOnRow'));

        $this->addmsgform->add(new \Zippy\Html\Form\CheckBoxList('userm', '<br>'));


        $this->add(new ClickLink('doctabp', $this, "onTab"));
        $this->add(new ClickLink('doctabd', $this, "onTab"));
        $this->add(new ClickLink('doctabf', $this, "onTab"));
        $this->add(new ClickLink('doctabc', $this, "onTab"));
        $this->add(new ClickLink('doctabh', $this, "onTab"));
        $this->add(new ClickLink('doctabs', $this, "onTab"));
    }

    // Устанавливаем  документ  для  просмотра
    public function setDoc(\App\Entity\Doc\Document $doc) {
        $this->_doc = $doc;
        $doc = $this->_doc->cast();
        // if (false == \App\ACL::checkShowDoc($doc, false)) {
        //   $this->setvisible(false);
        //   return;
        // }
        $this->setvisible(true);
        $html = $doc->generateReport();
        $this->preview->setText($html, true);
        $htmlpos = $doc->generatePosReport();

        $this->previewpos->setText($htmlpos, true);

        // проверяем  поддержку  экспорта
        $exportlist = $doc->supportedExport();
        $this->word->setVisible(in_array(Document::EX_WORD, $exportlist));
        $this->excel->setVisible(in_array(Document::EX_EXCEL, $exportlist));
        $this->pdf->setVisible(in_array(Document::EX_PDF, $exportlist));
        $this->pos->setVisible(in_array(Document::EX_POS, $exportlist));
        $this->previewpos->setVisible(in_array(Document::EX_POS, $exportlist));
        $this->email->setVisible(in_array(Document::EX_MAIL, $exportlist));

        $reportpage = "App/Pages/ShowDoc";

        $this->printmob->pagename = $reportpage;
        $this->printmob->params = array('print', $doc->document_id);

        $this->posmob->pagename = $reportpage;
        $this->posmob->params = array('pos', $doc->document_id);

        $this->html->pagename = $reportpage;
        $this->html->params = array('html', $doc->document_id);
        $this->word->pagename = $reportpage;
        $this->word->params = array('doc', $doc->document_id);
        $this->excel->pagename = $reportpage;
        $this->excel->params = array('xls', $doc->document_id);
        $this->pdf->pagename = $reportpage;
        $this->pdf->params = array('pdf', $doc->document_id);

        $this->updateMessages();
        $this->updateFiles();
        $this->updateDocs();

        $this->_p = Document::load($doc->parent_id);
        $this->pdoc->setVisible($this->_p instanceof Document);
        $this->pdoc->setValue($this->_p->meta_desc . ' ' . $this->_p->document_number);

        //статусы
        $this->_statelist = $this->_doc->getLogList();
        $this->dw_statelist->Reload();

        //оплаты
        $this->_paylist = \App\Entity\Pay::getPayments($this->_doc->document_id);
        $this->dw_paylist->Reload();

        //проводки
        $sql = " select e.entry_id, s.stock_id, s.partion,s.itemname,s.item_code,e.quantity,e.outprice  from  entrylist e join store_stock_view  s on e.stock_id = s.stock_id  where  coalesce(e.quantity,0) <> 0  and document_id=" . $this->_doc->document_id . " order  by e.entry_id";
        $this->_itemlist = \App\Entity\Entry::findBySql($sql);
        $this->dw_itemlist->Reload();

        if ($this->_doc->headerdata['scan'] > 0) {
            $this->scanimage->setVisible(true);
            $this->scanimage->setUrl('/loadfile.php?im=1&id=' . $this->_doc->headerdata['scan']);
        }

        $users = \App\Entity\User::getByBranch($this->_doc->branch_id);
        $this->addmsgform->userm->clean();
        $curr = System::getUser();
        foreach ($users as $id => $name) {
            $f = false;
            if ($id == $this->_doc->user_id) {
                $f = true;
            }//автор
            if ($id == $curr->user_id) {
                continue;
            }//себе не  нужно

            $this->addmsgform->userm->AddCheckBox($id, $f, $name);
        }


        $this->onTab($this->doctabp);
    }

    //вывод строки  лога состояний
    public function stateListOnRow($row) {
        $item = $row->getDataItem();
        //  $row->add(new Label('statehost', $item->hostname));
        $row->add(new Label('statedate', \App\Helper::fdt($item->createdon)));
        $row->add(new Label('stateuser', $item->username));
        $row->add(new Label('statename', Document::getStateName($item->docstate)));
    }

    //вывод строки  оплат
    public function payListOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('paydate', \App\Helper::fd($item->paydate)));
        $row->add(new Label('payamountp', H::fa($item->amount > 0 ? $item->amount : "")));
        $row->add(new Label('payamountm', H::fa($item->amount < 0 ? 0 - $item->amount : "")));

        $row->add(new Label('paymf', $item->mf_name));
    }

    //вывод строки  проводок
    public function itemListOnRow($row) {
        $entry = $row->getDataItem();

        $row->add(new Label('itname', $entry->itemname));
        $row->add(new Label('itcode', $entry->item_code));
        $row->add(new Label('itqty', H::fqty($entry->quantity)));
        $row->add(new Label('itpartion', H::fa($entry->partion)));
        $row->add(new Label('itprice', H::fa($entry->outprice)));
        $row->add(new Label('itamount', H::fa($entry->outprice * $entry->quantity)));


    }

    /**
     * добавление прикрепленного файла
     *
     * @param mixed $sender
     */
    public function OnFileSubmit($sender) {

        $file = $this->addfileform->addfile->getFile();
        if ($file['size'] > 10000000) {
            $this->getOwnerPage()->setError("filemore10M");
            return;
        }

        H::addFile($file, $this->_doc->document_id, $this->addfileform->adddescfile->getText(), \App\Entity\Message::TYPE_DOC);
        $this->addfileform->adddescfile->setText('');
        $this->updateFiles();
    }

    // обновление  списка  прикрепленных файлов
    private function updateFiles() {
        $this->_fileslist = H::getFileList($this->_doc->document_id, \App\Entity\Message::TYPE_DOC);
        $this->dw_files->Reload();
        $this->hfiles->setText(count($this->_fileslist));
        $this->hfiles->setVisible(count($this->_fileslist) > 0);
    }

    //вывод строки  прикрепленного файла
    public function filelistOnRow($row) {
        $item = $row->getDataItem();
        $url = _BASEURL . 'loadfile.php?id=' . $item->file_id;
        if (strlen($item->mime) > 0) {
            $url = $url . '&im=1';
        }
        $file = $row->add(new \Zippy\Html\Link\BookmarkableLink("filename", $url));
        $file->setValue($item->filename);
        $file->setAttribute('title', $item->description);

        $row->add(new ClickLink('delfile'))->onClick($this, 'deleteFileOnClick');
    }

    //удаление прикрепленного файла
    public function deleteFileOnClick($sender) {
        $file = $sender->owner->getDataItem();
        H::deleteFile($file->file_id);
        $this->updateFiles();
    }

    /**
     * добавление коментария
     *
     * @param mixed $sender
     */
    public function OnMsgSubmit($sender) {

        $user = System::getUser();

        $msg = new \App\Entity\Message();
        $msg->message = $this->addmsgform->addmsg->getText();
        $msg->created = time();
        $msg->user_id = $user->user_id;
        $msg->item_id = $this->_doc->document_id;
        $msg->item_type = \App\Entity\Message::TYPE_DOC;
        if (strlen($msg->message) == 0) {
            return;
        }
        $msg->save();

        $this->addmsgform->addmsg->setText('');
        $this->updateMessages();

        // уведомления


        foreach ($this->addmsgform->userm->getCheckedList() as $adr) {

            $n = new \App\Entity\Notify();
            $n->user_id = $adr;
            $n->message = "<b>" . H::l("newdoccomment") . ":</b> {$this->_doc->meta_desc} {$this->_doc->document_number}  ";
            $n->message .= "<br> {$msg->message} ";
            $n->message .= "<br>  <a href=\"/index.php?p=App/Pages/Register/DocList&arg={$this->_doc->document_id}\">" . H::l("toanswer") . "</a> ";
            $n->sender_id = $user->user_id;
            $n->save();
        }
    }

    //список   комментариев
    private function updateMessages() {
        $this->_msglist = \App\Entity\Message::getMessages(1, $this->_doc->document_id);
        $this->dw_msglist->Reload();
        $this->hmessages->setText(count($this->_msglist));
        $this->hmessages->setVisible(count($this->_msglist) > 0);
    }

    //вывод строки  коментария
    public function msgListOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label("msgdata", nl2br($item->message), true));
        $row->add(new Label("msgdate", \App\Helper::fdt($item->created)));
        $row->add(new Label("msguser", $item->username));

        $row->add(new ClickLink('delmsg'))->onClick($this, 'deleteMsgOnClick');
    }

    //удаление коментария
    public function deleteMsgOnClick($sender) {
        $msg = $sender->owner->getDataItem();
        \App\Entity\Message::delete($msg->message_id);
        $this->updateMessages();
    }

    // обновление  списка  связанных  документов
    private function updateDocs() {
        $this->_reldocs = $this->_doc->getChildren();
        $this->reldocs->Reload();

        $cnt = count($this->_reldocs);
        if ($this->_p != null) {
            $cnt++;
        }
        $this->hdocs->setText($cnt);
        $this->hdocs->setVisible($cnt > 0);
    }

    //вывод строки  дочернего  документа
    public function relDoclistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new ClickLink('docitem'))->onClick($this, 'detailDocOnClick');
        $row->add(new ClickLink('deldoc'))->onClick($this, 'deleteDocOnClick');
        $row->docitem->setValue($item->meta_desc . ' ' . $item->document_number);
    }

    //удаление дочернего  документа
    public function deleteDocOnClick($sender) {
        $doc = $sender->owner->getDataItem();
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("update documents set parent_id=0 where  document_id=" . $doc->document_id);
        $this->updateDocs();
    }

    //открыть дочерний документ
    public function detailDocOnClick($sender) {
        //$id = $sender->owner->getDataItem()->document_id;
        //App::Redirect('\App\Pages\Register\DocList', $id);
        $this->setDoc($sender->owner->getDataItem());
    }

    //открыть родительский документ
    public function parentDocOnClick($sender) {
        $this->setDoc($this->_p);
    }

    public function OnReldocSubmit($sender) {

        $id = $this->addrelform->addrel->getKey();
        $child = Document::load($id);
        if ($child instanceof Document) {
            $child->parent_id = $this->_doc->document_id;
            $child->save();
        }

        $this->updateDocs();
        $this->addrelform->addrel->setKey(0);
        $this->addrelform->addrel->setText('');
    }

    // автолоад списка  документов
    public function OnAddDoc($sender) {
        $text = $sender->getValue();
        $answer = array();
        $conn = \ZDB\DB::getConnect();
        $sql = "select document_id,document_number from documents where document_number  like '%{$text}%' and document_id <> {$this->_doc->document_id} order  by document_id desc  limit 0,20";
        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $answer[$row['document_id']] = $row['document_number'];
        }
        return $answer;
    }

    public function onMail($sender) {
        $this->_doc->sendEmail();
    }

    public function onTab($sender) {
        $page = $this->getOwnerPage();
        $page->_tvars['doctabp'] = $sender->id == 'doctabp';
        $page->_tvars['doctabc'] = $sender->id == 'doctabc';
        $page->_tvars['doctabf'] = $sender->id == 'doctabf';
        $page->_tvars['doctabd'] = $sender->id == 'doctabd';
        $page->_tvars['doctabh'] = $sender->id == 'doctabh';
        $page->_tvars['doctabs'] = $sender->id == 'doctabs';

        $page->_tvars['isscan'] = $this->_doc->headerdata['scan'] > 0;

        $page->_tvars['doctabpbadge'] = $sender->id == 'doctabp' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $page->_tvars['doctabcbadge'] = $sender->id == 'doctabc' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $page->_tvars['doctabfbadge'] = $sender->id == 'doctabf' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $page->_tvars['doctabdbadge'] = $sender->id == 'doctabd' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $page->_tvars['doctabhbadge'] = $sender->id == 'doctabh' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $page->_tvars['doctabsbadge'] = $sender->id == 'doctabs' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";

        $page->goDocView();
    }

}
