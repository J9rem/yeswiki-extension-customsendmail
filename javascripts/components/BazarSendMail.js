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

let componentName = 'BazarSendMail';
let isVueJS3 = (typeof Vue.createApp == "function");

let componentParams = {
    props: ['params','entries','hascontactfrom','ready','root','isadmin'],
    components: { SpinnerLoader,NbDest},
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
            emailfieldname: "bf_mail",
            htmlPreview: "",
            nextContentForPreview: [],
            nextPreviewTobeRetrieved: false,
            receiveHiddenCopy: false,
            selectedAddresses: [],
            sendToGroup: true,
            senderEmail: "",
            senderName: "",
            sendingMail: false,
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
        getContentsForUpdate(){
            let textearea = $(this.$el).find(`textarea.form-control.summernote[name=message]`);
            if (textearea == undefined || textearea.length == 0){
              return "Error summernote not found !";
            } else {
              return $(textearea).summernote('code');
            }
        },
        getData (){
            let availableIds = this.availableEntries.map((entry)=>entry.id_fiche);
            return {
                senderName: this.senderName,
                senderEmail: this.senderEmail,
                subject: this.subject,
                contacts: this.selectedAddresses.filter((id)=>availableIds.includes(id)),
                addsendertocontact: this.addSenderToContact,
                sendtogroup: this.sendToGroup,
                addsendertoreplyto: this.addSenderToReplyTo,
                addcontactstoreplyto: this.addContactsToReplyTo,
                receivehiddencopy: this.receiveHiddenCopy,
                emailfieldname: this.emailfieldname,
                selectmembers: this.params.selectmembers || '',
                selectmembersparentform: this.params.selectmembersparentform || '',
            };
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
        secureUpdatePreview(){
            if (this.ready){
                this.updatePreview(this.getContentsForUpdate(),{});
            }
        },
        sendmail(){
            if (this.sendingMail){
                return ;
            }
            let dataToSend= this.getData();
            dataToSend.message = this.getContentsForUpdate();
            if (dataToSend.subject.length == 0 || 
                dataToSend.senderEmail.length == 0 ||
                dataToSend.emailfieldname.length == 0){
                this.sendingMail = false;
                return ;
            }
            this.sendingMail = true;
            if (confirm(_t('CUSTOMSENDMAIL_EMAIL_SEND'))){
                // 1. Create a new XMLHttpRequest object
                let xhr = new XMLHttpRequest();
                // 2. Configure it: POST-request
                xhr.open('POST',wiki.url('?api/customsendmail/sendmail'));
                let data = this.prepareFormData(dataToSend);
                // 3. Listen load
                xhr.onload = () =>{
                    let responseDecoded = JSON.parse(xhr.response);
                    if (xhr.status == 200){
                        toastMessage(
                            _t(
                                'CUSTOMSENDMAIL_EMAIL_SENT',
                                {
                                'details':(typeof responseDecoded == "object" && responseDecoded.hasOwnProperty('sent for'))
                                    ? responseDecoded['sent for']
                                    : ''
                                }
                            ),
                            1500,
                            'alert alert-success'
                        );
                    } else {
                        toastMessage(
                            _t('CUSTOMSENDMAIL_EMAIL_NOT_SENT',
                                {
                                    'errorMsg':(typeof responseDecoded == "object" && responseDecoded.hasOwnProperty('error'))
                                        ? responseDecoded.error
                                        : ''
                                }
                            ),
                            3000,
                            "alert alert-danger"
                        );
                    }
                    this.sendingMail = false;
                    return ;
                }
                // 4 .listen error
                xhr.onerror = () => {
                    toastMessage(
                        _t('CUSTOMSENDMAIL_EMAIL_NOT_SENT',{'errorMsg':''}),
                        3000,
                        "alert alert-danger"
                    );
                    this.sendingMail = false;
                };
                // 5. Send the request over the network
                xhr.send(data);
            } else {
                this.sendingMail = false;
            }
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
            if (this.checkAll){
                this.selectedAddresses = [];
                this.checkAll = false;
            } else {
                this.selectedAddresses = Object.keys(this.availableEntries).map((key)=>{
                    return this.availableEntries[key].id_fiche;
                });
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
            if (this.ready && this.canShow(this.availableEntries) && !this.summernoteInit){
                this.$nextTick(()=>this.loadSummernoteWithLang());
            }
        },
        updatePreview(contents, options){
            if ((options.forced == undefined || !options.forced) && this.updatingPreview){
                this.nextPreviewTobeRetrieved = true;
                this.nextContentForPreview.push(contents);
              } else {
                this.updatingPreview = true;
                let dataToSend= this.getData();
                dataToSend.message = contents;
                
                // 1. Create a new XMLHttpRequest object
                let xhr = new XMLHttpRequest();
                // 2. Configure it: POST-request
                xhr.open('POST',wiki.url('?api/customsendmail/preview'));
                let data = this.prepareFormData(dataToSend);
                // 3. Listen load
                xhr.onload = () =>{
                    if (xhr.status == 200){
                        let responseDecoded = JSON.parse(xhr.response);
                        if (typeof responseDecoded == "object"){
                            this.htmlPreview = responseDecoded.html || "<b>Error !</b>";
                            this.sizePreview = responseDecoded.size || "Error !";
                        }
                    }
                    this.finishPreviewUpdate();
                }
                // 4 .listen error
                xhr.onerror = () => {
                    this.finishPreviewUpdate();
                };
                // 5. Send the request over the network
                xhr.send(data);
            }
        },
        updateSenderEmailFromLoggedUser(){
                // 1. Create a new XMLHttpRequest object
                let xhr = new XMLHttpRequest();
                // 2. Configure it: POST-request
                xhr.open('GET',wiki.url('?api/customsendmail/currentuseremail'));
                // 3. Listen load
                xhr.onload = () =>{
                    if (xhr.status == 200){
                        let responseDecoded = JSON.parse(xhr.response);
                        if (typeof responseDecoded == "object" && responseDecoded.hasOwnProperty('email') && 
                            responseDecoded.email.length > 0){
                            this.senderEmail = responseDecoded.email;
                            if (responseDecoded.hasOwnProperty('name') && responseDecoded.name.length > 0 && this.senderName.length == 0){
                                this.senderName = responseDecoded.name;
                            }
                            return ;
                        }
                    }
                    this.senderEmail = this.fromSlot('defaultsenderemail');
                }
                // 4 .listen error
                xhr.onerror = () => {
                    this.senderEmail = this.fromSlot('defaultsenderemail');
                };
                // 5. Send the request over the network
                xhr.send();
        },
        updateStatus(entriesIds){
            if (entriesIds.length>0){
                entriesIds.forEach((entryId)=>{
                    this.updatingIds.push(entryId);
                });
                // 1. Create a new XMLHttpRequest object
                let xhr = new XMLHttpRequest();
                // 2. Configure it: POST-request
                xhr.open('POST',wiki.url('?api/customsendmail/filterentries'));
                let data = this.prepareFormData({
                    entriesIds,
                    params: {
                        selectmembers: this.params.selectmembers || "",
                        selectmembersparentform: String(this.params.selectmembersparentform || "")
                    }
                });
                // 3. Listen load
                xhr.onload = () =>{
                    if (xhr.status == 200){
                        let responseDecoded = JSON.parse(xhr.response);
                        if (responseDecoded && responseDecoded.hasOwnProperty('entriesIds') && Array.isArray(responseDecoded.entriesIds)){
                            let newSelectedAddrresses = [];
                            let idsToRemoveFromSearchedEntries = [];
                            entriesIds.forEach((id)=>{
                                if (!this.cacheEntriesDisplay.hasOwnProperty(id)){
                                    this.cacheEntriesDisplay[id] = {
                                        display: true,
                                        auth: true
                                    }
                                }
                                this.cacheEntriesDisplay[id].auth = responseDecoded.entriesIds.includes(id);
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
                        }
                    }
                    this.removeIdsFromUpdating(entriesIds);
                }
                // 4 .listen error
                xhr.onerror = () => {
                    this.removeIdsFromUpdating(entriesIds);
                };
                // 5. Send the request over the network
                xhr.send(data);
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
                this.updateStatus(idsNotInCache);
                this.removeFromSearchedEntries(idsToRemoveFromSearchedEntries);
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
        sizePreview(){
            if ('previewsize' in this.$refs){
                this.$refs.previewsize.innerHTML = this.sizePreview;
            }
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
        addSenderToContact(){
            this.secureUpdatePreview();
        },
        addSenderToReplyTo(){
            this.secureUpdatePreview();
        },
        addContactsToReplyTo(){
            this.secureUpdatePreview();
        },
        receiveHiddenCopy(){
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
                            <input type="checkbox" @click="toggleCheckAll" :checked="checkAll"><span> <slot name="checkall"/></span>
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
                        <div class="form-group" v-if="!sendToGroup"><slot name="help"/></div>
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