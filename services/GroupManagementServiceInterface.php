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

interface GroupManagementServiceInterface
{
    public function getParentsWhereOwner($user, $formId): array;
    public function getParentsWhereAdminIds(array $parentsWhereOwner, array $user, string $groupSuffix, string $parentsForm): array;
    public function getParentsIds(string $parentsForm): array;
    public function getParent(string $parentsForm, string $tag): ?array;
    public function isParent(string $tag, string $parentsForm): bool;
}
