<?php

/*
 * This file is part of the YesWiki Extension customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    // controllers/ApiContoller.php
    'CUSTOMSENDMAIL_CONTACTEMAIL' => 'Destinataire(s)',
    'CUSTOMSENDMAIL_ONEEMAIL' => 'en un seul e-mail groupé',
    'CUSTOMSENDMAIL_ONEBYONE' => 'un envoi d\'email par destinataire',
    'CUSTOMSENDMAIL_REPLYTO' => 'Répondre à',
    'CUSTOMSENDMAIL_HIDDENCOPY' => 'Copie cahée à',

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
    'CUSTOMSENDMAIL_SENDMAIL_KEEPENTRIESWHEREADMINFORPARENT_LABEL' => 'Conserver uniquement les fiches pour lesquelles l\'utilisateur est administrateur des fiches mères.',

    // templates/bazar/send-mail.twig
    'CUSTOMSENDMAIL_DEFAULT_TITLE' => 'Envoyer un e-mail à :',
    'CUSTOMSENDMAIL_LAST_UPDATE' => 'Dernière maj : %{date}',
    'CUSTOMSENDMAIL_MESSAGE' => 'Message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT' => 'Sujet du message',
    'CUSTOMSENDMAIL_MESSAGE_SUBJECT_PLACEHOLDER' => 'Indiquez l\'objet du mail',
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
    'CUSTOMSENDMAIL_HELP' => "Pour les envois non groupés :\n".
        "{entryLink} => lien vers la fiche brut\n".
        "{entryLinkWithTitle} => lien vers la fiche avec son titre\n".
        "{entryLinkWithText} => lien vers la fiche avec le texte \"Voir la fiche xxx\"\n".
        "{entryEditLink} => lien vers la modification de la fiche (brut)\n".
        "{entryEditLinkWithText} => lien vers la modification de la fiche (avec le titre \"Modifier la fiche\")\n",
    'CUSTOMSENDMAIL_HASCONTACTFROM' => "Le paramètre \"contact_from\" est défini dans le fichier \"wakka.config.php\".\n".
        "Il remplacera l'adresse de l'expéditeur.\n".
        "Si aucune case \"répondre à\" n'est cochée ou si l'envoi n'est pas groupé, l'adresse de l'expéditeur sera l'adresse sélectionnée pour \"répondre à\".",
];
