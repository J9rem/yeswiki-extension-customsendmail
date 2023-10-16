<?php

return [
    /*
 * This file is part of the YesWiki Extension customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
    // config.yaml
    'EDIT_CONFIG_GROUP_CUSTOMSENDMAIL' => 'Custom Sendmail - custom extension',
    'EDIT_CONFIG_HINT_DEFAULT-SENDER-EMAIL' => 'Default email for template "send-mail.tpl.html"',
    // controllers/ApiContoller.php
    'CUSTOMSENDMAIL_CONTACTEMAIL' => 'Addresses',
    'CUSTOMSENDMAIL_ONEEMAIL' => 'in one group email',
    'CUSTOMSENDMAIL_ONEBYONE' => 'one email by addresse',
    'CUSTOMSENDMAIL_REPLYTO' => 'Reply to',
    'CUSTOMSENDMAIL_HIDDENCOPY' => 'Hidden copy to',
    // docs/actions/bazarliste.yaml via templates/aceditor/actions-builder.tpl.html
    'AB_BAZARTABLEAU_WITH_EMAIL_LABEL' => 'Table with emails',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT' => 'Hello,<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;----Fill your message here----<br/>',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT_LABEL' => 'Default content',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SENDERNAME_LABEL' => 'Default sender name',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SUBJECT_LABEL' => 'Default subject',
    'CUSTOMSENDMAIL_SENDMAIL_DESCRIPTION' => 'Alow to send emails to a group',
    'CUSTOMSENDMAIL_SENDMAIL_EMAILFIELDNAME_LABEL' => 'Field for email',
    'CUSTOMSENDMAIL_SENDMAIL_GROUP_IN_HIDDIN_COPY_LABEL' => 'By default, send in hidden copy if not sending group',
    'CUSTOMSENDMAIL_SENDMAIL_LABEL' => 'Send emails',
    'CUSTOMSENDMAIL_SENDMAIL_SENDTOGROUPDEFAULT_LABEL' => 'By default, send to all',
    'CUSTOMSENDMAIL_SENDMAIL_TITLE_EMPTY_LABEL' => 'Empty = \'%{emptyVal}\'',
    'CUSTOMSENDMAIL_SENDMAIL_TITLE_LABEL' => 'Title',
    // templates/bazar/send-mail.twig
    'CUSTOMSENDMAIL_ADDCONTACTSTOREPLYTO' => 'Force "Reply to all" (only for group sending)',
    'CUSTOMSENDMAIL_ADDSENDERTOCONTACT' => 'Add sender in addresses',
    'CUSTOMSENDMAIL_ADDSENDERTOREPLYTO' => 'Add sender in "Reply to"',
    'CUSTOMSENDMAIL_ADMINPART' => 'Only visible by admins',
    'CUSTOMSENDMAIL_CHECKALL' => 'Check all visible',
    'CUSTOMSENDMAIL_DEFAULT_TITLE' => 'Send an email to :',
    'CUSTOMSENDMAIL_DONE_FOR' => 'Done for',
    'CUSTOMSENDMAIL_GROUP_IN_HIDDEN_COPY' => 'Send in hidden copy',
    'CUSTOMSENDMAIL_GROUP_IN_HIDDEN_COPY_HELP' => 'Option only available if less than {nb} selected entries',
    'CUSTOMSENDMAIL_HASCONTACTFROM' => "Warning this wiki forces sender to %{forcedFrom}\n".
        "(sender's email is moved to \"Reply to\")",
    'CUSTOMSENDMAIL_HELP' => "For group sending:\n".
        "[text](lien) => href link\n".
        "{baseUrl} => wiki's baseUrl\n".
        "{entryId} => entryId\n".
        "{entryLink} => raw link to entry\n".
        "{entryLinkWithTitle} => link to entry with title\n".
        "{entryLinkWithText} => link to the entry with text \"See entry xxx\"\n".
        "{entryEditLink} => raw link to edit entry\n".
        "{entryEditLinkWithText} => link to edit entry (with title \"Edit entry\")\n",
    'CUSTOMSENDMAIL_HIDE' => 'Hide advanced parameters',
    'CUSTOMSENDMAIL_HIDE_DONE_FOR_ALL' => 'Reduce list',
    'CUSTOMSENDMAIL_LAST_UPDATE' => 'Last update : %{date}',
    'CUSTOMSENDMAIL_MESSAGE' => 'Message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT' => 'Object of message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT_PLACEHOLDER' => 'Give object of email',
    'CUSTOMSENDMAIL_PLURAL_NB_DEST_TEXT' => 'Currently {nb} addresses',
    'CUSTOMSENDMAIL_PREVIEW' => 'Preview',
    'CUSTOMSENDMAIL_RECEIVEHIDDENCOPY' => 'Receive a hidden copy',
    'CUSTOMSENDMAIL_RETURN_PARAM' => 'Return to parameters',
    'CUSTOMSENDMAIL_SECURITY_HIDDEN' => 'hidden by security',
    'CUSTOMSENDMAIL_SEE' => 'See advanced parameters',
    'CUSTOMSENDMAIL_SEE_DRAFT' => 'See draft',
    'CUSTOMSENDMAIL_SENDEREMAIL' => 'Sender\'s email',
    'CUSTOMSENDMAIL_SENDERNAME' => 'Sender\'s name',
    'CUSTOMSENDMAIL_SENDMAIL' => 'Send email(s)',
    'CUSTOMSENDMAIL_SENDTOGROUP' => 'Send a group email (everyone sees the complete list of addresses)',
    'CUSTOMSENDMAIL_SHOW_DONE_FOR_ALL' => 'Show the whole list',
    'CUSTOMSENDMAIL_SINGULAR_NB_DEST_TEXT' => 'Currently {nb} addresse',
    'CUSTOMSENDMAIL_SIZE' => 'Size :',
    'CUSTOMSENDMAIL_UNCHECKALL' => 'Uncheck all visible',
];
