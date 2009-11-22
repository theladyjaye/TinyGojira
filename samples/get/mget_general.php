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
 *    @package Sample 
 *    @author Adam Venturella <aventurella@gmail.com>
 *    @copyright Copyright (C) 2009 Adam Venturella
 *    @license http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 *
 **/

/**
 * Sample
 */
require 'tinygojira/TinyGojira.php';

$db   = new TinyGojira();
$data = null;

$db->put('Godzilla', 'Monster1');
$db->put('Mothra', 'Monster2');
$db->put('Gamera', 'Monster3');
$db->put('Megalon', 'Monster4');

$keys   = array('Mothra', 'Godzilla', 'Megalon');
$data   = $db->mget($keys);

if($data)
{
	print_r($data);
}
?>