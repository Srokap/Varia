<?php
require_once(dirname(__FILE__).'/engine/start.php');
register_translations(dirname(__FILE__).'/install/languages/', true);
echo "Core loaded\n";

// var_dump($cnt, $patterns, $transKeys);


$langFiles = array(
	dirname(__FILE__).'/install/languages/en.php',
	dirname(__FILE__).'/languages/en.php',
);
$plugins = elgg_get_plugin_ids_in_dir(dirname(__FILE__).'/mod/');
// var_dump($plugins);
foreach ($plugins as $plugin) {
// 	var_dump($plugin);
	$path = dirname(__FILE__).'/mod/' . $plugin . '/languages/en.php';
	if (file_exists($path)) {
		$langFiles[] = $path;
	}
}
// die();

$transKeys = array();
foreach ($langFiles as $filePath) {
	$contents = file_get_contents($filePath);
	if (preg_match_all('/[\'"]([^\'"]*Exception[^\'"]*)[\'"]/', $contents, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$transKeys[] = $match[1];
		}
	}
}
// var_dump($transKeys);
$regExp = "/elgg_echo\\s*\\(\\s*['\"](" . implode('|', $transKeys) . ")/";
var_dump($regExp);

$i = new RecursiveDirectoryIterator(dirname(__FILE__), RecursiveDirectoryIterator::SKIP_DOTS);
$i = new RecursiveIteratorIterator($i, RecursiveIteratorIterator::LEAVES_ONLY);
$i = new RegexIterator($i, "/.*\.php/");

// var_dump($transKeys);
foreach ($i as $filePath => $val) {
	$contents = file_get_contents($filePath);
	if (preg_match_all($regExp, $contents, $matches, PREG_SET_ORDER)) {
// 		var_dump($matches);
		foreach ($matches as $match) {
			$transKeys = array_diff($transKeys, array($match[1]));
		}
	}
}
// var_dump($transKeys);

$found = array();
foreach ($langFiles as $filePath) {
	$contents = file_get_contents($filePath);
	$ht = 0;
	foreach ($transKeys as $trans) {
		if ($pos = strpos($contents, $trans)) {
// 			var_dump($pos);
			$bef = strrpos(substr($contents, 0, $pos), "\n");
			$aft = strpos($contents, ",\n", $pos);
// 			var_dump($bef, $aft);
			
		} else {
			$bef = $aft = false;
		}
		if ($pos===false || $bef===false || $aft===false) {
// 			var_dump($bef, $aft);
		} else {
// 			var_dump($trans);
			$contents = substr($contents, 0, $bef+1) . substr($contents, $aft+2);
			$ht++;
			$found[] = $trans;
		}
	}
	if ($ht) {
		file_put_contents($filePath, $contents);
	}
}
var_dump(array_diff($transKeys, $found));

