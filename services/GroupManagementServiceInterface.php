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
    public function getParentsWhereAdminIds(array $parentsWhereOwner, array $user, string $groupSuffix, string $parentsForm): array;
    public function appendEntryWithData(array $entry, array &$results, string $key, $ids, $callback);
    public function filterEntriesFromParents(
        array $entries,
        bool $entriesMode,
        string $suffix,
        string $parentFormId,
        $extractExtraFields,
        string $keyIntoAppendData,
        $callbackForAppendData,
        $extraCallback,
        bool $extractAllIds
    );
}
