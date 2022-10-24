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

use Symfony\Component\Security\Csrf\CsrfTokenManager;
use YesWiki\Core\YesWikiAction;

class CustomSendmailAutoupdateLinkAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        return [
            'type' => (!empty($arg['type']) && in_array($arg['type'],["departments","areas","form"]))
                ? $arg['type']
                : ""
        ];
    }

    public function run()
    {
        if (!$this->wiki->UserIsAdmin()){
            return $this->render("@templates/alert-message.twig",[
                'type' => 'danger',
                'message' => _t('CUSTOMSENDMAIL_AUTOUPDATE_RESERVED_TO_ADMIN')
            ]);
        } else {
            switch ($this->arguments['type']) {
                case 'departments':
                    $listName = _t('CUSTOMSENDMAIL_AUTOUPDATE_OF_DEPARTEMENTS');
                    break;
                case 'areas':
                    $listName = _t('CUSTOMSENDMAIL_AUTOUPDATE_OF_AREAS');
                    break;
                case 'form':
                    $listName = _t('CUSTOMSENDMAIL_AUTOUPDATE_FORM');
                    break;
                
                default:
                    return $this->render("@templates/alert-message.twig",[
                        'type' => 'warning',
                        'message' => 'Parameter `type` should be defined for action `{{customsendmailautoupdatelink}}`!'
                    ]);
            }
            $text = _t('CUSTOMSENDMAIL_AUTOUPDATE_TEXT',[
                'listName' => $listName
            ]);
            $token = $this->wiki->services->get(CsrfTokenManager::class)->getToken("customsendmail\\handler\\update__\\{$this->arguments['type']}");
            return $this->callAction('button',[
                'link' => $this->wiki->Href('update','GererMisesAJour',[
                    'appendCustomSendMailObject' => $this->arguments['type'],
                    'token' => $token
                ],false),
                'text' => $text,
                'title' => $text,
                'class' => 'btn-secondary-2 new-window'
            ]);
        }
    }
}
