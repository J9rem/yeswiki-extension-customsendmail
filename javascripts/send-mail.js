const SendMailHelper = {
  retrievingPreview: false,
  nextPreviewTobeRetrieved: false,
  nextContentForPreview: {},
  checkedAll: function () {
    let elem = event.target;
    let newValue = $(elem).prop("checked");
    let parent = $(elem).closest("form.custom-sendmail-form");
    if (parent != undefined && parent.length > 0){
      if (newValue){
        $(parent).find(".mail-checkbox:not(:visible)").prop('checked', false);
        $(parent).find(".mail-checkbox:visible").prop('checked', true);
      } else {
        $(parent).find(".mail-checkbox").prop('checked', false);
      }
    }
    let nbBazarlist = $(parent).prop('id').replace(/^frm/,"");
    let contents = SendMailHelper.getContentsForUpdate(nbBazarlist);
    SendMailHelper.updatePreview(contents,{nbBazarlist});
  },
  getData: function (nbBazarlist){
    let senderName = $(`[name=namesender\\[${nbBazarlist}\\]]`).val();
    let senderEmail = $(`[name=mailsender\\[${nbBazarlist}\\]]`).val();
    let subject = $(`[name=subject\\[${nbBazarlist}\\]]`).val();
    let contacts = [];
    $(`[name=contact\\[${nbBazarlist}\\]\\[\\]]`).each(function (){
      if ($(this).prop('checked')){
        contacts.push($(this).val());
      }
    });
    contacts = contacts.join(',');
    let addsendertocontact = $(`[name=addsendertocontact\\[${nbBazarlist}\\]]`).prop('checked');
    let sendtogroup = $(`[name=sendtogroup\\[${nbBazarlist}\\]]`).prop('checked');
    let addsendertoreplyto = $(`[name=addsendertoreplyto\\[${nbBazarlist}\\]]`).prop('checked');
    let addcontactstoreplyto = $(`[name=addcontactstoreplyto\\[${nbBazarlist}\\]]`).prop('checked');
    let receivehiddencopy = $(`[name=receivehiddencopy\\[${nbBazarlist}\\]]`).prop('checked');
    return {senderName,senderEmail,subject,contacts,addsendertocontact,sendtogroup,addsendertoreplyto,addcontactstoreplyto,receivehiddencopy};
  },
  sendEmail: function (nbBazarlist,emailfieldname){
    event.preventDefault();
    let elem = event.target;
    $(elem).attr('disabled','disabled');
    $(elem).css('cursor','wait');
    let dataToSend = this.getData(nbBazarlist);
    dataToSend.message = $(`[name=message\\[${nbBazarlist}\\]]`).summernote('code');
    dataToSend.emailfieldname = emailfieldname;
    $.ajax({
      url: wiki.url('api/customsendmail/sendmail'),
      method: "POST",
      cache: false,
      async: true,
      data: dataToSend,
      success: function (data,){
        toastMessage(_t('CUSTOMSENDMAIL_EMAIL_SENT',{'details':data['sent for'] || ''}),1500,'alert alert-success')
      },
      error: function (e) {
        toastMessage(_t('CUSTOMSENDMAIL_EMAIL_NOT_SENT',{'errorMsg':(e.responseJSON.error || '')}), 3000, "alert alert-danger");
      },
      complete: function (){
        $(elem).removeAttr('disabled');
        $(elem).css('cursor','');
      }
    })
  },
  getContentsForUpdate: function(nbBazarlist){
    let textearea = $(`textarea.form-control.summernote[name=message\\[${nbBazarlist}\\]]`);
    if (textearea == undefined || textearea.length == 0){
      return "Error summernote not found !";
    } else {
      return $(textearea).summernote('code');
    }
  },
  updatePreview: function (contents, options){
    let nbBazarlist = options.nbBazarlist ||
      $(options.editable)
      .closest('.form-group.input-textarea.textarea.summernote')
      .find('textarea.form-control.summernote[name^=message]').attr('name').replace(/^message\[/,"").replace(/\]$/,"");
    if ((options.forced == undefined || !options.forced) && SendMailHelper.retrievingPreview){
      SendMailHelper.nextPreviewTobeRetrieved = true;
      SendMailHelper.nextContentForPreview[nbBazarlist] = contents;
    } else {
      SendMailHelper.retrievingPreview = true;
      let dataToSend= this.getData(nbBazarlist);
      dataToSend.message = contents;
      $.ajax({
        url: wiki.url('api/customsendmail/preview'),
        method: "POST",
        cache: true,
        async: true,
        data: dataToSend,
        success: function (data){
          let html = data.html || "<b>Error !</b>";
          let sizeStr = data.size || "Error !";
          $(`#customsendmailPreview${nbBazarlist}`).html($(html));
          $(`#customsendmailSize${nbBazarlist}`).text(sizeStr);
        },
        complete: function (){
          if (SendMailHelper.nextPreviewTobeRetrieved){
            let keys = Object.keys(SendMailHelper.nextContentForPreview);
            if (keys.length == 0){
              SendMailHelper.retrievingPreview = false;
              SendMailHelper.nextPreviewTobeRetrieved = false;
              SendMailHelper.nextContentForPreview = {};
            } else {
              let newNbBazarlist = keys[0];
              let content = SendMailHelper.nextContentForPreview[newNbBazarlist];
              delete SendMailHelper.nextContentForPreview[newNbBazarlist];
              SendMailHelper.updatePreview(content,{nbBazarlist:newNbBazarlist,forced:true})
            }
          } else {
            SendMailHelper.retrievingPreview = false;
            SendMailHelper.nextContentForPreview = {};
          }
        }
      })
    }
  },
  loadSummernote: function (langOptions){
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
          onChange: function (contents, $editable){
            SendMailHelper.updatePreview(contents, {editable:$editable});
          }
      }
    }});
  }
};
$(document).ready(function() {
  if (wiki.locale == "en"){
    SendMailHelper.loadSummernote({});
  } else {
    let langName = wiki.locale.toLowerCase() + '-' + wiki.locale.toUpperCase();
    let scriptUrl = wiki.baseUrl.replace(/\?$/,"");
    scriptUrl = scriptUrl + `tools/bazar/libs/vendor/summernote/lang/summernote-${langName}.js`;
    // load script
    $("body").append($('<script>').attr("src",scriptUrl));
    SendMailHelper.loadSummernote({lang:langName});
  }

  let selector = 'input[type=checkbox].mail-checkbox[name]';
  selector += ', input[type=checkbox][name^=sendtogroup\\[]';
  selector += ', input[type=checkbox][name^=addsendertocontact\\[]';
  selector += ', input[type=checkbox][name^=addsendertoreplyto\\[]';
  selector += ', input[type=checkbox][name^=addcontactstoreplyto\\[]';
  selector += ', input[type=checkbox][name^=receivehiddencopy\\[]';
  selector += ', input[type=text][name^=namesender\\[]';
  selector += ', input[type=email][name^=mailsender\\[]';
  selector += ', input[type=text][name^=subject\\[]';
  $(selector).on('change',function (){
    let nbBazarlist = $(this).attr('name').replace(/\[\]$/,"").replace(/^.*\[/,"").replace(/\]$/,"");
    let contents = SendMailHelper.getContentsForUpdate(nbBazarlist);
    SendMailHelper.updatePreview(contents,{nbBazarlist});
  })
});