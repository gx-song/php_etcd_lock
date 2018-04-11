<?php
	function get_lock($etcd_endpoint,$lock_key,$ttl,$flag)
	{	//var_dump($flag);die();
		if (!($etcd_endpoint and  $lock_key)) {
			return -1;
		}
		if (!$ttl) {
			$ttl = 60;
		}
		//curl 访问etcd v2
		$post_data = array(
			"value" => $lock_key,
			"ttl" => $ttl
		);
		if ($flag == 1) {
			$url = $etcd_endpoint.'/v2/keys/lock'.$lock_key;
		}else{
			$url = $etcd_endpoint.'/v2/keys/lock'.$lock_key.'?prevExist=false';
		}
		$curl_h = curl_init($url);
		curl_setopt($curl_h,CURLOPT_CUSTOMREQUEST,'PUT');
		curl_setopt($curl_h,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl_h,CURLOPT_POSTFIELDS,"value=".$lock_key);
		curl_setopt($curl_h,CURLOPT_POSTFIELDS,"ttl=".$ttl);
		curl_setopt($curl_h,CURLOPT_TIMEOUT,3);
		$output = curl_exec($curl_h);
		if (!$output) {
			printf("curl err!");
			curl_close($curl_h);
			return -1;
		}
		curl_close($curl_h);

		return $output;
	}
	function exten_lock($signo)
	{
		pcntl_alarm(2);
		$ret = get_lock("http://127.0.0.1:2379","body",30,1);
	}
	for ($i = 0;$i < 10;$i ++) {
		switch(pcntl_fork()) {
		case	-1:
			die("fork error!\n");
			break;
		case	0:
			//printf("child pid:%s\t",posix_getpid());
			$ret = get_lock("http://127.0.0.1:2379","body",30,0);
			//var_dump($ret);
			$json = json_decode($ret);
			if (isset($json->action) && $json->action == "create") {
				printf("child pid:%s\tget lock!\n",
					posix_getpid());
				declare(ticks=1);
				pcntl_signal(SIGALRM,"exten_lock",true);
				pcntl_alarm(2);
				for($i=0;$i<10;$i++){
					echo "$i\n";
					sleep(3);
				}
			}else if (isset($json->errorCode)) {
				printf("chinld pid:%s\tget lock fail!\n",
					posix_getpid());
			}
			break;
		default:
			exit(0);

		}	
	}
