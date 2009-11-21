<?php
/**
 *    TinyGojira
 * 
 *    Copyright (C) 2009 Adam Venturella
 *
 *    LICENSE:
 *
 *    Licensed under the Apache License, Version 2.0 (the "License"); you may not
 *    use this file except in compliance with the License.  You may obtain a copy
 *    of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 *    This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *    without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR 
 *    PURPOSE. See the License for the specific language governing permissions and
 *    limitations under the License.
 *
 *    Author: Adam Venturella - aventurella@gmail.com
 *
 *    @package TinyGojira
 *    @author Adam Venturella <aventurella@gmail.com>
 *    @copyright Copyright (C) 2009 Adam Venturella
 *    @license http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 *
 **/
class TinyGojira
{
	const kCommandIdPrefix = 0xC8;
	const kCommandPut      = 0x10;
	const kCommandOut      = 0x20;
	const kCommandGet      = 0x30;
	
	private $stream;
	private $client;
	
	public function __construct($options=null)
	{
		$this->create_client($options);
	}
	
	public function put($key, $value)
	{
		$len_key    = strlen($key);
		$len_value  = strlen($value);
		$data       = pack("CCNN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandPut, $len_key, $len_value).$key.$value;
		
		return $this->execute($dataata);
	}
	
	public function get($key)
	{
		$result = false;
		$data   = pack("CCN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandGet, strlen($key)).$key;
		
		if($this->execute($data))
		{
			$info     = unpack('Nlength/' ,stream_socket_recvfrom($this->client, 4));
			$response = unpack("A".$info['length']."data", stream_socket_recvfrom($this->client, $info['length']));
			$result   =  $response['data'];
		}
		
		return $result;
	}
	
	public function out($key)
	{
		$data   = pack("CCN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandOut, strlen($key)).$key;
		return $this->execute($data);
	}
	
	private function create_client($options=null)
	{
		$transport = isset($options['transport']) ? $options['transport'] : 'tcp://';
		$timeout   = isset($options['timeout']) ? $options['timeout']     : 10;
		$port      = isset($options['port']) ? $options['port']           : 1978;
		$host      = isset($options['host']) ? $options['host']           : '0.0.0.0';
		$errno     = null;
		$errstr    = null;
		
		$connection = $transport.$host.':'.$port;
		$this->client = stream_socket_client($connection, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT);

		if(!$this->client)
		{
			throw new Exception('TinyGojira unable to connect to host '.$socket.' : '.$errno.', '.$errstr);
			return;
		}
	}
	
	private function ok()
	{
		//$data   = stream_socket_recvfrom($this->client, 1, STREAM_PEEK);
		$data   = stream_socket_recvfrom($this->client, 1);
		$result = unpack('cok/', $data);
		return $result['ok'] == 0 ? true : false;
	}
	
	private function execute($data)
	{
		stream_socket_sendto($this->client, $data);
		return $this->ok();
	}
	
	public function __destruct()
	{
		fclose($this->client);
	}
}


?>