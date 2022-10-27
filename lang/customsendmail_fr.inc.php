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
    // actions/__BazarListeAction.php
    'CUSTOMSENDMAIL_PARENTS_TITLES' => 'Structures d\'intérêt',
    'CUSTOMSENDMAIL_AREAS_TITLES' => 'Zones d\'intérêt',
    // actions/CustomSendmailAutoupdateLinkAction.php
    'CUSTOMSENDMAIL_AUTOUPDATE_RESERVED_TO_ADMIN' => 'Lien de mise à jour uniquement accessible aux administrateurs connectés',
    'CUSTOMSENDMAIL_AUTOUPDATE_TEXT' => 'Créer automatique la liste %{listName}',
    'CUSTOMSENDMAIL_AUTOUPDATE_OF_DEPARTEMENTS' => 'des départements français',
    'CUSTOMSENDMAIL_AUTOUPDATE_OF_AREAS' => 'des régions françaises',
    'CUSTOMSENDMAIL_AUTOUPDATE_FORM' => ', le formulaire et les fiches associant régions et départements français',
    // config.yaml
    'EDIT_CONFIG_GROUP_CUSTOMSENDMAIL' => 'Custom Sendmail - extension personnalisée',
    'EDIT_CONFIG_HINT_GROUPSADMINSSUFFIXFOREMAILS' => 'Suffix des groupes admins qui peuvent envoyer des e-mails',
    'EDIT_CONFIG_HINT_DEFAULT-SENDER-EMAIL' => 'E-mail par défaut pour le template "send-mail.tpl.html"',
    'EDIT_CONFIG_HINT_AREAFIELDNAME' => 'Nom du champ avec la localisation validée pour les structures',
    'EDIT_CONFIG_HINT_POSTALCODEFIELDNAME' => 'Nom du champ avec le code postal',
    'EDIT_CONFIG_HINT_FORMIDAREATODEPARTMENT' => 'Numero du formulaire de correspondance entre région et département',
    // controllers/ApiContoller.php
    'CUSTOMSENDMAIL_CONTACTEMAIL' => 'Destinataire(s)',
    'CUSTOMSENDMAIL_ONEEMAIL' => 'en un seul e-mail groupé',
    'CUSTOMSENDMAIL_ONEBYONE' => 'un envoi d\'email par destinataire',
    'CUSTOMSENDMAIL_REPLYTO' => 'Répondre à',
    'CUSTOMSENDMAIL_HIDDENCOPY' => 'Copie cachée à',
    // docs/actions/bazarliste.yaml via templates/aceditor/actions-builder.tpl.html
    'CUSTOMSENDMAIL_SENDMAIL_DESCRIPTION' => 'Permet d\'envoyer des e-mails à un groupe de personnes',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SENDERNAME_LABEL' => 'Nom d\'expéditeur par défaut',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SUBJECT_LABEL' => 'Sujet par défaut',
    'CUSTOMSENDMAIL_SENDMAIL_LABEL' => 'Envoyer des e-mails',
    'CUSTOMSENDMAIL_SENDMAIL_TITLE_LABEL' => 'Titre',
    'CUSTOMSENDMAIL_SENDMAIL_TITLE_EMPTY_LABEL' => 'Vide = \'%{emptyVal}\'',
    'CUSTOMSENDMAIL_SENDMAIL_EMAILFIELDNAME_LABEL' => 'Champ pour l\'email',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT_LABEL' => 'Contenu par défaut',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT' => 'Bonjour,<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;----Complétez votre message ici----<br/>',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_LABEL' => 'Filtrer les fiches',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_HINT' => 'Filtre à partir des fiches mères (structures) où je suis administrateur',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_ONLY_MEMBERS' => 'Uniquement les membres',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_BY_AREA' => 'Membres ET profiles de la zone géographique',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERSPARENT_FORM_LABEL' => 'Formulaire parent',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_DISPLAY_FILTERS_LABEL' => 'Ajouter les fiches mères aux filtres',
    'AB_BAZARTABLEAU_WITH_EMAIL_LABEL' => 'Tableau avec e-mails',
    // templates/bazar/send-mail.twig
    'CUSTOMSENDMAIL_DEFAULT_TITLE' => 'Envoyer un e-mail à :',
    'CUSTOMSENDMAIL_LAST_UPDATE' => 'Dernière maj : %{date}',
    'CUSTOMSENDMAIL_MESSAGE' => 'Message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT' => 'Sujet du message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT_PLACEHOLDER' => 'Indiquez l\'objet de l\'e-mail',
    'CUSTOMSENDMAIL_SENDEREMAIL' => 'E-mail de l\'expéditeur',
    'CUSTOMSENDMAIL_SENDERNAME' => 'Nom de l\'expéditeur',
    'CUSTOMSENDMAIL_SENDMAIL' => 'Envoyer le(s) mail(s)',
    'CUSTOMSENDMAIL_PREVIEW' => 'Aperçu',
    'CUSTOMSENDMAIL_SIZE' => 'Taille :',
    'CUSTOMSENDMAIL_ADDSENDERTOCONTACT' => 'Ajouter l\'expéditeur dans les destinataires',
    'CUSTOMSENDMAIL_ADDSENDERTOREPLYTO' => 'Ajouter l\'expéditeur dans "Répondre à"',
    'CUSTOMSENDMAIL_ADDCONTACTSTOREPLYTO' => 'Ajouter les destinataires dans "Répondre à" (uniquement pour les envois groupés)',
    'CUSTOMSENDMAIL_RECEIVEHIDDENCOPY' => 'Recevoir une copie cachée',
    'CUSTOMSENDMAIL_SENDTOGROUP' => 'Faire un envoi groupé (tout le monde voit la liste de destinataires)',
    'CUSTOMSENDMAIL_HASCONTACTFROM' => "Attention, ce wiki force l'expéditeur des e-mails à %{forcedFrom}\n".
        "(l'e-mail de l'expéditeur est déplacé dans \"Répondre à\")",
    'CUSTOMSENDMAIL_SECURITY_HIDDEN' => 'masqué par sécurité',
    'CUSTOMSENDMAIL_SEE' => 'Voir les paramètres avancés',
    'CUSTOMSENDMAIL_HIDE' => 'Masquer les paramètres avancés',
];
