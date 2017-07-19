<?php

/**
 * Description of Service
 *
 * @author DartVadius
 */
class Unisender_Service {

    public static function getMergedArray($html, $voting) {
        if (!$html) {
            $html = [];
        }
        if (!$voting) {
            $voting = [];
        }
        return array_merge($html, $voting);
    }

    /**
     * set real values instead placeholders
     * 
     * @param string $html
     * @param array $post
     * @param string $str
     * @return string
     */
    public static function setValueToTemplate($html, $post, $str) {
        foreach ($post as $value) {
            $val = '{$' . $str . '}';
            $pos = strpos($html, $val);
            $html = $pos !== false ? substr_replace($html, $value, $pos, strlen($val)) : $html;
        }
        return $html;
    }

    /**
     * 
     * @param array $post
     * @param string $html
     * @return string
     */
    public static function prepareTemplate($post, $html) {
        if (isset($post['textarea'])) {
            $html = self::setValueToTemplate($html, $post['textarea'], 'textarea');
        }
        if (isset($post['input'])) {
            $html = self::setValueToTemplate($html, $post['input'], 'input');
        }
        return $html;
    }

    /**
     * save html template
     * 
     * @param array $data
     * @return boolean
     */
    public static function saveHtml($data) {
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        $htmlTemplatesModel = new Unisender_Model_UnisenderTemplateHtml();
        unset($data['save']);
        $data['contact_id'] = $identity['id'];
        $htmlTemplatesModel->insert($data);
        return TRUE;
    }

    /**
     * update html template
     * 
     * @param integer $id
     * @param array $data
     * @return boolean
     */
    public static function updateHtml($id, $data) {
        $htmlTemplatesModel = new Unisender_Model_UnisenderTemplateHtml();
        unset($data['save']);
        $data['public'] = (isset($data['public']) && $data['public'] == 1) ? $data['public'] : 0;
        $data['editable'] = (isset($data['editable']) && $data['editable'] == 1) ? $data['editable'] : 0;
        $htmlTemplatesModel->update($data, "id=$id");
        return TRUE;
    }

    /**
     * add voting varians to array with main voting data
     * 
     * @param integer $voting_id
     * @return boolean | array
     */
    public static function getVotingData($voting_id) {

        $mailerVotingVariantsModel = new Unisender_Model_UnisenderTemplateVotingVariants();

        $sql = $mailerVotingVariantsModel->select()->setIntegrityCheck(false)
                ->from('crm_unisender_template_voting')
                ->where("id = $voting_id");

        $voting_data = $mailerVotingVariantsModel->fetchRow($sql)->toArray();

        if (!empty($voting_data)) {
            $voting_data_key = $voting_data['id'];

            $sql = $mailerVotingVariantsModel->select()->setIntegrityCheck(false)
                    ->from('crm_unisender_template_voting_variants')
                    ->where("voting_id = $voting_data_key")->order("variant_number");

            $voting_variants = $mailerVotingVariantsModel->fetchAll($sql)->toArray();

            foreach ($voting_variants as $value) {
                $voting_data[$value['voting_id']]['variants'][$value['variant_number']] = $value['name'];
            }
            return $voting_data;
        }
        return FALSE;
    }

    /**
     * synchronize local contact list with Unisender contact list
     * create new one Unisender contact list if not exist
     * 
     * 
     * @param Unisender_Connector $connector
     * @param array $params
     * @return array                           local contact list data
     */
    public static function setUpContactList(Unisender_Connector $connector, $params) {
        $contactLists = new Unisender_Model_UnisenderDeliveryList();
        $contactList = $contactLists->getListById($params['contact_list_id']);
        $unisender_list_id = $connector->setContactList($contactList)->getCurrentList();
        $id = $contactList['id'];
        $contactLists->update(['unis_contact_list_id' => $unisender_list_id], "id=$id");
        return $contactList;
    }

    /**
     * synchronize local contacts with Unisender contacts
     * 
     * @param Unisender_Connector $connector
     * @param array $params
     * @return array
     */
    public static function synchronizeContacts(Unisender_Connector $connector, $params, $project_id) {
        
        $mailerContactSettings = new Application_Model_DbTable_MailerContactSettings();
        $contactLists = new Unisender_Model_UnisenderDeliveryList();

        //получаем контакты от сервиса
        $unisenderContactList = $connector->getContacts();

        //записываем данные по отписавшимся/подписавшимся пользователям в БД
        $mailerContactSettings->updateContactSettins($unisenderContactList);

        //получаем контакты для рассылки (описавшиеся пользователи в список не попадают)
        $localContactList = $contactLists->getContactListForGateway($params['contact_list_id']);

        //отписываем контакты от списка рассылки сервиса по данным из локальной БД и добавляем новые контакты
        $connector->deleteContacts($localContactList)->importContacts($localContactList, $project_id);
        return $localContactList;
    }

    /**
     * getting company_name and company_phones
     */
    public static function getCompanyData() {
        // getting company_name and company_phones
        $systemSettings = new Crm_SystemSettings();
        $company = [
            'name' => $systemSettings->getSettings('company_name'),
            'phones' => $systemSettings->getSettings('company_phones'),
            'email' => $systemSettings->getSettings('company_email')
        ];
        return $company;
    }

    /**
     * 
     * @param integer $contactId
     * @param string $type
     * @param integer $templale_id
     * @param integer $project_id
     * @return string
     */
    public static function createEmailBody($contactId, $type, $templale_id, $project_id) {
        $contactModel = new Application_Model_DbTable_Contact();
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/../data/unisender/templates/public/unisender/');

        $manager_data = $contactModel->getContact($contactId);
        $company = self::getCompanyData();
        $manager = self::createManager($manager_data, $company);

        $search = [
            '{$sender_name}',
            '{$sender_company_name}',
            '{$sender_phone}',
            '{$sender_email}',
        ];
        $replace = [
            $manager['name'],
            $company['name'],
            $manager['phone'],
            $manager['email'],
        ];

        if ($type == 'html') {
            $mailerTemplateHtmlModel = new Unisender_Model_UnisenderTemplateHtml();
            $template = $mailerTemplateHtmlModel->fetchRow("id=$templale_id")->toArray();
        }

        if ($type == 'voting') {
            $template = self::getVotingData($templale_id);
            $variants = [];
            foreach ($template[$templale_id]['variants'] as $variant_id => $variant) {
                $variants[$variant_id] = $variant;
            }
            $template['html'] = $template['text'];
        }

        $template['html'] = str_replace($search, $replace, $template['html']);
        $email_body = [
            'html' => $template['html'],
            'manager' => $manager_data
        ];

        $html->assign('data', $email_body);
        $html->assign('company', $company);
        if ($type == 'html') {
            return $html->render('general-html.phtml');
        } elseif ($type == 'voting') {
            $html->assign('variants', $variants);
            $html->assign('project_id', $project_id);
            return $html->render('voting.phtml');
        }
    }

    /**
     * 
     * @param array $manager_data
     * @param array $company
     * @return array
     */
    public static function createManager($manager_data, $company) {
        if (!empty($manager_data['name'])) {
            $manager['name'] = $manager_data['name'];
        } else {
            $manager['name'] = $company['name'];
        }

        if (!empty($manager_data['phone'][0])) {
            $manager['phone'] = $manager_data['phone'][0];
        } else {
            $manager['phone'] = $company['phones'][0];
        }

        if (!empty($manager_data['email'][0])) {
            $manager['email'] = $manager_data['email'][0]['value'];
        } else {
            $manager['email'] = $company['email'];
        }
        return $manager;
    }

    /**
     * 
     * @param array $params
     * @return integer          project ID
     */
    public static function createProject($params) {
        $project = new Unisender_Model_UnisenderProject();
        $identity = Zend_Auth::getInstance()->getStorage()->read();

        return $project->insert([
                    'name' => $params['name'],
                    'contact_id' => $identity['id'],
                    'template_type' => $params['template_type'],
                    'template_id' => $params['template_id'],
                    'recipients_list_id' => $params['contact_list_id'],
                    'last_send' => date("Y-m-d H:i:s"),
                    'send_on_schedule' => self::createShedule($params),
        ]);
    }

    public static function updateProject($project_id, $unisender_message_id, $unisender_campaign_id) {
        $project = new Unisender_Model_UnisenderProject();
        $project->update(['unisender_email_id' => $unisender_message_id, 'unisender_campaign_id' => $unisender_campaign_id, 'status' => 1], "id=$project_id");
    }

    /**
     * get formatted array for creating Unisender project
     * 
     * @param array $manager
     * @param string $body
     * @param array $params
     * @return array
     */
    public static function createMail($manager, $body, $params) {
        return [
            'sender_name' => $manager['name'],
            'sender_email' => $manager['email'],
            'subject' => $params['name'],
            'body' => $body,
            'shedule' => self::createShedule($params),
        ];
    }

    /**
     * create string formatted as Datetime
     * 
     * @param array $params
     * @return string | NULL
     */
    private static function createShedule($params) {
        $date = NULL;
        if (!empty($params['send_on_schedule']['date'])) {
            if (empty($params['send_on_schedule']['hour'])) {
                $params['send_on_schedule']['hour'] = '00';
            }
            if (empty($params['send_on_schedule']['minute'])) {
                $params['send_on_schedule']['minute'] = '00';
            }
            $date = $params['send_on_schedule']['date'] . ' ' . $params['send_on_schedule']['hour'] . ':' . $params['send_on_schedule']['minute'];
        }

        if (!empty($date)) {
            $date = new DateTime($date);
            return $date->format('Y-m-d H:i');
        }
        return NULL;
    }

}
