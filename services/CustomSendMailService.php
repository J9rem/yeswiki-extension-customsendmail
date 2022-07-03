<?php

/*
 * This file is part of the YesWiki Extension Customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Customsendmail\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Field\CheckboxField;
use YesWiki\Bazar\Field\CheckboxEntryField;
use YesWiki\Bazar\Field\EnumField;
use YesWiki\Bazar\Field\RadioEntryField;
use YesWiki\Bazar\Field\SelectEntryField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\Service\UserManager;
use YesWiki\Wiki;

class CustomSendMailService
{
    protected $aclService;
    protected $entryManager;
    protected $formManager;
    protected $pageManager;
    protected $params;
    protected $userManager;
    protected $wiki;

    public function __construct(
        AclService $aclService,
        EntryManager $entryManager,
        FormManager $formManager,
        PageManager $pageManager,
        ParameterBagInterface $params,
        UserManager $userManager,
        Wiki $wiki
    ) {
        $this->aclService = $aclService;
        $this->entryManager = $entryManager;
        $this->formManager = $formManager;
        $this->pageManager = $pageManager;
        $this->params = $params;
        $this->userManager = $userManager;
        $this->wiki = $wiki;
    }

    public function getAdminSuffix(): string
    {
        $suffix = !$this->params->has('GroupsAdminsSuffixForEmails') ? "" : $this->params->get('GroupsAdminsSuffixForEmails');
        return (empty($suffix) || !is_string($suffix)) ? "" : $suffix;
    }

    public function getAreaFieldName(): string
    {
        $fieldName = !$this->params->has('AreaFieldName') ? "" : $this->params->get('AreaFieldName');
        return (empty($fieldName) || !is_string($fieldName)) ? "" : $fieldName;
    }

    public function filterEntriesFromParents(array $entries, bool $entriesMode = true, string $mode = "only_members", string $selectmembersparentform = "")
    {
        $cacheParents = [];
        $entryCache = [];
        if ($this->wiki->UserIsAdmin()) {
            return $entries;
        } else {
            $suffix = $this->getAdminSuffix();
            if (empty($suffix)) {
                return [];
            } else {
                $user = $this->userManager->getLoggedUser();
                if (empty($user['name'])) {
                    return [];
                }
                if ($mode == "members_and_profiles_in_area") {
                    $areaFieldName = $this->getAreaFieldName();
                    if (empty($areaFieldName) || empty($selectmembersparentform)) {
                        return [];
                    } else {
                        $fieldForArea = $this->formManager->findFieldFromNameOrPropertyName($areaFieldName,$selectmembersparentform);
                        if (empty($fieldForArea) || !($fieldForArea instanceof EnumField)){
                            return [];
                        }
                        $parentsWhereAdmin = $this->getParentsWhereAdmin($selectmembersparentform,$cacheParents,$entryCache, $suffix, $user['name']);
                        $areas = [];
                        foreach ($parentsWhereAdmin as $idFiche => $entry) {
                            if ($fieldForArea instanceof CheckboxField){
                                $newAreas = $fieldForArea->getValues($entry);
                            } else {
                                $newAreas = !empty($entry[$fieldForArea->getPropertyName()]) ? [$entry[$fieldForArea->getPropertyName()]] : [];
                            }
                            foreach ($newAreas as $area) {
                                if (!in_array($area,$areas)){
                                    $areas[] = $area;
                                }
                            }
                        }
                    }
                }
                $results = [];
                foreach ($entries as $key => $value) {
                    if ($entriesMode) {
                        $entry = $value;
                    } elseif ($this->entryManager->isEntry($value)) {
                        $entry = $this->entryManager->getOne($value);
                    }
                    if (!empty($entry['id_typeannonce'])) {
                        $formId = $entry['id_typeannonce'];
                        $form = $this->formManager->getOne($formId);
                        if (!empty($form['prepared'])) {
                            foreach ($form['prepared'] as $field) {
                                if ($this->isAdminOfParent($entry,[$field], $entryCache, $suffix ,$user['name'])){
                                    if (!in_array($entry['id_fiche'], array_keys($results))) {
                                        $results[$entry['id_fiche']] = $entry;
                                    }
                                }
                                if ($mode == "members_and_profiles_in_area" &&
                                    !in_array($entry['id_fiche'], array_keys($results)) && 
                                    $field instanceof EnumField &&
                                    $field->getLinkedObjectName() == $fieldForArea->getLinkedObjectName()){
                                    if ($field instanceof CheckboxField){
                                        $currentAreas = $field->getValues($entry);
                                    } else {
                                        $currentAreas = !empty($entry[$field->getPropertyName()]) ? [$entry[$field->getPropertyName()]] : [];
                                    }
                                    foreach ($currentAreas as $area) {
                                        if (in_array($area,$areas)){
                                            $results[$entry['id_fiche']] = $entry;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return $results;
            }
        }
    }

    public function isAdminOfParent(array $entry, array $fields, array &$cache = [], string $suffix = "" ,string $loggedUserName = ""): bool
    {
        if (empty($suffix)){
            $suffix = $this->getAdminSuffix();
            if (empty($suffix)) {
                return false;
            }
        }
        if (empty($loggedUserName)){
            $user = $this->userManager->getLoggedUser();
            if (empty($user['name'])) {
                return false;
            } else {
                $loggedUserName = $user['name'];
            }
        }
        foreach ($fields as $field) {
            if ($field instanceof CheckboxEntryField ||
                    $field instanceof RadioEntryField ||
                    $field instanceof SelectEntryField) {
                $parentEntries = ($field instanceof CheckboxEntryField)
                ? $field->getValues($entry)
                : [$field->getValue($entry)];
                foreach ($parentEntries as $parentEntry) {
                    if ($this->isParentAdmin($parentEntry,$suffix,$loggedUserName,$cache)){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function getParentsWhereAdmin(string $id, array &$cache, array &$entryCache, string $suffix, string $loggedUserName): array
    {
        if (empty($cache[$id])){
            $cache[$id] = [];
        }
        if (empty($cache[$id]['entries'])) {
            $cache[$id]['entries'] = $this->entryManager->search([
                    'formsIds' => [$id]
                ], true, true);
        }
        if (empty($cache[$id]['entries_where_admin'])){
            $cache[$id]['entries_where_admin'] = array_filter($cache[$id]['entries'],function ($entry) use ($suffix, $loggedUserName, &$entryCache){
                return $this->isParentAdmin($entry['id_fiche'], $suffix, $loggedUserName, $entryCache);
            });
        }
        return $cache[$id]['entries_where_admin'];
    }

    private function isParentAdmin(string $entryId, string $suffix, string $loggedUserName, array &$cache): bool
    {
        if (empty($cache['isAdmin'])){
            $cache['isAdmin'] = [];
        }
        if (empty($cache['isNotAdmin'])){
            $cache['isNotAdmin'] = [];
        }
        if (in_array($entryId,$cache['isAdmin'])){
            return true;
        } elseif (in_array($entryId,$cache['isNotAdmin'])){
            return false;
        } else {
            if (!$this->entryManager->isEntry($entryId)){
                $cache['isNotAdmin'][] = $entryId;
                return false;
            }
            $parentOwner = $this->pageManager->getOwner($entryId);
            $groupName = "{$entryId}$suffix";
            $groupAcl = $this->wiki->GetGroupACL($groupName);
            if ((!empty($parentOwner) && $parentOwner == $loggedUserName) ||
                (!empty($groupAcl) && $this->aclService->check($groupAcl, $loggedUserName, true))){
                $cache['isAdmin'][] = $entryId;
                return true;
            } else {
                $cache['isNotAdmin'][] = $entryId;
                return false;
            }
        }
    }
}
