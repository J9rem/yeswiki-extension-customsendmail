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
        if (!$this->wiki->services->has(GroupController::class)) {
            return [];
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

            return $this->getService(GroupController::class)->defineBazarListeActionParams(
                $arg,
                $_GET ?? [],
                function (bool $isDynamic, bool $isAdmin, array $_arg) use ($selectmembers, $selectmembersdisplayfilters) {
                    $replaceTemplate = !$isDynamic && !empty($selectmembers) && !$isAdmin;
                    $options = ['selectmembers' => $selectmembers];
                    if (!$isDynamic && $selectmembersdisplayfilters) {
                        $groups = $this->formatArray($_GET['groups'] ?? $_arg['groups'] ?? null);
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
