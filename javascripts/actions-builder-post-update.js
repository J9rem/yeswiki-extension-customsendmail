/*
 * This file is part of the YesWiki Extension customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (typeof actionsBuilderData === 'object' &&
    'action_groups' in actionsBuilderData &&
    'bazarliste' in actionsBuilderData.action_groups &&
    'actions' in actionsBuilderData.action_groups.bazarliste &&
    'commons2' in actionsBuilderData.action_groups.bazarliste.actions &&
    'properties' in actionsBuilderData.action_groups.bazarliste.actions.commons2 &&
    'intrafiltersmode' in actionsBuilderData.action_groups.bazarliste.actions.commons2.properties &&
    'showOnlyFor' in actionsBuilderData.action_groups.bazarliste.actions.commons2.properties.intrafiltersmode && 
    !actionsBuilderData.action_groups.bazarliste.actions.commons2.properties.intrafiltersmode.showOnlyFor.includes('bazarcustomsendmail')
    ){
    actionsBuilderData.action_groups.bazarliste.actions.commons2.properties.intrafiltersmode.showOnlyFor.push('bazarcustomsendmail')
}