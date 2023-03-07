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

use Configuration;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Field\CheckboxField;
use YesWiki\Bazar\Field\EmailField;
use YesWiki\Bazar\Field\EnumField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Customsendmail\Service\GroupManagementServiceInterface;
use YesWiki\Wiki;

class CustomSendMailService
{
    public const FRENCH_DEPARTMENTS_TITLE = "Départements français";
    public const FRENCH_DEPARTMENTS_LIST_NAME = "ListeDepartementsFrancais";
    public const FRENCH_AREAS_TITLE = "Régions françaises";
    public const FRENCH_AREAS_LIST_NAME = "ListeRegionsFrancaises";
    public const KEY_FOR_PARENTS = "bf-custom-send-mail-parents";
    public const KEY_FOR_AREAS = "bf-custom-send-mail-areas";

    protected $areaAssociationCache;
    protected $areaAssociationForm;
    protected $entryManager;
    protected $departmentList;
    protected $departmentListName;
    protected $formManager;
    protected $groupManagementService;
    protected $listManager;
    protected $params;
    protected $postalCodeFieldName;
    protected $wiki;

    public function __construct(
        EntryManager $entryManager,
        FormManager $formManager,
        GroupManagementServiceInterface $groupManagementService,
        ListManager $listManager,
        ParameterBagInterface $params,
        Wiki $wiki
    ) {
        $this->areaAssociationCache = null;
        $this->areaAssociationForm = null;
        $this->departmentList = null;
        $this->departmentListName = null;
        $this->entryManager = $entryManager;
        $this->formManager = $formManager;
        $this->groupManagementService = $groupManagementService;
        $this->listManager = $listManager;
        $this->params = $params;
        $this->postalCodeFieldName = null;
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
        $suffix = $this->getAdminSuffix();
        $areaData = ($mode == "members_and_profiles_in_area") ? null : [];
        $filteredEntries = $this->groupManagementService->filterEntriesFromParents(
            $entries,
            $entriesMode,
            $suffix,
            $selectmembersparentform,
            function (array &$formCache, string $formId, $user) use (&$areaData, $selectmembersparentform, $suffix) {
                if (is_null($areaData)) {
                    // lazy loading
                    $areaData = $this->getAreas($selectmembersparentform, $suffix, $user);
                }
                $this->extractExtraFields($formCache, $formId, $areaData['fieldForArea'] ?? null);
            },
            self::KEY_FOR_PARENTS,
            $callback,
            function (array $entry, array &$results, array $formData, $user) use (&$areaData, $selectmembersparentform, $suffix, $mode, $callback, $appendDisplayData) {
                if (is_null($areaData)) {
                    // lazy loading
                    $areaData = $this->getAreas($selectmembersparentform, $suffix, $user);
                }
                if ($mode == "members_and_profiles_in_area" && (!empty($formData['areaFields']) || !empty($formData['association']))) {
                    $this->processAreas($entry, $results, $formData, $areaData['areas'], $suffix, $user, $callback, $appendDisplayData);
                }
            },
            $appendDisplayData
        );
        if (!empty($filteredEntries) && $appendDisplayData) {
            foreach ($filteredEntries as $entryId => $entry) {
                if (!empty($entry[self::KEY_FOR_PARENTS])) {
                    $filteredEntries[$entryId]['html_data'] = $filteredEntries[$entryId]['html_data'] . " data-".self::KEY_FOR_PARENTS."=\"".htmlentities(implode(',', $entry[self::KEY_FOR_PARENTS]))."\"";
                }
                if (!empty($entry[self::KEY_FOR_AREAS])) {
                    $filteredEntries[$entryId]['html_data'] = $filteredEntries[$entryId]['html_data'] . " data-".self::KEY_FOR_AREAS."=\"".htmlentities(implode(',', $entry[self::KEY_FOR_AREAS]))."\"";
                }
            }
        }
        return $filteredEntries;
    }

    /**
     * @param array &$formCache
     * @param scalar $formId
     * @param null|EnumField $fieldForArea
     */
    public function extractExtraFields(array &$formCache, string $formId, $fieldForArea)
    {
        $formCache[$formId]['areaFields'] = [];
        $formCache[$formId]['association'] = null;
        $areaAssociationForm = $this->getAreaAssociationForm();
        foreach ($formCache[$formId]['form']['prepared'] as $field) {
            if ($fieldForArea &&
                $field instanceof EnumField &&
                $field->getLinkedObjectName() === $fieldForArea->getLinkedObjectName()) {
                $formCache[$formId]['areaFields'][] = $field;
            }
            if (!empty($areaAssociationForm['linkedObjectName']) &&
                $field instanceof EnumField &&
                $field->getLinkedObjectName() === $areaAssociationForm['linkedObjectName']) {
                $formCache[$formId]['association'] = $field;
            }
        }
    }

    public function displayEmailIfAdminOfParent(array $entries, ?array $arg): array
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
            $selectmembers,
            $selectmembersparentform,
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

    public function updateFilters(?array $filters, ?string $renderedEntries, ?array $entries = null, array $arg = []): array
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
        $isMapTemplate = (!empty($arg['template']) && $arg['template'] === "map");
        if (!empty($renderedEntries)) {
            extract($this->getParentsAreasFromRender($renderedEntries));
        } elseif (!empty($entries)) {
            extract($this->getParentsAreas($entries, $isMapTemplate));
        } else {
            $parents = [];
            $areas = [];
        }
        $formattedParents = [];
        $formattedAreas = [];
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
                $entry = $this->entryManager->getOne($tagName);
                $label = empty($entry['bf_titre']) ? $tagName : $entry['bf_titre'];
                $parentList[] = [
                    "id" => self::KEY_FOR_PARENTS.$tagName,
                    "name" => self::KEY_FOR_PARENTS,
                    "value" => strval($tagName),
                    "label" => $label,
                    "nb" => $formattedParent['nb'] ?? 0,
                    "checked" => (!empty($tabfacette[self::KEY_FOR_PARENTS]) && in_array($tagName, $tabfacette[self::KEY_FOR_PARENTS])) ? " checked" : ""
                ];
            }
            $encoding = mb_internal_encoding();
            usort($parentList, function ($a, $b) use ($encoding) {
                if ($a['label'] == $b['label']) {
                    return 0;
                }
                return strcmp(mb_strtoupper($a['label'], $encoding), mb_strtoupper($b['label'], $encoding));
            });
            $areaList = [];
            $options = [];
            if ($selectmembers == "members_and_profiles_in_area") {
                $areaFieldName = $this->getAreaFieldName();
                if (!empty($areaFieldName) && !empty($selectmembersparentform)) {
                    $fieldForArea = $this->formManager->findFieldFromNameOrPropertyName($areaFieldName, $selectmembersparentform);
                    if (!empty($fieldForArea)) {
                        $options = $fieldForArea->getOptions();
                    }
                }
            }
            foreach ($formattedAreas as $tagName => $formattedArea) {
                $label = empty($options[$tagName]) ? $tagName : $options[$tagName];
                $areaList[] = [
                    "id" => self::KEY_FOR_AREAS.$tagName,
                    "name" => self::KEY_FOR_AREAS,
                    "value" => strval($tagName),
                    "label" => $label,
                    "nb" => $formattedArea['nb'] ?? 0,
                    "checked" => (!empty($tabfacette[self::KEY_FOR_PARENTS]) && in_array($tagName, $tabfacette[self::KEY_FOR_PARENTS])) ? " checked" : ""
                ];
            }
            usort($areaList, function ($a, $b) use ($encoding) {
                if ($a['value'] == $b['value']) {
                    return 0;
                }
                return strcmp(mb_strtoupper($a['value'], $encoding), mb_strtoupper($b['value'], $encoding));
            });
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
            if (count($parentList) > 1){
                $newFilters[self::KEY_FOR_PARENTS] = [
                    "icon" => "",
                    "title" => _t('CUSTOMSENDMAIL_PARENTS_TITLES'),
                    "collapsed" => false,
                    "index" => $index,
                    "list" => $parentList
                ];
            }
            foreach ($filters as $key => $value) {
                $newFilters[$key] = $value;
            }
            $filters = $newFilters;
        }
        return $filters;
    }

    private function getParentsAreasFromRender(string $renderedEntries): array
    {
        $parents = [];
        $areas = [];
        $tagOrComa = "[\p{L}\-_.0-9,]+" ; // WN_CAMEL_CASE_EVOLVED + ","
        $search = 'data-id_fiche="__tag__"';
        $search = preg_quote($search, "/");
        $search = str_replace('__tag__', '('.WN_CAMEL_CASE_EVOLVED.')', $search);

        $part1 = '__sep__data-__keyForParents__="__tagOrComa__"';
        $part1 = str_replace('__keyForParents__', self::KEY_FOR_PARENTS, $part1);
        $part1 = preg_quote($part1, "/");

        $part2 = '__sep__data-__keyForAreas__="__tagOrComa__"';
        $part2 = str_replace('__keyForAreas__', self::KEY_FOR_AREAS, $part2);
        $part2 = preg_quote($part2, "/");

        $search = "/{$search}(?:$part1$part2|$part1)/";
        $search = str_replace('__sep__', '[^>]+', $search);
        $search = str_replace('__tagOrComa__', "($tagOrComa)", $search);

        if (preg_match_all($search, $renderedEntries, $matches)) {
            foreach ($matches[0] as $idx => $match) {
                $tag = $matches[1][$idx];
                $parentsAsString = !empty($matches[2][$idx])
                    ? $matches[2][$idx]
                    : (
                        $matches[4][$idx]
                    );
                $areasAsString = $matches[3][$idx];
                $currentParents = empty($parentsAsString) ? [] : explode(',', $parentsAsString);
                if (!isset($parents[$tag])) {
                    $parents[$tag] = $currentParents;
                }
                $currentAreas = empty($areasAsString) ? [] : explode(',', $areasAsString);
                if (!isset($areas[$tag])) {
                    $areas[$tag] = $currentAreas;
                }
            }
        }
        return compact(['parents','areas']);
    }

    private function getParentsAreas(array $entries, bool $isMapTemplate): array
    {
        $parents = [];
        $areas = [];
        foreach ($entries as $entry) {
            if (!$isMapTemplate || (!empty($entry['bf_latitude']) && !empty($entry['bf_longitude']))) {
                foreach ([
                    self::KEY_FOR_PARENTS => 'parents',
                    self::KEY_FOR_AREAS => 'areas',
                ] as $key => $varName) {
                    $counter = -1;
                    $values = empty($entry[$key])
                        ? []
                        : (
                            is_string($entry[$key])
                            ? explode(',', $entry[$key])
                            : (
                                is_array($entry[$key])
                                ? (
                                    count(array_filter($entry[$key], function ($k) use (&$counter) {
                                        $counter = $counter + 1;
                                        return $k != $counter;
                                    }, ARRAY_FILTER_USE_KEY)) > 0
                                    ? array_keys(array_filter($entry[$key], function ($val) {
                                        return in_array($val, [1,true,"1","true"]);
                                    }))
                                    : $entry[$key]
                                )
                                : []
                            )
                        );
                    if (!isset($$varName[$entry['id_fiche']])) {
                        $$varName[$entry['id_fiche']] = $values;
                    }
                }
            }
        }
        return compact(['parents','areas']);
    }

    protected function getAreas($selectmembersparentform, $suffix, $user): array
    {
        $areaFieldName = $this->getAreaFieldName();
        $fieldForArea = null;
        $areas = [];
        if (!empty($areaFieldName) && !empty($selectmembersparentform)) {
            $fieldForArea = $this->formManager->findFieldFromNameOrPropertyName($areaFieldName, $selectmembersparentform);
            if (!empty($fieldForArea) && $fieldForArea instanceof EnumField) {
                $parentsWhereAdmin = $this->groupManagementService->getParentsWhereAdmin($selectmembersparentform, $suffix, $user['name']);
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
        array $formData,
        array $areas,
        ?string $suffix,
        $user,
        $callback,
        bool $appendDisplayData
    ) {
        if (!empty($areas)) {
            $validatedAreas = [];
            // same Area
            $currentAreas = [];
            foreach ($formData['areaFields'] as $field) {
                if ($field instanceof CheckboxField) {
                    $newAreas = $field->getValues($entry);
                } else {
                    $newAreas = !empty($entry[$field->getPropertyName()]) ? [$entry[$field->getPropertyName()]] : [];
                }
                foreach ($newAreas as $area) {
                    if (!in_array($area, $currentAreas)) {
                        $currentAreas[] = $area;
                    }
                }
            }

            // check administrative areas if $currentAreas is empty
            if (empty($currentAreas) && !empty($formData['association'])) {
                if ($formData['association'] instanceof CheckboxField) {
                    $currentAdminAreas = ($formData['association'])->getValues($entry);
                } else {
                    $currentAdminAreas = !empty($entry[($formData['association'])->getPropertyName()]) ? [$entry[($formData['association'])->getPropertyName()]] : [];
                }
                $associations = $this->getAssociations();
                foreach ($currentAdminAreas as $area) {
                    if (!empty($associations['areas'][$area])) {
                        foreach ($associations['areas'][$area] as $dept) {
                            if (!in_array($dept, $currentAreas)) {
                                $currentAreas[] = $dept;
                            }
                        }
                    }
                }
            }

            $listOfAreas = array_keys($areas);
            $validatedAreas = array_filter($currentAreas, function ($area) use ($listOfAreas) {
                return in_array($area, $listOfAreas);
            });

            // check postal code than append
            $areaFromPostalCode = $this->extractAreaFromPostalCode($entry);
            if (!empty($areaFromPostalCode) &&
                in_array($areaFromPostalCode, $listOfAreas) &&
                !in_array($areaFromPostalCode, $validatedAreas)
            ) {
                $validatedAreas[] = $areaFromPostalCode;
            }

            // save areas
            if (!empty($validatedAreas)) {
                $this->groupManagementService->appendEntryWithData(
                    $entry,
                    $results,
                    $appendDisplayData ? self::KEY_FOR_AREAS : '',
                    $validatedAreas,
                    function ($internalEntry) use ($formData, $suffix, $user, $callback) {
                        return (is_callable($callback))
                          ? $callback($internalEntry, $formData['form'], $suffix, $user)
                          : $internalEntry;
                    }
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
                        $this->groupManagementService->appendEntryWithData(
                            $entry,
                            $results,
                            self::KEY_FOR_PARENTS,
                            $validatedParentsIds,
                            function ($internalEntry) use ($formData, $suffix, $user, $callback) {
                                return (is_callable($callback))
                                  ? $callback($internalEntry, $formData['form'], $suffix, $user)
                                  : $internalEntry;
                            }
                        );
                    }
                }
            }
        }
    }

    private function getPostalCodeFieldName(): string
    {
        if (is_null($this->postalCodeFieldName)) {
            $this->postalCodeFieldName = $this->params->get('PostalCodeFieldName');
            if (!is_string($this->postalCodeFieldName)) {
                $this->postalCodeFieldName = "";
            }
        }
        return $this->postalCodeFieldName;
    }

    private function getDepartmentListName(): string
    {
        if (is_null($this->departmentListName)) {
            $this->departmentListName = $this->params->get('departmentListName');
            if (!is_string($this->departmentListName)) {
                $this->departmentListName = "";
            }
        }
        return $this->departmentListName;
    }

    private function getDepartmentList(): array
    {
        if (is_null($this->departmentList)) {
            $departmentListName = $this->getDepartmentListName();
            if (!empty($departmentListName)) {
                $list = $this->listManager->getOne($departmentListName);
                if (!empty($departmentListName['label'])) {
                    $this->departmentList = $departmentListName['label'];
                    return $this->departmentList;
                }
            }
            $this->departmentList = [];
        }
        return $this->departmentList;
    }

    private function getFormIdAreaToDepartment(): string
    {
        $formId = $this->params->get('formIdAreaToDepartment');
        return (
            !empty($formId) &&
            is_scalar($formId) &&
            (strval($formId) == strval(intval($formId))) &&
            intval($formId)>0
        )
            ? strval($formId)
            : "";
    }

    private function getAreaAssociationForm(): array
    {
        if (is_null($this->areaAssociationForm)) {
            $this->areaAssociationForm = [];
            $formId = $this->getFormIdAreaToDepartment();
            $departmentListName = $this->getDepartmentListName();
            if (!empty($formId) && !empty($departmentListName)) {
                $form = $this->formManager->getOne($formId);
                if (!empty($form['prepared'])) {
                    $areaField = null;
                    $deptField = null;
                    foreach ($form['prepared'] as $field) {
                        if (!$areaField &&
                            $field instanceof EnumField &&
                            !empty($field->getLinkedObjectName()) &&
                            $field->getLinkedObjectName() !== $departmentListName) {
                            $areaField = $field;
                        } elseif (!$deptField &&
                            $field instanceof EnumField &&
                            !empty($field->getLinkedObjectName()) &&
                            $field->getLinkedObjectName() === $departmentListName) {
                            $deptField = $field;
                        }
                    }
                    if ($areaField && $deptField) {
                        $this->areaAssociationForm = [
                            'form' => $form,
                            'field' => $areaField,
                            'formId' => $formId,
                            'linkedObjectName' => $areaField->getLinkedObjectName(),
                            'deptField' => $deptField
                        ];
                    }
                }
            }
        }
        return $this->areaAssociationForm;
    }

    private function getAssociations(): array
    {
        if (is_null($this->areaAssociationCache)) {
            $this->areaAssociationCache = [];
            $formData = $this->getAreaAssociationForm();
            if (!empty($formData)) {
                $entries = $this->entryManager->search([
                    'formsIds' => [$formData['formId']]
                ]);
                if (!empty($entries)) {
                    $areaPropName = ($formData['field'])->getPropertyName();
                    $deptPropName = ($formData['deptField'])->getPropertyName();
                    foreach ($entries as $entry) {
                        $area = (!empty($entry[$areaPropName]) && is_string($areaPropName))
                            ? explode(',', $entry[$areaPropName])[0]
                            : "";
                        if (!empty($area)) {
                            $depts = (!empty($entry[$deptPropName]) && is_string($deptPropName))
                                ? explode(',', $entry[$deptPropName])
                                : [];
                            foreach ($depts as $dept) {
                                if (!isset($this->areaAssociationCache['areas'])) {
                                    $this->areaAssociationCache['areas'] = [];
                                }
                                if (!isset($this->areaAssociationCache['areas'][$area])) {
                                    $this->areaAssociationCache['areas'][$area] = [];
                                }
                                if (!in_array($dept, $this->areaAssociationCache['areas'][$area])) {
                                    $this->areaAssociationCache['areas'][$area][] = $dept;
                                }
                                if (!isset($this->areaAssociationCache['depts'])) {
                                    $this->areaAssociationCache['depts'] = [];
                                }
                                if (!isset($this->areaAssociationCache['depts'][$dept])) {
                                    $this->areaAssociationCache['depts'][$dept] = [];
                                }
                                if (!in_array($area, $this->areaAssociationCache['depts'][$dept])) {
                                    $this->areaAssociationCache['depts'][$dept][] = $area;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->areaAssociationCache;
    }

    private function extractAreaFromPostalCode(array $entry): string
    {
        $departmentList = $this->getDepartmentList();
        if (!empty($departmentList)) {
            $postalCodeName = $this->getPostalCodeFieldName();
            $postalCode = (empty($entry[$postalCodeName]) || !is_string($entry[$postalCodeName])) ? '' : $entry[$postalCodeName];
            $postalCode = str_replace(" ", "", trim($postalCode));
            if (strlen($postalCode) === 5) {
                $twoChars = sub_str($postalCode, 0, 2);
                if (!empty($departmentList[$twoChars])) {
                    return $twoChars;
                }
                $threeChars = sub_str($postalCode, 0, 3);
                if (!empty($departmentList[$threeChars])) {
                    return $threeChars;
                }
            }
        }
        return "" ;
    }

    /**
     * @return array ['success' => bool, 'error' => string]
     */
    public function createDepartements(): array
    {
        $success = false;
        $error = '';

        $list = $this->listManager->getOne(self::FRENCH_DEPARTMENTS_LIST_NAME);
        if (!empty($list)) {
            $error = "not possible to create list of departments : '".self::FRENCH_DEPARTMENTS_LIST_NAME."' is alreadyExisting !";
            $success = false;
        } else {
            $this->listManager->create(self::FRENCH_DEPARTMENTS_TITLE, [
                "1"=> "Ain",
                "2"=> "Aisne",
                "3"=> "Allier",
                "4"=> "Alpes-de-Haute-Provence",
                "5"=> "Hautes-Alpes",
                "6"=> "Alpes-Maritimes",
                "7"=> "Ardèche",
                "8"=> "Ardennes",
                "9"=> "Ariège",
                "10"=> "Aube",
                "11"=> "Aude",
                "12"=> "Aveyron",
                "13"=> "Bouches-du-Rhône",
                "14"=> "Calvados",
                "15"=> "Cantal",
                "16"=> "Charente",
                "17"=> "Charente-Maritime",
                "18"=> "Cher",
                "19"=> "Corrèze",
                "2A"=> "Corse-du-Sud",
                "2B"=> "Haute-Corse",
                "21"=> "Côte-d'Or",
                "22"=> "Côtes-d'Armor",
                "23"=> "Creuse",
                "24"=> "Dordogne",
                "25"=> "Doubs",
                "26"=> "Drôme",
                "27"=> "Eure",
                "28"=> "Eure-et-Loir",
                "29"=> "Finistère",
                "30"=> "Gard",
                "31"=> "Haute-Garonne",
                "32"=> "Gers",
                "33"=> "Gironde",
                "34"=> "Hérault",
                "35"=> "Ille-et-Vilaine",
                "36"=> "Indre",
                "37"=> "Indre-et-Loire",
                "38"=> "Isère",
                "39"=> "Jura",
                "40"=> "Landes",
                "41"=> "Loir-et-Cher",
                "42"=> "Loire",
                "43"=> "Haute-Loire",
                "44"=> "Loire-Atlantique",
                "45"=> "Loiret",
                "46"=> "Lot",
                "47"=> "Lot-et-Garonne",
                "48"=> "Lozère",
                "49"=> "Maine-et-Loire",
                "50"=> "Manche",
                "51"=> "Marne",
                "52"=> "Haute-Marne",
                "53"=> "Mayenne",
                "54"=> "Meurthe-et-Moselle",
                "55"=> "Meuse",
                "56"=> "Morbihan",
                "57"=> "Moselle",
                "58"=> "Nièvre",
                "59"=> "Nord",
                "60"=> "Oise",
                "61"=> "Orne",
                "62"=> "Pas-de-Calais",
                "63"=> "Puy-de-Dôme",
                "64"=> "Pyrénnées-Atlantiques",
                "65"=> "Hautes-Pyrénnées",
                "66"=> "Pyrénnées-Orientales",
                "67"=> "Bas-Rhin",
                "68"=> "Haut-Rhin",
                "69"=> "Rhône",
                "70"=> "Haute-Saône",
                "71"=> "Saône-et-Loire",
                "72"=> "Sarthe",
                "73"=> "Savoie",
                "74"=> "Haute-Savoie",
                "75"=> "Paris",
                "76"=> "Seine-Maritime",
                "77"=> "Seine-et-Marne",
                "78"=> "Yvelines",
                "79"=> "Deux-Sèvres",
                "80"=> "Somme",
                "81"=> "Tarn",
                "82"=> "Tarn-et-Garonne",
                "83"=> "Var",
                "84"=> "Vaucluse",
                "85"=> "Vendée",
                "86"=> "Vienne",
                "87"=> "Haute-Vienne",
                "88"=> "Vosges",
                "89"=> "Yonne",
                "90"=> "Territoire-de-Belfort",
                "91"=> "Essonne",
                "92"=> "Hauts-de-Seine",
                "93"=> "Seine-Saint-Denis",
                "94"=> "Val-de-Marne",
                "95"=> "Val-d'Oise",
                "99"=> "Etranger",
                "971"=> "Guadeloupe",
                "972"=> "Martinique",
                "973"=> "Guyane",
                "974"=> "Réunion",
                "975"=> "St-Pierre-et-Miquelon",
                "976"=> "Mayotte",
                "977"=> "Saint-Barthélemy",
                "978"=> "Saint-Martin",
                "986"=> "Wallis-et-Futuna",
                "987"=> "Polynésie-Francaise",
                "988"=> "Nouvelle-Calédonie"
            ]);
            $list = $this->listManager->getOne(self::FRENCH_DEPARTMENTS_LIST_NAME);
            $success = !empty($list);
            if (!$success) {
                $error = "not possible to create list of departments : '".self::FRENCH_DEPARTMENTS_LIST_NAME."' error during creation !";
            }
        }
        return compact(['success','error']);
    }

    /**
     * @return array ['success' => bool, 'error' => string]
     */
    public function createAreas(): array
    {
        $success = false;
        $error = '';

        $list = $this->listManager->getOne(self::FRENCH_AREAS_LIST_NAME);
        if (!empty($list)) {
            $error = "not possible to create list of areas : '".self::FRENCH_AREAS_LIST_NAME."' is alreadyExisting !";
            $success = false;
        } else {
            // CODE ISO 3166-2
            $this->listManager->create(self::FRENCH_AREAS_TITLE, [
                "ARA"=> "Auvergne-Rhône-Alpes",
                "BFC"=> "Bourgogne-Franche-Comté",
                "BRE"=> "Bretagne",
                "CVL"=> "Centre-Val de Loire",
                "COR"=> "Corse",
                "GES"=> "Grand Est",
                "HDF"=> "Hauts-de-France",
                "IDF"=> "Île-de-France",
                "NOR"=> "Normandie",
                "NAQ"=> "Nouvelle-Aquitaine",
                "OCC"=> "Occitanie",
                "PDL"=> "Pays de la Loire",
                "PAC"=> "Provence-Alpes-Côte d'Azur",
                "GUA"=> "Guadeloupe",
                "GUF"=> "Guyane",
                "LRE"=> "La Réunion",
                "MTQ"=> "Martinique",
                "MAY"=> "Mayotte",
                "COM"=> "Collectivités d'outre-mer",
            ]);
            $list = $this->listManager->getOne(self::FRENCH_AREAS_LIST_NAME);
            $success = !empty($list);
            if (!$success) {
                $error = "not possible to create list of areas : '".self::FRENCH_AREAS_LIST_NAME."' error during creation !";
            }
        }
        return compact(['success','error']);
    }


    /**
     * @return array ['success' => bool, 'error' => string]
     */
    public function createFormToAssociateAreasAndDepartments(): array
    {
        $success = false;
        $error = '';

        $formId = $this->params->get('formIdAreaToDepartment');
        if (!empty($formId) && empty($this->getFormIdAreaToDepartment())) {
            $error = 'parameter \'formIdAreaToDepartment\' is defined but with a bad format !';
        } elseif (!empty($formId)) {
            $form = $this->formManager->getOne($formId);
            if (!empty($form)) {
                $error = 'not possible to create the form because already existing !';
            }
        }
        if (empty($error)) {
            $listDept = $this->listManager->getOne(self::FRENCH_DEPARTMENTS_LIST_NAME);
            if (empty($listDept) && ($res = $this->createDepartements()) && !$res['success']) {
                $error = $res['error'];
            }
        }
        if (empty($error)) {
            $listArea = $this->listManager->getOne(self::FRENCH_AREAS_LIST_NAME);
            if (empty($listArea) && ($res = $this->createAreas()) && !$res['success']) {
                $error = $res['error'];
            }
        }
        if (empty($error)) {
            $deptListName = self::FRENCH_DEPARTMENTS_LIST_NAME;
            $arealistName = self::FRENCH_AREAS_LIST_NAME;
            if (empty($formId)) {
                $formId = $this->formManager->findNewId();
            }
            $form = $this->formManager->create([
                'bn_id_nature' => $formId,
                'bn_label_nature' => 'Correspondance régions - départements',
                'bn_template' =>
                <<<TXT
                titre***Départements de {{bf_region}}***Titre Automatique***
                liste***$arealistName***Région*** *** *** ***bf_region*** ***1*** *** *** * *** * *** *** *** ***
                checkbox***$deptListName***Départements*** *** *** ***bf_departement*** ***1*** *** *** * *** * *** *** *** ***
                acls*** * ***@admins***comments-closed***
                TXT,
                'bn_description' => '',
                'bn_sem_context' => '',
                'bn_sem_type' => '',
                'bn_condition' => ''
            ]);
            $form = $this->formManager->getOne($formId);
            if (empty($form)) {
                $error = "not possible to create the form : error during creation !";
            } else {
                $this->saveFormIdInConfig($formId);
                $this->createEntriesForAssociation($formId);
                $success = true;
            }
        }
        return compact(['success','error']);
    }

    private function saveFormIdInConfig($formId)
    {
        // default acls in wakka.config.php
        include_once 'tools/templates/libs/Configuration.php';
        $config = new Configuration('wakka.config.php');
        $config->load();

        $baseKey = 'formIdAreaToDepartment';
        $config->$baseKey = $formId;
        $config->write();
        unset($config);
    }

    private function createEntriesForAssociation($formId)
    {
        foreach ([
            'ARA' => "1,3,7,15,26,38,42,43,63,69,73,74",
            'BFC' => "21,25,39,58,70,71,89,90",
            'BRE' => "22,29,35,44,56",
            "CVL" => "18,28,36,37,41,45",
            "COR" => "2A,2B",
            "GES" => "8,10,51,52,54,55,57,67,68,88",
            "HDF" => "2,59,60,62,80",
            "IDF" => "75,77,78,91,92,93,94,95",
            "NOR" => "14,27,50,61,76",
            "NAQ" => "16,17,19,23,24,33,40,47,64,79,86,87",
            "OCC" => "9,11,12,30,31,32,34,46,48,65,66,81,82",
            "PDL" => "44,49,53,72,85",
            "PAC" => "4,5,6,13,83,84",
            "GUA" => "971",
            "GUF" => "973",
            "LRE" => "974",
            "MTQ" => "972",
            "MAY" => "976",
            "COM" => "975,977,978,986,987",
        ] as $areaCode => $depts) {
            $this->entryManager->create(
                $formId,
                [
                    'antispam' => 1,
                    'bf_titre' => "Départements de {{bf_region}}",
                    'liste'.self::FRENCH_AREAS_LIST_NAME.'bf_region' => $areaCode,
                    'checkbox'.self::FRENCH_DEPARTMENTS_LIST_NAME.'bf_departement' => $depts,
                ],
            );
        }
    }

    private function extractDepartmentFromAdminAreas(array $areasarray): array
    {
        $departments = [];
        foreach ($areasarray as $area) {
        }
        return $departments;
    }
}
