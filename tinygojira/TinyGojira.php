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
	
	const kCommandIdPrefix  = 0xC8;
	const kCommandPut       = 0x10;
	const kCommandPutKeep   = 0x11;
	const kCommandPutCat    = 0x12;
	const kCommandPutSh1    = 0x13;
	const kCommandPutNR     = 0x18;
	const kCommandOut       = 0x20;
	const kCommandGet       = 0x30;
	const kCommandMGet      = 0x31;
	const kCommandFwmKeys   = 0x58;
	const kCommandAddInt    = 0x60;
	const kCommandAddDouble = 0x61;
	const kCommandVanish    = 0x72;
	const kCommandRnum      = 0x80;
	const kCommandSize      = 0x81;
	const kCommandStat      = 0x88;
	
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
	
	public function fwmkeys($prefix, $count)
	{
		$result = false;
		$count  = (int) $count;
		$data   = pack("CCNN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandFwmKeys, strlen($prefix), $count).$prefix;
		
		if($this->execute($data))
		{
			$info   = unpack('Nrecords/' ,stream_socket_recvfrom($this->client, 4));
			if($info['records'] > 0)
			{
				$result = array();
				
				for($i=0; $i < $info['records']; $i++)
				{
					$record_info = unpack("Nlength", stream_socket_recvfrom($this->client, 4));
					$record_data = unpack("A".$record_info['length']."value", stream_socket_recvfrom($this->client, $record_info['length']));
					$result[] = $record_data['value'];
				}
			}
		}
		
		return $result;
	}
	/*
	public function addint($key, $number=0)
	{
		$result = false;
		
		$number = (int) $number;
		//$data   = pack("CCNN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandAddInt, strlen($key), (int) $number).$key;
		$data = pack("CCNN", 0xC8, 0x60, strlen($key), (int)$number) . $key;
		
		if($this->execute($data))
		{
			$record_data = unpack('Nvalue/' ,stream_socket_recvfrom($this->client, 4));
			print_r($record_data);
			$result      = (int) $record_data['value'];
		}
		
		return $result;
	}
	*/

	
	/*public function adddouble($key, $number)
	{
		$result  = false;
		$number  = (double) $number;
	}*/
	
	public function vanish()
	{
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandVanish);
		return $this->execute($data);
	}
	
	public function rnum()
	{
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandRnum);
		
		// this command always returns with a status of 0
		$this->execute($data);
		return $this->read_64bit_int();
	}
	
	public function size()
	{
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandSize);
		
		// this command always returns with a status of 0
		$this->execute($data);
		return $this->read_64bit_int();
	}
	
	public function stat()
	{
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandStat);
		
		// this command always returns with a status of 0
		$this->execute($data);
		$response    = unpack("Nlength", stream_socket_recvfrom($this->client, 4));
		$record_data = unpack("A".$response['length']."value", stream_socket_recvfrom($this->client, $response['length']));
		
		return $record_data['value'];
	}
	
	private function read_64bit_int()
	{
		$result = false;
		if(PHP_INT_SIZE == 8) // 64 bit
		{
			$record_data = unpack("H16value", stream_socket_recvfrom($this->client, 8));
			$result = hexdec($record_data['value']);
		}
		else // 32 bit
		{
			$record_data = unpack("Nhigh/Nlow", stream_socket_recvfrom($this->client, 8));
			
			if($temp['high'] > 0)
			{
				if(function_exists('gmp_init'))
				{
					$hex = "0x".dechex($record_data['high']).dechex($record_data['low']);
					$result = gmp_strval(gmp_init($hex, 16));
				}
				else
				{
					// probably should convert this to a string...
					trigger_error("64 bit value required result truncated to 32 bits", E_USER_NOTICE);
					$result = $record_data['low'];
				}
			}
			else
			{
				$result = $record_data['low'];
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