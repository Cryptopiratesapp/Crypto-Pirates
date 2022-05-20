<?php $t = time() ?>
<!DOCTYPE html>
<html>
    <head>
        <title>Crypto Pirates</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta http-equiv="Cache-Control" content="no-cache">
		<script src="js/move/Move.js?t=<?=$t?>"></script>
		<script src="js/move/Sequence.js?t=<?=$t?>"></script>
		<script src="js/move/Actor.js?t=<?=$t?>"></script>
		<script src="js/move/ActorFactory.js?t=<?=$t?>"></script>
		<script src="js/ImgLoader.js?t=<?=$t?>"></script>
		<script src="js/GameObject.js?t=<?=$t?>"></script>
		<script src="js/Ship.js?t=<?=$t?>"></script>
		<script src="js/Stage.js?t=<?=$t?>"></script>
		<script src="js/view/View.js?t=<?=$t?>"></script>
		<script src="js/view/HUD.js?t=<?=$t?>"></script>
		<script src="js/view/ExploreView.js?t=<?=$t?>"></script>
		<script src="js/view/BattleView.js?t=<?=$t?>"></script>
		<script src="js/view/DockView.js?t=<?=$t?>"></script>
		<script src="js/view/DeadView.js?t=<?=$t?>"></script>
		<script src="js/view/StartupView.js?t=<?=$t?>"></script>
		<script src="js/log/CanvasLog.js?t=<?=$t?>"></script>
		<script src="js/log/LogEntry.js?t=<?=$t?>"></script>
		<script src="js/log/LogBuffer.js?t=<?=$t?>"></script>
		<script src="js/log/LogGenerator.js?t=<?=$t?>"></script>
		<script src="js/sky-engine/Particle.js?t=<?=$t?>"></script>
		<script src="js/sky-engine/ImageParticle.js?t=<?=$t?>"></script>
		<script src="js/sky-engine/ParticleFactory.js?t=<?=$t?>"></script>
		<script src="js/sky-engine/ImageFactory.js?t=<?=$t?>"></script>
		<script src="js/sky-engine/ParallaxPlane.js?t=<?=$t?>"></script>
		<script src="js/sky-engine/BackgroundPlane.js?t=<?=$t?>"></script>
		<script src="js/sky-engine/SkyEngine.js?t=<?=$t?>"></script>
		<script src="js/controller/Controller.js?t=<?=$t?>"></script>
		<script src="js/controller/ExploreController.js?t=<?=$t?>"></script>
		<script src="js/controller/ReturnController.js?t=<?=$t?>"></script>
		<script src="js/controller/BattleController.js?t=<?=$t?>"></script>
		<script src="js/controller/DockController.js?t=<?=$t?>"></script>
		<script src="js/controller/DeadController.js?t=<?=$t?>"></script>
		<script src="js/controller/StartupController.js?t=<?=$t?>"></script>
		<script src="js/command/CommandInterfaceCanvas.js?t=<?=$t?>"></script>
		<script src="js/command/Command.js?t=<?=$t?>"></script>
		<script src="js/command/DockCommand.js?t=<?=$t?>"></script>
		<script src="js/command/DepartCommand.js?t=<?=$t?>"></script>
		<script src="js/command/VolleyShotCommand.js?t=<?=$t?>"></script>
		<script src="js/command/RepairCommand.js?t=<?=$t?>"></script>
		<script src="js/command/FleeCommand.js?t=<?=$t?>"></script>
		<script src="js/mode/ModeInterfaceCanvas.js?t=<?=$t?>"></script>
		<script src="js/mode/Handle.js?t=<?=$t?>"></script>
		<script src="js/mode/Mode.js?t=<?=$t?>"></script>
		<script src="js/processor/Processor.js?t=<?=$t?>"></script>
		<script src="js/processor/DummyProcessor.js?t=<?=$t?>"></script>
		<script src="js/processor/YarrProcessor.js?t=<?=$t?>"></script>
		<script src="js/processor/DefaultProcessor.js?t=<?=$t?>"></script>
		<script src="js/Game.js?t=<?=$t?>"></script>
		<script src="js/events/ShipEvent.js?t=<?=$t?>"></script>
		<script src="js/messages.js?t=<?=$t?>"></script>
		<script src="js/colors.js?t=<?=$t?>"></script>
		<style>
			html, body {
				background-color: #364878;
				margin: 0;
				padding: 0;
				width: 100%;
				height: 100%;
				border: 0;
				overflow: hidden;
			}
		</style>
	</head>
	<body onresize="resize()">
		
	<form id="cmd" onsubmit="send_cmd"><input name="cmd" type="text" size="64"><input type="submit" value="send"><span style="color:white;margin-left:1em" id="cmd_status"></span></form>

	<canvas id="canvas" style="width:100%">Sorry, your browser does not suport canvas graphics</canvas>
		<script language="javascript">
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

			const canvas = document.getElementById('canvas');
			canvas.width = 1920;
			canvas.height = 1080;
			var ws = null;
		
			const game = new Game();
			game.stage = new Stage(canvas);
			game.imgLoader = new ImgLoader();
			game.actorFactory = new ActorFactory();
			let w = 720; //canvas.width >> 1;
			let h = 24 * 10 + 1;
			game.log = new CanvasLog(canvas.width - w, canvas.height - h - 12, w, h, new LogBuffer(10));

			game.imgLoader.load(
				[
					'ship.png', 'bg_1920.png',
					//'bg_stars1_1920.png', 'bg_stars2_1920.png',
					'bg_cloud1_1920.png', 'bg_cloud2_1920.png',
					'icon_res.png', 'icon_hp.png', 'icon_gold.png', 'icon_hops.png',
					'icon_side.png',
					'btn_play.png', 'btn_pause.png', 'btn_heal.png',
					'jelly00.png', 'jelly01.png', 'jelly02.png', 'shrimp00.png',
					'shrimp01.png', 'shrimp02.png', 'shrimp_boss.png',
					'dock.png', 'digits.png', 'digits_small.png',
					'mode_expl_hide.png', 'mode_expl_expl.png', 'mode_expl_agro.png',
					'mode_batl_hide.png', 'mode_batl_expl.png', 'mode_batl_agro.png',
					'btn_repair.png', 'btn_return.png', 'btn_return_cancel.png',
					'btn_volley_shot.png', 'btn_flee.png', 'btn_depart.png', 'btn_buy_ship.png'
				],
				start
			);

			function start() {
				resize();
				game.init();
				game.stage.canvas.addEventListener('click', game.onClick.bind(game));
				game.log.log('*** ws init');
				ws = new WebSocket('<?=$data['listener']?>');
				ws.onopen = function() {
					game.log.log('*** ws open');
					game.setZone({zone: 'startup'});
				};

				ws.onerror = function() {
					game.log.log('*** ws error');
				};

				ws.onclose = function() {
					game.log.log('*** ws closed');
				};

				ws.onmessage = function(evt) {
					//console.log('ws message: ' + evt.data);
					let processor = game.processor;
					if (processor.update(evt.data) && processor.output) {
						ws.send(processor.output);
					}
				};
		
				update();
			}

			function update() {
				game.update();
				let processor = game.processor;
				if (processor && processor.active && (processor.output || processor.pendingCommand)) {
					if (!processor.output) {
						processor.output = processor.pendingCommand;
						processor.pendingCommand = null;
					}
					game.log.log('> ' + processor.output);
					ws.send(processor.output);
					processor.output = null;
				}
				window.requestAnimationFrame(update);
			}
			
			function command(msg) {
				game.processor.pendingCommand = msg;
			}

			function resize() {
				let w = window.innerWidth;
				let h = window.innerHeight;
				//let fw = 1920;
				if (h / w >= 1080 / 1920) {
					canvas.style.width = '100%';
					canvas.style.height = '';
				} else {
					canvas.style.width = '';
					canvas.style.height = '100%';
				}
			}
		</script>
	</body>
</html>
