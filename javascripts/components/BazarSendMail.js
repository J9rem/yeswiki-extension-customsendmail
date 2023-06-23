/*
 * This file is part of the YesWiki Extension customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import SpinnerLoader from '../../../bazar/presentation/javascripts/components/SpinnerLoader.js'
import NbDest from './nb-dest.js'
import ProgressBar from './progress-bar.js'

let componentName = 'BazarSendMail';
let isVueJS3 = (typeof Vue.createApp == "function");

let componentParams = {
    props: ['params','entries','hascontactfrom','ready','root','isadmin'],
    components: { SpinnerLoader,NbDest,ProgressBar},
    data: function() {
        return {
            addContactsToReplyTo: false,
            addSenderToContact: true,
            addSenderToReplyTo: !this.hascontactfrom,
            advancedParamsVisibles: false,
            availableEntries: [],
            bsEventInit: false,
            cacheEntriesDisplay: {},
            checkAll: false,
            doneFor: null,
            emailfieldname: "bf_mail",
            forcedNotGroupinhiddencopy: false,
            htmlPreview: "",
            groupinhiddencopy: true,
            limitForForce: 100, // parameter than force sending separated e-mails
            nextContentForPreview: [],
            nextPreviewTobeRetrieved: false,
            receiveHiddenCopy: false,
            secureUpdatePreviewDebounce: 0,
            selectedAddresses: [],
            sendToGroup: true,
            senderEmail: "",
            senderName: "",
            sendingMail: false,
            showDoneForAll: false,
            sizePreview: "",
            subject: "",
            summernoteInit: false,
            uid: "",
            updatingIds: [],
            updatingPreview: false
        };
    },
    methods: {
        arraysEqual(a, b) {
          if (a === b) return true;
          if (a == null || b == null) return false;
          if (a.length !== b.length) return false;
    
          a.sort(); b.sort()
          for (var i = 0; i < a.length; ++i) {
            if (a[i] !== b[i]) return false;
          }
          return true;
        },
        canShow(entries){
            if (typeof entries == undefined){
                entries = this.availableEntries
            }
            this.$nextTick(()=>this.initBsEvents());
            return Object.keys(entries).length > 0;
        },
        async fetch(url,mode = 'get',dataToSend = {}){
            let func = (url,mode,dataToSend)=>{
                return (mode == 'get')
                    ? fetch(url)
                    : this.post(url,dataToSend)
            }
            return await func(url,mode,dataToSend)
                .then(async (response)=>{
                    let json = null
                    try {
                        json = await response.json()
                    } catch (error) {
                        throw {
                            'errorMsg': error+''
                        }
                    }
                    if (response.ok && typeof json == 'object'){
                        return json
                    } else {
                        throw {
                            'errorMsg': (typeof json == "object" && 'error' in json) ? json.error : ''
                        }
                    }
                })

        },
        finishPreviewUpdate(){
            if (this.nextPreviewTobeRetrieved){
                if (this.nextContentForPreview.length == 0){
                  this.updatingPreview = false;
                  this.nextPreviewTobeRetrieved = false;
                } else {
                  let contents = this.nextContentForPreview.pop();
                  this.nextContentForPreview = [];
                  this.updatePreview(contents,{forced:true});
                }
            } else {
                this.updatingPreview = false;
                this.nextContentForPreview = [];
            }
        },
        fromSlot(name){
            if (typeof this.$scopedSlots[name] == "function"){
                let slot = (this.$scopedSlots[name])();
                if (typeof slot == "object"){
                    return slot[0].text;
                }
            }
            return "";
        },
        getContacts(){
            let availableIds = this.availableEntries.map((entry)=>entry.id_fiche)
            return this.selectedAddresses.filter((id)=>availableIds.includes(id))
        },
        getContentsForUpdate(){
            let textearea = $(this.$el).find(`textarea.form-control.summernote[name=message]`)
            if (textearea == undefined || textearea.length == 0){
              return "Error summernote not found !"
            } else {
              return $(textearea).summernote('code')
            }
        },
        getData (){
            return {
                addcontactstoreplyto: this.addContactsToReplyTo,
                addsendertocontact: this.addSenderToContact,
                addsendertoreplyto: this.addSenderToReplyTo,
                contacts: this.getContacts(),
                emailfieldname: this.emailfieldname,
                groupinhiddencopy: this.groupinhiddencopy && !this.forcedNotGroupinhiddencopy,
                receivehiddencopy: this.receiveHiddenCopy,
                selectmembers: this.params.selectmembers || '',
                selectmembersparentform: this.params.selectmembersparentform || '',
                senderEmail: this.senderEmail,
                senderName: this.senderName,
                sendtogroup: this.sendToGroup,
                subject: this.subject
            }
        },
        getUID(){
            return ( (( 1+Math.random()) * 0x10000 ) | 0 ).toString( 16 ).substring( 1 );
        },
        initBsEvents(){
            let advancedParamsContainer = $(this.$refs.advancedParams);
            if(!this.bsEventInit && advancedParamsContainer != undefined && advancedParamsContainer.length > 0){
                this.bsEventInit = true;
                advancedParamsContainer.on('show.bs.collapse',()=>{this.advancedParamsVisibles = true});
                advancedParamsContainer.on('hide.bs.collapse',()=>{this.advancedParamsVisibles = false});
            }
        },
        isChecked(entry){
            return this.selectedAddresses.includes(entry.id_fiche);
        },
        loadSummernote (langOptions){
            if (this.summernoteInit){
                return;
            }
            this.summernoteInit = true;
            $(".summernote").summernote({...langOptions,...{
              height: 500,    // set editor height
              minHeight: 100, // set minimum height of editor
              maxHeight: 500,                // set maximum height of editor
              focus: false,                   // set focus to editable area after initializing summernote
              toolbar: [
                  //[groupname, [button list]]
                  //['style', ['style', 'well']],
                  ['style', ['style']],
                  ['textstyle', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                  ['color', ['color']],
                  ['para', ['ul', 'ol', 'paragraph']],
                  ['insert', ['hr', 'link', 'table']], // 'picture', 'video' removed because of the storage in the field
                  ['misc', ['codeview']]
              ],
              isNotSplitEdgePoint : true,
              styleTags: ['h3', 'h4', 'h5', 'h6', 'p', 'blockquote', 'pre'],
              oninit: function() {
                //$('button[data-original-title=Style]').prepend("Style").find("i").remove();
              },
              callbacks: {
                  onPaste: function (e) {
                      var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                      e.preventDefault();
                      document.execCommand('insertText', false, bufferText);
                  },
                  onChange: (contents, $editable)=>{
                    this.updatePreview(contents, {editable:$editable});
                  }
              }
            }});
        },
        loadSummernoteWithLang (){
            if (this.summernoteInit){
                return;
            }
            if (wiki.locale == "en"){
                this.loadSummernote({});
            } else {
                let langName = wiki.locale.toLowerCase() + '-' + wiki.locale.toUpperCase();
                let scriptUrl = wiki.baseUrl.replace(/\?$/,"");
                scriptUrl = scriptUrl + `tools/bazar/libs/vendor/summernote/lang/summernote-${langName}.js`;
                // load script
                $("body").append($('<script>').attr("src",scriptUrl));
                this.loadSummernote({lang:langName});
            }
        },
        async post(url,dataToSend){
            return await fetch(url,
                {
                    method: 'POST',
                    body: new URLSearchParams(this.prepareFormData(dataToSend)),
                    headers: (new Headers()).append('Content-Type','application/x-www-form-urlencoded')
                })
        },
        prepareFormData(thing){
            let formData = new FormData();
            if (typeof thing == "object"){
                let preForm =this.toPreFormData(thing);
                for (const key in preForm) {
                    formData.append(key,preForm[key]);
                }
            }
            return formData;
        },
        removeFromSearchedEntries(idsToRemoveFromSearchedEntries){
            if (idsToRemoveFromSearchedEntries.length > 0){
                this.$set(this.root,'searchedEntries',this.root.searchedEntries.filter(e => !idsToRemoveFromSearchedEntries.includes(e.id_fiche)))
            }
        },
        removeIdsFromUpdating(entriesIds){
            this.updatingIds = this.updatingIds.filter((entryId)=>!entriesIds.includes(entryId));
            this.updateAvailableEntries();
        },
        sanitizeString(objet,name,defaultValue){
            return (typeof objet === "object" &&
                objet.hasOwnProperty(name) && 
                typeof objet[name] === "string" &&
                objet[name].length > 0) 
                ? objet[name]
                : defaultValue;
        },
        secureUpdatePreview(forced = false){
            if (this.ready){
                if (forced || this.secureUpdatePreviewDebounce == 0){
                    this.secureUpdatePreviewDebounce = 1
                    this.updatePreview(this.getContentsForUpdate(),{})
                        .then(()=>{
                            if (this.secureUpdatePreviewDebounce == 2){
                                this.secureUpdatePreview(true)
                            } else {
                                this.secureUpdatePreviewDebounce = 0
                            }
                        })
                } else {
                    this.secureUpdatePreviewDebounce = 2
                }
            }
        },
        async sendmail(){
            if (this.sendingMail){
                return
            }
            let dataToSend= this.getData();
            dataToSend.message = this.getContentsForUpdate()
            if (dataToSend.subject.length == 0 || 
                dataToSend.senderEmail.length == 0 ||
                dataToSend.emailfieldname.length == 0){
                this.sendingMail = false
                return
            }
            this.sendingMail = true
            this.doneFor = []
            if (!confirm(_t('CUSTOMSENDMAIL_EMAIL_SEND'))){
                this.sendingMail = false
                return
            }
            return await this.sendMailInternal(dataToSend)
            .finally(()=>{
                this.sendingMail = false
            })
            .then((json)=>{
                if (Array.isArray(this.doneFor) && 'sent for' in json && typeof json['sent for'] === 'string'){
                    json['sent for'].split(',').forEach((e)=>{
                        if (!this.doneFor.includes(e)){
                            this.doneFor.push(e)
                        }
                    })
                }
                toastMessage(
                    _t(
                        'CUSTOMSENDMAIL_EMAIL_SENT',
                        {
                        'details':('sent for' in json)
                            ? json['sent for']
                            : ''
                        }
                    ),
                    1500,
                    'alert alert-success'
                )
            })
            .catch((error)=>{
                toastMessage(
                    _t('CUSTOMSENDMAIL_EMAIL_NOT_SENT',
                        {
                            'errorMsg':(typeof error == "object" && 'errorMsg' in error)
                                ? error.errorMsg
                                : ''
                        }
                    ),
                    3000,
                    "alert alert-danger"
                )
            })
        },
        async sendMailInternal(dataToSend){
            let contactsToSend = dataToSend.contacts
            return await this.fetch(wiki.url('?api/customsendmail/sendmail'),'post',dataToSend)
                .then(async (json)=>{
                    if (Array.isArray(this.doneFor) && 'sent for' in json && typeof json['sent for'] === 'string'){
                        let currentDone = json['sent for'].split(',')
                        currentDone.forEach((e)=>{
                            if (!this.doneFor.includes(e)){
                                this.doneFor.push(e)
                            }
                            if (contactsToSend.includes(e)){
                                contactsToSend = contactsToSend.filter((i)=>e!=i)
                            }
                        })
                        if (contactsToSend.length > 0){
                            let newJson = await this.sendMailInternal({...dataToSend,...{contacts:contactsToSend}})
                            let newDone = ('sent for' in newJson && typeof newJson['sent for'] === 'string')
                                ? newJson['sent for'].split(',')
                                : []
                            newDone.forEach((e)=>{
                                if (!currentDone.includes(e)){
                                    currentDone.push(e)
                                }
                            })
                            json['sent for'] = currentDone.join(',')
                        }
                    }
                    return json
                })
        },
        sortAvailableEntriesCkeckedFirst(){
            this.availableEntries.sort((a,b)=>{
                return (this.isChecked(a) !== this.isChecked(b))
                    ? (
                        this.isChecked(a) ? -1 : 1
                    )
                    : a.id_fiche.localeCompare(b.id_fiche)
            })
        },
        toogleAddresse(event){
            this.checkAll = false;
            let entryId = event.target.getAttribute('name');
            if (this.selectedAddresses.includes(entryId)){
                this.selectedAddresses = this.selectedAddresses.filter((id)=>id!=entryId)
            } else {
                this.selectedAddresses.push(entryId);
            }
            this.updatePreview(this.getContentsForUpdate(),{});
        },
        toggleCheckAll(){
            let availableIds = Object.keys(this.availableEntries).map((key)=>{
                return this.availableEntries[key].id_fiche
            })
            if (this.checkAll){
                this.selectedAddresses = this.selectedAddresses.filter((id)=>{
                    return !availableIds.includes(id)
                })
                this.checkAll = false;
            } else {
                this.selectedAddresses = [
                    ...this.selectedAddresses.filter((id)=>{
                        return !availableIds.includes(id)
                    }),
                    ...availableIds
                ]
                this.checkAll = true;
            }
            this.updatePreview(this.getContentsForUpdate(),{});
        },
        toPreFormData(thing,key =""){
            let type = typeof thing;
            switch (type) {
                case 'boolean':
                case 'number':
                case 'string':
                    return {
                        [key]:thing
                    };
                case 'object':
                    if (Object.keys(thing).length > 0){
                        let result = {};
                        for (const propkey in thing) {
                            result = {
                                ...result,
                                ...this.toPreFormData(
                                    thing[propkey],
                                    (key.length == 0) ? propkey : `${key}[${propkey}]`
                                )
                            }
                        }
                        return result;
                    } else if (thing === null) {
                        return {
                            [key]:null
                        };
                    } else {
                        return {
                            [key]: []
                        };
                    }
                
                case 'array':
                    if (thing.length == 0){
                        return {
                            [key]: []
                        };
                    }
                    let result = {};
                    thing.forEach((val,propkey)=>{
                        result = {
                            ...result,
                            ...this.toPreFormData(
                                val,
                                (key.length == 0) ? propkey : `${key}[${propkey}]`
                            )
                        }
                    });
                    return result;
                default:
                    return {
                        [key]:null
                    };
            }
        },
        updateAvailableEntries(){
            this.availableEntries = Object.keys(this.entries).filter((key)=>{
                let entry = this.entries[key];
                return entry.id_fiche && 
                    entry.id_fiche.length > 0 && 
                    this.cacheEntriesDisplay.hasOwnProperty(entry.id_fiche) &&
                    this.cacheEntriesDisplay[entry.id_fiche].auth &&
                    this.cacheEntriesDisplay[entry.id_fiche].display ;
            }).map((key)=>this.entries[key]);
            this.sortAvailableEntriesCkeckedFirst()
            if (this.ready && this.canShow(this.availableEntries) && !this.summernoteInit){
                this.$nextTick(()=>this.loadSummernoteWithLang());
            }
        },
        async updatePreview(contents, options){
            if ((options.forced == undefined || !options.forced) && this.updatingPreview){
                this.nextPreviewTobeRetrieved = true
                this.nextContentForPreview.push(contents)
                return
            } else {
                this.updatingPreview = true;
                this.doneFor = null
                let dataToSend= this.getData();
                dataToSend.message = contents;
                return await this.fetch(wiki.url('?api/customsendmail/preview'),'post',dataToSend)
                    .then((json)=>{
                        if (typeof json == "object"){
                            this.htmlPreview = json.html || "<b>Error !</b>";
                            this.sizePreview = json.size || "Error !";
                        }
                    })
                    .catch((e)=>{/* do nothing */})
                    .finally(()=>{
                        this.finishPreviewUpdate()
                    })
            }
        },
        async updateSenderEmailFromLoggedUser(){
            return await this.fetch(wiki.url('?api/customsendmail/currentuseremail'))
                .then((json)=>{
                    if ('email' in json && json.email.length > 0){
                        this.senderEmail = json.email
                        if ('name' in json && json.name.length > 0 && this.senderName.length == 0){
                            this.senderName = json.name;
                        }
                        return
                    } else {
                        throw 'email not found'
                    }
                })
                .catch((e)=>{
                    this.senderEmail = this.fromSlot('defaultsenderemail')
                })
        },
        async updateStatus(entriesIds){
            if (entriesIds.length>0){
                entriesIds.forEach((entryId)=>{
                    this.updatingIds.push(entryId);
                });
                return await this.fetch(wiki.url('?api/customsendmail/filterentries'),'post',{
                    entriesIds,
                    params: {
                        selectmembers: this.params.selectmembers || "",
                        selectmembersparentform: String(this.params.selectmembersparentform || "")
                    }
                })
                .then((json)=>{
                    if ('entriesIds' in json && Array.isArray(json.entriesIds)){
                        let newSelectedAddrresses = [];
                        let idsToRemoveFromSearchedEntries = [];
                        entriesIds.forEach((id)=>{
                            if (!this.cacheEntriesDisplay.hasOwnProperty(id)){
                                this.cacheEntriesDisplay[id] = {
                                    display: true,
                                    auth: true
                                }
                            }
                            this.cacheEntriesDisplay[id].auth = json.entriesIds.includes(id);
                            if (this.cacheEntriesDisplay[id].auth){
                                newSelectedAddrresses.push(id);
                            } else {
                                idsToRemoveFromSearchedEntries.push(id);
                            }
                        });
                        if (newSelectedAddrresses.length > 0){
                            this.selectedAddresses = [...this.selectedAddresses,...newSelectedAddrresses];
                        }
                        this.removeFromSearchedEntries(idsToRemoveFromSearchedEntries);
                    } else {
                        throw 'entriesIds not found'
                    }
                })
                .catch((e)=>{/* do nothing */})
                .finally(()=>{
                    this.removeIdsFromUpdating(entriesIds)
                })
            }
        }
    },
    mounted(){
        this.uid = this.getUID() + '-' + this.getUID();
        this.senderEmail = this.fromSlot('defaultsenderemail');
        this.updateSenderEmailFromLoggedUser();
    },
    watch: {
        entries:function(newVal, oldVal) {
            let newIds = newVal.map(e => e.id_fiche)
            let oldIds = oldVal.map(e => e.id_fiche)
            if (!this.arraysEqual(newIds, oldIds)) {
                let idsInCache = Object.keys(this.cacheEntriesDisplay);
                let idsToRemoveFromSearchedEntries = [];
                idsInCache.forEach((entryId)=>{
                    if (newIds.includes(entryId)){
                        this.cacheEntriesDisplay[entryId].display = true;
                        if (!this.cacheEntriesDisplay[entryId].auth && !idsToRemoveFromSearchedEntries.includes(entryId)){
                            // entryToRemoveFromFilteredEntries
                            idsToRemoveFromSearchedEntries.push(entryId);
                        }
                    }
                });
                let idsNotInCache = newIds.filter((entryId)=>{
                    return !idsInCache.includes(entryId) && !this.updatingIds.includes(entryId);
                });
                this.updateStatus(idsNotInCache).finally(()=>{
                    this.removeFromSearchedEntries(idsToRemoveFromSearchedEntries)
                })
            }
            this.updateAvailableEntries();
            if (typeof oldVal == "object" && Object.keys(oldVal).length != 0){
                this.secureUpdatePreview()
            }
        },
        params(){
            this.subject = this.sanitizeString(this.params,'defaultsubject','');
            this.emailfieldname = this.sanitizeString(this.params,'emailfieldname','bf_mail');
            this.sendToGroup = ('sendtogroupdefault' in this.params && [1,true,'true'].includes(this.params.sendtogroupdefault));
            this.groupinhiddencopy = (!('groupinhiddencopydefault' in this.params) || [1,true,'true'].includes(this.params.groupinhiddencopydefault));
            this.$nextTick(()=>{
                if (this.senderName.length == 0){
                    this.senderName = this.sanitizeString(this.params,'defaultsendername',"");
                }
            })
        },
        ready(){
            if (this.ready && this.canShow(this.availableEntries) && !this.summernoteInit){
                this.loadSummernoteWithLang();
            }
        },
        htmlPreview(){
            if ('preview' in this.$refs){
                this.$refs.preview.innerHTML = this.htmlPreview;
            }
        },
        availableEntries(){
            this.forcedNotGroupinhiddencopy = (this.availableEntries.length > this.limitForForce)
        },
        sizePreview(){
            if ('previewsize' in this.$refs){
                this.$refs.previewsize.innerHTML = this.sizePreview;
            }
        },
        addContactsToReplyTo(){
            this.secureUpdatePreview();
        },
        addSenderToContact(){
            this.secureUpdatePreview();
        },
        addSenderToReplyTo(){
            this.secureUpdatePreview();
        },
        forcedNotGroupinhiddencopy(){
            this.secureUpdatePreview();
        },
        groupinhiddencopy(){
            this.secureUpdatePreview();
        },
        receiveHiddenCopy(){
            this.secureUpdatePreview();
        },
        senderName(){
            this.secureUpdatePreview();
        },
        senderEmail(){
            this.secureUpdatePreview();
        },
        sendToGroup(){
            this.secureUpdatePreview();
        },
        subject(){
            this.secureUpdatePreview();
        }
    },
    template: `
      <div class="bazar-send-mail-container">
        <template v-if="canShow(availableEntries)">
            <form 
                :id="'frm'+this.uid" 
                :action="wiki.url('')"
                method="post"
                class="custom-sendmail-form">
                <div class="row">
                    <div class="col-sm-4">
                <h4><slot name="title"/></h4>
                        <label class="no-dblclick">
                            <input type="checkbox" @click="toggleCheckAll" :checked="checkAll"><span> <slot v-if="!checkAll" name="checkall"/><slot v-else name="uncheckall"/></span>
                        </label>
                        <br>
                        <ul class="list-unstyled">
                            <li v-for="entry in availableEntries" class="bazar-entry" style="margin-bottom:8px">
                                <label>
                                    <input 
                                        class="mail-checkbox" 
                                        type="checkbox" 
                                        :name="entry.id_fiche"
                                        @click="toogleAddresse"
                                        :checked="isChecked(entry)">
                                    <span>{{ entry.bf_titre }}&nbsp;
                                        <a 
                                            class="btn btn-xs btn-default modalbox" 
                                            :href="entry.url"
                                            :title="entry.bf_titre">
                                            <i class="glyphicon glyphicon-eye-open"></i>
                                        </a>
                                    </span>
                                </label>
                            </li>
                        </ul>
                    </div>
                    <div class="col-sm-8">
                        <a id="return-param" href="#draft-part" class="btn btn-xs btn-secondary-2">
                            <i class="far fa-file-alt"></i> <slot name="seedraft"/>
                        </a>
                        <NbDest :availableentries="availableEntries" :bazarsendmail="this"></NbDest>
                        <div class="form-group">
                          <label><slot name="sendername"/></label>
                          <input type="text" class="form-control" v-model="senderName" :placeholder="fromSlot('sendername')">
                        </div>
                        <div class="form-group">
                          <label><slot name="senderemail"/></label>
                          <input type="email" class="form-control" v-model="senderEmail" required="true">
                        </div>
                        <div class="form-group">
                          <label><slot name="defaultsubject"/></label>
                          <input type="text" class="form-control" v-model="subject" required="true" :placeholder="fromSlot('subjectplaceholder')">
                        </div>
                        <button type="button" class="btn btn-default btn-xs" data-toggle="collapse" :data-target="'#advanced-params-'+this.uid">
                          <span v-show="!advancedParamsVisibles"><slot name="see"/></span>
                          <span v-show="advancedParamsVisibles"><slot name="hide"/></span>
                        </button>
                        <div :id="'advanced-params-'+this.uid" class="collapse" ref="advancedParams">`
                            // <div class="form-group">
                            //     <label class="no-dblclick">
                            //         <input type="checkbox" @click="sendToGroup=!sendToGroup;addContactsToReplyTo=sendToGroup?addContactsToReplyTo:false" :checked="sendToGroup">
                            //         <span> <slot name="sendtogroup"/></span>
                            //     </label>
                            // </div>
                            +`<div class="form-group">
                                <label class="no-dblclick">
                                    <input type="checkbox" @click="addSenderToContact=!addSenderToContact" :checked="addSenderToContact">
                                    <span> <slot name="addsendertocontact"/></span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="no-dblclick">
                                    <input type="checkbox" @click="receiveHiddenCopy=!receiveHiddenCopy" :checked="receiveHiddenCopy">
                                    <span> <slot name="receivehiddencopy"/></span>
                                </label>
                            </div>
                            <div v-if="!sendToGroup" class="form-group">
                                <label class="no-dblclick">
                                    <input type="checkbox" @click="groupinhiddencopy=!groupinhiddencopy" :checked="groupinhiddencopy && !forcedNotGroupinhiddencopy" :disabled="forcedNotGroupinhiddencopy">
                                    <span> <slot name="groupinhiddencopy"/><slot v-if="forcedNotGroupinhiddencopy" name="groupinhiddencopylimited" :nb="limitForForce"/></span>
                                </label>
                            </div>
                            <div v-if="isadmin && (hascontactfrom || !sendToGroup)" class="well">
                                <i><slot name="adminpart"/></i>
                                <div class="form-group">
                                    <label class="no-dblclick">
                                        <input type="checkbox" @click="addSenderToReplyTo=!addSenderToReplyTo" :checked="addSenderToReplyTo" :disabled="hascontactfrom">
                                        <span> <slot name="addsendertoreplyto"/></span>
                                    </label>
                                </div>
                                <slot name="hascontactfrom"/>
                                <div class="form-group">
                                    <label class="no-dblclick">
                                        <input type="checkbox" @click="addContactsToReplyTo=!addContactsToReplyTo" :checked="addContactsToReplyTo" :disabled="!sendToGroup">
                                        <span> <slot name="addcontactstoreplyto"/></span>
                                    </label>
                                </div>
                            </div>
                            <div v-if="!isadmin && !hascontactfrom" class="form-group">
                                <label class="no-dblclick">
                                    <input type="checkbox" @click="addSenderToReplyTo=!addSenderToReplyTo" :checked="addSenderToReplyTo">
                                    <span> <slot name="addsendertoreplyto"/></span>
                                </label>
                            </div>
                            <div v-if="!isadmin && sendToGroup" class="form-group">
                                <div class="form-group">
                                    <label class="no-dblclick">
                                        <input type="checkbox" @click="addContactsToReplyTo=!addContactsToReplyTo" :checked="addContactsToReplyTo">
                                        <span> <slot name="addcontactstoreplyto"/></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div><NbDest :availableentries="availableEntries" :bazarsendmail="this"></NbDest></div>
                        <slot name="textarea"/>
                        <div class="clearfix"></div>
                        <div class="form-group" v-if="!sendToGroup && (!groupinhiddencopy || forcedNotGroupinhiddencopy)"><slot name="help"/></div>
                        <div v-if="Array.isArray(doneFor)">
                            <slot name="donefor"/>
                            <ProgressBar :donefor="doneFor" :bazarsendmail="this"></ProgressBar>
                            <button @click.prevent.stop="showDoneForAll=!showDoneForAll" class="btn btn-xs btn-info">
                                <slot v-if="!showDoneForAll" name="showdoneforall"/>
                                <slot v-else name="hidedoneforall"/>
                            </button>
                            <ul>
                                <template v-if="showDoneForAll">
                                    <li v-for="name in doneFor">
                                        {{ name }}
                                    </li>
                                </template>
                                <template v-else>
                                    <li v-for="name in doneFor.slice(0,1)">
                                        {{ name }}
                                    </li>
                                    <li>...</li>
                                </template>
                            </ul>
                        </div>
                        <button src="#" class="btn btn-xl btn-primary" @click.prevent.stop="sendmail" :disabled="sendingMail" :style="sendingMail ? {cursor:'wait'} : false">
                            <slot name="sendmail"/>
                        </button>
                        <br/>
                        <slot name="hascontactfrom"/>
                        <div id="draft-part" class="form-group well" style="min-height:300px;width:100%;">
                            <label><slot name="preview"/></label><br>
                            <i><slot name="previewsize"/> <span ref="previewsize"></span></i><br/>
                            <NbDest :availableentries="availableEntries" :bazarsendmail="this"></NbDest>
                            <hr/>
                            <div ref="preview"></div>
                        </div>
                        <a href="#return-param" class="btn btn-xs btn-secondary-2">
                            <i class="fas fa-wrench"></i> <slot name="returnparam"/>
                        </a>
                    </div>
                </div>
            </form>
        </template>
        <spinner-loader v-if="this.$root.isLoading || !ready" class="overlay super-overlay"></spinner-loader>
      </div>
    `
};

if (isVueJS3){
    if (window.hasOwnProperty('bazarVueApp')){ // bazarVueApp must be defined into bazar-list-dynamic
        if (!bazarVueApp.config.globalProperties.hasOwnProperty('wiki')){
            bazarVueApp.config.globalProperties.wiki = wiki;
        }
        if (!bazarVueApp.config.globalProperties.hasOwnProperty('_t')){
            bazarVueApp.config.globalProperties._t = _t;
        }
        window.bazarVueApp.component(componentName,componentParams);
    }
} else {
    if (!Vue.prototype.hasOwnProperty('wiki')){
        Vue.prototype.wiki = wiki;
    }
    if (!Vue.prototype.hasOwnProperty('_t')){
        Vue.prototype._t = _t;
    }
    Vue.component(componentName,componentParams);
}