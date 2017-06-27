<?php

/**
 * Description of SiteMapController
 *
 * @author DartVadius
 */
class SiteMapHtml_SiteMapController extends Zend_Controller_Action {

    public function indexAction() {

        $siteMap = new SiteMapHtml_Model_SiteMap();

        $config = $siteMap->getConfig();

        Utils::change_directory(APPLICATION_PATH . '/../cache/sitemap');

        $cache = new Crm_Caching('sitemap', null, true, 'sitemap');
        if (empty($cache->get())) {
            $siteMap->setCache($config);
        }

        $this->view->url = $cache->get();
        $this->view->col = $config['col_num'];
        $this->view->links = $config['num_url'];
    }

    public function configAction() {
        $plugConf = new Application_Model_DbTable_PluginsSettings();
        $siteMap = new SiteMapHtml_Model_SiteMap();

        $this->view->urlmap = $plugConf->getSettingBySystemName('siteMapHtml', 'urlmap')[0]['params'];
        $this->view->col_num = $plugConf->getSettingBySystemName('siteMapHtml', 'col_num')[0]['params'];
        $this->view->info_page = $plugConf->getSettingBySystemName('siteMapHtml', 'info_page')[0]['params'];
        $this->view->all_cat = $plugConf->getSettingBySystemName('siteMapHtml', 'all_cat')[0]['params'];
        $this->view->hidden_cat = $plugConf->getSettingBySystemName('siteMapHtml', 'hidden_cat')[0]['params'];
        $this->view->sub_cat = $plugConf->getSettingBySystemName('siteMapHtml', 'sub_cat')[0]['params'];
        $this->view->sub_deep = $plugConf->getSettingBySystemName('siteMapHtml', 'sub_deep')[0]['params'];
        $this->view->num_url = $plugConf->getSettingBySystemName('siteMapHtml', 'num_url')[0]['params'];
        $this->view->tags_url = $plugConf->getSettingBySystemName('siteMapHtml', 'tags_url')[0]['params'];
        $this->view->galery = $plugConf->getSettingBySystemName('siteMapHtml', 'galery')[0]['params'];
        $this->view->blog = $plugConf->getSettingBySystemName('siteMapHtml', 'blog')[0]['params'];
        $this->view->sort = unserialize($plugConf->getSettingBySystemName('siteMapHtml', 'sort')[0]['params']);
        $this->view->info_site = $plugConf->getSettingBySystemName('siteMapHtml', 'info_site')[0]['params'];
        $this->view->link = $siteMap->getUrl();
    }

    public function saveAction() {
        $siteMap = new SiteMapHtml_Model_SiteMap();
        $r = new Zend_Controller_Action_Helper_Redirector;

        $post = $this->_request->getPost();
        $siteMap->dropCache();
        $siteMap->dropSort();
        $siteMap->saveConfig($post);

        $r->gotoUrl('/siteMapHtml/site-map/config')->redirectAndExit();
    }

    public function dropAction() {
        $siteMap = new SiteMapHtml_Model_SiteMap();
        $r = new Zend_Controller_Action_Helper_Redirector;
        $siteMap->dropCache();
        $r->gotoUrl('/siteMapHtml/site-map/config')->redirectAndExit();
    }

}
