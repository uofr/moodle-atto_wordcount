YUI.add("moodle-atto_wordcount-button",function(o,t){var i=0;o.namespace("M.atto_wordcount").Button=o.Base.create("button",o.M.editor_atto.EditorPlugin,[],{block:0,updateRate:200,counterid:null,spacer:/(<\/(?!a>|b>|del>|em>|i>|ins>|s>|small>|strong>|sub>|sup>|u>)\w+>|<br> | <br\s*\/>)/g,mediaTags:/(<(audio|video)).*(<\/(audio|video)>)/gm,counter:new RegExp("[\\p{Z}\\p{Cc}—–]+","gu"),initializer:function(){var t=this.get("host"),e=t._wrapper;this.counterid=t.get("elementid")+"-word-count",this.counterid=this.counterid.replace(":","-"),this.counterElement=o.Node.create('<span class="badge badge-light" id="'+this.counterid+'">0</span>'),this.wordlimit=this.get("wordlimits")[i],i+=1,0!==this.wordlimit&&null!==this.wordlimit&&this.wordlimit!==undefined&&(e.appendChild(o.Node.create('<div class="'+this.toolbar.getAttribute("class")+' editor_atto_toolbar_bottom p-0 d-flex"><div class="d-inline-flex p-1"><strong>'+M.util.get_string("words","atto_wordcount")+': </strong><span id="'+this.counterid+'">0</span></div></div>')),this.wordlimit&&((e=document.createElement("span")).innerHTML="/",document.getElementById(this.counterid).parentNode.appendChild(e),(e=document.createElement("span")).innerHTML=this.wordlimit,document.getElementById(this.counterid).parentNode.appendChild(e)),this._count(t.get("editor")),this.get("host").on("pluginsloaded",function(){this.get("host").on("atto:selectionchanged",this._count,this)},this))},_count:function(e){var i=this;i.block||(i.block=1,setTimeout(function(){var t=i._getCount(e);o.one("#"+i.counterid).set("text",t),i.wordlimit&&(i.wordlimit-t<0?(o.one("#"+i.counterid).addClass("danger"),o.one("#"+i.counterid).removeClass("warning")):(i.wordlimit-t<10?o.one("#"+i.counterid).addClass("warning"):o.one("#"+i.counterid).removeClass("warning"),o.one("#"+i.counterid).removeClass("danger"))),setTimeout(function(){i.block=0},i.updateRate)}))},_getCount:function(){var t,e=0,i=this.get("host").getCleanHTML();return e=i&&(t=(t=(i=(i=(i=i.replace(this.spacer,"$1 ")).replace(/<.[^<>]*?>/g,"")).replace(/&nbsp;|&#160;/gi," ")).split(this.counter,-1)).filter(function(t){return""!=t.trim()}))?t.length:e}},{ATTRS:{wordlimits:{value:[0]}}})},"@VERSION@",{requires:["moodle-editor_atto-plugin"]});