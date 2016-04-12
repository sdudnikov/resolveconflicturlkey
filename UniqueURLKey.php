<?php
require_once 'abstract.php';

class Mage_UrlKey_Resolve_Conflict extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        foreach($this->_getStoresId() as $store_id){
            $this->_resolveConflictUrlKey($store_id);
        }

    }


    private function _resolveConflictUrlKey($store_id)
    {
        $data = $this->_getSameUrlKeyWithProductsId($store_id);
        foreach($data as $name => $value){
            foreach($value as $product_id => $t){
                $product = Mage::getModel("catalog/product")->setStoreId($store_id)->load($product_id);
                $product->setUrlKey($this->createUniqueUrlKey($product));

                try{
                    $product->save();
                }
                catch(Exception $e){
                    echo $e->getMessage();
                }
            }
        }
    }


    protected function createUniqueUrlKey($product){
        return $product->formatUrlKey($product->getName()." ".$product->getSku());
    }



    private function _getSameUrlKeyWithProductsId($store_id)
    {
        $read_con = Mage::getSingleton('core/resource')->getConnection('core_read');

        $query = "select t1.store_id as store_id, t1.entity_id as product_id_1, t1.value as value, t2.entity_id as product_id_2
                  from bradford.catalog_product_entity_varchar as t1
                  inner join bradford.catalog_product_entity_varchar as t2 on (t1.value = t2.value)
                  where (t1.attribute_id = 97) and
	                (t1.entity_id != t2.entity_id) and
                    (t1.store_id = t2.store_id) and
	                (t1.store_id = {$store_id})
                  group by concat(LEAST(t1.entity_id,t2.entity_id),GREATEST(t1.entity_id,t2.entity_id));";

        $result = array();
        foreach($read_con->fetchAll($query) as $item){
            $result[$item['value']][$item['product_id_1']] = 1;
            $result[$item['value']][$item['product_id_2']] = 1;
        }
        return $result;

    }

    private function _getStoresId()
    {
        $allStores = array();
        foreach (Mage::app()->getStores() as $_eachStoreId => $val) {
            $allStores[] = Mage::app()->getStore($_eachStoreId)->getId();
        }
        return $allStores;
    }
}

$shell = new Mage_UrlKey_Resolve_Conflict();
$shell->run();


