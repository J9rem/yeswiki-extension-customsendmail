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

            return $this->getService(GroupController::class)->defineBazarListeActionParams(
                $arg,
                $_GET ?? [],
                function (bool $isDynamic, bool $isAdmin, array $_arg) use ($selectmembers) {
                    $replaceTemplate = !$isDynamic && !empty($selectmembers) && !$isAdmin;
                    $options = ['selectmembers' => $selectmembers];
                    return compact(['replaceTemplate','options']);
                }
            );
        }
    }

    public function run()
    {
    }
}
