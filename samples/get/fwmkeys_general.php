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

$db->put('monster:Godzilla', 'Monster1');
$db->put('monster:Mothra', 'Monster2');
$db->put('robot:Gigantor', 'Robot1');
$db->put('monster:Gamera', 'Monster3');
$db->put('monster:Megalon', 'Monster4');
$db->put('robot:Voltron', 'Robot2');
?>

<h1>All Monster Keys</h1>
<?php
echo "<pre>".print_r($db->fwmkeys('monster:'), true)."</pre>";
?>
<h1>2 Monster Keys</h1>
<?php
echo "<pre>".print_r($db->fwmkeys('monster:', 2), true)."</pre>";
?>
<h1>All Robots Keys</h1>
<?php
echo "<pre>".print_r($db->fwmkeys('robot:'), true)."</pre>";
?>

