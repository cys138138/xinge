(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-user-index-index"],{1195:function(t,i,e){var a=e("ee53");"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var n=e("4f06").default;n("3e808d4a",a,!0,{sourceMap:!1,shadowMode:!1})},6653:function(t,i,e){"use strict";var a=function(){var t=this,i=t.$createElement,e=t._self._c||i;return e("v-uni-view",{staticClass:"content"},[e("v-uni-view",{staticClass:"top-box"},[e("v-uni-image",{attrs:{src:"https://wish.xingyuanxingqiu.com/static/upload/045f7456269c849b/d3d21e4caec26ba6.png"}}),e("v-uni-view",{staticClass:"top-head-box"},[e("v-uni-image",{staticClass:"user-head",attrs:{src:t.user.head_img_url,mode:"widthFix"}}),e("v-uni-text",{staticClass:"user-name"},[t._v(t._s(t.user.username))])],1)],1),e("v-uni-view",{staticClass:"center-main"},[e("v-uni-view",{staticClass:"list-box",attrs:{"data-url":"/pages/user/info/info"},on:{click:function(i){i=t.$handleEvent(i),t.goPage(i)}}},[e("v-uni-view",{staticClass:"list-box-left"},[e("v-uni-image",{staticClass:"left-icon-img",attrs:{src:"../../../static/user/info_icon.png",mode:"widthFix"}}),e("v-uni-text",[t._v("我的资料")])],1),e("v-uni-image",{staticClass:"right-icon-img",attrs:{src:"../../../static/user/right_icon.png",mode:"widthFix"}})],1),e("v-uni-view",{staticClass:"list-box",attrs:{"data-url":"/pages/user/order/order"},on:{click:function(i){i=t.$handleEvent(i),t.goPage(i)}}},[e("v-uni-view",{staticClass:"list-box-left"},[e("v-uni-image",{staticClass:"left-icon-img",attrs:{src:"../../../static/user/order_icon.png",mode:"widthFix"}}),e("v-uni-text",[t._v("我的订单")])],1),e("v-uni-image",{staticClass:"right-icon-img",attrs:{src:"../../../static/user/right_icon.png",mode:"widthFix"}})],1),e("v-uni-view",{staticClass:"list-box",attrs:{"data-url":"/pages/user/share/share"},on:{click:function(i){i=t.$handleEvent(i),t.goPage(i)}}},[e("v-uni-view",{staticClass:"list-box-left"},[e("v-uni-image",{staticClass:"left-icon-img",attrs:{src:"../../../static/user/share_icon.png",mode:"widthFix"}}),e("v-uni-text",[t._v("我的分享")])],1),e("v-uni-image",{staticClass:"right-icon-img",attrs:{src:"../../../static/user/right_icon.png",mode:"widthFix"}})],1),e("v-uni-view",{staticClass:"list-box",attrs:{"data-url":"/pages/user/account_info/account_info"},on:{click:function(i){i=t.$handleEvent(i),t.goPage(i)}}},[e("v-uni-view",{staticClass:"list-box-left"},[e("v-uni-image",{staticClass:"left-icon-img",attrs:{src:"../../../static/user/acce_icon.png",mode:"widthFix"}}),e("v-uni-text",[t._v("我的账户")])],1),e("v-uni-image",{staticClass:"right-icon-img",attrs:{src:"../../../static/user/right_icon.png",mode:"widthFix"}})],1),e("v-uni-view",{staticClass:"list-box",attrs:{"data-url":"/pages/user/feedback/feedback"},on:{click:function(i){i=t.$handleEvent(i),t.goPage(i)}}},[e("v-uni-view",{staticClass:"list-box-left"},[e("v-uni-image",{staticClass:"left-icon-img",attrs:{src:"../../../static/user/bbs_icon.png",mode:"widthFix"}}),e("v-uni-text",[t._v("意见反馈")])],1),e("v-uni-image",{staticClass:"right-icon-img",attrs:{src:"../../../static/user/right_icon.png",mode:"widthFix"}})],1)],1)],1)},n=[];e.d(i,"a",function(){return a}),e.d(i,"b",function(){return n})},"80a2":function(t,i,e){"use strict";var a=e("1195"),n=e.n(a);n.a},"93d4":function(t,i,e){"use strict";e.r(i);var a=e("6653"),n=e("9f84");for(var s in n)"default"!==s&&function(t){e.d(i,t,function(){return n[t]})}(s);e("80a2");var o=e("2877"),r=Object(o["a"])(n["default"],a["a"],a["b"],!1,null,"a9739532",null);i["default"]=r.exports},"9f84":function(t,i,e){"use strict";e.r(i);var a=e("a752"),n=e.n(a);for(var s in a)"default"!==s&&function(t){e.d(i,t,function(){return a[t]})}(s);i["default"]=n.a},a752:function(t,i,e){"use strict";function a(t,i){return o(t)||s(t,i)||n()}function n(){throw new TypeError("Invalid attempt to destructure non-iterable instance")}function s(t,i){var e=[],a=!0,n=!1,s=void 0;try{for(var o,r=t[Symbol.iterator]();!(a=(o=r.next()).done);a=!0)if(e.push(o.value),i&&e.length===i)break}catch(l){n=!0,s=l}finally{try{a||null==r["return"]||r["return"]()}finally{if(n)throw s}}return e}function o(t){if(Array.isArray(t))return t}Object.defineProperty(i,"__esModule",{value:!0}),i.default=void 0;var r={data:function(){return{user:{}}},onLoad:function(){this.getUserBaseInfo()},methods:{goPage:function(t){uni.navigateTo({url:t.currentTarget.dataset.url})},getUserBaseInfo:function(){var t=this;t.netApi.request({url:"/api/user/getUserBaseInfo.html",data:{}}).then(function(i){var e=a(i,2),n=(e[0],e[1]),s=n.data;t.user=s.data})}},onPullDownRefresh:function(){this.getUserBaseInfo(),uni.stopPullDownRefresh()}};i.default=r},ee53:function(t,i,e){i=t.exports=e("2350")(!1),i.push([t.i,".content[data-v-a9739532]{position:relative;width:100%;height:100%;background:url(https://wish.xingyuanxingqiu.com/static/upload/90f4f57c1ed8879d/191138299c52c78b.jpg);background-size:100% 100%;background-position:0 0;background-repeat:no-repeat;padding-bottom:%?100?%}.top-box[data-v-a9739532]{position:relative;height:%?320?%}.top-box uni-image[data-v-a9739532]{width:%?750?%;height:%?318?%}.top-head-box[data-v-a9739532]{position:absolute;top:%?83?%;left:%?62?%;display:-webkit-inline-box;display:-webkit-inline-flex;display:-ms-inline-flexbox;display:inline-flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-flow:row;-ms-flex-flow:row;flex-flow:row;-webkit-box-align:center;-webkit-align-items:center;-ms-flex-align:center;align-items:center;color:#fff}.top-box .user-head[data-v-a9739532]{width:%?106?%;height:%?106?%;border-radius:%?53?%;margin-right:%?38?%}.user-name[data-v-a9739532]{font-size:%?36?%}.center-main[data-v-a9739532]{position:relative;top:%?-96?%;height:%?900?%;width:%?630?%;margin:0 auto;background:#fff;border-radius:%?12?%;padding:%?26?% %?26?% %?26?% %?36?%;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-flow:column;-ms-flex-flow:column;flex-flow:column}.list-box[data-v-a9739532]{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-flow:row;-ms-flex-flow:row;flex-flow:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;-ms-flex-pack:justify;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;-ms-flex-align:center;align-items:center;border-bottom:1px solid #cecece;padding:%?26?% 0}.list-box-left[data-v-a9739532]{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-flow:row;-ms-flex-flow:row;flex-flow:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;-ms-flex-pack:justify;justify-content:space-between;padding-left:%?19?%}.left-icon-img[data-v-a9739532]{width:%?52?%;height:%?52?%;margin-right:%?52?%}.right-icon-img[data-v-a9739532]{width:%?21?%;height:%?40?%;margin-right:%?28?%}",""])}}]);