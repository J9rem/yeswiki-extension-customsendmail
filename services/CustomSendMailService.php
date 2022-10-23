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
use YesWiki\Bazar\Field\EmailField;
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
    public const KEY_FOR_PARENTS = "bf-custom-send-mail-parents";
    public const KEY_FOR_AREAS = "bf-custom-send-mail-areas";

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
        string $selectmembersparentform = "",
        $callback = null,
        bool $appendDisplayData = false
    ) {
        if ($this->wiki->UserIsAdmin() && $entriesMode && !$appendDisplayData) {
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
                                $parentsIds = $this->isAdminOfParent($entry, [$field], $suffix, $user['name'], $appendDisplayData);
                                if (!empty($parentsIds)) {
                                    $this->appendEntryWithData(
                                        $entry,
                                        $results,
                                        $form,
                                        $suffix,
                                        $user,
                                        $callback,
                                        self::KEY_FOR_PARENTS,
                                        $parentsIds,
                                        $appendDisplayData
                                    );
                                }
                                if ($mode == "members_and_profiles_in_area") {
                                    $this->processAreas($entry, $results, $field, $fieldForArea, $areas, $form, $suffix, $user, $callback, $appendDisplayData);
                                }
                            }
                        }
                    }
                }
                if ($appendDisplayData) {
                    foreach ($results as $entryId => $entry) {
                        if (!empty($entry[self::KEY_FOR_PARENTS])) {
                            $results[$entryId]['html_data'] = $results[$entryId]['html_data'] . " data-".self::KEY_FOR_PARENTS."=\"".htmlentities(implode(',', $entry[self::KEY_FOR_PARENTS]))."\"";
                        }
                        if (!empty($entry[self::KEY_FOR_AREAS])) {
                            $results[$entryId]['html_data'] = $results[$entryId]['html_data'] . " data-".self::KEY_FOR_AREAS."=\"".htmlentities(implode(',', $entry[self::KEY_FOR_AREAS]))."\"";
                        }
                    }
                }
                return $results;
            }
        }
    }

    public function displayEmailIfAdminOfParent(array $entries, ?array $arg): array
    {
        $selectmembersdisplayfilters = (
            !empty($arg['selectmembersdisplayfilters']) &&
            in_array($arg['selectmembersdisplayfilters'], [true,1,"1","true"], true)
        );
        $entriesIds = array_map(function ($entry) {
            return $entry['id_fiche'] ?? "";
        }, $entries);
        $filteredEntries = $this->filterEntriesFromParents(
            $entriesIds,
            false,
            "only_members",
            "",
            function (array $entry, array $form, string $suffix, $user) use (&$entries, $entriesIds) {
                $entryKey = array_search($entry['id_fiche'] ?? '', $entriesIds);
                if ($entryKey !== false) {
                    foreach ($form['prepared'] as $field) {
                        $propName = $field->getPropertyName();
                        if ($field instanceof EmailField && !empty($propName)) {
                            $email = $entry[$propName] ?? "";
                            if (!empty($email) && isset($entries[$entryKey][$propName])) {
                                $entries[$entryKey][$propName] = $email;
                            }
                        }
                    }
                }
                return $entry;
            },
            $selectmembersdisplayfilters
        );
        if ($selectmembersdisplayfilters) {
            foreach ($entries as $idx => $entry) {
                $entryId = $entry['id_fiche'] ?? "";
                if (!empty($entryId) && !empty($filteredEntries[$entryId]) &&
                    !empty($filteredEntries[$entryId][self::KEY_FOR_PARENTS])) {
                    $entries[$idx][self::KEY_FOR_PARENTS] = $filteredEntries[$entryId][self::KEY_FOR_PARENTS];
                    if (!empty($filteredEntries[$entryId][self::KEY_FOR_AREAS])) {
                        $entries[$idx][self::KEY_FOR_AREAS] = $filteredEntries[$entryId][self::KEY_FOR_AREAS];
                    }
                    if (!empty($filteredEntries[$entryId]['html_data'])) {
                        $entries[$idx]['html_data'] = $filteredEntries[$entryId]['html_data'];
                    }
                }
            }
        }
        return $entries;
    }

    public function updateFilters(?array $filters, ?string $renderedEntries): array
    {
        $keyForParents = preg_quote(self::KEY_FOR_PARENTS, "/");
        $keyForAreas = preg_quote(self::KEY_FOR_AREAS, "/");
        $tag = WN_CAMEL_CASE_EVOLVED;
        $tagOrComa = "[\p{L}\-_.0-9,]+" ; // WN_CAMEL_CASE_EVOLVED + ","
        if (preg_match_all("/data-id_fiche=\"($tag)\"[^>]+data-$keyForParents=\"($tagOrComa)\"[^>]*(?:data-$keyForAreas=\"($tagOrComa)\")?/", $renderedEntries, $matches)) {
            $parents = [];
            $areas = [];
            foreach ($matches[0] as $idx => $match) {
                $tag = $matches[1][$idx];
                $parentsAsString = $matches[2][$idx];
                $areasAsString = $matches[3][$idx];
                $currentParents = empty($parentsAsString) ? [] : explode(',', $parentsAsString);
                if (!isset($parents[$tag])) {
                    $parents[$tag] = $currentParents;
                }
                $currentAreas = empty($areasAsString) ? [] : explode(',', $areasAsString);
                if (!isset($parents[$tag])) {
                    $areas[$tag] = $areasAsString;
                }
            }
            $formattedParents = [];
            foreach ($parents as $entryId => $list) {
                foreach ($list as $tagName) {
                    if (!isset($formattedParents[$tagName])) {
                        $formattedParents[$tagName] = [
                            'nb' => 0
                        ];
                    }
                    $formattedParents[$tagName]['nb'] = $formattedParents[$tagName]['nb'] + 1;
                }
            }
            $formattedAreas = [];
            foreach ($areas as $entryId => $list) {
                foreach ($list as $tagName) {
                    if (!isset($formattedAreas[$tagName])) {
                        $formattedAreas[$tagName] = [
                            'nb' => 0
                        ];
                    }
                    $formattedAreas[$tagName]['nb'] = $formattedAreas[$tagName]['nb'] + 1;
                }
            }
        }
        if (!empty($formattedParents)) {
            $tabfacette = [];
            $tab = (empty($_GET['facette']) || !is_string($_GET['facette'])) ? [] : explode('|', $_GET['facette']);
            //découpe la requete autour des |
            foreach ($tab as $req) {
                $tabdecoup = explode('=', $req, 2);
                if (count($tabdecoup)>1) {
                    $tabfacette[$tabdecoup[0]] = explode(',', trim($tabdecoup[1]));
                }
            }
            $parentList = [];
            foreach ($formattedParents as $tagName => $formattedParent) {
                $parentList[] = [
                    "id" => self::KEY_FOR_PARENTS.$tagName,
                    "name" => self::KEY_FOR_PARENTS,
                    "value" => $tagName,
                    "label" => $tagName,
                    "nb" => $formattedParent['nb'] ?? 0,
                    "checked" => (!empty($tabfacette[self::KEY_FOR_PARENTS]) && in_array($tagName, $tabfacette[self::KEY_FOR_PARENTS])) ? " checked" : ""
                ];
            }
            $areaList = [];
            foreach ($formattedAreas as $tagName => $formattedArea) {
                $areaList[] = [
                    "id" => self::KEY_FOR_AREAS.$tagName,
                    "name" => self::KEY_FOR_AREAS,
                    "value" => $tagName,
                    "label" => $tagName,
                    "nb" => $formattedArea['nb'] ?? 0,
                    "checked" => (!empty($tabfacette[self::KEY_FOR_PARENTS]) && in_array($tagName, $tabfacette[self::KEY_FOR_PARENTS])) ? " checked" : ""
                ];
            }
            $newFilters = [];
            if (!empty($areaList)) {
                $newFilters[self::KEY_FOR_AREAS] = [
                    "icon" => "",
                    "title" => _t('CUSTOMSENDMAIL_AREAS_TITLES'),
                    "collapsed" => false,
                    "index" => 0,
                    "list" => $areaList
                ];
                $index = 1;
            } else {
                $index = 0;
            }
            $newFilters[self::KEY_FOR_PARENTS] = [
                "icon" => "",
                "title" => _t('CUSTOMSENDMAIL_PARENTS_TITLES'),
                "collapsed" => false,
                "index" => $index,
                "list" => $parentList
            ];
            foreach ($filters as $key => $value) {
                $newFilters[$key] = $value;
            }
            $filters = $newFilters;
        }
        return $filters;
    }

    public function isAdminOfParent(
        array $entry,
        array $fields,
        string $suffix = "",
        string $loggedUserName = "",
        bool $appendDisplayData = false
    ): array {
        if (empty($suffix)) {
            $suffix = $this->getAdminSuffix();
            if (empty($suffix)) {
                return [];
            }
        }
        if (empty($loggedUserName)) {
            $user = $this->userManager->getLoggedUser();
            if (empty($user['name'])) {
                return [];
            } else {
                $loggedUserName = $user['name'];
            }
        }
        $parentsIds = [];
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
                    if ($this->isParentAdmin($parentEntry, $suffix, $loggedUserName, $parentsForm) &&
                            !in_array($parentEntry, $parentsIds)) {
                        $parentsIds[] = $parentEntry;
                        if (!$appendDisplayData) {
                            return $parentsIds;
                        }
                    }
                }
            }
        }
        return $parentsIds;
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

    protected function processAreas(
        array $entry,
        array &$results,
        $field,
        $fieldForArea,
        array $areas,
        ?array $form,
        ?string $suffix,
        $user,
        $callback,
        bool $appendDisplayData = false
    ) {
        // same Area
        if (!empty($areas) &&
                $field instanceof EnumField &&
                $field->getLinkedObjectName() == $fieldForArea->getLinkedObjectName()) {
            if ($field instanceof CheckboxField) {
                $currentAreas = $field->getValues($entry);
            } else {
                $currentAreas = !empty($entry[$field->getPropertyName()]) ? [$entry[$field->getPropertyName()]] : [];
            }
            $validatedAreas = array_filter($currentAreas, function ($area) use ($areas) {
                return in_array($area, array_keys($areas));
            });
            if (!empty($validatedAreas)) {
                $this->appendEntryWithData(
                    $entry,
                    $results,
                    $form,
                    $suffix,
                    $user,
                    $callback,
                    self::KEY_FOR_AREAS,
                    $validatedAreas,
                    $appendDisplayData
                );
                if ($appendDisplayData) {
                    $validatedParentsIds = [];
                    foreach ($validatedAreas as $area) {
                        $parentsIds = $areas[$area];
                        foreach ($parentsIds as $parentId) {
                            if (!in_array($parentId, $validatedParentsIds)) {
                                $validatedParentsIds[] = $parentId;
                            }
                        }
                    }
                    if (!empty($validatedParentsIds)) {
                        $this->appendEntryWithData(
                            $entry,
                            $results,
                            $form,
                            $suffix,
                            $user,
                            $callback,
                            self::KEY_FOR_PARENTS,
                            $validatedParentsIds,
                            true
                        );
                    }
                }
            }
        }
        // check postal code
        // check group of areas
    }

    protected function appendEntryWithData(
        array $entry,
        array &$results,
        ?array $form,
        ?string $suffix,
        $user,
        $callback,
        string $key,
        $ids,
        bool $appendDisplayData = false
    ) {
        if (!in_array($entry['id_fiche'], array_keys($results))) {
            $results[$entry['id_fiche']] =  is_callable($callback) ? $callback($entry, $form, $suffix, $user) : $entry;
        }
        if ($appendDisplayData) {
            if (!isset($results[$entry['id_fiche']][$key])) {
                $results[$entry['id_fiche']][$key] = [];
            }
            foreach ($ids as $id) {
                if (!in_array($id, $results[$entry['id_fiche']][$key])) {
                    $results[$entry['id_fiche']][$key][] = $id;
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
        } elseif ($this->wiki->UserIsAdmin($loggedUserName)) {
            return true;
        } else {
            $parentOwner = $this->pageManager->getOwner($entryId);
            $groupName = "{$entryId}$suffix";
            $groupAcl = $this->wiki->GetGroupACL($groupName);
            return ((!empty($parentOwner) && $parentOwner == $loggedUserName) ||
                (!empty($groupAcl) && $this->aclService->check($groupAcl, $loggedUserName, true)));
        }
    }
}
