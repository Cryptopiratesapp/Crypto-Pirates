Введите email и пароль для входа в игру. Если вы здесь в первый раз, то нажмите кнопку "Зарегистрироваться" или "Подключить MetaMask".
<br><br>
<div style="float:left;width:50%">
<form method="post" action="site/controller.php">
	<input type="hidden" id="action" name="action" value="register">
	<label for="email">email:</label><br>
	<input type="text" id="email" name="email" size="32" maxlength="32">
	<br><br>
	<label for="password">Пароль:</label><br>
	<input type="password" id="password" name="password" size="32" maxlength="32">
	<br><br>
	<input type="submit" value="Войти" onClick="document.getElementById('action').value='login'"/>
	<input type="submit" value="Зарегистрироваться">
</form>
</div>
<div>
	<a href="" class="btn btn-primary" id="btn_metamask">...</a>
</div>
<script type="text/javascript" src="/js/ethers-5.4.7.umd.min.js"></script>
<script language="javascript">
	
	var provider = null;
	var signer = null;
	
	const has_metamask = typeof window.ethereum !== 'undefined' && window.ethereum.isMetaMask;
	if (has_metamask) {
		provider = new ethers.providers.Web3Provider(window.ethereum);
		signer = provider.getSigner();
	}		

	const btn_metamask = document.getElementById('btn_metamask');
	setTimeout(update_metamask_button, 200);

	async function sign(msg) {
		let res = await signer.signMessage(msg);
		
		return res;
	}
	
	async function connect_metamask(evt) {
		evt.preventDefault();
		delete btn_metamask.onclick;
		await window.ethereum.request({ method: 'eth_requestAccounts' }).then(update_metamask_button);
	}

	function update_metamask_button() {
		if (has_metamask) {
			if (window.ethereum.selectedAddress !== null) {
				btn_metamask.text = 'Логин через Metamask';
				btn_metamask.onclick = login;
			} else {
				btn_metamask.text = 'Подключить Metamask';
				btn_metamask.onclick = connect_metamask;
			}
		} else {
			btn_metamask.text = 'Установить Metamask';
			btn_metamask.href = 'https://metamask.io/';
			btn_metamask.target = '_blank';
		}
	}

	function login(evt) {
		evt.preventDefault();
		let addr = window.ethereum.selectedAddress;
		let xhr = new XMLHttpRequest();
		xhr.open('POST', '/site/eth_controller.php', true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onreadystatechange = function() {
			if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
				console.log(this.response);
				if (this.response === 'FALSE') {
					btn_metamask.value = 'ERROR';
				} else {
					// sign
					let signed_message = null;

					signer.signMessage('Crypto-Pirates-' + this.response).then(
						function(val) {
							signed_message = val;
							check_backend(val);
						}
					);

					btn_metamask.value = '...';
				}
			}
		};
		xhr.send('action=login&addr=' + addr);
	}
	
	function check_backend(val) {
		let addr = window.ethereum.selectedAddress;
		let xhr = new XMLHttpRequest();
		xhr.open('POST', '/site/eth_controller.php', true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onreadystatechange = function() {
			if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
				console.log(this.response);
				if (this.response === 'FALSE') {
					btn_metamask.value = 'ERROR';
				} else {
					window.location.reload();
				}
			}
		};
		xhr.send('action=check&addr=' + addr + '&msg=' + val);
	}
</script>
