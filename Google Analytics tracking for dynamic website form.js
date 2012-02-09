function getRadioValue (radioButtonOrGroup) {
  var buttonsLength = radioButtonOrGroup.length;
  if (buttonsLength) { // group
    for (var b = 0; b < buttonsLength; b++)
      if (radioButtonOrGroup[b].checked)
        return radioButtonOrGroup[b].value;

  }
  else if (radioButtonOrGroup.checked)
    return radioButtonOrGroup.value;
  return null;
}
function noenter() {
  return !(window.event && window.event.keyCode == 13); 
  }
  
function submitMe(a){

if(a==1){
if(document.form1.url.value=="https://"){
alert("Please enter your web site URL");
}else{
if(getRadioValue(document.form1.browsers)=="ie"){
openWindow(document.form1.url.value,'two','width=750,height=200,left=50,screenX=250,top=10,screenY=400,status=yes,toolbar=yes,location=yes');
openWindow('/popups/ie.html','one','width=750,height=350,left=50,screenX=250,top=400,screenY=400');
pageTracker._trackEvent("High or Low Assurance", "Internet Explorer");
}
if(getRadioValue(document.form1.browsers)=="ie7"){
openWindow('/popups/ie7.html','one','width=750,height=400,left=50,screenX=250,top=10,screenY=400');
openWindow(document.form1.url.value,'two','width=750,height=250,left=50,screenX=250,top=450,screenY=400,status=yes,toolbar=yes,location=yes');
pageTracker._trackEvent("High or Low Assurance", "Internet Explorer 7");
}
if(getRadioValue(document.form1.browsers)=="opera"){
openWindow('/popups/opera.html','one','width=780,height=300,left=50,screenX=250,top=10,screenY=400');
openWindow(document.form1.url.value,'two','width=780,height=250,left=50,screenX=250,top=350,screenY=350,status=yes,toolbar=yes,location=yes');
pageTracker._trackEvent("High or Low Assurance", "Opera");
}
if(getRadioValue(document.form1.browsers)=="firefox"){
openWindow('/popups/firefox.html','one','width=700,height=340,left=50,screenX=250,top=10,screenY=400');
openWindow(document.form1.url.value,'two','width=750,height=200,left=50,screenX=250,top=400,screenY=400,status=yes,toolbar=yes,location=yes');
pageTracker._trackEvent("High or Low Assurance", "Firefox");
}
}
}
if(a==2){
 rVal = getRadioValue(document.form2.comp);
 switch (rVal) {
    case "comparisons/price.html":
        pageTracker._trackEvent("Compare SSL", "Price");
        break;
    case "comparisons/warranty.html":
        pageTracker._trackEvent("Compare SSL", "Warranty");
        break;   
    case "comparisons/share.html":
        pageTracker._trackEvent("Compare SSL", "Global Market Share");
        break;        
    case "comparisons/trial.html":
        pageTracker._trackEvent("Compare SSL", "Free Trial");
        break;
    default:
        break;
 }
 window.location=rVal;
}
if(a==3){
 rVal = getRadioValue(document.form3.page);
 switch (rVal) {
    case "what_is_ssl.html":
        pageTracker._trackEvent("Knowledge Base", "What is SSL?");
        break;
    case "why_ssl.html":
        pageTracker._trackEvent("Knowledge Base", "Why do I need SSL?");
        break;   
    case "ssl/high_assurance.html":
        pageTracker._trackEvent("Knowledge Base", "What is High Assurance?");
        break;        
    case "ssl/low_assurance.html":
        pageTracker._trackEvent("Knowledge Base", "What is Low Assurance?");
        break;
    default:
        break;
 } 
 window.location=rVal;
}
if(a==4)
{
    if(document.form1.url.value=="https://"){
        alert("Please enter your web site URL");
    }
    else
    {
        url = document.form1.url.value;
        final_url="https://sslanalyzer.comodoca.com/?url=" + url;
        window.open(final_url,'Analyzer','width=700,height=650,left=50,screenX=250,top=10,screenY=400,scrollbars=1');
        pageTracker._trackEvent("SSL Analyzer", "GO");
    }
}
}

function openWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}