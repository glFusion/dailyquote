/*
*   Toggle enabled flag for quotes and categories
*/
var DQ_xmlHttp;

//function DQ_toggleEnabled(oldval, id, type, base_url)
//{
function DQ_toggleEnabled(ck, id, type)
{
  if (ck.checked) {
    newval=1;
  } else {
    newval=0;
  }

  DQ_xmlHttp = DQ_GetXmlHttpObject();
  if (DQ_xmlHttp==null) {
    alert ("Browser does not support HTTP Request")
    return
  }
  var url = glfusionSiteUrl + "/admin/plugins/dailyquote/ajax.php?action=toggleEnabled";
  url=url+"&id="+id;
  url=url+"&type="+type;
  url=url+"&newval="+newval;
  url=url+"&sid="+Math.random();
  DQ_xmlHttp.onreadystatechange=DQ_sc_Enabled;
  DQ_xmlHttp.open("GET",url,true);
  DQ_xmlHttp.send(null);
}

function DQ_sc_Enabled()
{
  if (DQ_xmlHttp.readyState==4 || DQ_xmlHttp.readyState=="complete") {
    jsonObj = JSON.parse(DQ_xmlHttp.responseText)

    // Get the checkbox element ID
    var elem = 'togena' + jsonObj.id;
    if (jsonObj.newval == 1) {
        document.getElementById(elem).checked = true;
    } else {
        document.getElementById(elem).checked = false;
    }
  }
}

function DQ_GetXmlHttpObject()
{
  var objXMLHttp=null
  if (window.XMLHttpRequest)
  {
    objXMLHttp=new XMLHttpRequest()
  }
  else if (window.ActiveXObject)
  {
    objXMLHttp=new ActiveXObject("Microsoft.XMLHTTP")
  }
  return objXMLHttp
}

