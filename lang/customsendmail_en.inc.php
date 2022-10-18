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
    'EDIT_CONFIG_HINT_GROUPSADMINSSUFFIXFOREMAILS' => 'Admins groups suffix which can send emails',
    'EDIT_CONFIG_HINT_DEFAULT-SENDER-EMAIL' => 'Default email for template "send-mail.tpl.html"',
    'EDIT_CONFIG_HINT_AREAFIELDNAME' => 'Fieldname for validated localization for structures',
    'EDIT_CONFIG_HINT_FORMIDAREATODEPARTMENT' => 'Form id of correspondance between area and department',
    // controllers/ApiContoller.php
    'CUSTOMSENDMAIL_CONTACTEMAIL' => 'Addresses',
    'CUSTOMSENDMAIL_ONEEMAIL' => 'in one group email',
    'CUSTOMSENDMAIL_ONEBYONE' => 'one email by addresse',
    'CUSTOMSENDMAIL_REPLYTO' => 'Reply to',
    'CUSTOMSENDMAIL_HIDDENCOPY' => 'Hidden copy to',
    // docs/actions/bazarliste.yaml via templates/aceditor/actions-builder.tpl.html
    'CUSTOMSENDMAIL_SENDMAIL_DESCRIPTION' => 'Alow to send emails to a group',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SENDERNAME_LABEL' => 'Default sender name',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SUBJECT_LABEL' => 'Default subject',
    'CUSTOMSENDMAIL_SENDMAIL_LABEL' => 'Send emails',
    'CUSTOMSENDMAIL_SENDMAIL_TITLE_LABEL' => 'Title',
    'CUSTOMSENDMAIL_SENDMAIL_TITLE_EMPTY_LABEL' => 'Empty = \'%{emptyVal}\'',
    'CUSTOMSENDMAIL_SENDMAIL_EMAILFIELDNAME_LABEL' => 'Field for email',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT_LABEL' => 'Default content',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT' => 'Hello,<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;----Fill your message here----<br/>',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_LABEL' => 'Filter entries',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_HINT' => 'Filter from parent entry (structures) where I am administrator',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_ONLY_MEMBERS' => 'Only members',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_BY_AREA' => 'Members AND profiles in area',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERSPARENT_FORM_LABEL' => 'Parent form',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_DISPLAY_FILTERS_LABEL' => 'Add parents entries to filters',
    'AB_BAZARTABLEAU_WITH_EMAIL_LABEL' => 'Table with emails',
    // templates/bazar/send-mail.twig
    'CUSTOMSENDMAIL_DEFAULT_TITLE' => 'Send an email to :',
    'CUSTOMSENDMAIL_LAST_UPDATE' => 'Last update : %{date}',
    'CUSTOMSENDMAIL_MESSAGE' => 'Message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT' => 'Object of message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT_PLACEHOLDER' => 'Give object of email',
    'CUSTOMSENDMAIL_SENDEREMAIL' => 'Sender\'s email',
    'CUSTOMSENDMAIL_SENDERNAME' => 'Sender\'s name',
    'CUSTOMSENDMAIL_SENDMAIL' => 'Send email(s)',
    'CUSTOMSENDMAIL_PREVIEW' => 'Preview',
    'CUSTOMSENDMAIL_SIZE' => 'Size :',
    'CUSTOMSENDMAIL_ADDSENDERTOCONTACT' => 'Add sender in addresses',
    'CUSTOMSENDMAIL_ADDSENDERTOREPLYTO' => 'Add sender in "Reply to"',
    'CUSTOMSENDMAIL_ADDCONTACTSTOREPLYTO' => 'Add addresses in "Reply to" (only for group sending)',
    'CUSTOMSENDMAIL_RECEIVEHIDDENCOPY' => 'Receive a hidden copy',
    'CUSTOMSENDMAIL_SENDTOGROUP' => 'Send a group email (everyone sees the complete list of addresses)',
];
