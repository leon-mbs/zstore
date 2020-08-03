<?php
namespace App\Modules\Tecdoc;

class DBHelper 
{
    private $type ="passenger";
    private $conn  ;
    
    public function __construct($type = 'passenger')
    {
        $this->type = $type;
       
        $this->conn = \ADONewConnection("mysqli");
        $this->conn->NConnect("localhost", "root", "root", "tecdoc");

        $this->conn->Execute("SET NAMES 'utf8'");
          
    }  
    
    
    public function getBrands() {
        switch ($this->type) {
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
            ORDER BY " . $order) ;
        $list = array();    
        foreach($res as  $row) {
           $list[$row['id']]=  $row['description'];
        }   
        return $list;
    } 
   
    public function getAllBrands() {
 
        $res = $this->conn->Execute("
            SELECT   description  
            FROM manufacturers
            WHERE canbedisplayed = 'True'  
            ORDER BY description"  ) ;
        $list = array();    
        foreach($res as  $row) {
           $list[]=  $row['description'];
        }   
        return $list;
    } 
  
    public function getModels($brand_id) {
        switch ($this->type) {
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
            ORDER BY fulldescription") ;
        $list = array();    
        foreach($res as  $row) {
           $list[$row['id']]=  $row['fulldescription'] ." (".$row['constructioninterval'].")";;
        }   
        return $list;
    } 

    public function getModifs($model_id) {
        switch ($this->type) {
            case 'passenger':
                $sql="
                    SELECT id, fulldescription  
                    FROM passanger_cars   
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND ispassengercar = 'True'";
                    
                break;
            case 'commercial':
                $sql="
                    SELECT id, fulldescription  
                    FROM commercial_vehicles 
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND iscommercialvehicle = 'True'";
                break;
            case 'motorbike':
                $sql="
                    SELECT id, fulldescription  
                    FROM motorbikes 
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND ismotorbike = 'True'";
                break;
            case 'engine':
                $sql="
                    SELECT id, fulldescription 
                    FROM engines 
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND isengine = 'True'";
                break;
            case 'axle':
                $sql="
                    SELECT id, fulldescription 
                    FROM axles
                    
                    WHERE canbedisplayed = 'True'
                    AND modelid = " . (int)$model_id . " AND isaxle = 'True'";
                break;
        }

        $sql .=  " ORDER BY fulldescription";

        $res = $this->conn->Execute($sql) ;
        $list = array();    
        foreach($res as  $row) {
           $list[$row['id']]=  $row['fulldescription']  ;
        }   
        return $list;
    } 
    
    public function getModifDetail($id) {
        switch ($this->type) {
            case 'passenger':
                $sql="
                    SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM passanger_car_attributes a  
                  
                    WHERE   a.passangercarid = " . (int)$id  ;
                    
                break;
            case 'commercial':
                $sql="
                    SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM commercial_vehicle_attributes a  
                  
                    WHERE   a.commercialvehicleid = " . (int)$id  ;
                break;
            case 'motorbike':
                $sql="
                    SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM motorbike_attributes a  
                  
                    WHERE   a.motorbikeid = " . (int)$id  ;
                break;
            case 'engine':
                $sql="
                    SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM engine_attributes a  
                  
                    WHERE   a.engineid = " . (int)$id  ;
                break;
            case 'axle':
                $sql="
                   SELECT   a.attributegroup, a.attributetype, a.displaytitle, a.displayvalue 
                    FROM axle_attributes a  
                  
                    WHERE   a.axleid = " . (int)$id  ;
                break;
        }

        $sql .=  " ORDER BY attributegroup,attributetype";

        $res = $this->conn->Execute($sql) ;
        $list = array();    
        foreach($res as  $row) {
           $list[ $row['attributetype']] =  $row['displayvalue'] ;
        }   
        return $list;
    } 
  
    public function getTree($id) {
        switch ($this->type) {
            case 'passenger':
                $sql="
                    SELECT id,parentId, description
                        FROM passanger_car_trees WHERE passangercarid=" . (int)$id .  " 
                         
                        ORDER BY id
                    "   ;
                    
                break;
            case 'commercial':
                $sql="
                    SELECT id,parentId, description
                        FROM commercial_vehicle_trees WHERE commercialvehicleid=" . (int)$id .  " 
                        ORDER BY id
                    "   ;
                break;
            case 'motorbike':
                $sql="
                     SELECT id,parentId, description
                        FROM motorbike_trees WHERE motorbikeid=" . (int)$id .  " 
                        ORDER BY id
                    "   ;
                break;
            case 'engine':
                $sql="
                    SELECT id,parentId, description
                        FROM engine_trees WHERE engineid=" . (int)$id .  " 
                        ORDER BY id
                    "   ;
                break;
            case 'axle':
                $sql="
                    SELECT id,parentId, description
                        FROM axle_trees WHERE axleid=" . (int)$id .  " 
                        ORDER BY id
                    "   ;
                break;
        }

        

        $res = $this->conn->Execute($sql) ;
        $list = array();    
        foreach($res as  $row) {
           $item = new \App\DataItem() ;
           $item->intree = false;
           $item->id = $row['id'];
           $item->parentId = $row['parentId'];
           $item->description = $row['description'];
           $list[$item->id] =  $item;
        }   
        return $list;
    } 

       
    public function searchByCategory($cat_id,$modif_id) {
       switch ($this->type) {
            case 'passenger':
                $sql="
                   SELECT al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN passanger_car_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN passanger_car_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.passangercarid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 2
                    ORDER BY s.description, al.datasupplierarticlenumber"   ;
 
                    
                break;
            case 'commercial':
                $sql="
                   SELECT al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN commercial_vehicle_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN commercial_vehicle_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.commertialvehicleid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 2
                    ORDER BY s.description, al.datasupplierarticlenumber"   ;
                break;
            case 'motorbike':
                $sql="
                   SELECT al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN motorbike_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN motorbike_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.motorbikeid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 2
                    ORDER BY s.description, al.datasupplierarticlenumber"   ;
                break;
            case 'engine':
                $sql="
                  SELECT al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN engine_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN engine_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.engineid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 2
                    ORDER BY s.description, al.datasupplierarticlenumber"   ;
                break;
            case 'axle':
                $sql="
                   SELECT al.datasupplierarticlenumber as part_number, s.description as  supplier_name, prd.description as product_name
                    FROM article_links al 
                    JOIN axle_pds pds on al.supplierid = pds.supplierid
                    JOIN suppliers s on s.id = al.supplierid
                    JOIN axle_prd prd on prd.id = al.productid
                    WHERE al.productid = pds.productid
                    AND al.linkageid = pds.axleid
                    AND al.linkageid = " . (int)$modif_id . "
                    AND pds.nodeid = " . (int)$cat_id . "
                    AND al.linkagetypeid = 2
                    ORDER BY s.description, al.datasupplierarticlenumber"   ;
                break;
        }

        

        $res = $this->conn->Execute($sql) ;
        $list = array();    
        foreach($res as  $row) {
           $item = new \App\DataItem() ;
           $item->part_number = $row['part_number'];           
           $item->supplier_name = $row['supplier_name'];           
           $item->product_name = $row['product_name'];           
           $list[] =  $item;
        }   
        return $list;

    }    
    
    public function searchByBrandAndCode($code,$brand) {
       
       $code = $this->conn->qstr($code);
        
       $sql = " SELECT  DISTINCT  s.description as supplier_name,al.datasupplierarticlenumber as part_number,prd.description as product_name 
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN passanger_car_prd prd on prd.id = al.productid
            WHERE   DataSupplierArticleNumber ={$code} "; 
       if(strlen($brand)>0) {
           $brand = $this->conn->qstr($brand);
           $sql .=  "  and s.description  = {$brand} ";
       }
       $sql .= " union ";
       $sql .= " SELECT  DISTINCT  s.description as supplier_name,al.datasupplierarticlenumber as part_number ,prd.description as product_name  
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN commercial_vehicle_prd prd on prd.id = al.productid
            WHERE   DataSupplierArticleNumber ={$code} "; 
       if(strlen($brand)>0) {
           $brand = $this->conn->qstr($brand);
           $sql .=  "  and s.description  = {$brand} ";
       }
        
       
       $sql .= " union ";
       $sql .= " SELECT  DISTINCT  s.description as supplier_name,al.datasupplierarticlenumber as part_number ,prd.description as product_name  
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN motorbike_prd prd on prd.id = al.productid
            WHERE   DataSupplierArticleNumber ={$code} "; 
       if(strlen($brand)>0) {
           $brand = $this->conn->qstr($brand);
           $sql .=  "  and s.description  = {$brand} ";
       }
        
       
       
       
       $res = $this->conn->Execute($sql) ;
            
            
       $list = array();     
        foreach($res as  $row) {
           $item = new \App\DataItem() ;
           $item->part_number = $row['part_number'];           
           $item->supplier_name = $row['supplier_name'];           
           $item->product_name = $row['product_name'];           
           $list[] =  $item;
        }  
        
        
        
        
         
        return $list;      
    }
    
    public function searchByBarCode($barcode  ) {
       
       $barcode = $this->conn->qstr($barcode);
        
       $sql = " SELECT  DISTINCT  s.description as supplier_name,al.datasupplierarticlenumber as part_number,prd.description as product_name 
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN passanger_car_prd prd on prd.id = al.productid
            JOIN article_ean ean on ean.supplierid = s.id and  ean.datasupplierarticlenumber = al.datasupplierarticlenumber
            WHERE   ean.ean ={$barcode} "; 
            $sql .= " union ";
       $sql .= " SELECT  DISTINCT  s.description as supplier_name,al.datasupplierarticlenumber as part_number,prd.description as product_name 
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN commercial_vehicle_prd prd on prd.id = al.productid
            JOIN article_ean ean on ean.supplierid = s.id and  ean.datasupplierarticlenumber = al.datasupplierarticlenumber
            WHERE   ean.ean ={$barcode} "; 
            $sql .= " union ";
       $sql .= " SELECT  DISTINCT  s.description as supplier_name,al.datasupplierarticlenumber as part_number,prd.description as product_name 
            FROM article_links al
            JOIN suppliers s on s.id = al.supplierid
            JOIN motorbike_prd prd on prd.id = al.productid
            JOIN article_ean ean on ean.supplierid = s.id and  ean.datasupplierarticlenumber = al.datasupplierarticlenumber
            WHERE   ean.ean ={$barcode} "; 
             
 
        
       
       
       
       $res = $this->conn->Execute($sql) ;
            
            
       $list = array();     
        foreach($res as  $row) {
           $item = new \App\DataItem() ;
           $item->part_number = $row['part_number'];           
           $item->supplier_name = $row['supplier_name'];           
           $item->product_name = $row['product_name'];           
           $list[] =  $item;
        }  
        
        
        
        
         
        return $list;      
    }
    
         
}
