(()=>{var e={},r={};function t(o){var n=r[o];if(void 0!==n)return n.exports;var a=r[o]={exports:{}};return e[o](a,a.exports,t),a.exports}t.m=e,(()=>{t.d=(e,r)=>{for(var o in r)t.o(r,o)&&!t.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:r[o]})}})(),(()=>{t.f={},t.e=e=>Promise.all(Object.keys(t.f).reduce((r,o)=>(t.f[o](e,r),r),[]))})(),(()=>{t.u=e=>"./js/aggro-warexo-plugin/"+e+".05ac4f.js"})(),(()=>{t.miniCssF=e=>{}})(),(()=>{t.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||Function("return this")()}catch(e){if("object"==typeof window)return window}}()})(),(()=>{t.o=(e,r)=>Object.prototype.hasOwnProperty.call(e,r)})(),(()=>{var e={};t.l=(r,o,n,a)=>{if(e[r]){e[r].push(o);return}if(void 0!==n)for(var i,l,u=document.getElementsByTagName("script"),s=0;s<u.length;s++){var c=u[s];if(c.getAttribute("src")==r){i=c;break}}i||(l=!0,(i=document.createElement("script")).charset="utf-8",i.timeout=120,t.nc&&i.setAttribute("nonce",t.nc),i.src=r),e[r]=[o];var p=(t,o)=>{i.onerror=i.onload=null,clearTimeout(d);var n=e[r];if(delete e[r],i.parentNode&&i.parentNode.removeChild(i),n&&n.forEach(e=>e(o)),t)return t(o)},d=setTimeout(p.bind(null,void 0,{type:"timeout",target:i}),12e4);i.onerror=p.bind(null,i.onerror),i.onload=p.bind(null,i.onload),l&&document.head.appendChild(i)}})(),(()=>{t.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}})(),(()=>{t.g.importScripts&&(e=t.g.location+"");var e,r=t.g.document;if(!e&&r&&(r.currentScript&&(e=r.currentScript.src),!e)){var o=r.getElementsByTagName("script");if(o.length)for(var n=o.length-1;n>-1&&!e;)e=o[n--].src}if(!e)throw Error("Automatic publicPath is not supported in this browser");e=e.replace(/#.*$/,"").replace(/\?.*$/,"").replace(/\/[^\/]+$/,"/"),t.p=e+"../../"})(),(()=>{var e={"aggro-warexo-plugin":0};t.f.j=(r,o)=>{var n=t.o(e,r)?e[r]:void 0;if(0!==n){if(n)o.push(n[2]);else{var a=new Promise((t,o)=>n=e[r]=[t,o]);o.push(n[2]=a);var i=t.p+t.u(r),l=Error();t.l(i,o=>{if(t.o(e,r)&&(0!==(n=e[r])&&(e[r]=void 0),n)){var a=o&&("load"===o.type?"missing":o.type),i=o&&o.target&&o.target.src;l.message="Loading chunk "+r+" failed.\n("+a+": "+i+")",l.name="ChunkLoadError",l.type=a,l.request=i,n[1](l)}},"chunk-"+r,r)}}};var r=(r,o)=>{var n,a,[i,l,u]=o,s=0;if(i.some(r=>0!==e[r])){for(n in l)t.o(l,n)&&(t.m[n]=l[n]);u&&u(t)}for(r&&r(o);s<i.length;s++)a=i[s],t.o(e,a)&&e[a]&&e[a][0](),e[a]=0},o=self.webpackChunk=self.webpackChunk||[];o.forEach(r.bind(null,0)),o.push=r.bind(null,o.push.bind(o))})(),window.PluginManager.register("GpsrCanvas",()=>t.e("gpsr-canvas.plugin").then(t.bind(t,501)),"[data-gpsr-canvas]")})();