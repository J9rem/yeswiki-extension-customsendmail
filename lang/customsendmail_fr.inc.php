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
    'CUSTOMSENDMAIL_PARENTS_TITLES' => 'Sélectionnez une structure',
    'CUSTOMSENDMAIL_AREAS_TITLES' => 'Périmètre géographique',
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
    'AB_BAZARTABLEAU_WITH_EMAIL_LABEL' => 'Tableau avec e-mails',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT' => 'Bonjour,<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;----Complétez votre message ici----<br/>',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULTCONTENT_LABEL' => 'Contenu par défaut',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SENDERNAME_LABEL' => 'Nom d\'expéditeur par défaut',
    'CUSTOMSENDMAIL_SENDMAIL_DEFAULT_SUBJECT_LABEL' => 'Sujet par défaut',
    'CUSTOMSENDMAIL_SENDMAIL_DESCRIPTION' => 'Permet d\'envoyer des e-mails à un groupe de personnes',
    'CUSTOMSENDMAIL_SENDMAIL_EMAILFIELDNAME_LABEL' => 'Champ pour l\'email',
    'CUSTOMSENDMAIL_SENDMAIL_GROUP_IN_HIDDIN_COPY_LABEL' => 'Par défaut, envoyer en copie caché si envoi non groupé',
    'CUSTOMSENDMAIL_SENDMAIL_LABEL' => 'Envoyer des e-mails',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERSPARENT_FORM_LABEL' => 'Formulaire parent',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_BY_AREA' => 'Membres ET profils de la zone géographique',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_DISPLAY_FILTERS_LABEL' => 'Ajouter les structures d\'intérêt et le périmètre géographique aux facettes',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_HINT' => 'Filtre à partir des fiches mères (structures) où je suis administrateur',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_LABEL' => 'Filtrer les fiches',
    'CUSTOMSENDMAIL_SENDMAIL_SELECTMEMBERS_ONLY_MEMBERS' => 'Uniquement les membres',
    'CUSTOMSENDMAIL_SENDMAIL_SENDTOGROUPDEFAULT_LABEL' => 'Par défaut, envoyer à tous',
    'CUSTOMSENDMAIL_SENDMAIL_TITLE_EMPTY_LABEL' => 'Vide = \'%{emptyVal}\'',
    'CUSTOMSENDMAIL_SENDMAIL_TITLE_LABEL' => 'Titre',
    // templates/bazar/send-mail.twig
    'CUSTOMSENDMAIL_ADDCONTACTSTOREPLYTO' => 'Forcer "Répondre à tous" (uniquement pour les envois groupés)',
    'CUSTOMSENDMAIL_ADDSENDERTOCONTACT' => 'Ajouter l\'expéditeur dans les destinataires',
    'CUSTOMSENDMAIL_ADDSENDERTOREPLYTO' => 'Ajouter l\'expéditeur dans "Répondre à"',
    'CUSTOMSENDMAIL_ADMINPART' => 'Visible uniquement par les administrateurices du site',
    'CUSTOMSENDMAIL_CHECKALL' => 'Cocher tout ce qui est visible',
    'CUSTOMSENDMAIL_DEFAULT_TITLE' => 'Envoyer un e-mail à :',
    'CUSTOMSENDMAIL_DONE_FOR' => 'Envoyé pour',
    'CUSTOMSENDMAIL_GROUP_IN_HIDDEN_COPY' => 'Envoyer en copie cachée',
    'CUSTOMSENDMAIL_GROUP_IN_HIDDEN_COPY_HELP' => 'Option uniquement disponible si moins de {nb} fiches sélectionnées',
    'CUSTOMSENDMAIL_HASCONTACTFROM' => "Attention, ce wiki force l'expéditeur des e-mails à %{forcedFrom}\n".
        "(l'e-mail de l'expéditeur est déplacé dans \"Répondre à\")",
    'CUSTOMSENDMAIL_HELP' => "Pour les envois non groupés :\n".
        "[text](lien) => lien href\n".
        "{baseUrl} => lien de base du wiki\n".
        "{entryId} => entryId\n".
        "{entryLink} => lien vers la fiche brut\n".
        "{entryLinkWithTitle} => lien vers la fiche avec son titre\n".
        "{entryLinkWithText} => lien vers la fiche avec le texte \"Voir la fiche xxx\"\n".
        "{entryEditLink} => lien vers la modification de la fiche (brut)\n".
        "{entryEditLinkWithText} => lien vers la modification de la fiche (avec le titre \"Modifier la fiche\")\n",
    'CUSTOMSENDMAIL_HIDE' => 'Masquer les paramètres avancés',
    'CUSTOMSENDMAIL_HIDE_DONE_FOR_ALL' => 'Réduire la liste',
    'CUSTOMSENDMAIL_LAST_UPDATE' => 'Dernière maj : %{date}',
    'CUSTOMSENDMAIL_MESSAGE' => 'Message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT' => 'Sujet du message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT_PLACEHOLDER' => 'Indiquez l\'objet de l\'e-mail',
    'CUSTOMSENDMAIL_PLURAL_NB_DEST_TEXT' => 'Actuellement {nb} destinataires',
    'CUSTOMSENDMAIL_PREVIEW' => 'Aperçu',
    'CUSTOMSENDMAIL_RECEIVEHIDDENCOPY' => 'Recevoir une copie cachée',
    'CUSTOMSENDMAIL_RETURN_PARAM' => 'Retourner aux paramètres',
    'CUSTOMSENDMAIL_SECURITY_HIDDEN' => 'masqué par sécurité',
    'CUSTOMSENDMAIL_SEE' => 'Voir les paramètres avancés',
    'CUSTOMSENDMAIL_SEE_DRAFT' => 'Voir le brouillon',
    'CUSTOMSENDMAIL_SENDEREMAIL' => 'E-mail de l\'expéditeur',
    'CUSTOMSENDMAIL_SENDERNAME' => 'Nom de l\'expéditeur',
    'CUSTOMSENDMAIL_SENDMAIL' => 'Envoyer le(s) mail(s)',
    'CUSTOMSENDMAIL_SENDTOGROUP' => 'Faire un envoi groupé (tout le monde voit la liste de destinataires)',
    'CUSTOMSENDMAIL_SHOW_DONE_FOR_ALL' => 'Montrer toute la liste',
    'CUSTOMSENDMAIL_SINGULAR_NB_DEST_TEXT' => 'Actuellement {nb} destinataire',
    'CUSTOMSENDMAIL_SIZE' => 'Taille :',
    'CUSTOMSENDMAIL_UNCHECKALL' => 'Décocher tout ce qui est visible',
];
