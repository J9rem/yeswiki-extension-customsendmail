<?php

/*
 * This file is part of the YesWiki Extension Customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Customsendmail\Controller;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Customsendmail\Service\CustomSendMailService;
use YesWiki\Core\YesWikiController;
use YesWiki\Groupmanagement\Entity\DataContainer;
use YesWiki\Wiki;

class GroupController extends YesWikiController implements EventSubscriberInterface
{
    protected $customSendMailService;
    protected $entryManager;

    public function __construct(
        CustomSendMailService $customSendMailService,
        EntryManager $entryManager,
        Wiki $wiki
    ) {
        $this->customSendMailService = $customSendMailService;
        $this->entryManager = $entryManager;
        $this->wiki = $wiki;
    }

    public static function getSubscribedEvents()
    {
        return [
            'groupmanagement.bazarliste.entriesready' => 'filterEntriesFromParentsAfter',
            'groupmanagement.bazarliste.beforedynamicquery' => 'keepOnlyFilteredEntriesFromParentsAfter',
        ];
    }

    public function filterEntriesFromParentsAfter($event)
    {
        $eventData = $event->getData();
        if (!empty($eventData) && is_array($eventData) && isset($eventData['dataContainer']) && ($eventData['dataContainer'] instanceof DataContainer)) {
            $bazarData = $eventData['dataContainer']->getData();
            $arg = $bazarData['param'] ?? [];
            $selectmembers = $arg['selectmembers'] ?? "";
            if (!empty($selectmembers) && isset($bazarData['fiches']) && is_array($bazarData['fiches'])) {
                $bazarData['fiches'] = $this->filterEntriesFromParents($bazarData['fiches'], $arg);
                $eventData['dataContainer']->setData($bazarData);
            }
        }
    }

    public function keepOnlyFilteredEntriesFromParentsAfter($event)
    {
        $eventData = $event->getData();
        if (!empty($eventData) && is_array($eventData)) {
            $formsIds = $eventData['formsIds'] ?? [];
            if (!empty($formsIds)) {
                $entries = $this->entryManager->search([
                    'formsIds' => $formsIds
                ], true, true);
                $entries = $this->filterEntriesFromParents($entries, [
                    'selectmembers' => $_GET['selectmembers'] ?? "",
                    'selectmembersparentform' => $_GET['selectmembersparentform'] ?? "",
                    'id' => $formsIds
                ]);
                if (empty($entries)) {
                    if (!isset($_GET['query'])) {
                        $_GET['query'] = [];
                    }
                    $_GET['query']['id_fiche']="";
                } else {
                    $rawIds = !empty($_GET['query']['id_fiche']) ? explode(',', $_GET['query']['id_fiche']) : [];
                    $ids = array_values(array_map(function ($entry) {
                        return $entry['id_fiche'];
                    }, $entries));
                    if (empty($rawIds)) {
                        $newIds = $ids;
                    } else {
                        $newIds = [];
                        foreach ($rawIds as $id) {
                            if (in_array($id, $ids)) {
                                $newIds[] = $id;
                            }
                        }
                    }
                    if (!isset($_GET['query'])) {
                        $_GET['query'] = [];
                    }
                    $_GET['query']['id_fiche'] = implode(',', $newIds);
                }
            }
        }
    }

    public function filterEntriesFromParents(array $entries, array $arg): array
    {
        $selectmembers = (
            !empty($arg['selectmembers']) &&
                is_string($arg['selectmembers']) &&
                in_array($arg['selectmembers'], ["only_members","members_and_profiles_in_area"], true)
        ) ? $arg['selectmembers'] : "";
        $selectmembersparentform = (
            !empty($arg['selectmembersparentform']) &&
                strval($arg['selectmembersparentform']) == strval(intval($arg['selectmembersparentform'])) &&
                intval($arg['selectmembersparentform']) > 0
        ) ? $arg['selectmembersparentform'] : "";

        if (!$this->wiki->UserIsAdmin() && !empty($selectmembers)) {
            $ids = $arg['id'] ?? null;
            if (empty($this->customSendMailService->getAdminSuffix()) || empty($ids)) {
                return [];
            } else {
                $ids = array_filter(is_array($ids) ? $ids : (is_string($ids) ? explode(',', $ids) : []), function ($id) {
                    return substr($id, 0, 4) != "http" && strval($id) == strval(intval($id));
                });
                if (empty($ids)) {
                    return [];
                } else {
                    return $this->customSendMailService->filterEntriesFromParents(
                        $entries,
                        true,
                        $selectmembers,
                        $selectmembersparentform
                    );
                }
            }
        }
        return $entries;
    }
}
