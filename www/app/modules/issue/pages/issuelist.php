<?php

namespace App\Modules\Issue\Pages ;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Image;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Link\RedirectLink;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\BookmarkableLink;
use \Zippy\Html\Link\SubmitLink;
use \ZCL\DB\EntityDataSource;
use \App\Application as App;
use \App\System;
use \App\Modules\Issue\Helper;
use \App\Filter;
use \ZCL\BT\Tags;
use \App\Modules\Issue\Entity\Issue;
 

/**
 * Главная страница
 */
class IssueList extends \App\Pages\Base 
{
 

    public function __construct() {
        parent::__construct();

        $allow = (strpos(System::getUser()->modules, 'issue') !== false || System::getUser()->userlogin == 'admin');
        if(!$allow){
            System::setErrorMsg('Нет права  доступа  к   модулю ');
            App::RedirectHome();
            return false;
        }
 
 
    }

   
 
    //вывод строки  списка   

    public function onRow($row) {
        $topic = $row->getDataitem();
        $row->add(new Label('title', $topic->title));
        //$row->add(new ClickLink('title', $this,'onTopic'));
        $fav = $row->add(new Label('fav'));
        $fav->setVisible($topic->favorites > 0);
   
    }
 
 
    //аплоад файла
    public function OnFile($form) {
        $file = $form->editfile->getFile();
        if (strlen($file['tmp_name']) > 0) {
            if (filesize($file['tmp_name']) / 1024 / 1024 > 10) {

                $this->setError("Файл слишком  большой");
                return;
            }
        } else
            return;

        $topic_id = $this->topiclist->getSelectedRow()->getDataItem()->topic_id;
           
        Helper::addFile($file,$topic_id)   ; 

        $this->_farr = Helper::findFileByTopic($topic_id);
        $this->filelist->Reload();
    }

    public function onFileRow($row) {
        $file = $row->getDataItem();
        $row->add(new ClickLink("filedel", $this, "onFileDel"));
        $row->add(new BookmarkableLink("filelink", "/loadfile.php?id=" . $file->file_id))->setValue($file->filename);
    }

    public function onFileDel($sender) {
        $file = $sender->getOwner()->getDataItem();
        Helper::deleteFile($file->file_id);
        $topic_id = $this->topiclist->getSelectedRow()->getDataItem()->topic_id;
 
        $this->_farr = Helper::findFileByTopic($topic_id);
        $this->filelist->Reload();
    }

    //обработчик поиска
    public function OnSearch($form) {
        $text = $form->skeyword->getText();
        $t = $form->searchtype->getValue();
        if ($text == "") {
            $this->setError('Enter text!');
            return;
        }

        $this->_sarr = TopicNode::searchByText($text, $t, $form->searchtitle->isChecked());
        $this->searchlist->Reload();
    }

    //обработчик  поиска  по тегу
    public function OnTagList($sender) {
        $text = $sender->getSelectedValue();
        $this->_sarr = TopicNode::searchByTag($text);
        $this->searchlist->Reload();
    }

 
}
