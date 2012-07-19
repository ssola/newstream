<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Newstream Lab</title>
	<script src="http://newstream.loc:8080/socket.io/socket.io.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
	<link rel="stylesheet" href="/application/views/css/flick/jquery-ui-1.8.21.custom.css" type="text/css" />
	<link rel="stylesheet" href="/application/views/css/selector.css" type="text/css" />
	<style>
	body {background:#eee;font-family:Arial;}
	ul {list-style:none;}
	ul li {cursor:pointer;list-style:none; border:1px solid #ccc; background:#fff;width:80%;padding:10px;margin-bottom:20px;}
	ul li:hover{border:1px solid #999;}
	ul li h2 {margin:4px;font-size:20px;color:#222;}
	ul li p {color:#555; margin:10px 5px 5px;}
	ul li p span {color:#147AFF;font-size:13px;}
	.shadow {
	  -moz-box-shadow:    3px 3px 5px 2px #ccc;
	  -webkit-box-shadow: 3px 3px 5px 2px #ccc;
	  box-shadow:         3px 3px 5px 2px #ccc;
	  -webkit-border-radius: 5px;
	  -moz-border-radius: 5px;
	  border-radius: 5px;	  
	}	
	.shadow:hover{
	  -moz-box-shadow:    3px 3px 5px 2px #999;
	  -webkit-box-shadow: 3px 3px 5px 2px #999;
	  box-shadow:         3px 3px 5px 2px #999;		
	}

	#newData {
		margin-left:40px;
		background:#B6DBF2 !important;
		border:1px solid #5A9DC7;
		padding:10px;
		width:75%;
		display:none;
		cursor:pointer;
	}

	#rightSide{
		position: relative;
		width:52%;
	}

	#rightSide p {color:#666;}

	#leftSide {width:35%;}

	.social {margin-top:10px;}
	#navigator{background:#000;color:#fff;padding:10px;width:780px;}
	#navigator a {color:#147AFF;}
	#header {margin-top:20px;height:100px;overflow: hidden;}
	#header-leftSide {width:35%;float:left;}
	#header-rightSide{width:60%;margin-left:3%;float:left;}

	</style>
</style>
<script>

$(document).ready(function() {
	var socket = io.connect('http://newstream.loc:8080');
	socket.on('connect', function(){
		console.log('connected');
	});

	var counter = 0;
	var firstTime = true;
	var mutex = false;

	socket.on('newArticle', function(data){
		counter = counter + 1;
		$('#newData').show();
		$('#newData').html( "Hey! <strong>" +counter + "</strong> new articles have been found");
		document.title = "("+counter+") Newstream by BeIdea Labs";
		if ( firstTime == true ) {
			if ( counter > 0 ) {
				socket.emit("sendMeArticles");
			}

			firstTime = false;
		}
	});

	socket.on('getArticles', function(articles) {
		console.log(articles);
		$.each(articles, function(article){
			var data = articles[article];
			if ( data ) {
				$('#articles').prepend('<li class="shadow" style="background:#eee" data-url="'+data.url+'"><h2>'+data.title+'</h2><p><strong>'+data.provider+'</strong> - '+data.description+'</p> <p id="'+data._id+'" class="social"></p></li>').fadeIn('slow');
				$('#'+data._id).html('<iframe src="//www.facebook.com/plugins/like.php?href='+data.url+'&amp;send=false&amp;layout=button_count&amp;width=300&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font&amp;height=21&amp;appId=420896694607664" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:300px; height:25px;" allowTransparency="true"></iframe>');
			}	
		});

		mutex = false;
		counter = 0;
		$('#newData').hide();
	});

	$('.shadow').live("click", function(){
		var url = $(this).attr('data-url');
		if ( $('#iframe').length <= 0 ) {
			$('#rightSide').html('<div id="navigator">Url</div><iframe id="iframe" width="800px" height="800px" src="" sandbox="allow-forms allow-scripts"></iframe>');
		}

		$('#navigator').html("<a href='"+url+"' target='_blank'>"+url+"</a>");
		$('#iframe').attr('src', url);
		$(this).css('background', '#ffffff');	
	});

	$('#newData').click(function(){ 
		if ( mutex == false ) {
			mutex = true;
			if ( counter > 0 ) {
				counter = 0;
				document.title = "Newstream by BeIdea Labs";				
				socket.emit("sendMeArticles");
			}
		}
	});	

	$('.changeLanguage').click(function(){
		var language = $(this).attr('rel');
		socket.emit("changeLanguage", language);
	});

	$('#iframe').load(function() {
		alert("Loaded");
	});

    var $sidebar = $("#rightSide"),
        $window = $(window),
        offset = $sidebar.offset(),
        topPadding = 60;

    $window.scroll(function() {
        if ($window.scrollTop() > offset.top) {
            $sidebar.stop().animate({
                marginTop: $window.scrollTop() - offset.top + topPadding
            });
        } else {
            $sidebar.stop().animate({
                marginTop: 0
            });
        }
    });	
});

</script>
</head>
<body>
<div id="header">
	<div id="header-leftSide">
		<h2 style="margin-left:40px"><img src="/application/views/logo.png"></h2>
	</div>
	<div id="header-rightSide">
		<a href="#" class="changeLanguage" rel="Swedish">Swedish</a>
		<a href="#" class="changeLanguage" rel="French">French</a>
	</div>
</div>
<div style="width:40%;float:left;positio:relative;" id="leftSide">
	<div id="newData" class="shadow">
		
	</div>	
	<ul id="articles">
	</ul>
</div>
<div style="float:left;margin-top:60px;height:auto;overflow:hidden;" id="rightSide">
	<p style="text-align:center"><img src="/application/views/arrow.png"><p>
	<p><strong>Hey!</strong> You must click the articles on your left side to read them. Automagically we are looking for new articles and we'll display them to you in real time.</p>
	<p><strong>newstream</strong> is looking for new articles 24/7 for you. It works with CodeIgniter, MongoDB, Node.JS, Express, Mongoose, Socket.io and jQuery.</p>
</div>
</body>
</html>