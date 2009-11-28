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

/**
 * Principal TinyGojira Class
 *
 * @package Core
 * @author Adam Venturella
 */
class TinyGojira implements Countable, Iterator
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
	const kCommandVSize     = 0x38;
	const kCommandIterInit  = 0x50;
	const kCommandIterNext  = 0x51;
	const kCommandFwmKeys   = 0x58;
	const kCommandAddInt    = 0x60;
	const kCommandAddDouble = 0x61;
	const kCommandVanish    = 0x72;
	const kCommandRnum      = 0x80;
	const kCommandSize      = 0x81;
	const kCommandStat      = 0x88;
	
	private $stream;
	private $client;
	private $iterator_position = 0;
	private $iterator_key;
	
	/**
	 * TinyGojira Constructor
	 * @see TinyGojira::create_client()
	 *
	 *
	 * @param array $options 
	 *               Available option keys:
	 *               transport => if not provided defaults to tcp://
	 *               timeout   => if not provided defaults to 10
	 *               port      => if not provided defaults to 1978
	 *               host      => if not provided defaults to 0.0.0.0
	 *               
	 * @author Adam Venturella
	 */
	public function __construct($options=null)
	{
		$this->create_client($options);
	}
	
	/**
	 * Store a record into the database.
	 *
	 * @param string $key the key of the record
	 * @param string $value the value of the record
	 * @return boolean true/false upon success respectively
	 * @author Adam Venturella
	 * @example ../samples/put/put_general.php Insert data
	 */
	public function put($key, $value)
	{
		return $this->execute($this->prepare_put(TinyGojira::kCommandPut, $key, $value));
	}
	
	/**
	 * Store a record into the database.  Do not replace an existing record
	 *
	 * @param string $key 
	 * @param string $value 
	 * @return boolean true/false upon success respectively
	 * @author Adam Venturella
	 * @example ../samples/put/putkeep_general.php Insert data, do not replace existing data with same key
	 */
	public function putkeep($key, $value)
	{
		return $this->execute($this->prepare_put(TinyGojira::kCommandPutKeep, $key, $value));
	}
	
	/**
	 * Concatenate a value at the end of the existing record.  
	 * If no record exists a new record is created 
	 *
	 * @param string $key 
	 * @param string $value 
	 * @return boolean true/false upon success respectively
	 * @author Adam Venturella
	 * @example ../samples/put/putcat_general.php Concatenate data
	 */
	public function putcat($key, $value)
	{
		return $this->execute($this->prepare_put(TinyGojira::kCommandPutCat, $key, $value));
	}
	
	/**
	 * Concatenate a value at the end of the existing record and shift it to the left.
	 * If no record exists a new record is created 
	 *
	 * @param string $key 
	 * @param string $value 
	 * @param string $width 
	 * @return boolean true/false upon success respectively
	 * @author Adam Venturella
	 * @example ../samples/put/putshl_general.php Concatenate and shift to left
	 */
	public function putshl($key, $value, $width)
	{
		$data =  pack("CCNNN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandPutSh1, strlen($key), strlen($value), $width).$key.$value;
		return $this->execute($data);
	}
	
	/**
	 * Store a record into the database with no success/failure response
	 * from the server for the operation.
	 *
	 * @param string $key 
	 * @param string $value 
	 * @return void
	 * @author Adam Venturella
	 */
	public function putnr($key, $value)
	{
		$this->execute($this->prepare_put(TinyGojira::kCommandPutNR, $key, $value), true);
	}
	
	/**
	 * Remove a record from the database
	 *
	 * @param string $key 
	 * @return boolean true/false upon success respectively
	 * @author Adam Venturella
	 * @example ../samples/delete/out_general.php Delete data from the database
	 */
	public function out($key)
	{
		$data   = pack("CCN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandOut, strlen($key)).$key;
		return $this->execute($data);
	}
	
	/**
	 * Retrieve a record from the database for a given key
	 *
	 * @param string $key 
	 * @return string
	 * @author Adam Venturella
	 * @example ../samples/get/get_general.php Retrieve data from the database
	 */
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
	
	/**
	 * Retrieve multiple records from the database for an array of keys
	 *
	 * @param string $array strings representing keys in the database to return values for
	 * @return array key/value array where keys from the argument $array contain their corresponding values
	 * @author Adam Venturella
	 * @example ../samples/get/mget_general.php Retrieve multiple records from the database with given keys
	 */
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
	
	/**
	 * Initialize the iterator of the database.
	 *
	 * @return boolean true/false respectively upon success/fail
	 * @author Adam Venturella
	 */
	public function iterinit()
	{
		$result = false;
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandIterInit);
		
		if($this->execute($data)){
			$result = true;
		}
		
		return $result;
	}
	
	/**
	 * Get the next key of the iterator of the database.
	 *
	 * @return mixed false if error else value of the key
	 * @author Adam Venturella
	 */
	public function iternext()
	{
		
		$result = false;
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandIterNext);
		if($this->execute($data))
		{
			$record_info = unpack("Nlength/", stream_socket_recvfrom($this->client, 4));
			$record_data = unpack("A".$record_info['length']."data", stream_socket_recvfrom($this->client, $record_info['length']));
			$result = $record_data['data'];
		}
		
		return $result;
	}
	
	/**
	 * Get the size the value of a record in the database for a given key.
	 *
	 * @param string $key 
	 * @return mixed false if no record is found, or int representing the length
	 * @author Adam Venturella
	 */
	public function vsiz($key)
	{
		$result = false;
		$data   = pack("CCN", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandVSize, strlen($key)).$key;
		
		if($this->execute($data))
		{
			$response = unpack("Nlength", stream_socket_recvfrom($this->client, 4));
			$result = $response['length'];
		}
		
		return $result;
	}
	
	/**
	 * Get forward matching keys in a database.  
	 * eg: get all keys starting with the prefix "monster:"
	 * number of results is limited by the $count argument
	 *
	 * @param string $prefix 
	 * @param int $count 
	 * @return mixed false on error or array of matching keys
	 * @author Adam Venturella
	 * @example ../samples/get/fwmkeys_general.php Get keys matching a prefix
	 */
	public function fwmkeys($prefix, $count=-1)
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
	
	/**
	 * Remove all records from the database
	 *
	 * @return boolean true/false upon success respectively
	 * @author Adam Venturella
	 * @example ../samples/delete/vanish_general.php Remove all records from the database
	 */
	public function vanish()
	{
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandVanish);
		return $this->execute($data);
	}
	
	/**
	 * Number of records in the database
	 *
	 * @return int will return 0 or greater
	 * @author Adam Venturella
	 * @example ../samples/misc/rnum_general.php Get the number of records in the database
	 */
	public function rnum()
	{
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandRnum);
		
		// this command always returns with a status of 0
		$this->execute($data);
		return $this->read_64bit_int();
	}
	
	/**
	 * Get the size, in bytes, of the database
	 *
	 * @return int
	 * @author Adam Venturella
	 * @example ../samples/misc/size_general.php Get the size of the database on disk or in memory
	 */
	public function size()
	{
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandSize);
		
		// this command always returns with a status of 0
		$this->execute($data);
		return $this->read_64bit_int();
	}
	
	/**
	 * Get the status string of the database
	 *
	 * @return string
	 * @author Adam Venturella
	 * @example ../samples/misc/stat_general.php Get the status string of the database
	 */
	public function stat()
	{
		$data   = pack("CC", TinyGojira::kCommandIdPrefix, TinyGojira::kCommandStat);
		
		// this command always returns with a status of 0
		$this->execute($data);
		$response    = unpack("Nlength", stream_socket_recvfrom($this->client, 4));
		$record_data = unpack("A".$response['length']."value", stream_socket_recvfrom($this->client, $response['length']));
		
		return $record_data['value'];
	}
	
	/**
	 * Used to return a value from $this->size() and $this->rnum()
	 * those 2 methods return a 64 bit value.  If a 64 bit value is 
	 * required on a 32 bit system, it will attempt to send back a string 
	 * representation of the number assuming gmp_* functions are available.
	 * See: {@link http://php.net/gmp PHP GMP Manual}.
	 * Otherwise, on 32 bit systems it will only return a 32 bit int:
	 * 2,147,483,647 Max
	 * 
	 *
	 * @return int Architecture dependent
	 * @author Adam Venturella
	 */
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
	
	/**
	 * Create the client socket connection to the server
	 *
	 * @param array $options
	 *               Available option keys:
	 *               transport => if not provided defaults to tcp://
	 *               timeout   => if not provided defaults to 10
	 *               port      => if not provided defaults to 1978
	 *               host      => if not provided defaults to 0.0.0.0
	 * @return void
	 * @author Adam Venturella
	 */
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
	
	/**
	 * The put operations all a require a bit of similar work.
	 * this is a convenience method to that end
	 *
	 * @param string $command 
	 * @param string $key 
	 * @param string $value 
	 * @return void
	 * @author Adam Venturella
	 */
	private function prepare_put($command, $key, $value)
	{
		return pack("CCNN", TinyGojira::kCommandIdPrefix, $command, strlen($key), strlen($value)).$key.$value;
	}
	
	/**
	 * Execute a command on the database
	 *
	 * @param bytes $data 
	 * @param string $no_response Do we expect a response from the server?  
	 *                            If false, error checking occurs.  Otherwise, if true
	 *                            We do not check for any errors.
	 * @return void
	 * @author Adam Venturella
	 */
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
	
	/**
	 * Check a command to see if an error occured
	 *
	 * @return boolean true means all is well, false means we had a problem.
	 * @author Adam Venturella
	 */
	private function ok()
	{
		//$data   = stream_socket_recvfrom($this->client, 1, STREAM_PEEK);
		$data   = stream_socket_recvfrom($this->client, 1);
		$result = unpack('cok/', $data);
		return $result['ok'] == 0 ? true : false;
	}
	
	/**
	 * Countable implementation
	 *
	 * @return int
	 * @author Adam Venturella
	 */
	public function count()
	{
		return $this->rnum();
	}
	
	/**
	 * Iterator implementation
	 *
	 * @return void
	 * @author Adam Venturella
	 */
	private function rewind() 
	{
		$this->iterinit();
		$this->iterator_key = $this->iternext();
	}

	/**
	 * Iterator implementation
	 *
	 * @return void
	 * @author Adam Venturella
	 */
	public function current() 
	{
		return $this->get($this->iterator_key);
	}

	/**
	 * Iterator implementation
	 *
	 * @return void
	 * @author Adam Venturella
	 */
	public function key() 
	{
		return $this->iterator_key;
	}

	/**
	 * Iterator implementation
	 *
	 * @return void
	 * @author Adam Venturella
	 */
	public function next() 
	{
		$this->iterator_key = $this->iternext();
	}
	
	/**
	 * Iterator implementation
	 *
	 * @return void
	 * @author Adam Venturella
	 */
	private function valid() 
	{
		return $this->vsiz($this->iterator_key) > 0 ? true : false;
	}
	
	/**
	 * Be sure to close the socket connection.
	 *
	 * @return void
	 * @author Adam Venturella
	 */
	public function __destruct()
	{
		fclose($this->client);
	}
}


?>