# Extension customsendmail

Cette extension créé un template bazar pour envoyer des e-mails à un groupe et gérer l'affichage de données sensibles pour les administrateurs des structures.

!> **Important**, pour fonctionner cette extension requiert :  
    - l'installation de l'extension [`groupmanagement`](https://github.com/J9rem/yeswiki-extension-groupmanagement#fran%C3%A7ais)  
    - l'installation de l'extension [`comschange`](https://github.com/J9rem/yeswiki-extension-comschange#fran%C3%A7ais)

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