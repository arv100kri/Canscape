<?php
$contents=$_POST["exportContent"];
$contents=str_replace("•","&#149;",$contents);
$contents=str_replace("whitespace","&#160",$contents);
$contents=str_replace("lt","<",$contents);
$contents=str_replace("gt",">",$contents);
$contents=str_replace("amp","&",$contents);

$htmlData="<html>
<head>
<link rel='stylesheet' href='css/jquery-ui-1.8.17.custom.css' />
<script type='text/javascript' src='js/jquery.min.js'></script>
<script type='text/javascript' src='js/jquery-ui-1.8.17.custom.min.js'></script>
<script type='text/javascript' src='js/jquery.svg.js'></script>
<script type='text/javascript' src='js/jquery.svganim.js'></script>
<script>
var selection=null;
var rotationAngle=0;
var Sx=1,Sy=1;
var Tx=0,Ty=0;
var Rx=0,Ry=0;

//Selection specifics

var sx,sy,tx,ty,rx,ry;

var s1x,s1y,t1x,t1y;

function storeTransform()
{
	var svg = document.getElementById('viewport');
	if(svg.getAttribute('transform')!=null)
	{
		var matrix=svg.getAttribute('transform').substring(7);
		var matrix_elements=matrix.split(',');
		matrix_elements[5]=matrix_elements[5].substring(0,matrix_elements[5].indexOf(')'));
		Sx=parseFloat(matrix_elements[0]);
		Sy=parseFloat(matrix_elements[3]);
		Tx=parseFloat(matrix_elements[4]);
		Ty=parseFloat(matrix_elements[5]);
		Rx=parseFloat(matrix_elements[1]);
		Ry=parseFloat(matrix_elements[2]);
	}
}
function removeTiles()
{
	var background = document.getElementById('background');
	background.parentNode.removeChild(background);
}
function zoomTo(element)
{
	highlightAll();
	storeTransform();
	var svg=document.getElementById('viewport');
	while(element.nodeName=='tspan')
	{
		element=element.parentNode;	
	}
	selection=element;
	var box = element.getBBox();
	var initx=box.x;
	var inity=box.y;
	var transform='';
	sx=1,sy=1,tx=0,ty=0,rx=0,ry=0;
	if(element.getAttribute('transform')!=null)
	{	
		transform=element.getAttribute('transform');
		if(element.getAttribute('transform').indexOf('matrix')>=0)
		{
			var matrix=element.getAttribute('transform').substring(7);
			var matrix_elements=matrix.split(',');
			matrix_elements[5]=matrix_elements[5].substring(0,matrix_elements[5].indexOf(')'));
			sx=parseFloat(matrix_elements[0]);
			sy=parseFloat(matrix_elements[3]);
			tx=parseFloat(matrix_elements[4]);
			ty=parseFloat(matrix_elements[5]);
			rx=parseFloat(matrix_elements[1]);
			ry=parseFloat(matrix_elements[2]);
			rotationAngle=Math.asin(rx)*180/Math.PI;	
		}
		if(element.getAttribute('transform').indexOf('rotate')>=0)				//get the exact rotation angle if available
		{
			var index=element.getAttribute('transform').indexOf('rotate');
			var str=element.getAttribute('transform').substring(index+7);
			var str_elements=str.split(',');
			rotationAngle=parseFloat(str_elements[0]);
			if(index==0)
			{
				transform='';
			}
			else
			{
				transform=transform.substring(0,index-1);	
			}
		}
	}
	box.x*=sx;
	box.x+=tx;
	box.x*=Sx;
	box.x+=Tx;

	box.y*=sy;
	box.y+=ty;
	box.y*=Sy;
	box.y+=Ty;
	
	box.width*=Sx*sx;
	box.height*=Sy*sy;

	var cx,cy;
	if(element.nodeName=='image' || element.nodeName=='rect')
	{
		cx=parseFloat(element.getAttribute('x'))+parseFloat(element.getAttribute('width'))/2;
		cy=parseFloat(element.getAttribute('y'))+parseFloat(element.getAttribute('height'))/2;
	}
	else if(element.nodeName=='circle' || element.nodeName=='ellipse')
	{
		cx=parseFloat(element.getAttribute('cx'));
		cy=parseFloat(element.getAttribute('cy'));
	}
	else if(element.nodeName=='line')
	{
		var x1=parseFloat(element.getAttribute('x1'));
		var x2=parseFloat(element.getAttribute('x2'));
		var y1=parseFloat(element.getAttribute('y1'));
		var y2=parseFloat(element.getAttribute('y2'));		
		cx=(x1+x2)/2;
		cy=(y1+y2)/2;
	}
	
	else if(element.nodeName=='text')
	{
		var x=parseFloat(element.getAttribute('x'));
		var y=parseFloat(element.getAttribute('y'));
		cx=x+10;
		cy=y+10;	
	}
	else if(element.nodeName=='g')
	{
		var view = element.getBBox();
		cx=(view.x+view.width)/2;
		cy=(view.y+view.height)/2;;
	}
	cx*=sx;
	cx+=tx;
	cx*=Sx;
	cx+=Tx;
	
	cy*=sy;
	cy+=ty;
	cy*=Sy;
	cy+=Ty;
	
	if(element.parentNode.getAttribute('class')=='frame')
	{
		if(element.parentNode.getAttribute('transform') != null)
		{
			var matrix=element.parentNode.getAttribute('transform').substring(7);
			var matrix_elements=matrix.split(',');
			matrix_elements[5]=matrix_elements[5].substring(0,matrix_elements[5].indexOf(')'));
			s1x = parseFloat(matrix_elements[0]);
			s1y = parseFloat(matrix_elements[3]);
			t1x = parseFloat(matrix_elements[4]);
			t1y = parseFloat(matrix_elements[5]);
		}
		else
		{ 
			s1x = 1;
			s1y = 1;
			t1x = 0;
			t1y = 0;
		}
		box.x*=s1x;
		box.x+=t1x;
		box.y*=s1y;
		box.y+=t1y;
		box.width*=s1x;
		box.height*=s1y;
		cx*=s1x;
		cx+=t1x;
		cy*=s1y;
		cy+=t1y;
	}
	
	var rotator;
	rotator='rotate('+0+','+cx+','+cy+')';
	if(transform.length!=0)
	{
		rotator=transform+','+rotator;	
	}
	element.setAttribute('transform',rotator);
	$('#svgcanvas').animate({svgViewBox : box.x+' '+box.y+' '+box.width+' '+box.height}, 650);
	if(element.getAttribute('class')!='hotSpot' && element.parentNode.getAttribute('class')!='frame')
	{
		for(var i = 0;i<window.frame.length;++i)
		{
			if(window.frame[i].getAttribute('item') != element.getAttribute('item'))
			{
				$(window.frame[i]).animate({svgOpacity: '0.25'}, 650);
			}
		}
	}
}
function highlightAll()
{
	for(var i = 0;i<window.frame.length;i++)
	{
		$(window.frame[i]).animate({svgOpacity:'1.0'}, 650);
	}
}
function zoomNormal()
{
	var cx,cy;
	if(selection!=null)
	{
		highlightAll();
		var transform='';
		if(selection.getAttribute('transform')!=null)
		{	
			transform=selection.getAttribute('transform');
			if(selection.getAttribute('transform').indexOf('rotate')>=0)				//get the exact rotation angle if available
			{
				var index=selection.getAttribute('transform').indexOf('rotate');
				if(index==0)
				{
					transform='';
				}
				else
				{
					transform=transform.substring(0,index-1);	
				}
			}
		}
		var cx,cy;
		if(selection.nodeName=='image' || selection.nodeName=='rect')
		{
			cx=parseFloat(selection.getAttribute('x'))+parseFloat(selection.getAttribute('width'))/2;
			cy=parseFloat(selection.getAttribute('y'))+parseFloat(selection.getAttribute('height'))/2;
		}
		else if(selection.nodeName=='circle' || selection.nodeName=='ellipse')
		{
			cx=parseFloat(selection.getAttribute('cx'));
			cy=parseFloat(selection.getAttribute('cy'));
		}
		else if(selection.nodeName=='line')
		{
			var x1=parseFloat(selection.getAttribute('x1'));
			var x2=parseFloat(selection.getAttribute('x2'));
			var y1=parseFloat(selection.getAttribute('y1'));
			var y2=parseFloat(selection.getAttribute('y2'));		
			cx=(x1+x2)/2;
			cy=(y1+y2)/2;
		}
		
		else if(selection.nodeName=='text')
		{
			var x=parseFloat(selection.getAttribute('x'));
			var y=parseFloat(selection.getAttribute('y'));
			cx=x+10;
			cy=y+10;	
		}
		else if(selection.nodeName=='g')
		{	
			var view = selection.getBBox();
			cx=(view.x+view.width)/2;
			cy=(view.y+view.height)/2;;
		}
		var rotator;
		rotator='rotate('+rotationAngle+','+cx+','+cy+')';
		if(transform.length!=0)
		{
			rotator=transform+','+rotator;	
		}
		selection.setAttribute('transform',rotator);
	}
	selection=null;
	$('#svgcanvas').animate({svgViewBox : 0+' '+0+' '+screen.width+' '+screen.height}, 650);
}

function swapArrayElements(array_object, index_a, index_b)
{
    var temp = array_object[index_a];
    array_object[index_a] = array_object[index_b];
    array_object[index_b] = temp;
}
$(function() 
{
	$( '#dialog').dialog(
	{
		autoOpen: false, 
		resizable : false,
		open: function(event, ui) 
		{ 
			//hide close button.
			$(this).parent().children().children('.ui-dialog-titlebar-close').hide();
		}
	});
});
function getElementByItem(itemName)
{
	var root = document.getElementById('viewport');
	var itemSelected=root.firstChild;
	while(itemSelected!=null)
	{
		if(itemSelected.nodeType==1 && itemSelected.getAttribute('item')==itemName)
			return itemSelected;
		itemSelected = itemSelected.nextSibling;
	}
	return null;
}
function removeChildren(node)
{
	while (node.hasChildNodes()) 
	{
    	node.removeChild(node.lastChild);
    }
}
function highlight(element,event)
{
	var posX = event.clientX;
	var posY = event.clientY;
	var cutoffX = screen.width/2;
	var cutoffY = screen.height/2;
	var X,Y;
	if(posX < cutoffX)
		X = 'right';
	else
		X = 'left';
	if(posY < cutoffY)
		Y = 'buttom';
	else
		Y = 'top';
	$(element).animate({svgOpacity : '0.5'}, 400);
	var textDescription = document.createTextNode(element.getAttribute('content'));
	removeChildren(document.getElementById('description'));
	document.getElementById('description').appendChild(textDescription);
	$('#dialog').dialog('option', 'title', element.getAttribute('title'));
	$('#dialog').dialog('option', 'position', [X,Y]);
	$('#dialog').dialog('open');
}
function lowlight(element)
{
	$(element).animate({svgOpacity : '0.0'}, 400);
	$('#dialog').dialog('close');
}
function loadHotSpots()
{
	var root = document.getElementById('viewport');
	var itemSelected=root.firstChild;
	while(itemSelected!=null)
	{
		if(itemSelected.nodeType==1 && itemSelected.getAttribute('class')=='hotSpot')
		{
			lowlight(itemSelected);
			itemSelected.setAttribute('onmouseover','highlight(this,event)');
			itemSelected.setAttribute('onmouseout','lowlight(this)');
		}
		itemSelected = itemSelected.nextSibling;
	}
}
var frame = new Array();
var svgNS = 'http://www.w3.org/2000/svg';
var xlinkNS = 'http://www.w3.org/1999/xlink';
function loadFrames()
{
	var root = document.getElementById('viewport');
	var itemSelected=root.firstChild;
	while(itemSelected!=null)
	{
		if(itemSelected.nodeType==1 && (itemSelected.getAttribute('class')=='shape' || itemSelected.getAttribute('class')=='frame'))
		{
			window.frame.push(itemSelected);
		}
		if(itemSelected.nodeType==1 && itemSelected.getAttribute('class')=='hotSpot')
		{
			var x = itemSelected.getBBox().x;
			var y = itemSelected.getBBox().y;
			
			sx=1,sy=1,tx=0,ty=0,rx=0,ry=0;
			if(itemSelected.getAttribute('transform')!=null)
			{	
				transform=itemSelected.getAttribute('transform');
				if(itemSelected.getAttribute('transform').indexOf('matrix')>=0)
				{
					var matrix=itemSelected.getAttribute('transform').substring(7);
					var matrix_elements=matrix.split(',');
					matrix_elements[5]=matrix_elements[5].substring(0,matrix_elements[5].indexOf(')'));
					sx=parseFloat(matrix_elements[0]);
					sy=parseFloat(matrix_elements[3]);
					tx=parseFloat(matrix_elements[4]);
					ty=parseFloat(matrix_elements[5]);
					rx=parseFloat(matrix_elements[1]);
					ry=parseFloat(matrix_elements[2]);
					storeTransform();
					x*=sx;
					x+=tx;
					y*=sy;
					y+=ty-16;
				}
			}	
			var callImg = document.createElementNS(svgNS,'image');
			callImg.setAttributeNS(xlinkNS,'xlink:href','pImages/callout.svg');	
			callImg.setAttributeNS(null,'x',x);
			callImg.setAttributeNS(null,'y',y);
			callImg.setAttributeNS(null,'width',16);
			callImg.setAttributeNS(null,'height',16);
			document.getElementById('viewport').appendChild(callImg);
		}
		itemSelected = itemSelected.nextSibling;
	}
	maxNum=window.frame.length+1;
	for(var i = 0;i<window.frame.length;i++)
	{
		for(var j = 0;j<window.frame.length;j++)
		{
			var inode = window.frame[i];
			var jnode = window.frame[j];
			var ifno = parseInt(inode.getAttribute('fno'));
			var jfno = parseInt(jnode.getAttribute('fno'));
			if(ifno < jfno)
			{
				swapArrayElements(window.frame,i,j);
			}
		}
	}
}
var viewNumber = -1;
var maxNum;
var minNum=0;
function moveFrame(event)
{
	var e = window.event || event;
	switch(e.keyCode)
	{
		case 37:
		case 33:
		if(viewNumber <minNum)
		{
			return;
		}
		viewNumber --;
		zoomTo(window.frame[viewNumber]);
		break;
		case 39:
		case 34:
			if(viewNumber >maxNum)
			{
				viewNumber=maxNum;
				return;
			}
			viewNumber ++;
			zoomTo(window.frame[viewNumber]);
			break;
		case 40:
			zoomNormal();
			break;
		case 38:	
			viewNumber =-1;
			zoomNormal();
			break;				
				
		case 35: 
			viewNumber=maxNum;
			zoomTo(window.frame[viewNumber]);								
			break;
										
		case 36: 
			viewNumber=minNum;
			zoomTo(window.frame[viewNumber]);					
			break;
	}
}
</script>
</head>
<body>
<div id='dialog' title='Title goes here' style='display:none;height:100%'>
	<p id='description' style='font-family:serif;text-align:justify;'>Description goes here</p>
</div>

<div id='svgbasics' tabindex='0'>
<svg id='svgcanvas' xmlns='http://www.w3.org/2000/svg' version='1.1' id='svgcanvas' xmlns:xlink='http://www.w3.org/1999/xlink' onclick='zoomNormal()' onload='document.getElementById(\"svgbasics\").focus();loadFrames();loadHotSpots();removeTiles();'>".$contents." 
</svg>
</div>
<script type='text/javascript'>
document.getElementById('svgbasics').addEventListener('keydown', moveFrame, false);
</script>
</body>
</html>";
$fn=$_POST["fileName"].".html";
$fh = fopen($fn, 'w');
fwrite($fh, $htmlData);
fclose($fh);
?>
