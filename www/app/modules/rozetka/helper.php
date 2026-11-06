<?php

namespace App\Modules\Rozetka;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Helper as H;
use App\System;

/**
 * Логіка модуля без прив'язки до сторінок: налаштування, перетворення
 * замовлення Rozetka в документ Zippy, XML-прайс.
 */
class Helper
{
    public const MODULE = 'rozetka';

    // статуси Rozetka, які продавець виставляє сам (документація, OrdersStatuses)
    public const ST_NEW        = 1;   // Нове замовлення
    public const ST_PACKING    = 2;   // Комплектується. Дані підтверджені
    public const ST_SHIPPED    = 3;   // Передано в службу доставки (потрібна ТТН)
    public const ST_DONE       = 6;   // Замовлення виконано
    public const ST_PROCESSING = 26;  // Опрацьовується менеджером
    public const ST_WAITPAY    = 55;  // Очікує отримання оплати
    public const ST_TOCARRIER  = 61;  // Заплановано передачу перевізникові (потрібна ТТН)

    // ---------------------------------------------------------- settings

    public static function opt($key, $def = '') {
        $m = System::getOptions('modules');
        return $m['roz' . $key] ?? $def;
    }

    public static function enabled() {
        $m = System::getOptions('modules');
        return intval($m[self::MODULE] ?? 0) == 1;
    }

    /**
     * Доступ до сторінок модуля: адміністратор або користувач з модулем у ролі
     */
    public static function allowed() {
        $u = System::getUser();
        return $u->rolename == 'admins' || strpos($u->modules ?? '', self::MODULE) !== false;
    }

    // ---------------------------------------------------------- statuses

    /**
     * Повний довідник статусів Rozetka (з документації, розділ OrdersStatuses)
     */
    public static function statusList() {
        return [
            1 => 'Нове замовлення', 2 => 'Комплектується. Дані підтверджені', 3 => 'Передано в службу доставки',
            4 => 'Доставляється', 5 => 'Чекає в пункті самовивозу', 6 => 'Замовлення виконано',
            7 => 'Не оброблено продавцем протягом дня', 11 => 'Не прийшов за замовленням', 12 => 'Відмова при отриманні',
            13 => 'Скасовано Адміністратором', 15 => 'Некоректний ТТН', 16 => 'Немає в наявності / брак',
            17 => 'Не влаштовують умови оплати', 18 => "Не вдалося зв'язатися", 19 => 'Замовлення повернено',
            20 => 'Товар не підходить за характеристиками', 24 => 'Скасування. Не влаштовує доставка', 25 => 'Тестове замовлення',
            26 => 'Опрацьовується менеджером', 27 => 'Вимагає доукомплектації', 28 => 'Неправильні контактні дані',
            29 => 'Ціна змінилася', 30 => 'Закінчився термін резерву', 31 => 'Скасування. Створено дублююче замовлення',
            33 => 'Умови доставки не влаштовують', 37 => 'Передоплата не влаштовує', 38 => 'Не влаштовує товар',
            40 => 'Клієнт передумав', 42 => 'Немає в наявності', 43 => 'Брак', 44 => 'Скасування. Фейкове замовлення',
            45 => 'Скасовано покупцем', 46 => 'Відновлено при продзвоні', 47 => 'Планується повторний дзвінок',
            49 => 'Повторне замовлення', 50 => 'Клієнт не оплатив', 51 => 'У кредиті відмовлено',
            52 => 'Нове замовлення за 1 клік', 53 => 'Очікує підтвердження клієнтом', 54 => 'Опрацьовано автоматично',
            55 => 'Очікує отримання оплати', 56 => 'Комплектується перевізником', 57 => 'Заплановано доставку',
            58 => 'Передано до служби доставки в Польщі', 59 => 'На митному контролі', 60 => 'Передано до служби доставки в Україні',
            61 => 'Заплановано передачу перевізникові', 62 => 'Змінено дату видачі', 63 => 'Обробляється службою доставки',
            64 => 'Доставляється в місто отримувача', 65 => 'Перебуває в місті отримувача', 67 => "Видано кур'єру в місті отримувача",
            68 => 'Отримано частково', 69 => 'Змінено дату чи адресу доставки', 71 => 'Скасування. Товар повернувся',
            72 => 'Скасування. Здійснено повернення коштів', 73 => 'У черзі на отримання', 74 => 'Сьогодні закінчується термін резерву',
            75 => 'Скасування. Скасовано КЦ', 76 => 'Очікує клієнта у почтоматі', 77 => 'Скасування. Відмовлено банком',
            78 => 'Змінено час видачі', 79 => 'Очікує модерації КЦ', 80 => 'Чекає на отримання від Продавця',
        ];
    }

    /**
     * Статуси, які має сенс виставляти з Zippy (рештою керує сама Rozetka за ТТН)
     */
    public static function sellerStatusList() {
        $all = self::statusList();
        $ids = [self::ST_PROCESSING, self::ST_PACKING, self::ST_WAITPAY, self::ST_TOCARRIER, self::ST_SHIPPED, self::ST_DONE,
                47, 42, 43, 40, 18, 28, 29, 17, 33, 37, 38];
        $out = [];
        foreach ($ids as $id) {
            $out[$id] = $all[$id];
        }
        return $out;
    }

    public static function statusName($id) {
        $all = self::statusList();
        return $all[intval($id)] ?? ('статус #' . intval($id));
    }

    /**
     * Групи замовлень для завантаження (orders/search?types=)
     */
    public static function typesList() {
        return [
            Api::TYPES_NEW       => 'Нові',
            Api::TYPES_INPROCESS => 'В обробці',
            Api::TYPES_SHIPPING  => 'Доставляються',
            Api::TYPES_DONE      => 'Успішно завершені',
            Api::TYPES_FAILED    => 'Неуспішно завершені',
            Api::TYPES_ALL       => 'Усі',
        ];
    }

    // ------------------------------------------------------------ orders

    /**
     * Чи вже імпортовано замовлення Rozetka з таким ID
     */
    public static function findImported($rozId) {
        $conn = \ZDB\DB::getConnect();
        $id = intval($rozId);
        return intval($conn->GetOne("select count(*) from documents_view where meta_name='Order' and content like '%<rozorder>{$id}</rozorder>%'")) > 0;
    }

    /**
     * Служба доставки Rozetka -> тип доставки Zippy
     */
    public static function deliveryType(array $roz) {
        $name = mb_strtolower(trim(($roz['delivery']['delivery_service_name'] ?? '') . ' ' . ($roz['delivery']['name_logo'] ?? '')));
        if (strlen($name) == 0) {
            return Document::DEL_SERVICE;
        }
        if (strpos($name, 'нова пошта') !== false || strpos($name, 'nova') !== false || strpos($name, 'novaposhta') !== false) {
            return Document::DEL_NP;
        }
        if (strpos($name, 'укрпошта') !== false || strpos($name, 'ukr') !== false) {
            return Document::DEL_UP;
        }
        if (strpos($name, 'meest') !== false || strpos($name, 'міст експрес') !== false) {
            return Document::DEL_MEEST;
        }
        if (strpos($name, 'rozetka') !== false || strpos($name, 'розетка') !== false) {
            return Document::DEL_ROZ;
        }
        if (strpos($name, 'самовив') !== false || strpos($name, 'магазину') !== false) {
            return Document::DEL_SELF;
        }
        return Document::DEL_SERVICE;
    }

    /**
     * Адреса доставки одним рядком
     */
    public static function deliveryAddress(array $roz) {
        $d = $roz['delivery'] ?? [];
        $parts = [];
        $city = $d['city']['title'] ?? $d['city']['city_name'] ?? $d['city']['name'] ?? '';
        if (strlen($city ?? '') > 0) {
            $parts[] = $city;
        }
        if (strlen($d['place_street'] ?? '') > 0) {
            $parts[] = $d['place_street'] . (strlen($d['place_house'] ?? '') > 0 ? ', ' . $d['place_house'] : '')
                     . (strlen($d['place_flat'] ?? '') > 0 ? ', кв. ' . $d['place_flat'] : '');
        }
        if (strlen($d['place_number'] ?? '') > 0) {
            $parts[] = 'відділення ' . $d['place_number'];
        }
        return implode(', ', $parts);
    }

    /**
     * Будує (не зберігає) документ «Замовлення» з замовлення Rozetka.
     *
     * @param  array $roz     замовлення з orders/search або orders/{id}
     * @param  array $warn    сюди складаються попередження (артикул не знайдено тощо)
     * @return Document|null  null, якщо жодного товару не вдалося знайти
     */
    public static function buildOrder(array $roz, array &$warn = []) {
        $doc = Document::create('Order');
        $pricetype = self::opt('pricetype', 'price1');

        // товари: шукаємо за артикулом
        $itlist = [];
        $j = 0;
        foreach ($roz['purchases'] ?? [] as $p) {
            if (intval($p['status'] ?? 1) == 0) {
                continue;   // видалений при редагуванні
            }
            $article = trim($p['item']['article'] ?? $p['item']['price_offer_id'] ?? '');
            $name = $p['item_name'] ?? $p['item']['name'] ?? '';
            if (strlen($article) == 0) {
                $warn[] = "Замовлення {$roz['id']}: у товару «{$name}» немає артикулу";
                continue;
            }
            $tovar = Item::getFirst('disabled <> 1 and item_code=' . Item::qstr($article));
            if ($tovar == null) {
                $warn[] = "Замовлення {$roz['id']}: артикул {$article} («{$name}») не знайдено в номенклатурі";
                continue;
            }
            $price = floatval($p['price_with_discount'] ?? 0);
            if ($price <= 0) {
                $price = floatval($p['price'] ?? 0);
            }
            $tovar->quantity = H::fqty($p['quantity'] ?? 1);
            $tovar->price = H::fa($price);
            $tovar->amount = $tovar->quantity * $tovar->price;
            $j++;
            $tovar->rowid = $j;
            $itlist[$j] = $tovar;
        }
        if (count($itlist) == 0) {
            return null;
        }
        $doc->packDetails('detaildata', $itlist);

        $d = $roz['delivery'] ?? [];
        $name  = trim($roz['user_title']['full_name'] ?? '');
        $phone = trim($roz['user_phone'] ?? '');
        $recipient = trim($d['recipient_title'] ?? '');
        $recphone  = trim($d['recipient_phone'] ?? '');
        $email = trim($d['email'] ?? '');
        if (strlen($name) == 0) {
            $name = $recipient;
        }
        if (strlen($phone) == 0) {
            $phone = $recphone;
        }

        $amount = floatval($roz['amount_with_discount'] ?? 0);
        if ($amount <= 0) {
            $amount = floatval($roz['amount'] ?? 0);
        }
        $cost = floatval($roz['cost_with_discount'] ?? 0);
        if ($cost <= 0) {
            $cost = $amount;
        }

        $doc->document_date = strtotime($roz['created'] ?? '') ?: time();
        $doc->amount = H::fa($amount);
        $doc->payamount = H::fa($cost);

        $hd = &$doc->headerdata;
        $hd['rozorder']      = intval($roz['id']);
        $hd['rozorderback']  = 0;
        $hd['rozstatus']     = intval($roz['status'] ?? 0);
        $hd['rozclient']     = $name;
        $hd['outnumber']     = strval($roz['id']);
        $hd['name']          = $name;
        $hd['phone']         = $phone;
        $hd['email']         = $email;
        $hd['pricetype']     = $pricetype;
        $hd['salesource']    = self::opt('salesource', 0);
        $hd['paytype']       = intval(self::opt('paytype', 2));
        $hd['payment']       = intval(self::opt('mf', 0));
        $hd['store']         = intval(self::opt('store', 0));
        $hd['delivery']      = self::deliveryType($roz);
        $hd['delivery_name'] = Document::getDeliveryTypes(true)[$hd['delivery']] ?? '';
        $hd['ship_address']  = self::deliveryAddress($roz);
        if ($hd['delivery'] == Document::DEL_NP) {
            $hd['npaddress'] = $hd['ship_address'];
        }
        if (strlen($roz['ttn'] ?? '') > 0) {
            $hd['ttn'] = $roz['ttn'];
        }

        // нотатки — те, що не має власного поля
        $n = ["Rozetka №{$roz['id']}"];
        $n[] = 'Статус: ' . self::statusName($roz['status'] ?? 0);
        if (strlen($recipient) > 0 && $recipient != $name) {
            $n[] = 'Одержувач: ' . $recipient . (strlen($recphone) > 0 ? ' ' . $recphone : '');
        }
        if (strlen($d['delivery_service_name'] ?? '') > 0) {
            $n[] = 'Доставка: ' . $d['delivery_service_name'] . (floatval($d['cost'] ?? 0) > 0 ? ' ' . H::fa($d['cost']) : '');
        }
        if (strlen($hd['ship_address']) > 0) {
            $n[] = 'Адреса: ' . $hd['ship_address'];
        }
        if (strlen($roz['payment_type_name'] ?? '') > 0) {
            $n[] = 'Оплата: ' . $roz['payment_type_name'] . (!empty($roz['is_payed']) ? ' (сплачено)' : '');
        }
        if (strlen($roz['comment'] ?? '') > 0) {
            $n[] = 'Коментар: ' . $roz['comment'];
        }
        $doc->notes = implode('; ', $n);

        return $doc;
    }

    /**
     * Зберігає побудований документ: номер, контрагент, статуси.
     * Повертає збережений документ.
     */
    public static function importOrder(Document $doc) {
        $doc->document_number = $doc->nextNumber();
        if (strlen($doc->document_number ?? '') == 0) {
            $doc->document_number = 'RZ-00001';
        }

        if (intval(self::opt('insertcust', 0)) == 1) {
            $phone = \App\Util::handlePhone($doc->headerdata['phone'] ?? '');
            $email = $doc->headerdata['email'] ?? '';
            $cust = null;
            if (strlen($phone ?? '') > 0) {
                $cust = Customer::getByPhone($phone);
            }
            if ($cust == null && strlen($email) > 0) {
                $cust = Customer::getByEmail($email);
            }
            if ($cust == null && strlen($doc->headerdata['name'] ?? '') > 0 && (strlen($phone ?? '') > 0 || strlen($email) > 0)) {
                $cust = new Customer();
                $cust->customer_name = $doc->headerdata['name'];
                $cust->phone = $phone;
                $cust->email = $email;
                $cust->type = Customer::TYPE_BAYER;
                $cust->comment = 'Клієнт Rozetka';
                $cust->save();
            }
            if ($cust != null) {
                $doc->customer_id = $cust->customer_id;
                $doc->customer_name = $cust->customer_name;
            }
        }

        $doc->save();
        $doc->updateStatus(Document::STATE_NEW);
        $doc->updateStatus(Document::STATE_INPROCESS);
        return $doc;
    }

    /**
     * Документи, чий статус ще не відправлено на Rozetka
     */
    public static function pendingExport() {
        return Document::find("meta_name='Order' and content like '%<rozorderback>0</rozorderback>%' and state <> " . Document::STATE_NEW, 'document_id desc');
    }

    // -------------------------------------------------------------- feed

    /**
     * XML-прайс у форматі, який вимагає Rozetka
     * (sellerhelp.rozetka.com.ua/p185-pricelist-requirements.html)
     */
    public static function feed() {
        $firm = System::getOptions('firm');
        $common = System::getOptions('common');
        $pricetype = self::opt('pricetype', 'price1');
        $store = intval(self::opt('store', 0));
        $vendor = trim(self::opt('vendor', ''));
        $useimages = intval($common['useimages'] ?? 0) == 1;

        $x = new \XMLWriter();
        $x->openMemory();
        $x->setIndent(true);
        $x->startDocument('1.0', 'UTF-8');
        $x->startElement('yml_catalog');
        $x->writeAttribute('date', date('Y-m-d H:i'));
        $x->startElement('shop');
        $shopName = trim($firm['firm_name'] ?? '');
        if (strlen($shopName) == 0) {
            $shopName = 'Zippy';
        }
        $x->writeElement('name', mb_substr($shopName, 0, 255));
        $x->writeElement('company', mb_substr($shopName, 0, 255));
        $x->writeElement('url', rtrim(_BASEURL, '/'));

        $x->startElement('currencies');
        $x->startElement('currency');
        $x->writeAttribute('id', 'UAH');
        $x->writeAttribute('rate', '1');
        $x->endElement();
        $x->endElement();

        $cats = \App\Entity\Category::find('', 'cat_name');
        $x->startElement('categories');
        foreach ($cats as $c) {
            $x->startElement('category');
            $x->writeAttribute('id', intval($c->cat_id));
            if (intval($c->parent_id) > 0) {
                $x->writeAttribute('parentId', intval($c->parent_id));
            }
            $x->text(trim($c->cat_name));
            $x->endElement();
        }
        $x->endElement();

        $x->startElement('offers');
        $count = 0;
        foreach (Item::findYield("disabled <> 1 and item_code <> ''", 'item_id') as $item) {
            if (intval($item->noshop ?? 0) == 1) {
                continue;   // ознака «не для магазину» лежить у detail
            }
            $price = floatval($item->getPrice($pricetype, $store));
            if ($price <= 0) {
                continue;
            }
            $qty = intval(floor(floatval($item->getQuantity($store))));
            if ($qty < 0) {
                $qty = 0;
            }
            $x->startElement('offer');
            // id має бути незмінним і лише з латиниці/цифр — беремо item_id
            $x->writeAttribute('id', intval($item->item_id));
            $x->writeAttribute('available', $qty > 0 ? 'true' : 'false');
            $x->writeElement('price', number_format($price, 2, '.', ''));
            $x->writeElement('currencyId', 'UAH');
            $x->writeElement('categoryId', intval($item->cat_id));
            if ($useimages && intval($item->image_id) > 0) {
                $x->writeElement('picture', _BASEURL . 'loadshopimage.php?id=' . intval($item->image_id));
            }
            $v = trim($item->manufacturer ?? '');
            $x->writeElement('vendor', strlen($v) > 0 ? $v : $vendor);
            $x->writeElement('name', mb_substr(trim($item->itemname), 0, 255));
            $x->writeElement('stock_quantity', $qty);
            $x->writeElement('article', trim($item->item_code));
            $desc = trim(strip_tags($item->description ?? ''));
            if (strlen($desc) > 0) {
                $x->startElement('description');
                $x->writeCdata(mb_substr($desc, 0, 50000));
                $x->endElement();
            }
            if (strlen($item->bar_code ?? '') > 0) {
                $x->startElement('param');
                $x->writeAttribute('name', 'Штрихкод');
                $x->text(trim($item->bar_code));
                $x->endElement();
            }
            $x->endElement();
            $count++;
        }
        $x->endElement(); // offers
        $x->endElement(); // shop
        $x->endElement(); // yml_catalog
        $x->endDocument();
        return $x->outputMemory();
    }

    /**
     * Ключ, яким захищено адресу прайсу
     */
    public static function feedKey() {
        return self::opt('feedkey', '');
    }

    public static function feedUrl() {
        $k = self::feedKey();
        return strlen($k) > 0 ? _BASEURL . 'rozetka.php?key=' . $k : '';
    }
}
