<?php

require_once __DIR__ . ("/../lib/Tinify/Exception.php");
require_once __DIR__ . ("/../lib/Tinify/ResultMeta.php");
require_once __DIR__ . ("/../lib/Tinify/Result.php");
require_once __DIR__ . ("/../lib/Tinify/Source.php");
require_once __DIR__ . ("/../lib/Tinify/Client.php");
require_once __DIR__ . ("/../lib/Tinify.php");

/**
 * Description of PhotoCpmpression_Model_PhotoCompression
 *
 * @author DartVadius
 */
class PhotoCompression_Model_PhotoCompression {

    private $api_key;
    private $proxy;
    private $request;

    public function __construct() {
        $router = new Zend_Controller_Router_Rewrite();
        $this->request = new Zend_Controller_Request_Http();
        $router->route($this->request);

        $config = $this->getConfig();
        $this->api_key = $config['api_key'];

        if (!empty($config['proxy'])) {
            $this->proxy = $config['proxy'];
        } else {
            $this->proxy = NULL;
        }
    }

    /**
     *
     * @return array
     */
    public function getConfig() {
        $plugin = new Application_Model_DbTable_Plugins();
        return $plugin->getBySettingsBySystemName('photoCompression');
    }

    public function saveConfig($post) {
        $plugin = new Application_Model_DbTable_Plugins();
        $plugConf = new Application_Model_DbTable_PluginsSettings();

        $pluginId = $plugin->getBySystemName('photoCompression')['id'];

        $plugConf->saveSettings($pluginId, 'api_key', $post['api_key']);

        $plugConf->saveSettings($pluginId, 'proxy_set', $post['proxy_set']);

        return TRUE;
    }

    /**
     * check out whether the image was compressed, and compresses it if not
     *
     * @param string $img relative path to image
     *
     * @return boolean
     */
    public function saveImg($img) {
        \Tinify\setKey($this->api_key);
        if (!empty($this->proxy)) {
            \Tinify\setProxy($this->proxy);
        }
        try {
            $sourceData = file_get_contents(urldecode($img));
            $resultData = \Tinify\fromBuffer($sourceData)->toBuffer();
            file_put_contents($img, $resultData, LOCK_EX);
            $config = new PhotoCompression_Model_PhotoCompressionConfig();
            $config->insert(['path' => $img]);
            return true;
        } catch (DomainException $e) {
            return false;
        }
    }

    /**
     * validate api key to tinyPNG service
     *
     * @return boolean
     */
    public function checkApiKey() {
        try {
            \Tinify\setKey($this->api_key);
            \Tinify\validate();
        } catch (\Tinify\Exception $e) {
            return FALSE;
        }
        return TRUE;
    }


    /**
     * validate api key to tinyPNG service
     *
     * @param string $key
     *
     * @return boolean
     */
    public function checkConfigApiKey($key = null) {
        try {
            \Tinify\setKey($key);
            \Tinify\validate();
        } catch (\Tinify\Exception $e) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * get array with all products Id's
     *
     * @return boolean|array
     */
    private function getProductIds() {
        $ids = [];
        $product = new Application_Model_DbTable_Products();
        $products = $product->getAllProductsCols(['id']);
        if (!empty($products)) {
            foreach ($products as $value) {
                $ids[] = $value['id'];
            }
            return $ids;
        }
        return FALSE;
    }

    /**
     * get array with relative paths to images of all products
     *
     * @return boolean|array
     */
    public function getProductImgs() {
        $ids = $this->getProductIds();
        $imgArr = [];
        foreach ($ids as $id) {
            $prodImg = $this->getOneProductImgs($id);
            if ($prodImg) {
                foreach ($prodImg as $pic) {
                    if (!empty($pic)) {
                        $imgArr[] = $pic;
                    }
                }
            }
        }
        if (!empty($imgArr)) {
            return $imgArr;
        }
        return false;
    }

    /**
     * get array with relative paths to images of current product
     *
     * @param int $id product ID
     * @return boolean|array
     */
    public function getOneProductImgs($id) {
        $product_images_model = new Shop_Model_ProductImages();

        $images = $product_images_model->getImagesPathByProductId($id);
        $compressedImages = $this->getCompressedImgs();

        if ($compressedImages) {
            $images = array_diff((array)$images, (array)$compressedImages);
        }

        if (!empty($images)) {
            return $images;
        }
        return false;
    }

    /**
     * get array with photos extensions of categories  where keys are categories ID's
     *
     * @return boolean|array
     */
    private function getCategoriesIds() {
        $ids = [];
        $category = new Application_Model_DbTable_Categoryes();
        $categories = $category->getAllCategoriesWithFields(['id', 'category_icon', 'category_logo']);
        if (!empty($categories)) {
            foreach ($categories as $key => $value) {
                $ids[$key]['category_icon'] = $value['category_icon'];
                $ids[$key]['category_logo'] = $value['category_logo'];
            }
        } else {
            return false;
        }
        return $ids;
    }

    /**
     * get array with relative paths to images of all categories
     *
     * @return boolean|array
     */
    public function getCategoriesImgs() {
        $imgArr = [];
        $newImgArr = [];
        $url = 'modules/shop/category_img/';
        $ids = $this->getCategoriesIds();
//        print_r($ids);
//        die;
        if (!empty($ids)) {
            foreach ($ids as $id => $type) {
                if (!empty($type['category_icon'])) {
                    if (file_exists($url . $id . '/icon_' . $id . '.0x320.' . $type['category_icon'])) {
                        $imgArr[] = $url . $id . '/icon_' . $id . '.0x320.' . $type['category_icon'];
                    }
                    if (file_exists($url . $id . '/icon_' . $id . '.200x0.' . $type['category_icon'])) {
                        $imgArr[] = $url . $id . '/icon_' . $id . '.200x0.' . $type['category_icon'];
                    }
                    if (file_exists($url . $id . '/icon_' . $id . '.48x48.' . $type['category_icon'])) {
                        $imgArr[] = $url . $id . '/icon_' . $id . '.48x48.' . $type['category_icon'];
                    }
                    if (file_exists($url . $id . '/icon_' . $id . '.750x0.' . $type['category_icon'])) {
                        $imgArr[] = $url . $id . '/icon_' . $id . '.750x0.' . $type['category_icon'];
                    }
                    if (file_exists($url . $id . '/icon_' . $id . '.96x96.' . $type['category_icon'])) {
                        $imgArr[] = $url . $id . '/icon_' . $id . '.96x96.' . $type['category_icon'];
                    }
                    if (file_exists($url . $id . '/icon_' . $id . '.970x970.' . $type['category_icon'])) {
                        $imgArr[] = $url . $id . '/icon_' . $id . '.970x970.' . $type['category_icon'];
                    }
                }
                if (!empty($type['category_logo'])) {
                    if (file_exists($url . $id . '/logo_' . $id . '.0x320.' . $type['category_logo'])) {
                        $imgArr[] = $url . $id . '/logo_' . $id . '.0x320.' . $type['category_logo'];
                    }
                    if (file_exists($url . $id . '/logo_' . $id . '.200x0.' . $type['category_logo'])) {
                        $imgArr[] = $url . $id . '/logo_' . $id . '.200x0.' . $type['category_logo'];
                    }
                    if (file_exists($url . $id . '/logo_' . $id . '.48x48.' . $type['category_logo'])) {
                        $imgArr[] = $url . $id . '/logo_' . $id . '.48x48.' . $type['category_logo'];
                    }
                    if (file_exists($url . $id . '/logo_' . $id . '.750x0.' . $type['category_logo'])) {
                        $imgArr[] = $url . $id . '/logo_' . $id . '.750x0.' . $type['category_logo'];
                    }
                    if (file_exists($url . $id . '/logo_' . $id . '.96x96.' . $type['category_logo'])) {
                        $imgArr[] = $url . $id . '/logo_' . $id . '.96x96.' . $type['category_logo'];
                    }
                    if (file_exists($url . $id . '/logo_' . $id . '.970x970.' . $type['category_logo'])) {
                        $imgArr[] = $url . $id . '/logo_' . $id . '.970x970.' . $type['category_logo'];
                    }
                }
            }
        }

        $compressedImages = $this->getCompressedImgs();

        if ($compressedImages) {
            $imgArr = array_diff($imgArr, $compressedImages);
        }

        if (!empty($imgArr)) {
            foreach ($imgArr as $value) {
                $newImgArr[] = $value;
            }
            return $newImgArr;
        }
        return FALSE;
    }

    /**
     * get array with relative paths to images of current category
     *
     * @param int $id category ID
     * @return boolean|array
     */
    public function getOneCategoryImgs($id) {
        $imgArr = [];
        $newImgArr = [];
        $url = 'modules/shop/category_img/';
        $ids = $this->getCategoriesIds();
        if (!empty($ids[$id])) {
            if (!empty($ids[$id]['category_icon'])) {
                if (file_exists($url . $id . '/icon_' . $id . '.0x320.' . $ids[$id]['category_icon'])) {
                    $imgArr[] = $url . $id . '/icon_' . $id . '.0x320.' . $ids[$id]['category_icon'];
                }
                if (file_exists($url . $id . '/icon_' . $id . '.200x0.' . $ids[$id]['category_icon'])) {
                    $imgArr[] = $url . $id . '/icon_' . $id . '.200x0.' . $ids[$id]['category_icon'];
                }
                if (file_exists($url . $id . '/icon_' . $id . '.48x48.' . $ids[$id]['category_icon'])) {
                    $imgArr[] = $url . $id . '/icon_' . $id . '.48x48.' . $ids[$id]['category_icon'];
                }
                if (file_exists($url . $id . '/icon_' . $id . '.750x0.' . $ids[$id]['category_icon'])) {
                    $imgArr[] = $url . $id . '/icon_' . $id . '.750x0.' . $ids[$id]['category_icon'];
                }
                if (file_exists($url . $id . '/icon_' . $id . '.96x96.' . $ids[$id]['category_icon'])) {
                    $imgArr[] = $url . $id . '/icon_' . $id . '.96x96.' . $ids[$id]['category_icon'];
                }
                if (file_exists($url . $id . '/icon_' . $id . '.970x970.' . $ids[$id]['category_icon'])) {
                    $imgArr[] = $url . $id . '/icon_' . $id . '.970x970.' . $ids[$id]['category_icon'];
                }
            }
            if (!empty($ids[$id]['category_logo'])) {
                if (file_exists($url . $id . '/logo_' . $id . '.0x320.' . $ids[$id]['category_logo'])) {
                    $imgArr[] = $url . $id . '/logo_' . $id . '.0x320.' . $ids[$id]['category_logo'];
                }
                if (file_exists($url . $id . '/logo_' . $id . '.200x0.' . $ids[$id]['category_logo'])) {
                    $imgArr[] = $url . $id . '/logo_' . $id . '.200x0.' . $ids[$id]['category_logo'];
                }
                if (file_exists($url . $id . '/logo_' . $id . '.48x48.' . $ids[$id]['category_logo'])) {
                    $imgArr[] = $url . $id . '/logo_' . $id . '.48x48.' . $ids[$id]['category_logo'];
                }
                if (file_exists($url . $id . '/logo_' . $id . '.750x0.' . $ids[$id]['category_logo'])) {
                    $imgArr[] = $url . $id . '/logo_' . $id . '.750x0.' . $ids[$id]['category_logo'];
                }
                if (file_exists($url . $id . '/logo_' . $id . '.96x96.' . $ids[$id]['category_logo'])) {
                    $imgArr[] = $url . $id . '/logo_' . $id . '.96x96.' . $ids[$id]['category_logo'];
                }
                if (file_exists($url . $id . '/logo_' . $id . '.970x970.' . $ids[$id]['category_logo'])) {
                    $imgArr[] = $url . $id . '/logo_' . $id . '.970x970.' . $ids[$id]['category_logo'];
                }
            }
        } else {
            return FALSE;
        }

        $compressedImages = $this->getCompressedImgs();

        if ($compressedImages) {
            $imgArr = array_diff($imgArr, $compressedImages);
        }

        if (!empty($imgArr)) {
            foreach ($imgArr as $value) {
                $newImgArr[] = $value;
            }
            return $newImgArr;
        }
        return FALSE;
    }

    private function ifFileExist($path) {
        return file_exists($path);
    }

    /**
     * get array with relative paths to compressed images
     *
     * @return boolean|array
     */
    private function getCompressedImgs() {
        $compressedImgs = [];
        $plugin = new Application_Model_DbTable_Plugins();
        $select = $plugin->select()->setIntegrityCheck(FALSE)->from('photo_compression');
        if ($plugin->fetchAll($select)) {
            $all = $plugin->fetchAll($select)->toArray();
            foreach ($all as $value) {
                $compressedImgs[] = $value['path'];
            }
            return $compressedImgs;
        }
        return false;
    }

    public function removeDeadLinks() {
        $config = new PhotoCompression_Model_PhotoCompressionConfig();
        $links = $this->getCompressedImgs();
        foreach ($links as $link) {
            if (!file_exists($link)) {
                $config->delete("path = '$link'");
            }
        }
        return TRUE;
    }

    public function getMonthCompressCount() {
        \Tinify\setKey($this->api_key);
        \Tinify\validate();
        return \Tinify\compressionCount();
    }

}
