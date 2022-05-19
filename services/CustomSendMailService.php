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
use YesWiki\Bazar\Field\CheckboxEntryField;
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

    public function filterEntriesAsParentAdminOrOwner(array $entries, bool $entriesMode = true)
    {
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
                } else {
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
                                    if ($field instanceof CheckboxEntryField ||
                                        $field instanceof RadioEntryField ||
                                        $field instanceof SelectEntryField) {
                                        $parentEntries = ($field instanceof CheckboxEntryField)
                                            ? $field->getValues($entry)
                                            : [$field->getValue($entry)];
                                        foreach ($parentEntries as $parentEntry) {
                                            if ($this->entryManager->isEntry($parentEntry)) {
                                                $parentOwner = $this->pageManager->getOwner($parentEntry);
                                                $groupName = "{$parentEntry}$suffix";
                                                $groupAcl = $this->wiki->GetGroupACL($groupName);
                                                if ((!empty($parentOwner) && $parentOwner == $user['name']) ||
                                                    (!empty($groupAcl) && $this->aclService->check($groupAcl, $user['name'], true))) {
                                                    if (!in_array($entry['id_fiche'], array_keys($results))) {
                                                        $results[$entry['id_fiche']] = $entry;
                                                    }
                                                    break;
                                                }
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
    }
}
