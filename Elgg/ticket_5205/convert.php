<?php
require_once(dirname(__FILE__).'/engine/start.php');
register_translations(dirname(__FILE__).'/install/languages/', true);
echo "Core loaded\n";

$i = new RecursiveDirectoryIterator(dirname(__FILE__), RecursiveDirectoryIterator::SKIP_DOTS);
$i = new RecursiveIteratorIterator($i, RecursiveIteratorIterator::LEAVES_ONLY);
$i = new RegexIterator($i, "/.*\.php/");

$cnt = 0;
$regExp = "/throw\s+new\s+[^\n]*Exception\s*\(\s*"
	."(elgg_echo\s*\(['\"]([^'\"]+)['\"]\s*(?:\,\s*"
	."array\s*\(\s*((?:[^\)\(]|\([^\)\(]*\))*)\s*\))?\s*\))/";
$patterns = array();
$transKeys = array();
foreach ($i as $filePath => $val) {
	$contents = file_get_contents($filePath);
	if ($hits = preg_match_all($regExp, $contents, $matches, PREG_SET_ORDER)) {
// 		var_dump($filePath);
		foreach ($matches as $match) {
			$trans = elgg_echo($match[2]);
			if ($trans == $match[2]) {
				//missing translation
				var_dump('MISSING TRANSLATION', $trans);
				continue;
			}
			$transKeys[] = $match[2];
			if (isset($match[3])) {
				$vals = array_map('trim', explode(',', $match[3]));
			} else {
				$vals = array();
			}
			
// 			var_dump($match, $trans, $vals);
			$separator = '"';//this is safe for all cases
			if ($pats = preg_match_all("/%./", $trans, $ms)) {
				// replace printf stubs
				for ($i=0; $i<$pats; $i++) {
					$pos = strpos($trans, '%');
					if ($pos!==false) {
						if ($vals[$i]) {
							$trans = substr($trans, 0, $pos) . $separator . ' . ' 
								. $vals[$i] . ' . ' . $separator . substr($trans, $pos+2);
						} else {
							$trans = substr($trans, 0, $pos) . substr($trans, $pos+2);
						}
					}
				}
				//test if counts match
				foreach ($ms as $m) {
					$patterns[$m[0]] ++;
				}
			}
			$trans = $separator . $trans . $separator;
			$trans = str_replace(array($separator.$separator.' . ', ' . '.$separator.$separator), array('', ''), $trans);
// 			var_dump($match);
			$prev = $match[0];
			$next = str_replace($match[1], $trans, $prev);
// 			var_dump($prev, $next);
			$contents = str_replace($prev, $next, $contents);
		}
		file_put_contents($filePath, $contents);
		$cnt += $hits;
	}
// 	if($cnt>10) break;
}
$transKeys = array_unique($transKeys);
var_dump($cnt, $patterns, $transKeys);

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

