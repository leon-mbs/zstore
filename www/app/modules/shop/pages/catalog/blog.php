<?php

namespace App\Modules\Shop\Pages\Catalog;
use App\Modules\Shop\Entity\Article;

class Blog extends Base
{
    public function __construct($article=0) {
        parent::__construct();

        if($article > 0) {
           $this->_tvars['islist']  = false;
           $art= Article::load($article);
           $this->_tvars['date']  =   \App\Helper::fd($art->createdon) ;
           $this->_tvars['title'] =  $art->title;
           $this->_tvars['long']  =  $art->longdata ;
           
        }
        else {
           $this->_tvars['islist']  = true;
           $this->_tvars['list']  = [];
           foreach(Article::findYield('isactive=1','createdon desc') as $art)  { 
               $this->_tvars['list'][]  = ['date'=> \App\Helper::fd($art->createdon),'title'=>$art->title,'short'=>$art->shortdata,'id'=>$art->id ] ;
           }
        }
    }
}