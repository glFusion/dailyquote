/*  $Id: toggleEnabled.js 12 2009-07-09 18:53:04Z root $
 */
var DQ_xmlHttp;

function DQ_toggleEnabled(newval, id, type, base_url)
{
  DQ_xmlHttp = DQ_GetXmlHttpObject();
  if (DQ_xmlHttp==null) {
    alert ("Browser does not support HTTP Request")
    return
  }
  var url=base_url + "/admin/plugins/dailyquote/ajax.php?action=toggleEnabled";
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
  var newstate;

  if (DQ_xmlHttp.readyState==4 || DQ_xmlHttp.readyState=="complete")
  {
    xmlDoc=DQ_xmlHttp.responseXML;
    id = xmlDoc.getElementsByTagName("id")[0].childNodes[0].nodeValue;
    imgurl = xmlDoc.getElementsByTagName("imgurl")[0].childNodes[0].nodeValue;
    baseurl = xmlDoc.getElementsByTagName("baseurl")[0].childNodes[0].nodeValue;
    type = xmlDoc.getElementsByTagName("type")[0].childNodes[0].nodeValue;
    if (xmlDoc.getElementsByTagName("newval")[0].childNodes[0].nodeValue == 1) {
        newval = 0;
    } else {
        newval = 1;
    }
    newhtml = 
        " <img src=\""+imgurl+"\" " +
        "style=\"display:inline; width:16px; height:16px;\" " +
        "onclick='DQ_toggleEnabled("+newval+", \""+id+"\", \""+type+"\", \""+baseurl+"\");" +
        "' /> ";
    document.getElementById("togena"+id).innerHTML=newhtml;
  }

        //"width=\"16\" height=\"16\" " +
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

