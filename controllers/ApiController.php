<?php

/*
 * This file is part of the YesWiki Extension Customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Customsendmail\Controller;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Bazar\Field\EmailField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;
use YesWiki\Customsendmail\Service\CustomSendMailService;

class ApiController extends YesWikiController
{
    /**
     * @Route("/api/customsendmail/preview", methods={"POST"},options={"acl":{"public","+"}})
     */
    public function previewEmail()
    {
        extract($this->getParams($this->wiki->UserIsAdmin()));
        if ($addsendertocontact) {
            $contacts = (empty($contacts) ? '' : "$contacts,").$senderEmail;
        }
        $contactsLegend = $sendtogroup ? _t('CUSTOMSENDMAIL_ONEEMAIL') : _t('CUSTOMSENDMAIL_ONEBYONE');
        // $message = htmlspecialchars_decode(html_entity_decode($message));
        $message = $this->replaceLinks($message, $sendtogroup, "EntryIdExample");
        $contactFromMail = !empty($this->wiki->config['contact_from']) ? ($this->wiki->UserIsAdmin() ? $this->wiki->config['contact_from'] : 'contact_from'):'';
        $realSenderName = !empty($this->wiki->config['contact_from']) ? $contactFromMail : $senderName;
        $realSenderEmail = !empty($this->wiki->config['contact_from']) ? $contactFromMail : $senderEmail;
        $replyto = (!empty($this->wiki->config['contact_from']) && !$addsendertoreplyto && !($addcontactstoreplyto && $sendtogroup))
            ? $senderEmail
            : (
                (($addsendertoreplyto) ? $senderEmail : "").
                (($addcontactstoreplyto && $sendtogroup) ? (
                    ($addsendertoreplyto ? "," : "").
                    $contacts
                ): "")
            );
        $replyto .= (!empty($this->wiki->config['contact_reply_to']) ? (
            (empty($replyto) ? "" : ",").
            ($this->wiki->UserIsAdmin() ? $this->wiki->config['contact_reply_to'] : 'contact_reply_to')
        ): "");
        $hiddenCopy = $receivehiddencopy ? $senderEmail : "";

        $html = "";
        $html .= "<div><strong>"._t('CUSTOMSENDMAIL_SENDERNAME')."</strong> : $realSenderName</div>";
        $html .= "<div><strong>"._t('CUSTOMSENDMAIL_SENDEREMAIL')."</strong> : $realSenderEmail</div>";
        $html .= "<div><strong>"._t('CUSTOMSENDMAIL_CONTACTEMAIL')."</strong> : $contacts (&lt;$contactsLegend&gt;)</div>";
        $html .= empty($replyto) ? "" : "<div><strong>"._t('CUSTOMSENDMAIL_REPLYTO')."</strong> : $replyto</div>";
        $html .= empty($hiddenCopy) ? "" : "<div><strong>"._t('CUSTOMSENDMAIL_HIDDENCOPY')."</strong> : $hiddenCopy</div>";
        $html .= "<div><strong>"._t('CUSTOMSENDMAIL_MESSAGE_SUBJECT')."</strong> : $subject</div>";
        $html .= "<div><strong>"._t('CUSTOMSENDMAIL_MESSAGE')."</strong> :<br/><hr/>";
        $html .= "$message</div>";
        $size = strlen($html);
        return new ApiResponse(['html' => $html,'size'=>$size]);
    }

    /**
     * @Route("/api/customsendmail/sendmail", methods={"POST"},options={"acl":{"public","+"}})
     */
    public function sendmailApi()
    {
        $isAdmin = $this->wiki->UserIsAdmin();
        $customSendMailService = $this->getService(CustomSendMailService::class);
        if (!$isAdmin) {
            $suffix = $customSendMailService->getAdminSuffix();
            if (empty($suffix)) {
                return new ApiResponse(['error' => '(only for admins)'], Response::HTTP_UNAUTHORIZED);
            }
        }
        $params = $this->getParams($isAdmin);
        
        $filteredContacts = $isAdmin ? $params['contacts']
            : implode(',', array_keys($customSendMailService->filterEntriesFromParents(explode(',', $params['contacts']), false, "only_members")));

        $startLink = preg_quote("<a", '/');
        $endStart = preg_quote(">", '/');
        $endLink = preg_quote("</a>", '/');
        $startP = preg_quote("<p", '/');
        $endP = preg_quote("</p>", '/');
        $link = preg_quote("href=\"", '/')."([^\"]*)".preg_quote("\"", '/');
        $messageTxt = strip_tags($params['message'], '<br><a><p>');
        $messageTxt = preg_replace("/{$startLink}[^>]*{$link}[^>]*{$endStart}([^<]*){$endLink}/", "$2 ($1)", $messageTxt);
        $messageTxt = str_replace(['<br>','<br\>'], "\n", $messageTxt);
        $messageTxt = str_replace('&nbsp;', " ", $messageTxt);
        $messageTxt = preg_replace("/{$startP}[^>]*{$endStart}([^<]*){$endP}/", "$1\n", $messageTxt);
        $messageTxt = html_entity_decode($messageTxt);
        
        $emailfieldname = filter_input(INPUT_POST, 'emailfieldname', FILTER_SANITIZE_STRING);
        $contacts = $this->getContacts($filteredContacts, $emailfieldname, $isAdmin);
        if ($params['addsendertocontact']) {
            $contacts['sender-email'] = $params['senderEmail'];
        }
        $hiddenCopies = $params['receivehiddencopy'] ? [$params['senderEmail']]: [];
        $repliesTo = $params['addsendertoreplyto'] ? [$addsendertoreplyto] : [];
        if ($params['sendtogroup'] && $params['addcontactstoreplyto']) {
            $repliesTo = array_merge($repliesTo, array_values($contacts));
        }
        $doneFor = [];
        $error = false;
        try {
            if ($params['sendtogroup']) {
                if (!empty($contacts) && $this->sendMail($params['senderEmail'], $params['senderName'], $contacts, $repliesTo, $hiddenCopies, $params['subject'], $messageTxt, $params['message'])) {
                    $doneFor = array_merge($doneFor, array_keys($contacts));
                } else {
                    $error = true;
                }
            } else {
                foreach ($contacts as $id => $contact) {
                    $message = $this->replaceLinks($params['message'], false, $id == "sender-email" ? "" : $id);
                    $messageTxtReplaced = $this->replaceLinks($messageTxt, false, $id == "sender-email" ? "" : $id, true);
                    if ($this->sendMail($params['senderEmail'], $params['senderName'], [$contact], $repliesTo, $hiddenCopies, $params['subject'], $messageTxtReplaced, $message)) {
                        $doneFor[] = $id;
                    } else {
                        $error = true;
                    }
                }
            }
        } catch (Throwable $th) {
            if ($isAdmin) {
                return new ApiResponse(['error' => 'message not sent','exceptionMessage' => $th->__toString()], Response::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $error = true;
            }
        }

        if (!$error && !empty($doneFor)) {
            return new ApiResponse(['sent for'=> implode(',', $doneFor)]);
        } elseif ($error && !empty($doneFor)) {
            return new ApiResponse(['error' => 'Part of messages not sent'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            return new ApiResponse(['error' => 'message not sent'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getContacts(string $contactslist, string $emailfieldname, bool $isAdmin): array
    {
        $contactsIds = explode(',', $contactslist);
        $entryManager = $this->getService(EntryManager::class);
        $formManager = $this->getService(FormManager::class);
        $contacts = [];
        $formsCache = [];
        foreach ($contactsIds as $entryId) {
            if ($entryManager->isEntry($entryId)) {
                $entry = $entryManager->getOne($entryId, false, null, false, true);
                if (!empty($entry['id_typeannonce']) && strval($entry['id_typeannonce']) == strval(intval($entry['id_typeannonce']))) {
                    $formId = $entry['id_typeannonce'];
                    if (!isset($formsCache[$formId])) {
                        if (!empty($emailfieldname)) {
                            $field = $formManager->findFieldFromNameOrPropertyName($emailfieldname, $formId);
                            $formsCache[$formId] = [
                                'prepared' => empty($field) ? [] :[
                                    $field
                                ]
                            ];
                        } else {
                            $formsCache[$formId] = $formManager->getOne($formId);
                        }
                        if (!empty($formsCache[$formId]['prepared'])) {
                            foreach ($formsCache[$formId]['prepared'] as $field) {
                                if ($field instanceof EmailField) {
                                    $formsCache[$formId]['email field'] = $field;
                                    break;
                                }
                            }
                        }
                    }
                    if (!empty($formsCache[$formId]['email field']) &&
                        !empty($entry[($formsCache[$formId]['email field'])->getPropertyName()])) {
                        $email = $entry[($formsCache[$formId]['email field'])->getPropertyName()];
                        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
                        if (!empty(trim($email))) {
                            $contacts[$entryId] = $email;
                        }
                    }
                }
            }
        }

        return $contacts;
    }


    private function sendMail(
        string $mail_sender,
        string $name_sender,
        array $contacts,
        array $repliesTo,
        array $hiddenCopies,
        string $subject,
        string $message_txt,
        string $message_html
    ):bool {
        if (empty($contacts)) {
            return false;
        }
        //Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        try {
            $mail->set('CharSet', 'utf-8');
    
            if ($this->wiki->config['contact_mail_func'] == 'smtp') {
                //Tell PHPMailer to use SMTP
                $mail->isSMTP();
                //Enable SMTP debugging
                // 0 = off (for production use)
                // 1 = client messages
                // 2 = client and server messages
                $mail->SMTPDebug = $this->wiki->config['contact_debug'];
                //Ask for HTML-friendly debug output can be a function($str, $level)
                $mail->Debugoutput = 'html';
                //Set the hostname of the mail server
                $mail->Host = $this->wiki->config['contact_smtp_host'];
                //Set the SMTP port number - likely to be 25, 465 or 587
                $mail->Port = $this->wiki->config['contact_smtp_port'];
                //Whether to use SMTP authentication
                if (!empty($this->wiki->config['contact_smtp_user'])) {
                    $mail->SMTPAuth = true;
                    //Username to use for SMTP authentication
                    $mail->Username = $this->wiki->config['contact_smtp_user'];
                    //Password to use for SMTP authentication
                    $mail->Password = $this->wiki->config['contact_smtp_pass'];
                } else {
                    $mail->SMTPAuth = false;
                }
            } elseif ($this->wiki->config['contact_mail_func'] == 'sendmail') {
                // Set PHPMailer to use the sendmail transport
                $mail->isSendmail();
            }
    
            //Set an alternative reply-to address
            if (!empty($this->wiki->config['contact_reply_to'])) {
                $mail->addReplyTo($this->wiki->config['contact_reply_to']);
            }
            if (count($repliesTo) > 1) {
                foreach ($repliesTo as $contact) {
                    $mail->addReplyTo($contact, $contact);
                }
            }
            // Set always the same 'from' address (to avoid spam, it's a good practice to set the from field with an address from
            // the same domain than the sending mail server)
            if (!empty($this->wiki->config['contact_from'])) {
                if (empty($repliesTo)) {
                    $mail->addReplyTo($mail_sender, $name_sender);
                }
                $mail_sender = $this->wiki->config['contact_from'];
            }
            //Set who the message is to be sent from
            if (empty($name_sender)) {
                $name_sender = $mail_sender;
            }
            $mail->setFrom($mail_sender, $name_sender);
    
            //Set who the message is to be sent to
            foreach ($contacts as $key => $mail_receiver) {
                $mail->addAddress($mail_receiver, $mail_receiver);
            }

            foreach ($hiddenCopies as $contact) {
                $mail->addBCC($contact, $contact);
            }
            //Set the subject line
            $mail->Subject = $subject;
    
            // That's bad if only text passed to function: Linebreaks won't be rendered.
            //if (empty($message_html)) {
            //  $message_html = $message_txt;
            //}
    
            if (empty($message_html)) {
                $mail->isHTML(false);
                $mail->Body = $message_txt ;
            } else {
                $mail->isHTML(true);
                $mail->Body = $message_html ;
                if (!empty($message_txt)) {
                    $mail->AltBody = $message_txt;
                }
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            if ($this->wiki->UserIsAdmin()) {
                throw $e;
            }
            return false;
        }
    }

    private function getParams(bool $isAdmin): array
    {
        $message = (isset($_POST['message']) && is_string($_POST['message'])) ? $_POST['message'] : '';
        $senderName = filter_input(INPUT_POST, 'senderName', FILTER_SANITIZE_STRING);
        $senderEmail = filter_input(INPUT_POST, 'senderEmail', FILTER_VALIDATE_EMAIL);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $contacts = filter_input(INPUT_POST, 'contacts', FILTER_SANITIZE_STRING);
        $addsendertocontact = filter_input(INPUT_POST, 'addsendertocontact', FILTER_VALIDATE_BOOL);
        $sendtogroup = $isAdmin && filter_input(INPUT_POST, 'sendtogroup', FILTER_VALIDATE_BOOL);
        $addsendertoreplyto = filter_input(INPUT_POST, 'addsendertoreplyto', FILTER_VALIDATE_BOOL);
        $addcontactstoreplyto = $isAdmin && filter_input(INPUT_POST, 'addcontactstoreplyto', FILTER_VALIDATE_BOOL);
        $receivehiddencopy = filter_input(INPUT_POST, 'receivehiddencopy', FILTER_VALIDATE_BOOL);
        return compact(['message','senderName','senderEmail','subject','contacts','addsendertocontact','sendtogroup','addsendertoreplyto','addcontactstoreplyto','receivehiddencopy']);
    }

    private function replaceLinks(string $message, bool $sendtogroup, string $entryId, bool $modeTxt = false):string
    {
        $output = $message;
        $entryManager = $this->getService(EntryManager::class);
        if (!$sendtogroup) {
            if ($entryManager->isEntry($entryId)) {
                $entry = $entryManager->getOne($entryId);
                $title = $entry['bf_titre'] ?? $entryId;
            } else {
                $title = $entryId;
            }
            $link = $this->wiki->Href('', $entryId);
            $editLink = $this->wiki->Href('edit', $entryId);
            $output = str_replace(
                ['{entryLink}','{entryLinkWithTitle}','{entryEditLink}','{entryEditLinkWithText}','{entryLinkWithText}'],
                ($modeTxt)
                ? [$link,"$title ($link)",$editLink, _t('BAZ_MODIFIER_LA_FICHE'). " \"$title\" ($editLink)",_t('BAZ_SEE_ENTRY'). " \"$title\" ($link)"]
                :["<a href=\"$link\" title=\"$title\" target=\"blank\">$link</a>","<a href=\"$link\" target=\"blank\">$title</a>","<a href=\"$editLink\" target=\"blank\">$editLink</a>","<a href=\"$editLink\" target=\"blank\">"._t('BAZ_MODIFIER_LA_FICHE'). " \"$title\"</a>","<a href=\"$link\" target=\"blank\">"._t('BAZ_SEE_ENTRY'). " \"$title\"</a>"],
                $output
            );
        }
        return $output;
    }

    /**
     * Display Bazar api documentation
     *
     * @return string
     */
    public function getDocumentation()
    {
        $output = "";
        if ($this->wiki->UserIsAdmin()) {
            $output .= '<h2>Custom Sendmail</h2>' . "\n";
    
            $output .= '
            <p>
            <b><code>POST ' . $this->wiki->href('', 'api/customsendmail/preview') . '</code></b><br />
            Permet de générer une prévisualisation de l\'e-mail
            </p>';

            $output .= '
            <p>
            <b><code>POST ' . $this->wiki->href('', 'api/customsendmail/sendmail') . '</code></b><br />
            Permet l\'envoi d\'e-mails
            </p>';
        }

        return $output;
    }
}
