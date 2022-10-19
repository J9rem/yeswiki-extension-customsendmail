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
use YesWiki\Customsendmail\Service\GroupManagementServiceInterface;
use YesWiki\Wiki;

class CustomSendMailService
{
    protected $aclService;
    protected $entryManager;
    protected $formManager;
    protected $groupManagementService;
    protected $pageManager;
    protected $params;
    protected $userManager;
    protected $wiki;

    public function __construct(
        AclService $aclService,
        EntryManager $entryManager,
        FormManager $formManager,
        GroupManagementServiceInterface $groupManagementService,
        PageManager $pageManager,
        ParameterBagInterface $params,
        UserManager $userManager,
        Wiki $wiki
    ) {
        $this->aclService = $aclService;
        $this->entryManager = $entryManager;
        $this->formManager = $formManager;
        $this->groupManagementService = $groupManagementService;
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

    public function filterEntriesFromParents(
        array $entries,
        bool $entriesMode = true,
        string $mode = "only_members",
        string $selectmembersparentform = ""
    ) {
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
                    extract($this->getAreas($selectmembersparentform, $suffix, $user));
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
                                if ($this->isAdminOfParent($entry, [$field], $suffix, $user['name'])) {
                                    if (!in_array($entry['id_fiche'], array_keys($results))) {
                                        $results[$entry['id_fiche']] = $entry;
                                    }
                                }
                                if ($mode == "members_and_profiles_in_area") {
                                    $this->processAreas($entry, $results, $field, $fieldForArea, $areas);
                                }
                            }
                        }
                    }
                }
                return $results;
            }
        }
    }

    public function isAdminOfParent(array $entry, array $fields, string $suffix = "", string $loggedUserName = ""): bool
    {
        if (empty($suffix)) {
            $suffix = $this->getAdminSuffix();
            if (empty($suffix)) {
                return false;
            }
        }
        if (empty($loggedUserName)) {
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
                : (
                    !empty($entry[$field->getPropertyName()])
                    ? [$entry[$field->getPropertyName()]]
                    : []
                );
                $parentsForm = strval($field->getLinkedObjectName());
                foreach ($parentEntries as $parentEntry) {
                    if ($this->isParentAdmin($parentEntry, $suffix, $loggedUserName, $parentsForm)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    protected function getAreas($selectmembersparentform, $suffix, $user): array
    {
        $areaFieldName = $this->getAreaFieldName();
        $fieldForArea = null;
        $areas = [];
        if (!empty($areaFieldName) && !empty($selectmembersparentform)) {
            $fieldForArea = $this->formManager->findFieldFromNameOrPropertyName($areaFieldName, $selectmembersparentform);
            if (!empty($fieldForArea) && $fieldForArea instanceof EnumField) {
                $parentsWhereAdmin = $this->getParentsWhereAdmin($selectmembersparentform, $suffix, $user['name']);
                foreach ($parentsWhereAdmin as $idFiche => $entry) {
                    if ($fieldForArea instanceof CheckboxField) {
                        $newAreas = $fieldForArea->getValues($entry);
                    } else {
                        $newAreas = !empty($entry[$fieldForArea->getPropertyName()]) ? [$entry[$fieldForArea->getPropertyName()]] : [];
                    }
                    foreach ($newAreas as $area) {
                        if (!in_array($area, array_keys($areas))) {
                            $areas[$area] = [];
                        }
                        $areas[$area][] = empty($entry['id_fiche']) ? $idFiche : $entry['id_fiche'];
                    }
                }
            }
        }
        return compact(['areas','fieldForArea']);
    }

    protected function processAreas(array $entry, array &$results, $field, $fieldForArea, array $areas)
    {
        if (!empty($areas) &&
                !in_array($entry['id_fiche'], array_keys($results)) &&
                $field instanceof EnumField &&
                $field->getLinkedObjectName() == $fieldForArea->getLinkedObjectName()) {
            if ($field instanceof CheckboxField) {
                $currentAreas = $field->getValues($entry);
            } else {
                $currentAreas = !empty($entry[$field->getPropertyName()]) ? [$entry[$field->getPropertyName()]] : [];
            }
            foreach ($currentAreas as $area) {
                if (in_array($area, array_keys($areas))) {
                    $results[$entry['id_fiche']] = $entry;
                    break;
                }
            }
        }
    }

    private function getParentsWhereAdmin(string $id, string $suffix, string $loggedUserName): array
    {
        $parentsWhereOwner = $this->groupManagementService->getParentsWhereOwner(['name'=>$loggedUserName], $id);
        $parentsWhereAdminIds = $this->groupManagementService->getParentsWhereAdminIds(
            $parentsWhereOwner,
            ['name'=>$loggedUserName],
            $suffix,
            $id
        );

        return array_filter(array_map(function ($entryId) {
            return $this->groupManagementService->getParent($id, $entryId);
        }, $parentsWhereAdminIds), function ($entry) {
            return !empty($entry) && !empty($entry['id_fiche']) && $this->aclService->hasAccess('read', $entry['id_fiche'], $loggedUserName);
        });
    }

    private function isParentAdmin(string $entryId, string $suffix, string $loggedUserName, string $parentsForm): bool
    {
        if (!$this->groupManagementService->isParent($entryId, $parentsForm) ||
            !$this->aclService->hasAccess('read', $entryId, $loggedUserName)) {
            return false;
        } else {
            $parentOwner = $this->pageManager->getOwner($entryId);
            $groupName = "{$entryId}$suffix";
            $groupAcl = $this->wiki->GetGroupACL($groupName);
            return ((!empty($parentOwner) && $parentOwner == $loggedUserName) ||
                (!empty($groupAcl) && $this->aclService->check($groupAcl, $loggedUserName, true)));
        }
    }
}
