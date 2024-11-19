<?php

namespace App\Modules\Tecdoc;

class DBHelper
{
    private $type = "passenger";
    private $conn;

    public function __construct($type = 'passenger') {
        $this->type = $type;
        $modules = \App\System::getOptions("modules");
        if ($modules['td_seconddb'] == 1) {
            $_config = parse_ini_file(_ROOT . 'config/config.ini', true);


            $this->conn = \ADONewConnection("mysqli");
            $this->conn->NConnect($_config['tecdocdb']['host'], $_config['tecdocdb']['user'], $_config['tecdocdb']['pass'], $_config['tecdocdb']['name']);
            $this->conn->Execute("SET NAMES 'utf8'");
        } else {
            $this->conn = \ZDB\DB::getConnect();
        }
    }


    public function getBrands() {
        switch($this->type) {
            case 'passenger':
                $where = " AND ispassengercar = 'True'";
                break;
            case 'commercial':
                $where = " AND iscommercialvehicle = 'True'";
                break;
            case 'motorbike':
                $where = " AND ismotorbike  = 'True' AND haslink = 'True'";
                break;
            case 'engine':
                $where = " AND isengine = 'True'";
                break;
            case 'axle':
                $where = " AND isaxle = 'True'";
                break;
        }

        $order = $this->type == 'motorbike' ? 'description' : 'matchcode';

        $res = $this->conn->Execute("
            SELECT id, description  
            FROM manufacturers
            WHERE canbedisplayed = 'True' " . $where . "
            ORDER BY " . $order);
        $list = array();
        foreach ($res as $row) {
            $list[$row['id']] = $row['description'];
        }
        return $list;
    }

    public function getAllBrands() {

        $res = $this->conn->Execute("
            SELECT   description  
            FROM manufacturers
            WHERE canbedisplayed = 'True'  
            ORDER BY description");
        $list = array();
        foreach ($res as $row) {
            $list[] = $row['description'];
        }
        return $list;
    }

    public function getModels($brand_id) {
        switch($this->type) {
            case 'passenger':
                $where = " AND ispassengercar = 'True'";
                break;
            case 'commercial':
                $where = " AND iscommercialvehicle = 'True'";
                break;
            case 'motorbike':
                $where = " AND ismotorbike  = 'True' AND haslink = 'True'";
                break;
            case 'engine':
                $where = " AND isengine = 'True'";
                break;
            case 'axle':
                $where = " AND isaxle = 'True'";
                break;
        }

        $order = $this->type == 'motorbike' ? 'description' : 'matchcode';

        $res = $this->conn->Execute("
            SELECT id, fulldescription ,constructioninterval  
            FROM models
            WHERE canbedisplayed = 'True'
            AND manufacturerid = " . (int)$brand_id . " " . $where . "
            ORDER BY fulldescription");
        $list = array();
        foreach ($res as $row) {
            $list[$row['id']] = $row['fulldescription'] . " (" . $row['constructioninterval'] . ")";;
        }
        return $list;
    }

    public function getModifs($model_id) {
        switch($this->type) {
            case 'passenger':
                $sql = "
                    SELECT id, fulldescription  
                    FROM passanger_cars   
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND ispassengercar = 'True'";

                break;
            case 'commercial':
                $sql = "
                    SELECT id, fulldescription  
                    FROM commercial_vehicles 
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND iscommercialvehicle = 'True'";
                break;
            case 'motorbike':
                $sql = "
                    SELECT id, fulldescription  
                    FROM motorbikes 
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND ismotorbike = 'True'";
                break;
            case 'engine':
                $sql = "
                    SELECT id, fulldescription 
                    FROM engines 
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND isengine = 'True'";
                break;
            case 'axle':
                $sql = "
                    SELECT id, fulldescription 
                    FROM axles
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND isaxle = 'True'";
                break;
        }

        $sql .= " ORDER BY fulldescription";

        $res = $this->conn->Execute($sql);
        $list = array();
        foreach ($res as $row) {
            $list[$row['id']] = $row['fulldescription'];
        }
        return $list;
    }

    public function getModifDetail($id) {
        switch($this->type) {
            case 'passenger':
                $sql = "
                    SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM passanger_car_attributes a  
                  
                    WHERE   a.passangercarid = " . (int)$id;

                break;
            case 'commercial':
                $sql = "
                    SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM commercial_vehicle_attributes a  
                  
                    WHERE   a.commercialvehicleid = " . (int)$id;
                break;
            case 'motorbike':
                $sql = "
                    SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM motorbike_attributes a  
                  
                    WHERE   a.motorbikeid = " . (int)$id;
                break;
            case 'engine':
                $sql = "
                    SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM engine_attributes a  
                  
                    WHERE   a.engineid = " . (int)$id;
                break;
            case 'axle':
                $sql = "
                   SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM axle_attributes a  
                  
                    WHERE   a.axleid = " . (int)$id;
                break;
        }

        $sql .= " ORDER BY attributegroup,attributetype";

        $res = $this->conn->Execute($sql);
        $list = array();
        foreach ($res as $row) {
            $list[$row['attributetype']] = $row['displayvalue'];
        }
        return $list;
    }

    public function getTree($id) {
        switch($this->type) {
            case 'passenger':
                $sql = "
                    SELECT id,parentId, description
                        FROM passanger_car_trees WHERE passangercarid=" . (int)$id . " 
                         
                        ORDER BY id
                    ";

                break;
            case 'commercial':
                $sql = "
                    SELECT id,parentId, description
                        FROM commercial_vehicle_trees WHERE commercialvehicleid=" . (int)$id . " 
                        ORDER BY id
                    ";
                break;
            case 'motorbike':
                $sql = "
                     SELECT id,parentId, description
                        FROM motorbike_trees WHERE motorbikeid=" . (int)$id . " 
                        ORDER BY id
                    ";
                break;
            case 'engine':
                $sql = "
                    SELECT id,parentId, description
                        FROM engine_trees WHERE engineid=" . (int)$id . " 
                        ORDER BY id
                    ";
                break;
            case 'axle':
                $sql = "
                    SELECT id,parentId, description
                        FROM axle_trees WHERE axleid=" . (int)$id . " 
                        ORDER BY id
                    ";
                break;
        }


        $res = $this->conn->Execute($sql);
        $list = array();
        foreach ($res as $row) {
            $item = new \App\DataItem();
            $item->intree = false;
            $item->id = $row['id'];
            $item->parentId = $row['parentId'];
            $item->description = $row['description'];
            $list[$item->id] = $item;
        }
        return $list;
    }


    public function searchByCategory($cat_id, $modif_id) {
        switch($this->type) {
            case 'passenger':
                $sql = "
                   SELECT s.id as brand_id, al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN passanger_car_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN passanger_car_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.passangercarid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 2
                    ORDER BY s.description, al.datasupplierarticlenumber";


                break;
            case 'commercial':
                $sql = "
                   SELECT s.id as brand_id, al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN commercial_vehicle_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN commercial_vehicle_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.commertialvehicleid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 16
                    ORDER BY s.description, al.datasupplierarticlenumber";
                break;
            case 'motorbike':
                $sql = "
                   SELECT s.id as brand_id, al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN motorbike_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN motorbike_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.motorbikeid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 777
                    ORDER BY s.description, al.datasupplierarticlenumber";
                break;
            case 'engine':
                $sql = "
                  SELECT s.id as brand_id, al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN engine_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN engine_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.engineid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 14
                    ORDER BY s.description, al.datasupplierarticlenumber";
                break;
            case 'axle':
                $sql = "
                   SELECT s.id as brand_id, al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN axle_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN axle_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.axleid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 19
                    ORDER BY s.description, al.datasupplierarticlenumber";
                break;
        }


        $res = $this->conn->Execute($sql);
        $list = array();
        foreach ($res as $row) {
            $item = new \App\DataItem();
            $item->part_number = $row['part_number'];
            $item->supplier_name = $row['supplier_name'];
            $item->product_name = $row['product_name'];
            $item->brand_id = $row['brand_id'];
            $list[] = $item;
        }
        return $list;

    }

    public function searchByBrandAndCode($code, $brand) {

        $code = $this->conn->qstr($code);

        $sql = " SELECT  DISTINCT s.id as brand_id, s.description as supplier_name,al.datasupplierarticlenumber as part_number,prd.description as product_name 
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN passanger_car_prd prd on prd.id = al.productid
            WHERE   DataSupplierArticleNumber ={$code} ";
        if (strlen($brand) > 0) {
            $brand = $this->conn->qstr($brand);
            $sql .= "  and s.description  = {$brand} ";
        }
        $sql .= " union ";
        $sql .= " SELECT  DISTINCT s.id as brand_id, s.description as supplier_name,al.datasupplierarticlenumber as part_number ,prd.description as product_name  
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN commercial_vehicle_prd prd on prd.id = al.productid
            WHERE   DataSupplierArticleNumber ={$code} ";
        if (strlen($brand) > 0) {
            $brand = $this->conn->qstr($brand);
            $sql .= "  and s.description  = {$brand} ";
        }


        $sql .= " union ";
        $sql .= " SELECT  DISTINCT s.id as brand_id, s.description as supplier_name,al.datasupplierarticlenumber as part_number ,prd.description as product_name  
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN motorbike_prd prd on prd.id = al.productid
            WHERE   DataSupplierArticleNumber ={$code} ";
        if (strlen($brand) > 0) {
            $brand = $this->conn->qstr($brand);
            $sql .= "  and s.description  = {$brand} ";
        }


        $res = $this->conn->Execute($sql);


        $list = array();
        foreach ($res as $row) {
            $item = new \App\DataItem();
            $item->part_number = $row['part_number'];
            $item->supplier_name = $row['supplier_name'];
            $item->product_name = $row['product_name'];
            $item->brand_id = $row['brand_id'];
            $list[] = $item;
        }


        return $list;
    }

    public function searchByBarCode($barcode) {

        $barcode = $this->conn->qstr($barcode);

        $sql = " SELECT  DISTINCT s.id as brand_id, s.description as supplier_name,al.datasupplierarticlenumber as part_number,prd.description as product_name 
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN passanger_car_prd prd on prd.id = al.productid
            JOIN article_ean ean on ean.supplierid = s.id and  ean.datasupplierarticlenumber = al.datasupplierarticlenumber
            WHERE   ean.ean ={$barcode} ";
        $sql .= " union ";
        $sql .= " SELECT  DISTINCT s.id as brand_id, s.description as supplier_name,al.datasupplierarticlenumber as part_number,prd.description as product_name 
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN commercial_vehicle_prd prd on prd.id = al.productid
            JOIN article_ean ean on ean.supplierid = s.id and  ean.datasupplierarticlenumber = al.datasupplierarticlenumber
            WHERE   ean.ean ={$barcode} ";
        $sql .= " union ";
        $sql .= " SELECT  DISTINCT s.id as brand_id, s.description as supplier_name,al.datasupplierarticlenumber as part_number,prd.description as product_name 
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN motorbike_prd prd on prd.id = al.productid
            JOIN article_ean ean on ean.supplierid = s.id and  ean.datasupplierarticlenumber = al.datasupplierarticlenumber
            WHERE   ean.ean ={$barcode} ";


        $res = $this->conn->Execute($sql);


        $list = array();
        foreach ($res as $row) {
            $item = new \App\DataItem();
            $item->part_number = $row['part_number'];
            $item->supplier_name = $row['supplier_name'];
            $item->product_name = $row['product_name'];
            $item->brand_id = $row['brand_id'];
            $list[] = $item;
        }


        return $list;
    }

    public function getAttributes($number, $brand_id) {
        $list = array();
        $number = $this->conn->qstr($number);

        $r = $this->conn->GetOne("   SELECT   ArticleStateDisplayValue FROM articles WHERE DataSupplierArticleNumber=" . $number . " AND supplierId=" . $brand_id);
        if (strlen($r) > 0) {
            $list['Статус'] = $r;
        }

        $r = $this->conn->GetOne("   SELECT   ean FROM article_ean WHERE datasupplierarticlenumber=" . $number . " AND supplierid=" . $brand_id);
        if (strlen($r) > 0) {
            $list['Штрих-код'] = $r;
        }


        $res = $this->conn->Execute("SELECT   description, displayvalue FROM article_attributes WHERE datasupplierarticlenumber=" . $number . "  AND supplierId=" . $brand_id);
        foreach ($res as $row) {
            $list[$row['description']] = $row['displayvalue'];
        }

        return $list;

    }

    public function getImage($number, $brand_id) {

        $number = $this->conn->qstr($number);

        $row = $this->conn->GetRow("SELECT Description, PictureName FROM article_images WHERE DocumentType ='Picture'  and  DataSupplierArticleNumber=" . $number . "  AND supplierId=" . $brand_id . "    limit 0,1");

        return $row;
    }

    public function getOemNumbers($number, $brand_id) {

        $number = $this->conn->qstr($number);
        $list = array();
        $col = $this->conn->GetCol("SELECT DISTINCT a.OENbr FROM article_oe a 
            WHERE a.datasupplierarticlenumber=" . $number . " AND a.manufacturerId=" . $brand_id . "
            ORDER BY a.OENbr");

        return $col;
    }

    public function getReplace($number, $brand_id) {

        $number = $this->conn->qstr($number);
        $list = array();
        $rs = $this->conn->Execute("SELECT s.id, s.description as  supplier, a.replacenbr   FROM article_rn a 
            JOIN suppliers s ON s.id=a.replacesupplierid  
            WHERE a.datasupplierarticlenumber=" . $number . " AND a.supplierid=" . $brand_id . "
             ");

        foreach ($rs as $r) {
            $item = new \App\DataItem();
            $item->sid = $r['id'];
            $item->supplier = $r['supplier'];
            $item->replacenbr = $r['replacenbr'];
            $list[] = $item;
        }

        return $list;
    }

    public function getArtParts($number, $brand_id) {

        $number = $this->conn->qstr($number);
        $list = array();
        $rs = $this->conn->Execute("SELECT DISTINCT description as Brand, Quantity, PartsDataSupplierArticleNumber as partnumber FROM article_parts 
            JOIN suppliers ON id=PartsSupplierId 
            WHERE  DataSupplierArticleNumber=" . $number . " AND  supplierid=" . $brand_id . "
             ");

        foreach ($rs as $r) {
            $item = new \App\DataItem();
            $item->Brand = $r['Brand'];
            $item->Quantity = $r['Quantity'];
            $item->partnumber = $r['partnumber'];
            $list[] = $item;
        }

        return $list;
    }

    public function getArtCross($number, $brand_id) {

        $number = $this->conn->qstr($number);
        $list = array();
        $r = $this->conn->Execute("SELECT DISTINCT s.description, c.PartsDataSupplierArticleNumber as crossnumber   
            FROM article_oe a 
            JOIN manufacturers m ON m.id=a.manufacturerId 
            JOIN article_cross c ON c.OENbr=a.OENbr
            JOIN suppliers s ON s.id=c.SupplierId
            WHERE a.datasupplierarticlenumber=" . $number . " AND a.manufacturerId=" . $brand_id . "
             ");

        foreach ($r as $row) {
            $item = new \App\DataItem();
            $item->description = $row['description'];
            $item->cross = $row['crossnumber'];

            $list[] = $item;
        }
        return $list;
    }

    public function getArtVehicles($number, $brand_id) {

        $number = $this->conn->qstr($number);
        $list = array();
        $rs = $this->conn->Execute("SELECT linkageTypeId, linkageId FROM article_li    
              WHERE  DataSupplierArticleNumber=" . $number . " AND  supplierId=" . $brand_id . "
             ");

        foreach ($rs as $r) {
            switch($r['linkageTypeId']) {
                case 'PassengerCar':
                    $sql = "SELECT DISTINCT     p.constructioninterval, p.fulldescription FROM passanger_cars p 
                        
                        WHERE p.id=" . $r['linkageId'];
                    break;
                case 'CommercialVehicle':
                    $sql = "SELECT DISTINCT      p.constructioninterval, p.fulldescription FROM commercial_vehicles p 
                        
                        WHERE p.id=" . $r['linkageId'];

                    break;
                case 'Motorbike':
                    $sql = "SELECT DISTINCT       p.constructioninterval, p.fulldescription FROM motorbikes p 
                        
                        WHERE p.id=" . $r['linkageId'];

                    break;
                case 'Engine':
                    $sql = "SELECT DISTINCT       p.constructioninterval, p.fulldescription FROM  engines  p 
                        
                        WHERE p.id=" . $r['linkageId'];

                    break;
                case 'Axle':
                    $sql = "SELECT DISTINCT      p.constructioninterval, p.fulldescription FROM axles p 
                        
                        WHERE p.id=" . $r['linkageId'];

                    break;

            }

            if (strlen($sql) > 0) {
                $r = $this->conn->Execute($sql);
                foreach ($r as $row) {
                    $item = new \App\DataItem();


                    $item->years = $row['constructioninterval'];
                    $item->desc = $row['fulldescription'];

                    $list[] = $item;
                }


            }


        }

        return $list;
    }
}
