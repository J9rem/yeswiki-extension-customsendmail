<?php

/*
 * This file is part of the YesWiki Extension Customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Customsendmail;

use YesWiki\Bazar\Controller\EntryController;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiAction;
use YesWiki\Customsendmail\Service\CustomSendMailService;

class __BazarListeAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $query = $this->getService(EntryController::class)->formatQuery($arg, $_GET);
        if (!$this->wiki->UserIsAdmin() && $this->formatBoolean($arg, false, 'keepentrieswhereadminforparent')) {
            $customSendMailService = $this->getService(CustomSendMailService::class);
            $ids = $arg['id'] ?? null;
            if (empty($customSendMailService->getAdminSuffix()) || empty($ids)) {
                $query['id_fiche'] = "";
            } else {
                $ids = array_filter(is_array($ids) ? $ids :(is_string($ids) ? explode(',', $ids) : []), function ($id) {
                    return substr($id, 0, 4) != "http" && strval($id) == strval(intval($id));
                });
                if (empty($ids)) {
                    $query['id_fiche'] = "";
                } else {
                    $entries = $this->getService(EntryManager::class)->search([
                        'formsIds' => $ids
                    ], true, true);
                    $entries = $customSendMailService->filterEntriesAsParentAdminOrOwner($entries);
                    $query['id_fiche'] = implode(',', array_keys($entries));
                }
            }
            return [
                // Paramètres pour une requete specifique
                'query' => $query,
            ];
        }

        return [];
    }

    public function run()
    {
    }
}
