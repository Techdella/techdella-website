/* TechDella consent + analytics loader (§12.3, §8.4). Printed inline in <head> by ads/module.php
   before any Google tag; window.__tdc is replaced with the JSON config. Keep consent.min.js < 2 KB.
   Regenerate: npx esbuild consent.js --minify --target=es2015 --legal-comments=none > consent.min.js */
(function(w,d,c){var l=w.dataLayer=w.dataLayer||[];function g(){l.push(arguments)}w.gtag=w.gtag||g;
var D='denied',G='granted',tz='';
g('consent','default',{ad_storage:D,ad_user_data:D,ad_personalization:D,analytics_storage:D,region:c.r,wait_for_update:500});
g('consent','default',{ad_storage:G,ad_user_data:G,ad_personalization:G,analytics_storage:G});
g('set','ads_data_redaction',true);
try{tz=Intl.DateTimeFormat().resolvedOptions().timeZone||''}catch{/* no Intl */}
var eu=/^(Europe|Arctic)\/|^Atlantic\/(Reykjavik|Canary|Madeira|Azores|Faroe)/.test(tz),s={a:!eu,n:!eu},q={a:[],n:[]},ev=[];
function fire(k){s[k]=1;var f=q[k];q[k]=[];f.forEach(function(x){x()})}
function on(k,f){s[k]?f():q[k].push(f)}
function js(u){var e=d.createElement('script');e.async=true;e.src=u;d.head.appendChild(e)}
function idle(f){w.requestIdleCallback?w.requestIdleCallback(f,{timeout:4000}):setTimeout(f,2000)}
function tcf(n){if(!w.__tcfapi)return n<20&&setTimeout(function(){tcf(n+1)},500);w.__tcfapi('addEventListener',2,function(t,ok){if(!ok||!t)return;var y=t.gdprApplies===false;if(y||t.eventStatus=='tcloaded'||t.eventStatus=='useractioncomplete'){fire('n');if(y||(t.purpose&&t.purpose.consents&&t.purpose.consents[1]))fire('a')}})}
w.tdConsent={eu:eu,analytics:function(f){on('a',f)},ads:function(f){on('n',f)},q:ev};
if(c.p&&(c.ad||eu||c.all)){var cmp=function(){js('https://fundingchoicesmessages.google.com/i/'+c.p+'?ers=1');tcf(0)};c.ad?cmp():idle(cmp)}
on('a',function(){idle(function(){if(c.ga){js('https://www.googletagmanager.com/gtag/js?id='+c.ga);g('js',new Date());g('config',c.ga);w.tdConsent.ga=1;ev.splice(0).forEach(function(e){g('event',e[0],e[1])})}
if(c.gtm){l.push({'gtm.start':+new Date(),event:'gtm.js'});js('https://www.googletagmanager.com/gtm.js?id='+c.gtm)}
if(c.cl){w.clarity=w.clarity||function(){(w.clarity.q=w.clarity.q||[]).push(arguments)};js('https://www.clarity.ms/tag/'+c.cl)}})});
d.addEventListener('DOMContentLoaded',function(){var t=w.td;if(!t||!t.track||!c.ga||c.gtm)return;var o=t.track;t.track=function(e,a){o(e,a);w.tdConsent.ga?g('event',e,a||{}):ev.push([e,a||{}])}})
})(window,document,window.__tdc);
