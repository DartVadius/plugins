<?php

/**
 * Description of ServiceStatistic
 *
 * @author DartVadius
 */
class Unisender_ServiceStatistic {

    public static function setIndexStat($lists, $campaigns) {
        if (!empty($campaigns)) {
            $result = [];
            foreach ($campaigns as $campaign) {
                $campaign['list_name'] = $lists[$campaign['list_id']]['name'];
                $campaign['contact_id'] = $lists[$campaign['list_id']]['contact_id'];
                $campaign['list_owner'] = $lists[$campaign['list_id']]['list_owner'];
                $result[] = $campaign;
            }
            return $result;
        }
        return FALSE;
    }

    public static function setProjectContacts($deliveryStats, $contacts) {
        $result = [];
        if (!empty($contacts)) {
            foreach ($contacts as $contact) {
                $result[$contact['email']] = $contact;
            }
        }

        $stat = [];
        if (!empty($deliveryStats)) {
            foreach ($deliveryStats as $user) {
                if (!empty($result[$user['email']])) {
                    $user['user_name'] = $result[$user['email']]['name'];
                    $user['user_id'] = $result[$user['email']]['contact_id'];
                }
                $user['send_result'] = self::replaceDeliveryStatus($user['send_result']);
                $stat[] = $user;
            }
        }

        return $stat;
    }

    private static function replaceDeliveryStatus($user) {
        $search = [
            'not_sent',
            'ok_sent',
            'ok_delivered',
            'ok_read',
            'ok_link_visited',
            'ok_unsubscribed',
            'err_user_unknown',
            'err_user_inactive',
            'err_spam_rejected',
            'err_delivery_failed',
            'err_unsubscribed',
            'err_not_allowed',
            'err_internal'
        ];
        $replace = [
            'Not sent',
            'Mail sent',
            'Mail delivered',
            'Mail read',
            'Link visited',
            'User unsubscribed',
            'Unknown user',
            'Inactive user',
            'Mail rejected as spam by server',
            'Delivery failed',
            'Unsubscribed user',
            'Mail sending is blocked',
            'Internal error',
        ];

        return str_replace($search, $replace, $user);
    }

    /**
     * format visited links data
     * 
     * @param array $links
     * @param array $contacts
     * @return array
     */
    public static function setLinks($links, $contacts) {
        $result = [];
        if (!empty($contacts)) {
            foreach ($contacts as $contact) {
                $result[$contact['email']] = $contact;
            }
        }

        $stat = [];
        if (!empty($links)) {
            foreach ($links as $link) {
                if (!empty($result[$link['email']])) {
                    $link['user_name'] = $result[$link['email']]['name'];
                    $link['user_id'] = $result[$link['email']]['contact_id'];
                }
                $stat[] = $link;
            }
        }

        return $stat;
    }

    /**
     * save voting hash for each user in current contact list
     * 
     * @param integer $project_id
     * @param array $localContactList
     * @return boolean
     */
    public static function saveVoting($project_id, $localContactList) {
        $votingModel = new Unisender_Model_UnisenderVotingAnswer();
        $err = [];
        if (!empty($localContactList['contacts'])) {
            foreach ($localContactList['contacts'] as $contact) {
                try {
                    $votingModel->insert(['user_id' => $contact['contact_id'], 'project_id' => $project_id, 'hash' => hash('sha512', $contact['contact_id'] . $project_id)]);
                } catch (Exception $exc) {
                    $err[] = $exc->getTraceAsString();
                }
            }
        }
        if (empty($err)) {
            return TRUE;
        }
        return $err;
    }

    /**
     * save user choice in db
     * 
     * @param array $params
     * @return boolean
     */
    public static function updateVoting($params) {
        $votingModel = new Unisender_Model_UnisenderVotingAnswer();
        $hash = $params['user_id'];
        try {
            $where = $votingModel->getDefaultAdapter()->quoteInto('hash=?', $hash);
            $votingModel->update(['variant' => $params['var_id']], $where);
            return TRUE;
        } catch (Exception $exc) {
            $exc->getTraceAsString();
        }
    }

    /**
     * get result of voting
     * 
     * @param array $project
     * @return array
     */
    public static function getVotingResult($project) {
        $votingAnswerModel = new Unisender_Model_UnisenderVotingAnswer();
        $votingVariantsModel = new Unisender_Model_UnisenderTemplateVotingVariants();
        
        $variants = $votingVariantsModel->getVariantsByTemplateId($project['template_id']);
        $answers = $votingAnswerModel->getVoteByProjectId($project['id']);

        if (!empty($variants)) {
            $var = [];
            foreach ($variants as $variant) {
                $var[$variant['variant_number']] = $variant;
                $var[$variant['variant_number']]['count'] = 0;
            }
        }
        if (!empty($answers)) {
            foreach ($answers as $answer) {
                if (!empty($answer['variant'])) {
                    $var[$answer['variant']]['count']++;
                }
            }
        }
        return $var;
    }
    
    public static function getLinksCount($links) {
        if (!empty($links)) {
            $count = [];
            foreach ($links as $link) {
                $count[] = $link['url'];
            }
            $count = array_count_values($count);
            return $count;
        }
        return FALSE;
    }

}
