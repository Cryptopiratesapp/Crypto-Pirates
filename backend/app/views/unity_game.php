<!DOCTYPE html>
<html lang="en-us">
<head>
	<meta charset="utf-8">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Unity WebGL Player | Crypto-pirates</title>
	<link rel="shortcut icon" href="TemplateData/favicon.ico">
	<link rel="stylesheet" href="TemplateData/style.css">
	<script src="TemplateData/UnityProgress.js"></script>
	<script src="Build/UnityLoader.js"></script>
	<style>
	body {
		background: url(TemplateData/back_green.png) no-repeat center center fixed;
		-moz-background-size: cover;
		-webkit-background-size: cover;
		-o-background-size: cover;
		background-size: cover;
	}
	</style>
</head>
<body>
	<div class="webgl-content">
		<div id="unityContainer" style="width: 100%; height: 100%"></div>
	</div>
	<?php if ($dev) : ?>
	<form id="cmd" onsubmit="send_cmd"><input name="cmd" type="text" size="64"><input type="submit" value="send"><span style="color:white;margin-left:1em" id="cmd_status"></span></form>
	<?php endif ?> 
	<script>
		<?php if ($dev) : ?>
		document.getElementById('cmd').onsubmit = function (evt) {
			evt.preventDefault();

			document.getElementById('cmd_status').textContent = '';
			let cmd = evt.target.cmd.value;
			let xhr = new XMLHttpRequest();
			xhr.open('POST', 'console.php', true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onreadystatechange = function() {
				if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
					document.getElementById('cmd_status').textContent = this.response;
				} else {
					document.getElementById('cmd_status').textContent = '???';
				}
			};
			xhr.send('cmd=' + encodeURIComponent(cmd));
		};
		<?php endif ?>

		UnityLoader.compatibilityCheck = function (e, t) { t(); };

		var container = document.getElementById('unityContainer');
		var unityInstance = UnityLoader.instantiate('unityContainer', 'Build/crypt.json', { onProgress: myProgress });

		var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

		function myProgress(instance, progress) {
			UnityProgress(instance,progress);
			if (progress === 1 && isMobile) {
				unityInstance.SendMessage('Bootstrap', 'ChangeMobileGameFontSize', 30);
			}
		}

		if (isMobile) {
			var Width = innerWidth;
			var Height = Width / 2.2;
			container.style.width = Width + 'px';
			container.style.height = Height + 'px';

			var supportsOrientationChange = 'onorientationchange' in window,
			orientationEvent = supportsOrientationChange ? 'orientationchange' : 'resize';

			window.addEventListener(
				orientationEvent,
				function() {
					Width = innerWidth;
					Height = Width / 2.2;
					container.style.width = Width + 'px';
					container.style.height = Height + 'px';
				},
				false
			);
		} else {
			container.style.width = '80vw';
			container.style.height = '85vh';
		}
	</script>
</body>
</html>