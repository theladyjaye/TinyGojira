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
	// NR = No Response
	
	const kCommandIdPrefix = 0xC8;
	const kCommandPut      = 0x10;
	const kCommandPutKeep  = 0x11;
	const kCommandPutCat   = 0x12;
	const kCommandPutSh1   = 0x13;
	const kCommandPutNR    = 0x18;
	const kCommandOut      = 0x20;
	const kCommandGet      = 0x30;
	const kCommandMGet     = 0x31;
	
	private $stream;
	private $client;
	
	public function __construct($options=null)
	{
		$this->create_client($options);
	}
	
	public function put($key, $value)
	{
		return $this->execute($this->prepare_put(TinyGojira::kCommandPut, $key, $value));
	}
	
	public function putkeep($key, $value)
	{
		return $this->execute($this->prepare_put(TinyGojira::kCommandPutKeep, $key, $value));
	}
	
	public function putcat($key, $value)
	{
		return $this->execute($this->prepare_put(TinyGojira::kCommandPutCat, $key, $value));
	}
	
	public function putshl($key, $value, $width)
	{
		$data =  pack("CCNNN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandPutSh1, strlen($key), strlen($value), $width).$key.$value;
		return $this->execute($data);
	}
	
	public function putnr($key, $value)
	{
		$this->execute($this->prepare_put(TinyGojira::kCommandPutNR, $key, $value), true);
	}
	
	public function out($key)
	{
		$data   = pack("CCN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandOut, strlen($key)).$key;
		return $this->execute($data);
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
	
	public function mget($array)
	{
		$result = false;
		$data   = pack("CCN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandMGet, count($array));
		$keys   = "";
		
		foreach($array as $key)
		{
			$data = $data.pack("N", strlen($key));
			$data = $data.$key;
		}
		
		if($this->execute($data))
		{
			$info   = unpack('Nrecords/' ,stream_socket_recvfrom($this->client, 4));
			if($info['records'] > 0)
			{
				$result = array();
				for($i=0; $i < $info['records']; $i++)
				{
					$record_info = unpack("Nkey/Nvalue", stream_socket_recvfrom($this->client, 8));
					$pattern     = "A".$record_info['key']."key/A".$record_info['value']."value";
					$length      = $record_info['key']+$record_info['value'];
					$record_data = unpack($pattern, stream_socket_recvfrom($this->client, $length));
					$result[$record_data['key']] = $record_data['value'];
				}
			}
		}
		
		return $result;
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
	
	private function prepare_put($command, $key, $value)
	{
		return pack("CCNN", TinyGojira::kCommandIdPrefix, $command, strlen($key), strlen($value)).$key.$value;
	}
	
	private function execute($data, $no_response=false)
	{
		stream_socket_sendto($this->client, $data);
		
		if($no_response)
		{
			return;
		}
		else
		{
			return $this->ok();
		}
	}
	
	private function ok()
	{
		//$data   = stream_socket_recvfrom($this->client, 1, STREAM_PEEK);
		$data   = stream_socket_recvfrom($this->client, 1);
		$result = unpack('cok/', $data);
		return $result['ok'] == 0 ? true : false;
	}
	
	public function __destruct()
	{
		fclose($this->client);
	}
}


?>