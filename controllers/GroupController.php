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
            'groupmanagement.bazarliste.afterdynamicquery' => 'keepOnlyFilteredEntriesFromParentsAfter',
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
        if (!$this->wiki->UserIsAdmin()) {
            $selectmembers = (
                !empty($_GET['selectmembers']) &&
                    is_string($_GET['selectmembers']) &&
                    in_array($_GET['selectmembers'], ["only_members","members_and_profiles_in_area"], true)
            ) ? $_GET['selectmembers'] : "";
            if (!empty($selectmembers)) {
                $eventData = $event->getData();
                if (!empty($eventData) &&
                    is_array($eventData) &&
                    isset($eventData['response']) &&
                    method_exists($eventData['response'], 'getContent')) {
                    $response = $eventData['response'];
                    $status = $response->getStatusCode();
                    if ($status < 400) {
                        $content = $response->getContent();
                        $contentDecoded = json_decode($content, true);
                        if (!empty($contentDecoded) && !empty($contentDecoded['entries']) && is_array($contentDecoded['entries'])) {
                            $fieldMapping = $contentDecoded['fieldMapping'] ?? [];
                            $idFicheIdx = array_search("id_fiche", $fieldMapping);
                            if ($idFicheIdx !== false && $idFicheIdx > -1) {
                                $entries = array_filter(array_map(function($entryData) use ($idFicheIdx){
                                    $entryId = $entryData[$idFicheIdx] ?? "";
                                    if (!empty($entryId)){
                                        $entry = $this->entryManager->getOne($entryId);
                                        if (!empty($entry['id_fiche'])){
                                            return $entry;
                                        }
                                    }
                                    return [];
                                },$contentDecoded['entries']),function($entry){
                                    return !empty($entry);
                                });
                                
                                $entries = $this->filterEntriesFromParents($entries, [
                                    'selectmembers' => $selectmembers,
                                    'selectmembersparentform' => $_GET['selectmembersparentform'] ?? "",
                                    'id' => $_GET['idtypeannonce'] ?? ""
                                ]);
                                $entriesIds = array_map(function($entry){
                                    return $entry['id_fiche'] ?? "";
                                },$entries);
                                foreach ($contentDecoded['entries'] as $idx => $entry) {
                                    if (empty($entry[$idFicheIdx]) || !in_array($entry[$idFicheIdx],$entriesIds)) {
                                        unset($contentDecoded['entries'][$idx]);
                                    }
                                }
                                $response->setData($contentDecoded);
                            }
                        }
                    }
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
