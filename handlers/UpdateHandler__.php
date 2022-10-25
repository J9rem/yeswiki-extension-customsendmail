<?php

/*
 * This file is part of the YesWiki Extension customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Customsendmail;

use Configuration;
use Exception;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use YesWiki\Core\Controller\CsrfTokenController;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Customsendmail\Service\CustomSendMailService;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    protected $csrfTokenController;
    protected $customSendMailService;
    protected $securityController;

    public function run()
    {
        $this->csrfTokenController = $this->getService(CsrfTokenController::class);
        $this->customSendMailService = $this->getService(CustomSendMailService::class);
        $this->securityController = $this->getService(SecurityController::class);
        if ($this->securityController->isWikiHibernated()) {
            throw new Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }

        // add List if not existing
        if (!empty($_GET['appendCustomSendMailObject']) &&
            is_string($_GET['appendCustomSendMailObject'])) {
            $output = '<strong>Extension customsendmail</strong><br/>';
            $output .= $this->addListIfNotExisting($_GET['appendCustomSendMailObject']);
            $output .= '<hr/>';

            // set output
            $this->output = str_replace(
                '<!-- end handler /update -->',
                $output.'<!-- end handler /update -->',
                $this->output
            );
        }
        return null;
    }

    private function addListIfNotExisting(string $appendCustomSendMailObject): string
    {
        try {
            $this->csrfTokenController->checkToken("customsendmail\\handler\\update__\\$appendCustomSendMailObject", 'GET', 'token');
        } catch (TokenNotFoundException $th) {
            $output = "&#10060; not possible to update an object : '{$th->getMessage()}' !<br/>";
            return $output;
        }
        switch ($appendCustomSendMailObject) {
            case 'departments':
                $output = "ℹ️ Updating list of departments... ";
                extract($this->customSendMailService->createDepartements());
                if (!$success) {
                    $output .= "&#10060; $error<br/>";
                    return $output;
                }
                break;
            case 'areas':
                $output = "ℹ️ Updating list of areas... ";
                extract($this->customSendMailService->createAreas());
                if (!$success) {
                    $output .= "&#10060; $error<br/>";
                    return $output;
                }
                break;
            case 'form':
                $output = "ℹ️ Updating form associating areas and departments... ";

                extract($this->customSendMailService->createFormToAssociateAreasAndDepartments());
                if (!$success) {
                    $output .= "&#10060; $error<br/>";
                    return $output;
                }
                break;
            default:
                $output = "&#10060; not possible to update an object : type '$appendCustomSendMailObject' is unknown !<br/>";
                return $output;
        }

        $output .= '✅ Done !<br />';

        return $output;
    }
}
