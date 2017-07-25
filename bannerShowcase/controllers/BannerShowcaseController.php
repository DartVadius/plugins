<?php

/**
 * Description of BannerShowcaseController
 *
 * @author DartVadius
 */
class BannerShowcase_BannerShowcaseController extends Zend_Controller_Action {

    public function indexAction() {
        $bannerModel = new BannerShowcase_Model_Banner();
        if ($this->_request->isPost()) {
            $post = $this->_request->getParams();
            $bannerModel->save($post);
            $this->_request->clearParams();
        }
        $this->view->banners = $bannerModel->getAll();
        $this->view->model = $bannerModel;
    }
    
    public function editAction() {
        $bannerModel = new BannerShowcase_Model_Banner();
        $get = $this->_request->getParams();
        if ($this->_request->isPost()) {
            $post = $this->_request->getParams();
//            print_r($post);
//            die;
            $bannerModel->edit($post);
            $this->_request->clearParams();
            $this->redirect('/bannerShowcase/banner-showcase/index');
        }
        $this->view->banner = $bannerModel->getById($get['id']);
        $this->view->products = $bannerModel->getProductsById($get['id']);
        $this->view->categories = $bannerModel->getCategoriesById($get['id']);
    }

    public function displayAction() {
        $get = $this->_request->getParams();
        $file = APPLICATION_PATH . '/plugins/frontend/bannerShowcase/banners/' . $get['name'];
        $this->_helper->layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        $info = getimagesize($file);
        $mimeType = $info['mime'];
        $size = filesize($file);
        $data = file_get_contents($file);
        $response = $this->getResponse();
        $response->setHeader('Content-Type', $mimeType, true);
        $response->setHeader('Content-Length', $size, true);
        $response->setHeader('Content-Transfer-Encoding', 'binary', true);
        $response->setHeader('Cache-Control', 'max-age=3600, must-revalidate', true);
        $response->setBody($data);
        $response->sendResponse();
        die;
    }

    public function deleteAction() {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $bannerModel = new BannerShowcase_Model_Banner();
            $post = $this->_request->getParams();
            $this->_helper->json(['status' => $bannerModel->deleteById($post['id'])]);
        }
    }

    public function searchAction() {
        $post = $this->_request->getPost();
        $name = trim($post['search']);
        $checkbox = NULL;

        if (!empty($post['checkbox'])) {
            $checkbox = array_unique($post['checkbox']);
            $checkbox = implode(', ', $checkbox);
        }

        $res = BannerShowcase_Model_Banner::search($name, $post['id'], $checkbox);
        $arr = [
            'id' => $post['id'],
            'name' => $res,
        ];

        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/plugins/frontend/bannerShowcase/views/scripts/banner-showcase');
        $html->assign('arr', $arr);

        $body_html = $html->render('search.phtml');
        $this->_helper->json([
            "html" => $body_html,
            'id' => $post['id'],
        ]);
    }

}
