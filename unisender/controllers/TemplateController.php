<?php

/**
 * Description of TemplateController
 *
 * @author DartVadius
 */
class Unisender_TemplateController extends Zend_Controller_Action {

    /**
     * show list of raw templates
     */
    public function listAction() {
        $this->view->headTitle($this->view->translate('List of templates'), 'PREPEND');
        $templateModel = new Unisender_Model_UnisenderTemplate();
        $this->view->template_list = $templateModel->getAll();
    }

    /**
     * create raw template
     */
    public function addTemplateAction() {

        $this->view->headTitle($this->view->translate('Creation of template'), 'PREPEND');

        $form = new Application_Form_AddTemplateUnisender();

        if ($this->_request->isPost() && $form->isValid($this->_request->getPost())) {
            $post = $this->_request->getPost();
            $templateModel = new Unisender_Model_UnisenderTemplate();
            $pk = $templateModel->add([
                'name' => $post['name'],
                'html' => $post['html']
            ]);

            if ($pk) {
                $this->_helper->redirector->goToRoute(array('module' => 'unisender', 'controller' => 'template', 'action' => 'select-type', 'template_id' => $pk), 'default', true);
            }
        }

        $this->view->form = $form;
    }

    public function selectTypeAction() {
        $template_id = $this->_request->getParam('template_id');
        $templateModel = new Unisender_Model_UnisenderTemplate();
        $template = $templateModel->getById($template_id);

        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            $html = Unisender_Service::prepareTemplate($post, $template['html']);
            $_SESSION['email_template'] = $html;
            $redirect_action = $post['submit'];
            $this->_helper->redirector->goToRoute(array('module' => 'unisender', 'controller' => 'template', 'action' => $redirect_action), 'default', true);
        }

        $this->view->template = $template;
    }

    /**
     * edit raw template
     */
    public function editTemplateAction() {

        $this->view->headTitle($this->view->translate('Editing of template'), 'PREPEND');

        $template_id = $this->_request->getParam('template_id');

        $templateModel = new Unisender_Model_UnisenderTemplate();
        $form = new Application_Form_AddTemplateUnisender();

        if ($this->_request->isPost() && $form->isValid($this->_request->getPost())) {
            $post = $this->_request->getPost();
            $pk = $templateModel->editTemplate([
                'name' => $post['name'],
                'html' => $post['html']
                    ], $post['id']);
            $this->_helper->redirector->goToRoute(array('module' => 'unisender', 'controller' => 'template', 'action' => 'select-type', 'template_id' => $template_id), 'default', true);
        }

        $template = $templateModel->getById($template_id);
        $form->populate($template);

        $this->view->form = $form;
    }

    /**
     * delete raw template by id
     */
    public function deleteTemplateAction() {
        $template_id = $this->_request->getParam('template_id');

        $templateModel = new Unisender_Model_UnisenderTemplate();
        $templateModel->removeTemplate($template_id);

        $this->_helper->redirector->goToRoute(array('module' => 'unisender', 'controller' => 'template', 'action' => 'list'), 'default', true);
    }

    /**
     * delete html template by id
     */
    public function htmlDeleteAction() {
        $htmlTemplatesModel = new Unisender_Model_UnisenderTemplateHtml();
        if ($this->_request->getParam('id')) {
            $id = $this->_request->getParam('id');
            $htmlTemplatesModel->delete("id=$id");
            $this->_helper->redirector('index', 'index', 'unisender');
        }
    }

    /**
     * create/update html template
     */
    public function htmlAction() {
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        $form = new Application_Form_UnisenderHtml();
        $htmlTemplatesModel = new Unisender_Model_UnisenderTemplateHtml();

        //update
        if ($this->_request->getParam('id')) {
            $id = $this->_request->getParam('id');

            if ($this->_request->isPost() && $form->isValid($this->_request->getPost())) {
                Unisender_Service::updateHtml($id, $this->_request->getPost());
                $this->_helper->redirector('index', 'index', 'unisender');
            }

            $form_data = $htmlTemplatesModel->fetchRow("id=$id")->toArray();

            if ($identity['id'] != $form_data['contact_id'] && $form_data['editable'] == 0) {
                $this->_helper->redirector('index', 'index', 'unisender');
            }

            $this->view->headTitle($this->view->translate('Editing of email'), 'PREPEND');
            $this->view->title = $this->view->translate('Editing of email');
            $form->populate($form_data);
            //create
        } else {
            $form_data = $_SESSION['email_template'];
            unset($_SESSION['email_template']);
            if ($this->_request->isPost() && $form->isValid($this->_request->getPost())) {
                Unisender_Service::saveHtml($this->_request->getPost());
                $this->_helper->redirector('index', 'index', 'unisender');
            }
            if (!empty($form_data)) {
                $form->populate(['html' => $form_data]);
            }

            $this->view->headTitle($this->view->translate('Creation of email'), 'PREPEND');
            $this->view->title = $this->view->translate('Creation of email');
        }
        $this->view->form = $form;
    }

    public function votingDeleteAction() {
        if ($this->_request->getParam('id')) {
            $mailerTemplateVotingModel = new Unisender_Model_UnisenderTemplateVoting();
            $mailerVotingVariantsModel = new Unisender_Model_UnisenderTemplateVotingVariants();
            $id = $this->_request->getParam('id');
            $mailerTemplateVotingModel->delete("id = $id");
            $mailerVotingVariantsModel->delete("voting_id = $id");
            $this->_helper->redirector('index', 'index', 'unisender');
        }
    }

    public function votingAction() {

        $this->view->headTitle($this->view->translate('Create voting'), 'PREPEND');

        $mailerTemplateVotingModel = new Unisender_Model_UnisenderTemplateVoting();
        $mailerVotingVariantsModel = new Unisender_Model_UnisenderTemplateVotingVariants();

        $id = $this->_request->getParam('id');

        $identity = Zend_Auth::getInstance()->getStorage()->read();

        $post = $this->_request->getPost();
        if (!empty($post['back'])) {
            $this->_helper->redirector->goToRoute(['module' => 'unisender'], 'default', true);
        }

        $variants = $post;
        unset($variants['name'], $variants['description'], $variants['subject'], $variants['public'], $variants['editable'], $variants['text'], $variants['save']);

        if (empty($post)) {
            $count_variants = '2';
        } else {
            $count_variants = count($variants);

            $unempty_variants = '0';
            foreach ($variants as $name => $value) {
                if ($value != '') {
                    $unempty_variants++;
                }
            }

            if ($unempty_variants > 1) {
                $no_variants = '1';
            } elseif ($unempty_variants < 2) {
                $no_variants = '0';
            }
        }



        $form = new Application_Form_UnisenderVoting([
            'variant' => $count_variants
        ]);

        if (isset($no_variants)) {
            $this->view->no_variants = $no_variants;
        }

        if ($id) {
            $voting_data = Unisender_Service::getVotingData($id);

            if (empty($post)) {
                $count_variants = count($voting_data[$id]['variants']);
            } else {
                $count_variants = count($variants);
            }

            $form = new Application_Form_UnisenderVoting([
                'variant' => $count_variants
            ]);

            if ($identity['id'] != $voting_data['contact_id'] && $voting_data['editable'] == 0) {
                $this->_helper->redirector('index', 'index', 'unisender');
            }

            $form_data = [
                'name' => $voting_data['name'],
                'description' => $voting_data['description'],
                'subject' => $voting_data['subject'],
                'public' => $voting_data['public'],
                'editable' => $voting_data['editable'],
                'text' => $voting_data['text']
            ];

            if (isset($voting_data[$id]['variants'])) {
                foreach ($voting_data[$id]['variants'] as $variant_number => $variant) {
                    $form_data['variant' . $variant_number] = $variant;
                }
            }

            $form->populate($form_data);

            if ($this->_request->isPost() && $form->isValid($this->_request->getPost())) {
                if (!empty($post['save']) && $unempty_variants > 1) {

                    $body = [
                        'name' => $post['name'],
                        'description' => $post['description'],
                        'contact_id' => $identity['id'],
                        'public' => $post['public'],
                        'editable' => $post['editable'],
                        'subject' => $post['subject'],
                        'text' => $post['text']
                    ];

                    $mailerTemplateVotingModel->update($body, "id = $id");

                    $mailerVotingVariantsModel->delete("voting_id = $id");

                    $i = '1';
                    foreach ($variants as $var => $value) {
                        if ($value != "") {
                            $data_variants = [
                                'voting_id' => $id,
                                'variant_number' => $i,
                                'name' => $value
                            ];
                            $mailerVotingVariantsModel->insert($data_variants);
                            $i++;
                        }
                    }

                    $this->_helper->redirector->goToRoute(['module' => 'unisender'], 'default', true);
                }
            }

            $title = $this->view->translate('Editing of voting');
        } else {
            if ($this->_request->isPost() && $form->isValid($this->_request->getPost())) {

                if (!empty($post['save'])) {

                    if ($unempty_variants > 1) {

                        $body = [
                            'name' => $post['name'],
                            'description' => $post['description'],
                            'contact_id' => $identity['id'],
                            'public' => isset($post['public']) ? $post['public'] : 0,
                            'editable' => isset($post['editable']) ? $post['editable'] : 0,
                            'subject' => $post['subject'],
                            'text' => $post['text']
                        ];

                        $voting_id = $mailerTemplateVotingModel->insert($body);

                        $i = '1';
                        foreach ($variants as $var => $value) {
                            if ($value != "") {
                                $data_variants = [
                                    'voting_id' => $voting_id,
                                    'variant_number' => $i,
                                    'name' => $value
                                ];
                                $mailerVotingVariantsModel->insert($data_variants);
                                $i++;
                            }
                        }
                        $this->_helper->redirector->goToRoute(['module' => 'unisender'], 'default', true);
                    }
                }
            }
            $form_data = $_SESSION['email_template'];
            unset($_SESSION['email_template']);
            if (!empty($form_data)) {
                $form->populate(['text' => $form_data]);
            }
            $title = $this->view->translate('Creation of voting');
        }

        $this->view->title = $title;
        $this->view->form = $form;
    }

}
