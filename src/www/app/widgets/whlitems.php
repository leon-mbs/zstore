<?php

namespace App\Widgets;

use \Zippy\Binding\PropertyBinding as Bind;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Label;
use \App\Helper;
use \App\DataItem;
use \App\System;
use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \Carbon\Carbon;

/**
 * Виджет для  просмотра состояния склада
 */
class WHLItems extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'whighitems') !== false || System::getUser()->userlogin == 'admin');

        $conn = $conn = \ZDB\DB::getConnect();
        $lowitems = array(); //нехватка
        $highitems = array(); //затоваривание

        if ($visible) {



            $avritems = array();

            // средние  продажи  за последний месяц
            $sql = "select ss.item_id,ss.`itemname`,ss.`storename`,ss.store_id , coalesce( sum(0-sc.quantity ),0)  as quantity             
                  from   `entrylist_view` sc join store_stock_view ss on sc.stock_id= ss.stock_id    
                       where sc.document_date >" . $conn->DBDate(strtotime('- 30 day')) . " and document_date <  " . $conn->DBDate(time()) . "
                   and sc.quantity < 0 
                   group  by ss.item_id,ss.`itemname`,ss.`storename`,ss.store_id   
                      ";

            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {
                $row['quantity'] = $row['quantity'] / 30;
                $avritems[$row['item_id'] . '_' . $row['store_id']] = $row;
            }



            //остатки на  сегодня
            $sql = "select  ss.item_id, ss.store_id,ss.itemname,ss.storename , coalesce( sum(quantity),0)  as quantity
                        from  entrylist_view sc join store_stock_view ss on sc.stock_id= ss.stock_id 
                        where ss.item_id >0 and   document_date <  " . $conn->DBDate(time()) . "
                        group  by  ss.item_id,  ss.store_id,ss.itemname,ss.storename
                        order  by  ss.itemname ";

             
            $rs = $conn->Execute($sql);
            foreach ($rs as $row) {
                if (isset($data['noliq'][$row['item_id'] . '_' . $row['store_id']])) {
                    $data['noliq'][$row['item_id'] . '_' . $row['store_id']]->now = $row['quantity'];
                    continue;
                }
                if (isset($avritems[$row['item_id'] . '_' . $row['store_id']])) {
                    $avr = $avritems[$row['item_id'] . '_' . $row['store_id']];
                    $norma = $avr['quantity'] * 1.2;  //запас  20%
                    if ($norma < 1)
                        $norma = 1;
                    $prev = $row['quantity'];
                    for ($i = 1; $i <= 5; $i++) {
                        $it = new DataItem($row);
                        $it->quantity = $prev - $avr['quantity'];
                        $it->date = Carbon::now()->addDays($i)->startOfDay()->timestamp;

                        if (isset($planned[$row['item_id'] . '_' . $row['store_id'] . '_' . $it->date])) {
                            $it->quantity += $planned[$row['item_id'] . '_' . $row['store_id'] . '_' . $it->date]['quantity'];
                        }
                        $prev = $it->quantity;
                        $it->quantity = round($it->quantity);
                        $it->state = 0;
                        $it->norma = $norma;
                        if ($it->quantity < $norma / 2) {

                            $lowitems[] = $it;
                        }


                        if ($it->quantity > $norma * 1.5) {
                            $highitems[] = $it;
                        }
                    }

                    continue;
                }
            }

            $lowitems = \Pinq\Traversable::from($lowitems)
                            ->orderByAscending(function ($row) {
                                return $row->date;
                            })
                            ->thenByAscending(function ($row) {
                                return $row->itemname;
                            })
                            ->thenByAscending(function ($row) {
                                return $row->storename;
                            })
                            ->select(function ($row) {
                                return $row;
                            })->asArray();

            $highitems = \Pinq\Traversable::from($highitems)
                            ->orderByAscending(function ($row) {
                                return $row->date;
                            })
                            ->thenByAscending(function ($row) {
                                return $row->itemname;
                            })
                            ->thenByAscending(function ($row) {
                                return $row->storename;
                            })
                            ->select(function ($row) {
                                return $row;
                            })->asArray();
        }

        $highlist = $this->add(new DataView('highlist', new ArrayDataSource($highitems), $this, 'highlistOnRow'));
        $highlist->Reload();

        $lowlist = $this->add(new DataView('lowlist', new ArrayDataSource($lowitems), $this, 'lowlistOnRow'));
        $lowlist->Reload();

        if ((count($highitems) == 0 && count($lowitems) == 0) || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function lowlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('lowdate', date('d.m.Y', $item->date)));
        $row->add(new Label('lowitem', $item->itemname));
        $row->add(new Label('lowstore', $item->storename));
        $row->add(new Label('lowqty', " " . $item->quantity));
        $row->add(new Label('lowneed', $item->norma));

        $row->setAttribute('class', 'alert alert-warning');

        if ($item->quantity <= 0) {
            $row->setAttribute('class', 'alert alert-danger');
        }
    }

    public function highlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('highdate', date('d.m.Y', $item->date)));
        $row->add(new Label('highitem', $item->itemname));
        $row->add(new Label('highstore', $item->storename));
        $row->add(new Label('highqty', " " . $item->quantity));
        $row->add(new Label('highneed', $item->norma));

        $row->setAttribute('class', 'alert alert-warning');

        if ($item->quantity > $item->norma * 2) {
            $row->setAttribute('class', 'alert alert-danger');
        }
    }

}
