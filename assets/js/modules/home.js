(function(){
const carousels=document.querySelectorAll("[data-home-sponsor-carousel]");
if(!carousels.length){return;}
carousels.forEach((carousel)=>{
const slides=Array.from(carousel.querySelectorAll(".home-media-slide"));
if(!slides.length){return;}
let index=0;
let timer=null;
const show=(nextIndex)=>{
index=(nextIndex+slides.length)%slides.length;
slides.forEach((slide,slideIndex)=>{
const active=slideIndex===index;
slide.classList.toggle("is-active",active);
slide.setAttribute("aria-hidden",active?"false":"true");
if(slide instanceof HTMLAnchorElement){
slide.tabIndex=active?0:-1;
}
});
};
const start=()=>{
if(timer||slides.length<2||window.matchMedia("(prefers-reduced-motion: reduce)").matches){return;}
timer=window.setInterval(()=>show(index+1),3500);
};
const stop=()=>{if(!timer){return;}window.clearInterval(timer);timer=null;};
carousel.classList.add("is-ready");
show(0);
carousel.addEventListener("mouseenter",stop);
carousel.addEventListener("mouseleave",start);
carousel.addEventListener("focusin",stop);
carousel.addEventListener("focusout",start);
document.addEventListener("visibilitychange",()=>{if(document.hidden){stop();}else{start();}});
start();
});
})();

(function(){
const root=document.querySelector("[data-ham-weather-root]");
if(!root){return;}
const weather=root.querySelector("[data-ham-weather-weather]");
const propagationWrapper=root.querySelector("[data-ham-weather-propagation-wrapper]");
const propagation=propagationWrapper?propagationWrapper.querySelector("[data-ham-weather-propagation]"):null;
const advice=root.querySelector("[data-ham-weather-advice]");
const updated=root.querySelector("[data-ham-weather-updated]");
const refreshBtn=root.querySelector("[data-ham-weather-refresh]");
const label=root.getAttribute("data-updated-label")||"";
const url=root.getAttribute("data-refresh-url");
const refreshMs=Number(root.getAttribute("data-refresh-ms")||"900000");
if(!advice||!url||refreshMs<60000){return;}
let lastUpdateAt=Date.now();
let isInViewport=false;
let isRefreshing=false;
const renderUpdated=(iso)=>{if(!updated){return;}const value=iso?new Date(iso):new Date();updated.textContent=label+" "+value.toLocaleString();};
const setRefreshing=(state)=>{isRefreshing=state;if(refreshBtn){refreshBtn.disabled=state;}};
const setPropagation=(value)=>{if(!propagationWrapper||!propagation){return;}const html=typeof value==="string"?value:"";if(html.trim()===""){propagationWrapper.style.display="none";propagation.innerHTML="";return;}propagationWrapper.style.display="";propagation.innerHTML=html;};
const tick=async(force=false)=>{if(isRefreshing||(!isInViewport&&!force)){return;}setRefreshing(true);try{const sep=url.includes("?")?"&":"?";const endpoint=url+sep+"_ts="+Date.now();const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),10000);const res=await fetch(endpoint,{cache:"no-store",credentials:"same-origin",headers:{"X-Requested-With":"XMLHttpRequest","Accept":"application/json"},signal:controller.signal});clearTimeout(timeout);if(!res.ok){return;}const payload=await res.json();if(payload&&typeof payload.weather==="string"&&weather){weather.innerHTML=payload.weather;}setPropagation(payload?payload.propagation:"");if(payload&&typeof payload.advice==="string"){advice.innerHTML=payload.advice;}lastUpdateAt=Date.now();renderUpdated(payload&&typeof payload.updated_at==="string"?payload.updated_at:undefined);}catch(_e){}finally{setRefreshing(false);}};
if(refreshBtn){refreshBtn.addEventListener("click",()=>{tick(true);});}
setPropagation(propagation?propagation.innerHTML:"");
renderUpdated();
setInterval(tick,refreshMs);
const enableUpdates=()=>{if(isInViewport){return;}isInViewport=true;tick();};
if("IntersectionObserver" in window){const observer=new IntersectionObserver((entries)=>{entries.forEach((entry)=>{if(entry.isIntersecting){enableUpdates();observer.disconnect();}});},{threshold:0.1});observer.observe(root);}else{enableUpdates();}
document.addEventListener("visibilitychange",()=>{if(document.visibilityState==="visible"&&Date.now()-lastUpdateAt>=refreshMs){tick();}});
})();

(function(){
const calendars=document.querySelectorAll("[data-home-events-calendar]");
if(!calendars.length){return;}
calendars.forEach((calendarEl)=>{
let config={};
try{config=calendarEl.dataset.calendarConfig?JSON.parse(calendarEl.dataset.calendarConfig):{};}catch(_e){config={};}
if(!window.FullCalendar){
const message=config.loadError||"Calendar unavailable.";
calendarEl.insertAdjacentHTML("beforeend",`<p class="help">${message}</p>`);
return;
}
const calendar=new FullCalendar.Calendar(calendarEl,{
locale:config.locale||document.documentElement.lang||"fr",
firstDay:1,
height:"auto",
initialView:config.initialView||"listMonth",
headerToolbar:false,
buttonText:config.buttonText||{},
events:config.eventsUrl||"",
eventClick(info){
if(info.event.url){
info.jsEvent.preventDefault();
window.location.href=info.event.url;
}
}
});
calendar.render();
});
})();
