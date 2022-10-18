# Extension customsendmail

This extension creates a bazar template to send email to a group and managing display of private data to administrator of structures.

!> **Warning**, to work, this extension requires :  
    - install of extension [`groupmanagement`](https://github.com/J9rem/yeswiki-extension-groupmanagement#english)  
    - install of extension [`comschange`](https://github.com/J9rem/yeswiki-extension-comschange#english)  
    - install of extension [`zfuture43`](https://github.com/J9rem/yeswiki-extension-zfuture43#english)

## Display private data for administrators

### Configuration

To display private data for administrators, the name of the field corresponding to areas or departements of the strucutres must be type into the configuration file.

  1. in the form dedicated to structures, create a field of type `checkbox`, `liste` or `radio` to allow users to define areas concerning the structure (area or departement by example).
  2. in the same form, create another field of type `checkbox`, `liste` or `radio` only accessible to yeswiki administrators and whiwh allows, after moderation, what are the validated areas for each structure. This field can represent a departement even if the first field accessible by all users can correspond to a different level (example, areas).
  3. Note fieldname only accessible to administrators (example `checkboxListeDepartementsFrancaisbf_departements_valides` or `bf_departements_valides`, the short name is more stable and will work).
  4. go to page `GererConfig` (you can click bellow to go to this page)
  ```yeswiki preview=70px
  {{button link="GererConfig" class="btn-primary new-window" text="Go to page GererConfig" title="Go to page GererConfig"}}
  ```
  5. in part `Custom Sendmail - custom extension`, copy the field name in the parameter `AreaFieldName`


### Usage

To display private data, action `bazarliste` should be configured by the following procedure.

 1. edit a page (handler `/edit`)
 2. Click on button components
 3. Then choose "Display form data"
 4. Choose the form to display and the template
 5. Check box "Advanced Parameters"
 6. then choose in the menu "filter entries", the wanted option between "only members" and "members AND profiles from area"
 7. if option "members AND profiles from area",it is needed to choose the parent form id
 8. Check box "Add parents entries to filters" to add parent entries to filters.