"use strict";(self.webpackChunk=self.webpackChunk||[]).push([["gpsr-canvas.plugin"],{720:(t,e,i)=>{i.r(e),i.d(e,{default:()=>n});let{PluginBaseClass:l}=window;class n extends l{init(){if(!this.options.infos)return;let t=this.options.infos,e=window.getComputedStyle(this.el),i=this.el,l=i.parentNode.clientWidth,n=i.parentNode.clientHeight,o=window.devicePixelRatio;i.width=l*o,i.height=n*o,i.style.width=l+"px",i.style.height=n+"px",i.getContext("2d").scale(o,o);let s=e.fontSize+" '"+e.fontFamily.split(",").shift()+"'";document.fonts.load(s).finally(()=>{let l=i.getContext("2d");l.font=s,l.fillStyle=e.color,l.fillText(t.company,0,21),l.fillText(t.address,0,42),l.fillText([t.zip,t.city,t.country].join(" "),0,63),l.fillText(t.email,0,84)})}}n.options={infos:null}}}]);