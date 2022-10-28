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
use YesWiki\Groupmanagement\Controller\GroupController;

class __BazarListeAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $newArg = [];
        if (!empty($arg['template']) && $arg['template'] == "send-mail"){
            $newArg['dynamic'] = true;
            $newArg['pagination'] = -1;
            $arg['dynamic'] = true;
            $arg['pagination'] = -1;
        }
        if (!$this->wiki->services->has(GroupController::class)) {
            return $newArg;
        } else {
            $selectmembers = (
                !empty($arg['selectmembers']) &&
                    is_string($arg['selectmembers']) &&
                    in_array($arg['selectmembers'], ["only_members","members_and_profiles_in_area"], true)
            ) ? $arg['selectmembers'] : "";

            $selectmembersdisplayfilters = (
                !empty($arg['selectmembersdisplayfilters']) &&
                in_array($arg['selectmembersdisplayfilters'], [true,1,"1","true"], true)
            );

            return $newArg + $this->getService(GroupController::class)->defineBazarListeActionParams(
                $arg,
                $_GET ?? [],
                function (bool $isDynamic, bool $isAdmin, array $_arg) use ($selectmembers, $selectmembersdisplayfilters) {
                    $replaceTemplate = !$isDynamic && !empty($selectmembers) && !$isAdmin;
                    $options = ['selectmembers' => $selectmembers];
                    if ($selectmembersdisplayfilters) {
                        $groups = $this->formatArray($_GET['groups'] ?? $_arg['groups'] ?? null);
                        if (!$isDynamic){
                            $groupicons = $this->formatArray($_arg['groupicons'] ?? null);
                            $titles = $this->formatArray($_GET['titles'] ?? $_arg['titles'] ?? null);
                            array_unshift($groups, CustomSendMailService::KEY_FOR_PARENTS);
                            array_unshift($groupicons, "");
                            array_unshift($titles, _t('CUSTOMSENDMAIL_PARENTS_TITLES'));
                            if ($selectmembers == "members_and_profiles_in_area") {
                                array_unshift($groups, CustomSendMailService::KEY_FOR_PARENTS);
                                array_unshift($groupicons, "");
                                array_unshift($titles, _t('CUSTOMSENDMAIL_AREAS_TITLES'));
                            }
                            $options['groups'] = $groups;
                            $options['groupicons'] = $groupicons;
                            $options['titles'] = $titles;
                            $options['customSendMailService'] = $this->getService(CustomSendMailService::class);
                        } elseif (empty($groups)) {
                            // force groups for layout
                            $options['groups'] = [CustomSendMailService::KEY_FOR_PARENTS];
                        }
                    }
                    return compact(['replaceTemplate','options']);
                }
            );
        }
    }

    public function run()
    {
    }
}
