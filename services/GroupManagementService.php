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

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Groupmanagement\Service\GroupManagementService as MainGroupManagementService;
use YesWiki\Customsendmail\Service\GroupManagementServiceInterface;
use YesWiki\Wiki;

if (file_exists('tools/groupmanagement/services/GroupManagementService.php')) {
    include_once 'tools/groupmanagement/services/GroupManagementService.php';
}
if (file_exists('tools/customsendmail/services/GroupManagementServiceInterface.php')) {
    include_once 'tools/customsendmail/services/GroupManagementServiceInterface.php';
}

if (class_exists(MainGroupManagementService::class, false)) {
    class GroupManagementService extends MainGroupManagementService implements GroupManagementServiceInterface
    {
    }
} else {
    class GroupManagementService implements GroupManagementServiceInterface
    {
        protected $wiki;

        public function __construct(Wiki $wiki)
        {
            $this->wiki = $wiki;
        }
        public function getParentsWhereOwner($user, $formId): array
        {
            $this->triggerErrorIfNeeded();
            return [];
        }
        public function getParentsWhereAdmin(array $parentsWhereOwner, array $user, string $groupSuffix, string $parentsForm): array
        {
            $this->triggerErrorIfNeeded();
            return [];
        }
        public function getParentsIds(string $parentsForm): array
        {
            $this->triggerErrorIfNeeded();
            return [];
        }
        public function getParent(string $parentsForm, string $tag): ?array
        {
            $this->triggerErrorIfNeeded();
            return [];
        }
        public function isParent(string $tag, string $parentsForm): bool
        {
            $this->triggerErrorIfNeeded();
            false;
        }
        protected function triggerErrorIfNeeded()
        {
            if ($this->wiki->UserIsAdmin()) {
                trigger_error("Extension `customsendmail` works only with extension `groupmanagement` !");
            }
        }
    }
}
