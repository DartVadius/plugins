<?php

/**
 * Description of CustomCategory
 *
 * @author DartVadius
 */
class CustomCategory_Model_CustomCategory {

    public static function searchCategory($name, $checkbox = NULL) {
        $categoryModel = new Application_Model_DbTable_Categoryes();
        $sql = $categoryModel->select()->setIntegrityCheck(FALSE)
                ->from('shop_category')
                ->where('name LIKE ?', "%$name%");
        if (!empty($checkbox)) {
            $sql->orWhere("id IN ($checkbox)")->group('id');
        }

        return $categoryModel->fetchAll($sql)->toArray();
    }

    public static function findCategoryByConfig($urls) {
        $categoryModel = new Application_Model_DbTable_Categoryes();
        $res = [];
        foreach ($urls as $key => $url) {
            if (!empty($url)) {
                $url = explode(',', $url);
                foreach ($url as $value) {
                    $sql = $categoryModel->select()->setIntegrityCheck(FALSE)
                            ->from('shop_category')
                            ->where('id=?', $value);
                    $cat = $categoryModel->fetchAll($sql)->toArray();
                    $res[$key][$cat[0]['name']] = $value;
                }
            }
        }
        return $res;
    }

    public static function findCategoriesForView($configCat) {
        $categoryModel = new Application_Model_DbTable_Categoryes();
        if (empty($configCat)) {
            return FALSE;
        }
        $categories = [];
        foreach ($configCat as $key => $id) {
            $subCatId = NULL;
            $categories[$key]['parent'] = self::getCategoryById($id);

            $subCatId = $categoryModel->getSubcategoriesIds($id);

            if (!empty($subCatId)) {
                foreach ($subCatId as $subId) {
                    if ($subId['id'] != $id) {
                        $categories[$key]['child'][$subId['id']] = self::getCategoryById($subId['id']);
                    }
                }
            }
        }
        return $categories;
    }
    
    private function getCategoryById($id) {
        $categoryModel = new Application_Model_DbTable_Categoryes();
        $sql = $categoryModel->select()->setIntegrityCheck(FALSE)->from('shop_category')
                ->where('id = ?', $id);
        return $categoryModel->fetchRow($sql)->toArray();
    }

    public static function findConfigByUri($pluginId, $uri) {
        $config = new Application_Model_DbTable_PluginsSettings();

        $sql = $config->select()->setIntegrityCheck(FALSE)->from('crm_plugins_settings')
                ->where('plugins_id = ?', $pluginId)
                ->where('name = ?', $uri);
        $cat = $config->fetchRow($sql)->toArray();
        if (!empty($cat['params'])) {
            return explode(',', $cat['params']);
        }
        return FALSE;
    }

}
