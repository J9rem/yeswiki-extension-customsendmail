# Extension customsendmail

Cette extension créé un template bazar pour envoyer des e-mails à un groupe et gérer l'affichage de données sensibles pour les administrateurs des structures.

!> **Important**, pour fonctionner cette extension requiert :  
    - l'installation de l'extension [`groupmanagement`](https://github.com/J9rem/yeswiki-extension-groupmanagement#fran%C3%A7ais)  
    - l'installation de l'extension [`comschange`](https://github.com/J9rem/yeswiki-extension-comschange#fran%C3%A7ais)  
    - l'installation de l'extension [`zfuture43`](https://github.com/J9rem/yeswiki-extension-zfuture43#fran%C3%A7ais)

## Affichage des données sensibles

### Configuration

Pour afficher les données sensibles, il est nécessaire de fournir au ficher de configuration du wiki, le nom du champ qui correspond aux régions ou aux départements correspondant à la structure. IL est conseillé que ce champ soit modéré par les administrateurs.

  1. dans le formulaire correspondant aux structures, créer un champ de type `checkbox`, `liste` ou `radio` qui permet aux usagers de fournir une indication de localisation (région ou département par exemple).
  2. dans ce même formulaire, créer un autre champ de type `checkbox`, `liste` ou `radio` uniquement accessibles pour les administrateurs et qui permet de définir, après modération, quelles sont les zones géographiques validées pour cette structure. Ce champ peut représenter les départements alors que le premier champ accessible à tous les usagers peut représenter un niveau différent (exemple, les régions).
  3. Noter le nom du champ uniquement accessible aux administrateurs (exemple `checkboxListeDepartementsFrancaisbf_departements_valides` ou `bf_departements_valides`, le nom court est plus stable et fonctionnera très bien).
  4. se rendre sur la page `GererConfig` de votre wiki (vous pouvez cliquer ci-dessous pour vous y rendre)
  ```yeswiki preview=70px
  {{button link="GererConfig" class="btn-primary new-window" text="Se rendre sur la page GererConfig" title="Se rendre sur la page GererConfig"}}
  ```
  5. dans la partie `Custom Sendmail - extension personnalisée`, recopier le nom du champ pour le paramètre `AreaFieldName`

### Fonctionnement

Pour afficher les données sensibles, il faut configurer l'action `bazarliste` en suivant cette procédure.

 1. modifier une page (handler `/edit`)
 2. Appuyer sur le bouton composant  
    ![image du bouton composant](images/bouton_composant.png ':size=300')
 3. Choisir ensuite "Afficher les données d'un formulaire"  
    ![menu de choix des composanrs](images/display_data_in_form.png ':size=300')
 4. Choisir le formulaire à afficher et le format des données (`template`)
 5. Cocher la case "Paramètres Avancés"  
    ![case à cocher paramètres avancés](images/parametres_avancees.png ':size=300')
 6. puis choisir dans le menu "filtrer les fiches", l'option désirée entre "uniquement les membres" et "membres ET profiles de la zone géographique"  
    ![menu filtrage des fiches](images/filter_menu.png ':size=300')
 7. si l'option "membres ET profiles de la zone géographique", il faudra choisir le formulaire parent associé  
    ![copie d'écran choix du formulaire](images/choix_formulaire.png ':size=300')
 8. Cocher la case "Ajouter les fiches mères aux filtres" pour ajouter lee choix des fiches structures mères aux facettes.

### Critère d'affichage pour "membres ET profiles de la zone géographique"

||Structure|Structure|Acteur|Acteur|Acteur|Acteur|Est affiché ?|
|:-|:-|:-|:-|:-|:-|:-|:-|
|_Nom_|**Région**|**Départements validés**|**Région**|**Département**|**Code postal**|**Est membre ?**||
|_Nom du champ_|`bf_region`|`checkboxListeDepartementsFrancais`|`bf_region_adhesion`|`bf_departement_adhesion`|`bf_codepostal`|`bf_structure_locale_adhesion`|
|_Liste associée_|`ListeRegionsFrancaises`|`ListeDepartementsFrancais`|`ListeRegionsFrancaises`|`ListeDepartementsFrancais`|---|formulaire structure|
||peu importe|peu importe|peu importe|peu importe|peu importe|**oui**|oui|
||peu importe|**vide**|peu importe|peu importe|peu importe|non|non|
||peu importe|**Morbihan,Finistère**|Bretagne|vide|peu importe|non|oui (*)|
||peu importe|**Morbihan,Finistère**|PACA|peu importe|vide|non|non|
||peu importe|**Morbihan,Finistère**|vide|vide|vide|non|non|
||peu importe|**Morbihan,Finistère**|peu importe|Finistère|peu importe|non|oui|
||peu importe|**Morbihan,Finistère**|Bretagne|Ille-et-Vilaine|vide|non|non|
||peu importe|**Morbihan,Finistère**|PACA|peu importe|**56000**|non|oui|
||peu importe|**Morbihan,Finistère**|PACA|peu importe|**13000**|non|non|
||peu importe|**Morbihan,Finistère**|vide|vide|**56000**|non|oui|
||peu importe|**Morbihan,Finistère**|vide|vide|**13000**|non|non|
||peu importe|**Morbihan,Finistère**|Bretagne|Ille-et-Vilaine|**56000**|non|oui|
||peu importe|**Morbihan,Finistère**|Bretagne|Ille-et-Vilaine|**13000**|non|non|

(*): ne fonctionne que si un formulaire d'association de régions et de départements a été créé

**important** : la détection automatique de département à partir du code postal ne fonctionne que si la liste des départements possède le bon numéro de département comme clé.

#### Création du formulaire d'association entre régions et départements

 1. créer un formulaire avec ce code
    ```
    titre***Départements de {{bf_region}}***Titre Automatique***
    liste***ListeRegionsFrancaises***Région*** *** *** ***bf_region*** ***1*** *** *** * *** * *** *** *** ***
    checkbox***ListeDepartementsFrancais***Départements*** *** *** ***bf_departement*** ***1*** *** *** * *** * *** *** *** ***
    acls*** * ***@admins***comments-closed***
    ```
 2. enregistrer puis revenir modifier le formulaire avec le constructeur graphique pour sélectionner les bonnes listes
 3. Puis créer une fiche région pour chaque région française de la liste des régions.
 4. se rendre sur la page `GererConfig` de votre wiki (vous pouvez cliquer ci-dessous pour vous y rendre)
 ```yeswiki preview=70px
 {{button link="GererConfig" class="btn-primary new-window" text="Se rendre sur la page GererConfig" title="Se rendre sur la page GererConfig"}}
 ```
 5. dans la partie `Custom Sendmail - extension personnalisée`, mettre le numéro du formulaire en question pour le paramètre `formIdAreaToDepartment`