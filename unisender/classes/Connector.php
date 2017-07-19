<?php

require_once 'UnisenderApi.php';

/**
 * Description of connector
 *
 * @author DartVadius
 */
class Unisender_Connector {

    private $apiKey;
    private $currentList;
    private $message_id = NULL;
    private $campaign_id = NULL;
    public $connector;
    private $error = [];

    public function __construct() {
        $data = new Unisender_Model_EmailGateway();
        $config = $data->getBySystemName('Unisender');
        $params = [];
        foreach ($config as $param) {
            $params[$param['param_name']] = $param['param_value'];
        }
        $this->apiKey = $params['api_key'];
        $this->connector = new UnisenderApi($this->apiKey);
        return $this;
    }

    public function getMessageId() {
        if (!empty($this->message_id)) {
            return $this->message_id;
        }
        return $this->error;
    }

    public function getCampaignId() {
        if (!empty($this->campaign_id)) {
            return $this->campaign_id;
        }
        return $this->error;
    }

    /**
     * send mail
     * 
     * @return string
     */
    public function sendEmail($mail) {
        $result = json_decode($this->connector->createCampaign(['message_id' => $this->message_id, 'start_time' => $mail['shedule'], 'defer' => 1, 'track_read' => 1, 'track_links' => 1]), TRUE);
        if (!empty($result['result']['campaign_id'])) {
            $this->campaign_id = $result['result']['campaign_id'];
            return $this;
        }
        $this->error[] = $result['error'];
        return $this;
    }

    /**
     * create email template for dispatch
     * 
     * @param array $mail
     * @return $this
     */
    public function createEmail($mail) {
        $result = json_decode($this->connector->createEmailMessage([
                    'sender_name' => $mail['sender_name'],
                    'sender_email' => $mail['sender_email'],
                    'subject' => $mail['subject'],
                    'body' => $mail['body'],
                    'list_id' => $this->currentList,
                    'generate_text' => 1
                ]), TRUE);
        if (!empty($result['result']['message_id'])) {
            $this->message_id = $result['result']['message_id'];
            return $this;
        }
        $this->error[] = $result['error'];
        return $this;
    }

    /**
     * create contact list in Unisender if not exist
     * base method for setting up $this->currentList
     * 
     * @param array $contactList
     * @return $this
     */
    public function setContactList($contactList) {
        if (!empty($contactList)) {
            $lists = $this->connector->getLists();
            $result = json_decode($lists, TRUE)['result'];

            foreach ($result as $list) {
                if ($list['id'] == $contactList['unis_contact_list_id']) {
                    $this->currentList = $list['id'];
                    return $this;
                }
            }
            $currentList = json_decode($this->connector->createList(["title" => $contactList['name']]), TRUE);

            $this->currentList = $currentList['result']['id'];
            return $this;
        }
        $this->error[] = 'empty contact list';
        return $this;
    }

    public function getCurrentList() {
        return $this->currentList;
    }

    /**
     * delete contacts from Unisender contact list, which not exist in our contact list
     * 
     * @param array $contactList
     * @return $this
     */
    public function deleteContacts($contactList) {
        $this->setContactList($contactList);
        $oldContactList = $this->getContacts();
        $oldEmails = [];
        $newEmails = [];
        if (!empty($oldContactList)) {
            foreach ($oldContactList as $value) {
                $oldEmails[] = $value[0];
            }
        }
        if (!empty($contactList)) {
            foreach ($contactList['contacts'] as $contact) {
                $newEmails[] = $contact['email'];
            }
        }

        $contactToDelete = array_diff($oldEmails, $newEmails);
        if (!empty($contactToDelete)) {
            foreach ($contactToDelete as $delete) {
                $this->connector->exclude(['contact_type' => 'email', 'contact' => $delete, 'list_ids' => $this->currentList]);
            }
        }
        return $this;
    }

    /**
     * upload contacts from our mail list to Unisender mail list
     * 
     * @param array $contactList
     * @return boolean
     */
    public function importContacts($contactList, $project_id) {
        if (!empty($contactList)) {
            $field_names = [
                'field_names[0]' => 'email',
                'field_names[1]' => 'Name',
                'field_names[2]' => 'email_list_ids',
                'field_names[3]' => 'user_id',
            ];
            $data = [];
            $i = 0;
            foreach ($contactList['contacts'] as $contact) {
                if ($i < 400) {
                    $data["data[$i][0]"] = $contact['email'];
                    $data["data[$i][1]"] = $contact['name'];
                    $data["data[$i][2]"] = $this->currentList;
                    $data["data[$i][3]"] = hash('sha512', $contact['contact_id'] . $project_id);
                    $i++;
                } else {
                    $data["data[$i][0]"] = $contact['email'];
                    $data["data[$i][1]"] = $contact['name'];
                    $data["data[$i][2]"] = $this->currentList;
                    $data["data[$i][3]"] = hash('sha512', $contact['contact_id'] . $project_id);
                    $this->connector->importContacts(['field_names' => $field_names, 'data' => $data]);
                    $i = 0;
                    $data = [];
                }
            }
            if (!empty($data)) {
                $this->connector->importContacts(['field_names' => $field_names, 'data' => $data]);
            }
        }
        return $this;
    }

    /**
     * get users by current unisender's list id
     * 
     * @return array
     */
    public function getContacts() {
        $offset = 0;
        $users = [];
        do {
            $usersPage = json_decode($this->connector->exportContacts(['offset' => $offset, 'limit' => 300]), TRUE)['result']['data'];
            $offset += 300;
            foreach ($usersPage as $user) {
                $users[] = $user;
            }
        } while (!empty($usersPage));

        return $users;
    }

    /**
     * get list of additional user fields from Unisender
     * 
     * @return array
     */
    public function getFields() {
        return json_decode($this->connector->getFields(), TRUE);
    }

    /**
     * create new user field in Unisender
     * 
     * @return array
     */
    public function createField($name, $publicName, $type) {
        return json_decode($this->connector->createField(['name' => $name, 'public_name' => $publicName, 'type' => $type]), TRUE);
    }

    /**
     * get list of available campaigns
     * 
     * @param datetime $from    filter by date 'from'
     * @param datetime $to      filter by date 'to'
     * @param integer $limit    number of returning results
     * @return boolean | array
     */
    public function getCampaigns($from = NULL, $to = NULL, $limit = 25) {
        $data = [
            'from' => $from,
            'to' => $to,
            'limit' => $limit,
        ];
        $result = json_decode($this->connector->getCampaigns($data), TRUE)['result'];
        if (!empty($result)) {
            return $result;
        }
        return FALSE;
    }

    /**
     * get delivery statistic by user
     * 
     * @param integer $id   Unisender campaign ID
     * @return boolean | array
     */
    public function getDeliveryStats($id) {
        $result = json_decode($this->connector->getCampaignDeliveryStats(['campaign_id' => $id]), TRUE);
        if (!empty($result['result']['data'])) {
            $stat = [];
            foreach ($result['result']['data'] as $data) {
                $data = array_combine($result['result']['fields'], $data);
                $stat[] = $data;
            }
            return $stat;
        }
        return FALSE;
    }

    /**
     * get campaign common statistic
     * 
     * @param integer $id   Unisender campaign ID
     * @return boolean | array
     */
    public function getCommonStats($id) {
        $result = json_decode($this->connector->getCampaignCommonStats(['campaign_id' => $id]), TRUE);
        if (!empty($result['result'])) {
            return $result['result'];
        }
        return FALSE;
    }

    public function getVisitedLinksStatistic($id) {
        $result = json_decode($this->connector->getVisitedLinks(['campaign_id' => $id, 'group' => 1]), TRUE);
        
        if (!empty($result['result']['data'])) {
            $stat = [];
            foreach ($result['result']['data'] as $data) {
                $data = array_combine($result['result']['fields'], $data);
                $stat[] = $data;
            }
//            print_r($stat);
//        die;
            return $stat;
        }
        return FALSE;
    }

}
