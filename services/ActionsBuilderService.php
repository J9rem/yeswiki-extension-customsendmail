<?php

/*
 * This file is part of the YesWiki Extension customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Customsendmail\Service;

use YesWiki\Aceditor\Service\ActionsBuilderService as AceditorActionsBuilderService;
use YesWiki\Core\Service\TemplateEngine;
use YesWiki\Wiki;

trait ActionsBuilderServiceCommon
{
    protected $previousData;
    protected $data;
    protected $parentActionsBuilderService;
    protected $renderer;
    protected $wiki;

    public function __construct(TemplateEngine $renderer, Wiki $wiki, $parentActionsBuilderService)
    {
        $this->data = null;
        $this->previousData = null;
        $this->parentActionsBuilderService = $parentActionsBuilderService;
        $this->renderer = $renderer;
        $this->wiki = $wiki;
    }

    public function setPreviousData(?array $data)
    {
        if (is_null($this->previousData)) {
            $this->previousData = is_array($data) ? $data : [];
            if ($this->parentActionsBuilderService && method_exists($this->parentActionsBuilderService, 'setPreviousData')) {
                $this->parentActionsBuilderService->setPreviousData($data);
            }
        }
    }

    // ---------------------
    // Data for the template
    // ---------------------
    public function getData()
    {
        if (is_null($this->data)) {
            if (!empty($this->parentActionsBuilderService)) {
                $this->data = $this->parentActionsBuilderService->getData();
            } else {
                $this->data = $this->previousData;
            }

            if (isset($this->data['action_groups']['bazarliste'])) {
                if (isset($this->data['action_groups']['bazarliste']['actions']) &&
                        !isset($this->data['action_groups']['bazarliste']['actions']['bazarcustomsendmail'])) {
                    $this->data['action_groups']['bazarliste']['actions']['bazarcustomsendmail'] = [
                        'label' => _t('CUSTOMSENDMAIL_SENDMAIL_LABEL'),
                        'description' => _t('CUSTOMSENDMAIL_SENDMAIL_DESCRIPTION'),
                        'width' => '35%',
                        'properties' => [
                            'template' => [
                                'value' => 'send-mail',
                            ],
                            'title' => [
                                'label' => _t('CUSTOMSENDMAIL_SENDMAIL_TITLE_LABEL'),
                                'hint' => _t('CUSTOMSENDMAIL_SENDMAIL_TITLE_EMPTY_LABEL', ['emptyVal' => _t('CUSTOMSENDMAIL_DEFAULT_TITLE')]),
                                'type' => 'text',
                                'default' => _t('CUSTOMSENDMAIL_DEFAULT_TITLE'),
                            ],
                            'defaultsendername' => [
                                'label' => _t('CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SENDERNAME_LABEL'),
                                'type' => 'text',
                                'default' => _t('CUSTOMSENDMAIL_SENDERNAME'),
                            ],
                            'defaultsubject' => [
                                'label' => _t('CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SUBJECT_LABEL'),
                                'type' => 'text',
                                'default' => '',
                            ],
                            'emailfieldname' => [
                                'label' => _t('CUSTOMSENDMAIL_SENDMAIL_EMAILFIELDNAME_LABEL'),
                                'type' => 'form-field',
                                'default' => 'bf_mail',
                            ],
                            'defaultcontent' => [
                                'label' => _t('CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT_LABEL'),
                                'type' => 'text',
                                'default' => _t('CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT'),
                            ],
                            'sendtogroupdefault' => [
                                'label' => _t('CUSTOMSENDMAIL_SENDMAIL_SENDTOGROUPDEFAULT_LABEL'),
                                'type' => 'checkbox',
                                'default' => 'false'
                            ]
                        ],
                    ];
                }

                if (isset($this->data['action_groups']['bazarliste']['actions']['bazartableau'])) {
                    $this->data['action_groups']['bazarliste']['actions']['bazartableauwithemail'] = $this->data['action_groups']['bazarliste']['actions']['bazartableau'];
                    $this->data['action_groups']['bazarliste']['actions']['bazartableauwithemail']['label'] =
                        _t('AB_BAZARTABLEAU_WITH_EMAIL_LABEL');
                    $this->data['action_groups']['bazarliste']['actions']['bazartableauwithemail']['properties']['template']['value'] = 'tableau-with-email.tpl.html';
                }
                if (isset($this->data['action_groups']['bazarliste']['actions']['commons']['properties']['showexportbuttons']['showExceptFor']) &&
                        !in_array('bazarcustomsendmail', $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['showexportbuttons']['showExceptFor'])) {
                    $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['showexportbuttons']['showExceptFor'][] =  'bazarcustomsendmail';
                }
                if (!empty($this->wiki->config['GroupsAdminsSuffixForEmails']) &&
                        isset($this->data['action_groups']['bazarliste']['actions']['commons']['properties'])) {
                    $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['selectmembers'] = [
                        'label' => _t('CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_LABEL'),
                        'hint' => _t('CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_HINT'),
                        'type' => 'list',
                        'default' => '',
                        'advanced' => true,
                        'options' => [
                            'only_members' => _t('CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_ONLY_MEMBERS'),
                            'members_and_profiles_in_area' => _t('CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_BY_AREA'),
                        ],
                    ];
                    $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['selectmembersparentform'] = [
                        'label' => _t('CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERSPARENT_FORM_LABEL'),
                        'type' => 'form-select',
                        'advanced' => true,
                        'required' => true,
                        'min' => 1,
                        'showif' => [
                            'selectmembers' => 'only_members|members_and_profiles_in_area' ,
                        ],
                    ];
                    $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['selectmembersdisplayfilters'] = [
                        'label' => _t('CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_DISPLAY_FILTERS_LABEL'),
                        'type' => 'checkbox',
                        'advanced' => true,
                        'default' => "false",
                        'checkedvalue' => "true",
                        'uncheckedvalue' => "false",
                        'showif' => 'selectmembers'
                    ];
                }
            }
        }
        return $this->data;
    }
}

if (class_exists(AceditorActionsBuilderService::class, false)) {
    class ActionsBuilderService extends AceditorActionsBuilderService
    {
        use ActionsBuilderServiceCommon;
    }
} else {
    class ActionsBuilderService
    {
        use ActionsBuilderServiceCommon;
    }
}
