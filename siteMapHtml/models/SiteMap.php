<?php

/**
 * SiteMap
 *
 * @author DartVadius
 */
class SiteMapHtml_Model_SiteMap {

    private $router;
    private $request;

    public function __construct() {
        $this->router = new Zend_Controller_Router_Rewrite();
        $this->request = new Zend_Controller_Request_Http();
        $this->router->route($this->request);
    }

    public function getConfig() {
        $plugin = new Application_Model_DbTable_Plugins();
        return $plugin->getBySettingsBySystemName('siteMapHtml');
    }

    public function saveConfig($post) {
        $plugin = new Application_Model_DbTable_Plugins();
        $plugConf = new Application_Model_DbTable_PluginsSettings();

        $pluginId = $plugin->getBySystemName('siteMapHtml')['id'];

        if (strlen($post['urlmap']) > 0 && $post['urlmap'][0] !== '/') {
            $post['urlmap'] = '/' . $post['urlmap'];
        }
        if (isset($post['urlmap'])) {
            $plugConf->saveSettings($pluginId, 'urlmap', trim($post['urlmap']));
        }
        if (isset($post['col_num'])) {
            $plugConf->saveSettings($pluginId, 'col_num', $post['col_num']);
        }
        if (isset($post['info_page'])) {
            $plugConf->saveSettings($pluginId, 'info_page', $post['info_page']);
        }
        if (isset($post['all_cat'])) {
            $plugConf->saveSettings($pluginId, 'all_cat', $post['all_cat']);
        }
        if (isset($post['hidden_cat'])) {
            $plugConf->saveSettings($pluginId, 'hidden_cat', $post['hidden_cat']);
        }
        if (isset($post['sub_cat'])) {
            $plugConf->saveSettings($pluginId, 'sub_cat', $post['sub_cat']);
        }
        if (isset($post['sub_deep'])) {
            $plugConf->saveSettings($pluginId, 'sub_deep', trim($post['sub_deep']));
        }
        if (isset($post['num_url'])) {
            $plugConf->saveSettings($pluginId, 'num_url', trim($post['num_url']));
        }
        if (isset($post['tags_url'])) {
            $plugConf->saveSettings($pluginId, 'tags_url', $post['tags_url']);
        }
        if (isset($post['galery'])) {
            $plugConf->saveSettings($pluginId, 'galery', $post['galery']);
        }
        if (isset($post['blog'])) {
            $plugConf->saveSettings($pluginId, 'blog', $post['blog']);
        }

        if (isset($post['sort'])) {
            $plugConf->saveSettings($pluginId, 'sort', serialize($post['sort']));
        }
        if (isset($post['sort'])) {
            $plugConf->saveSettings($pluginId, 'info_site', $post['info_site']);
        }
        return TRUE;
    }

    public function getSiteInfo() {
        $url = $this->request->getScheme() . '://' . Zend_Registry::get('rootDomain') . '/page/';
        $pages = new Shop_Model_Page();
        $select = $pages->select()->setIntegrityCheck(FALSE)->from('shop_page');
        if (!$pages->fetchAll($select)) {
            return FALSE;
        }
        $page = $pages->fetchAll($select)->toArray();
        if(!empty($page)) {
            return $this->setIdToUrl($url, $page);
        }
        return FALSE;
    }

    /**
     * get array with categories links
     *
     * @param int $hidden   show/hide not active categories
     * @param int $sub      show subcategories or only root categories
     * @param int $deep     depth of subcategories
     * @return boolean|array
     */
    public function getCategories($hidden = NULL, $sub = NULL, $deep = NULL) {
        $url = $this->request->getScheme() . '://' . Zend_Registry::get('rootDomain') . '/category/';
        $categoryes = new Application_Model_DbTable_Categoryes();
        $select = $categoryes->select()->setIntegrityCheck(FALSE)->from('shop_category');

        if ($hidden === '0') {
            $select->where('status = 1');
        }

        if ($sub === '0') {
            $select->where('depth = 0');
        } elseif (!empty($deep)) {
            $deep -= 1;
            $select->where("depth < $deep OR depth = $deep");
        }

        if (!$categoryes->fetchAll($select)) {
            return FALSE;
        }

        $categoryList = $categoryes->fetchAll($select)->toArray();
        return $this->setIdToUrl($url, $categoryList);
    }

    /**
     * get array with galleries links
     *
     * @return boolean|array
     */
    public function getGallery() {
        $url = $this->request->getScheme() . '://' . Zend_Registry::get('rootDomain') . '/gallery/';
        $galleries = new Application_Model_DbTable_GalleryAlbum();
        $select = $galleries->select()->setIntegrityCheck(FALSE)->from('gallery_album');
        if (!$galleries->fetchAll($select)) {
            return FALSE;
        }
        $galleryList = $galleries->fetchAll($select)->toArray();
        return $this->setIdToUrl($url, $galleryList);
    }

    /**
     * get array with blog's articles links
     *
     * @return boolean|array
     */
    public function getBlog() {
        $parent = new Application_Model_DbTable_Blog();
        $blog = new Application_Model_DbTable_BlogPost();
        $urlList = [];

        //get blog list
        $select = $parent->select()->setIntegrityCheck(FALSE)->from('blog_blog');
        if (!$parent->fetchAll($select)) {
            return FALSE;
        }
        $parentList = $parent->fetchAll($select)->toArray();

        //get articles list
        $select = $blog->select()->setIntegrityCheck(FALSE)->from('blog_post');
        if (!$blog->fetchAll($select)) {
            return FALSE;
        }
        $blogList = $blog->fetchAll($select)->toArray();

        foreach ($parentList as $parent) {
            $url = $this->request->getScheme() . '://' . Zend_Registry::get('rootDomain') . '/blog/' . $parent['url'] . '/';
            foreach ($blogList as $oneBlog) {
                if ($oneBlog['blog_id'] == $parent['id']) {
                    $urlList[$oneBlog['title']] = $url . $oneBlog['url'];
                }
            }
        }

        return $urlList;
    }

    /**
     * get array with product's info pages links
     * 
     * @return boolean|array
     */
    public function getInfo() {
        $products = new Application_Model_DbTable_Products();
        $pages = new Shop_Model_ProductPages();
        $urlList = [];

        $select = $products->select()->setIntegrityCheck(FALSE)->from('shop_product');
        if (!$products->fetchAll($select)) {
            return FALSE;
        }
        $productList = $products->fetchAll($select)->toArray();

        $select = $pages->select()->setIntegrityCheck(FALSE)->from('shop_product_pages');
        if (!$pages->fetchAll($select)) {
            return FALSE;
        }
        $pageList = $pages->fetchAll($select)->toArray();

        foreach ($productList as $product) {
            $url = $this->request->getScheme() . '://' . Zend_Registry::get('rootDomain') . '/product/' . $product['url'] . '/';
            foreach ($pageList as $page) {
                if ($page['product_id'] == $product['id']) {
                    $urlList[$page['name']] = $url . $page['url'];
                }
            }
        }
        return $urlList;
    }

    /**
     * get array with tag's search links
     * 
     * @return boolean|array
     */
    public function getTag() {
        $urlList = [];
        $url = $this->request->getScheme() . '://' . Zend_Registry::get('rootDomain') . '/tag/';
        $tags = new Shop_Model_Tag();
        $select = $tags->select()->setIntegrityCheck(FALSE)->from('shop_tag');
        if (!$tags->fetchAll($select)) {
            return FALSE;
        }
        $tagList = $tags->fetchAll($select)->toArray();
        foreach ($tagList as $tag) {
            $urlList[$tag['name']] = $url . $tag['name'];
        }
        return $urlList;
    }

    public function getProduct() {
        $url = $this->request->getScheme() . '://' . Zend_Registry::get('rootDomain') . '/product/';
        $products = new Application_Model_DbTable_Products();
        $select = $products->select()->setIntegrityCheck(FALSE)->from('shop_product');
        if (!$products->fetchAll($select)) {
            return FALSE;
        }
        $productList = $products->fetchAll($select)->toArray();
        return $this->setIdToUrl($url, $productList);
    }

    /**
     *
     * @param string $url       url template
     * @param array $arrayId
     * @return array
     */
    private function setIdToUrl($url, $arrayId) {
        $urlList = [];
        foreach ($arrayId as $Id) {
            $urlList[$Id['name']] = $url . $Id['url'];
        }
        return $urlList;
    }

    public function getUrl() {
        return $this->request->getScheme() . '://' . Zend_Registry::get('rootDomain');
    }

    public function dropCache() {
        $frontendOptions = [];
        $backendOptions = ['cache_dir' => APPLICATION_PATH . "/../cache/sitemap"];
        $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        $file = new Crm_Caching('sitemap', null, true, 'sitemap');
        if ($file->get()) {
            $cache->remove('sitemap');
        }
    }

    /**
     * set cache for site map
     * 
     * @param array $config
     * @return boolean
     */
    public function setCache($config) {
        if (empty($config)) {
            return FALSE;
        }
        $cache = new Crm_Caching('sitemap', null, true, 'sitemap');
        $flip = [];
        $categoryes = NULL;
        $galery = NULL;
        $blog = NULL;
        $info = NULL;
        $tag = NULL;
        if ($config['all_cat']) {
            $categoryes = $this->getCategories($config['hidden_cat'], $config['sub_cat'], $config['sub_deep']);
        }
        if ($config['galery']) {
            $galery = $this->getGallery();
        }
        if ($config['blog']) {
            $blog = $this->getBlog();
        }
        if ($config['info_page']) {
            $info = $this->getInfo();
        }
        if ($config['tags_url']) {
            $tag = $this->getTag();
        }
        if ($config['info_site']) {
            $infoSite = $this->getSiteInfo();
        }

        $flip = array_flip($config['sort']);
        $flip['product'] = $this->getProduct();
        $flip['blog'] = $blog;
        $flip['info'] = $info;
        $flip['category'] = $categoryes;
        $flip['galery'] = $galery;
        $flip['tags'] = $tag;
        $flip['site'] = $infoSite;
        $cache->save($flip);
        return TRUE;
    }
    
    /**
     * drop sort order for site map
     */
    public function dropSort() {
        $plugin = new Application_Model_DbTable_Plugins();
        $plugConf = new Application_Model_DbTable_PluginsSettings();

        $pluginId = $plugin->getBySystemName('siteMapHtml')['id'];
        $plugConf->delete("plugins_id=$pluginId AND name='sort'");
        return TRUE;
    }

}
