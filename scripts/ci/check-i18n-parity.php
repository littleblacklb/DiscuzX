<?php
declare(strict_types=1);

/**
 * [Discuz!] language-key parity checker
 *
 * Compares the set of $lang[...] keys between the locale directories under
 * source/i18n (e.g. SC_UTF8 vs TC_UTF8). Contributions frequently add a string
 * to one locale but not the other; this check catches that on pull requests.
 *
 * Keys are extracted statically with PHP's tokenizer rather than by executing
 * the language files: the files interpolate constants such as ADMINSCRIPT and
 * $_G['siteurl'] (e.g. lang_admincp.php), so requiring them would fatal.
 *
 * Keys are compared as fully-qualified dotted paths (nested arrays included).
 *
 * Only ASCII keys are compared. A handful of language entries use a localized
 * string as an array key because that key is itself rendered as a display
 * label (e.g. lang_exif.php's `img_info` => ['文件信息' => ...], whose
 * translation legitimately differs per locale). Such non-ASCII keys are
 * intentionally skipped, which keeps the check free of false positives without
 * an allowlist. Code that looks up $lang['...'] can only reference ASCII keys,
 * so those are the ones that must stay in sync.
 *
 * Usage:
 *   php scripts/ci/check-i18n-parity.php [path/to/source/i18n]
 *
 * Exit status: 0 = in sync, 1 = mismatch found, 2 = usage/setup error.
 */

if(PHP_SAPI !== 'cli') {
	exit(2);
}

$i18nDir = $argv[1] ?? dirname(__DIR__, 2).'/upload/source/i18n';
if(!is_dir($i18nDir)) {
	fwrite(STDERR, "i18n directory not found: {$i18nDir}\n");
	exit(2);
}

const MAX_KEYS_PER_FILE = 60;
// Unit separator: cannot occur in language keys, so path segments never collide
// (some real keys contain '.' and '-').
const PATH_SEP = "\x1f";

/**
 * Filter a token stream down to meaningful tokens (no whitespace/comments).
 *
 * @return array<int, array{id: int|null, text: string}>
 */
function meaningful_tokens(string $source): array {
	$out = [];
	foreach(token_get_all($source) as $tok) {
		if(is_array($tok)) {
			if($tok[0] === T_WHITESPACE || $tok[0] === T_COMMENT || $tok[0] === T_DOC_COMMENT) {
				continue;
			}
			$out[] = ['id' => $tok[0], 'text' => $tok[1]];
		} else {
			$out[] = ['id' => null, 'text' => $tok];
		}
	}
	return $out;
}

/**
 * Normalise a string / integer array key token to its literal value.
 */
function decode_key(array $tok): string {
	if($tok['id'] === T_LNUMBER) {
		return $tok['text'];
	}
	$text = $tok['text'];
	if($text === '' || $text[0] !== "'") {
		return $text;
	}
	$inner = substr($text, 1, -1);
	return str_replace(["\\\\", "\\'"], ["\\", "'"], $inner);
}

/**
 * Whether a dotted path is code-addressable, i.e. contains only ASCII. Keys
 * that mix in non-ASCII characters are localized display labels and are not
 * expected to match across locales.
 */
function is_addressable(string $path): bool {
	return preg_match('/[^\x20-\x7e]/', str_replace(PATH_SEP, '', $path)) === 0;
}

/**
 * Human-readable form of a path (PATH_SEP -> '.').
 */
function display_path(string $path): string {
	return str_replace(PATH_SEP, '.', $path);
}

/**
 * Recursively collect keys of an array literal.
 *
 * $i must point at the opening '[' or the T_ARRAY keyword; on return it points
 * just past the matching closer. Recorded keys are paths relative to $prefix,
 * or top-level keys when $prefix is ''.
 *
 * @param array<int, array{id: int|null, text: string}> $t
 * @param array<string, true>                           $keys
 */
function walk_array(array $t, int &$i, string $prefix, array &$keys): void {
	$n = count($t);

	if(isset($t[$i]) && $t[$i]['id'] === T_ARRAY) {
		$i++; // consume the 'array' keyword; '(' follows
	}
	$i++; // consume the opening '[' or '('
	$auto = 0;

	while($i < $n) {
		$start = $i;
		$tok = $t[$i];

		if($tok['text'] === ']' || $tok['text'] === ')') {
			$i++;
			return;
		}
		if($tok['text'] === ',') {
			$i++;
			continue;
		}

		$key = null;
		if(($tok['id'] === T_CONSTANT_ENCAPSED_STRING || $tok['id'] === T_LNUMBER)
			&& isset($t[$i + 1]) && $t[$i + 1]['id'] === T_DOUBLE_ARROW) {
			$key = decode_key($tok);
			$i += 2; // consume key and '=>'
		}

		$label = $key !== null ? $key : '#'.($auto++);
		$path = $prefix === '' ? $label : $prefix.PATH_SEP.$label;
		$keys[$path] = true;

		if(isset($t[$i]) && ($t[$i]['text'] === '[' || $t[$i]['id'] === T_ARRAY)) {
			walk_array($t, $i, $path, $keys);
		} else {
			// Skip the scalar value expression up to the next ',' or closer
			// at this level, keeping bracket depth balanced.
			$depth = 0;
			while($i < $n) {
				$s = $t[$i]['text'];
				if($s === '[' || $s === '(' || $s === '{') {
					$depth++;
				} elseif($s === ']' || $s === ')' || $s === '}') {
					if($depth === 0) {
						break;
					}
					$depth--;
				} elseif($s === ',' && $depth === 0) {
					break;
				}
				$i++;
			}
		}

		if($i === $start) {
			$i++; // safety: guarantee forward progress
		}
	}
}

/**
 * Extract the keys of every literal `$lang = [...]` block in a file.
 *
 * @return array<string, true>
 */
function extract_lang_keys(string $file): array {
	$keys = [];
	$t = meaningful_tokens((string)file_get_contents($file));
	$n = count($t);

	for($i = 0; $i < $n; $i++) {
		if($t[$i]['id'] !== T_VARIABLE || $t[$i]['text'] !== '$lang') {
			continue;
		}
		// Match `$lang = [` / `$lang = array(` and ignore runtime merges such
		// as `$lang = array_merge($lang, $extend_lang);`.
		if(!isset($t[$i + 2]) || $t[$i + 1]['text'] !== '='
			|| ($t[$i + 2]['text'] !== '[' && $t[$i + 2]['id'] !== T_ARRAY)) {
			continue;
		}
		$j = $i + 2;
		walk_array($t, $j, '', $keys);
		$i = $j;
	}

	return $keys;
}

/**
 * Sorted list of `.php` files (relative to $base) under $base.
 *
 * @return array<int, string>
 */
function php_files(string $base): array {
	$files = [];
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
	);
	foreach($it as $f) {
		if($f->isFile() && strtolower($f->getExtension()) === 'php') {
			$rel = str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
			$files[] = $rel;
		}
	}
	sort($files);
	return $files;
}

$locales = [];
foreach(scandir($i18nDir) as $entry) {
	if($entry === '.' || $entry === '..') {
		continue;
	}
	$path = $i18nDir.'/'.$entry;
	if(is_dir($path) && php_files($path) !== []) {
		$locales[] = $entry;
	}
}
sort($locales);

if(count($locales) < 2) {
	echo "i18n parity check: found fewer than two locale directories in {$i18nDir}; nothing to compare.\n";
	exit(0);
}

echo 'i18n parity check: locales = '.implode(', ', $locales)."\n";

// Build [locale][relpath] => key set, and the file list per locale.
$keySets = [];
$fileLists = [];
foreach($locales as $locale) {
	$files = php_files($i18nDir.'/'.$locale);
	$fileLists[$locale] = array_flip($files);
	foreach($files as $rel) {
		$keySets[$locale][$rel] = extract_lang_keys($i18nDir.'/'.$locale.'/'.$rel);
	}
}

$reference = $locales[0];
$allFiles = [];
foreach($locales as $locale) {
	$allFiles += $fileLists[$locale];
}
$allFiles = array_keys($allFiles);
sort($allFiles);

echo 'Comparing '.count($allFiles).' file(s) against reference locale '.$reference.".\n";

$failures = 0;

// File-level parity.
foreach($allFiles as $rel) {
	$missing = [];
	foreach($locales as $locale) {
		if(!isset($fileLists[$locale][$rel])) {
			$missing[] = $locale;
		}
	}
	if($missing !== []) {
		$present = array_values(array_diff($locales, $missing));
		echo '::error file='.$i18nDir.'/'.$rel.'::present in '.implode(', ', $present)
			.' but missing in '.implode(', ', $missing)."\n";
		$failures++;
	}
}

// Key-level parity, for files present in every locale.
$checked = 0;
$ignored = 0;
foreach($allFiles as $rel) {
	foreach($locales as $locale) {
		if(!isset($keySets[$locale][$rel])) {
			continue 2;
		}
	}
	$checked++;

	$refKeys = $keySets[$reference][$rel];
	$ignored += count($refKeys) - count(array_filter($refKeys, 'is_addressable', ARRAY_FILTER_USE_KEY));

	foreach($locales as $locale) {
		if($locale === $reference) {
			continue;
		}
		$other = $keySets[$locale][$rel];
		$onlyRef = $onlyOther = [];
		foreach($refKeys as $k => $_) {
			if(is_addressable($k) && !isset($other[$k])) {
				$onlyRef[] = $k;
			}
		}
		foreach($other as $k => $_) {
			if(is_addressable($k) && !isset($refKeys[$k])) {
				$onlyOther[] = $k;
			}
		}
		if($onlyRef === [] && $onlyOther === []) {
			continue;
		}
		sort($onlyRef);
		sort($onlyOther);

		$path = $i18nDir.'/'.$locale.'/'.$rel;
		$parts = [];
		if($onlyRef !== []) {
			$parts[] = count($onlyRef).' key(s) only in '.$reference;
		}
		if($onlyOther !== []) {
			$parts[] = count($onlyOther).' key(s) only in '.$locale;
		}
		echo '::error file='.$path.'::'.implode('; ', $parts)."\n";
		echo "MISMATCH {$rel}  ({$reference} vs {$locale})\n";
		foreach(array_slice($onlyRef, 0, MAX_KEYS_PER_FILE) as $k) {
			echo '  - '.display_path($k)."   (only in {$reference})\n";
		}
		if(count($onlyRef) > MAX_KEYS_PER_FILE) {
			echo '  ... and '.(count($onlyRef) - MAX_KEYS_PER_FILE)." more only in {$reference}\n";
		}
		foreach(array_slice($onlyOther, 0, MAX_KEYS_PER_FILE) as $k) {
			echo '  + '.display_path($k)."   (only in {$locale})\n";
		}
		if(count($onlyOther) > MAX_KEYS_PER_FILE) {
			echo '  ... and '.(count($onlyOther) - MAX_KEYS_PER_FILE)." more only in {$locale}\n";
		}
		$failures++;
	}
}

echo "Checked {$checked} file(s) present in all locales";
if($ignored > 0) {
	echo " ({$ignored} non-ASCII label key(s) skipped)";
}
echo ".\n";

if($failures > 0) {
	echo "\ni18n parity check FAILED: {$failures} mismatch(es). ASCII language keys in ["
		.implode(', ', $locales)."] must match.\n";
	exit(1);
}

echo "i18n parity check passed: all locales define identical language keys.\n";
exit(0);
