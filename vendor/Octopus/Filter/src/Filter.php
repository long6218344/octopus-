<?php
/**
 * Copyright (c) 2013,上海二三四五网络科技股份有限公司
 * 文件名称：Filter.php
 * 摘    要：Filter基类
 * 作    者：杜海明
 * 修改日期：2015.03.03
 */
namespace Octopus;

class Filter
{
    
    protected $neverAllowedStr = array(
		'document.cookie'	=> '[removed]',
		'document.write'	=> '[removed]',
		'.parentNode'		=> '[removed]',
		'.innerHTML'		=> '[removed]',
		'-moz-binding'		=> '[removed]',
		'<!--'				=> '&lt;!--',
		'-->'				=> '--&gt;',
		'<![CDATA['			=> '&lt;![CDATA[',
		'<comment>'			=> '&lt;comment&gt;'
	);
    
    
    protected $neverAllowedRegex = array(
		'javascript\s*:',
		'(document|(document\.)?window)\.(location|on\w*)',
		'expression\s*(\(|&\#40;)', // CSS and IE
		'vbscript\s*:', // IE, surprise!
		'wscript\s*:', // IE
		'jscript\s*:', // IE
		'vbs\s*:', // IE
		'Redirect\s+30\d:',
		"([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
	);
    
    protected $xssHash			= '';
    
    public function __construct($charset = 'gbk')
    {
        $this->charset = $charset;
    }
    
    
    public function removeInvisibleCharacters($str, $urlEncoded = TRUE)
	{
		$nonDisplayables = array();
		if ($urlEncoded)
		{
			$nonDisplayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$nonDisplayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}
		$nonDisplayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127
        do
		{
			$str = preg_replace($nonDisplayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}
    
    protected function convertAttribute($match)
	{
		return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
	}
    
    
    protected function decodeEntity($match)
	{
		// Protect GET variables in URLs
		// 901119URL5918AMP18930PROTECT8198
		$match = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-/]+)|i', $this->getXssHash().'\\1=\\2', $match[0]);
        // Decode, then un-protect URL GET vars
		return str_replace(
			$this->getXssHash(),
			'&',
			$this->entityDecode($match, strtoupper($this->charset))
		);
	}
    
    protected function doNeverAllowed($str)
	{
		$str = str_replace(array_keys($this->neverAllowedStr), $this->neverAllowedStr, $str);

		foreach ($this->neverAllowedRegex as $regex)
		{
			$str = preg_replace('#'.$regex.'#is', '[removed]', $str);
		}

		return $str;
	}
    
    
    protected function compactExplodedWords($matches)
	{
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}
    
    protected function filterAttributes($str)
	{
		$out = '';

		if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
		{
			foreach ($matches[0] as $match)
			{
				$out .= preg_replace("#/\*.*?\*/#s", '', $match);
			}
		}

		return $out;
	}
    
    protected function jsLinkRemoval($match)
	{
		return str_replace(
			$match[1],
			preg_replace(
				'#href=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
				'',
				$this->filterAttributes(str_replace(array('<', '>'), '', $match[1]))
			),
			$match[0]
		);
	}
    
    protected function jsImgRemoval($match)
	{
		return str_replace(
			$match[1],
			preg_replace(
				'#src=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
				'',
				$this->filterAttributes(str_replace(array('<', '>'), '', $match[1]))
			),
			$match[0]
		);
	}
    
    protected function removeEvilAttributes($str, $isImage)
	{
		// All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
		$evilAttributes = array('on\w*', 'style', 'xmlns', 'formaction', 'form', 'xlink:href');

		if ($isImage === TRUE)
		{
			/*
			 * Adobe Photoshop puts XML metadata into JFIF images,
			 * including namespacing, so we have to allow this for images.
			 */
			unset($evilAttributes[array_search('xmlns', $evilAttributes)]);
		}

		do {
			$count = 0;
			$attribs = array();

			// find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
			preg_match_all('/(?<!\w)('.implode('|', $evilAttributes).')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{
				$attribs[] = preg_quote($attr[0], '/');
			}

			// find occurrences of illegal attribute strings without quotes
			preg_match_all('/(?<!\w)('.implode('|', $evilAttributes).')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{
				$attribs[] = preg_quote($attr[0], '/');
			}

			// replace illegal attribute strings that are inside an html tag
			if (count($attribs) > 0)
			{
				$str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)('.implode('|', $attribs).')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
			}

		}
		while ($count);

		return $str;
	}
    
    
    protected function sanitizeNaughtyHtml($matches)
	{
		return '&lt;'.$matches[1].$matches[2].$matches[3] // encode opening brace
			// encode captured opening or closing brace to prevent recursive vectors:
			.str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);
	}
    
    
	public function xssClean($str, $isImage = FALSE)
	{
		// Is the string an array?
		if (is_array($str))
		{
			while (list($key) = each($str))
			{
				$str[$key] = $this->xssClean($str[$key]);
			}

			return $str;
		}
		//Remove Invisible Characters
		$str = $this->removeInvisibleCharacters($str);
		/*
		 * URL Decode
		 * Just in case stuff like this is submitted:
		 * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		 * Note: Use rawurldecode() so it does not remove plus signs
		 */
		do
		{
			$str = rawurldecode($str);
		}
		while (preg_match('/%[0-9a-f]{2,}/i', $str));
		/*
		 * Convert character entities to ASCII
		 *
		 * This permits our tests below to work reliably.
		 * We only convert entities that are within tags since
		 * these are the ones that will pose security problems.
		 */
		$str = preg_replace_callback("/[^a-z0-9>]+[a-z0-9]+=([\'\"]).*?\\1/si", array($this, 'convertAttribute'), $str);
		$str = preg_replace_callback('/<\w+.*/si', array($this, 'decodeEntity'), $str);
        
		// Remove Invisible Characters Again!
		$str = $this->removeInvisibleCharacters($str);

		/*
		 * Convert all tabs to spaces
		 *
		 * This prevents strings like this: ja	vascript
		 * NOTE: we deal with spaces between characters later.
		 * NOTE: preg_replace was found to be amazingly slow here on
		 * large blocks of data, so we use str_replace.
		 */
		$str = str_replace("\t", ' ', $str);

		// Capture converted string for later comparison
		$convertedString = $str;

		// Remove Strings that are never allowed
		$str = $this->doNeverAllowed($str);

		/*
		 * Makes PHP tags safe
		 *
		 * Note: XML tags are inadvertently replaced too:
		 *
		 * <?xml
		 *
		 * But it doesn't seem to pose a problem.
		 */
		if ($isImage === TRUE)
		{
			// Images have a tendency to have the PHP short opening and
			// closing tags every so often so we skip those and only
			// do the long opening tags.
			$str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
		}
		else
		{
			$str = str_replace(array('<?', '?'.'>'), array('&lt;?', '?&gt;'), $str);
		}

		/*
		 * Compact any exploded words
		 *
		 * This corrects words like:  j a v a s c r i p t
		 * These words are compacted back to their correct state.
		 */
		$words = array(
			'javascript', 'expression', 'vbscript', 'jscript', 'wscript',
			'vbs', 'script', 'base64', 'applet', 'alert', 'document',
			'write', 'cookie', 'window', 'confirm', 'prompt'
		);

		foreach ($words as $word)
		{
			$word = implode('\s*', str_split($word)).'\s*';

			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback('#('.substr($word, 0, -3).')(\W)#is', array($this, 'compactExplodedWords'), $str);
		}

		/*
		 * Remove disallowed Javascript in links or img tags
		 * We used to do some version comparisons and use of stripos(),
		 * but it is dog slow compared to these simplified non-capturing
		 * preg_match(), especially if the pattern exists in the string
		 *
		 * Note: It was reported that not only space characters, but all in
		 * the following pattern can be parsed as separators between a tag name
		 * and its attributes: [\d\s"\'`;,\/\=\(\x00\x0B\x09\x0C]
		 * ... however, removeInvisibleCharacters() above already strips the
		 * hex-encoded ones, so we'll skip them below.
		 */
		do
		{
			$original = $str;

			if (preg_match('/<a/i', $str))
			{
				$str = preg_replace_callback('#<a[^a-z0-9>]+([^>]*?)(?:>|$)#si', array($this, 'jsLinkRemoval'), $str);
			}

			if (preg_match('/<img/i', $str))
			{
				$str = preg_replace_callback('#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si', array($this, 'jsImgRemoval'), $str);
			}

			if (preg_match('/script|xss/i', $str))
			{
				$str = preg_replace('#</*(?:script|xss).*?>#si', '[removed]', $str);
			}
		}
		while($original !== $str);

		unset($original);

		// Remove evil attributes such as style, onclick and xmlns
		$str = $this->removeEvilAttributes($str, $isImage);

		/*
		 * Sanitize naughty HTML elements
		 *
		 * If a tag containing any of the words in the list
		 * below is found, the tag gets converted to entities.
		 *
		 * So this: <blink>
		 * Becomes: &lt;blink&gt;
		 */
		$naughty = 'alert|prompt|confirm|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|button|select|isindex|layer|link|meta|keygen|object|plaintext|style|script|textarea|title|math|video|svg|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, 'sanitizeNaughtyHtml'), $str);

		/*
		 * Sanitize naughty scripting elements
		 *
		 * Similar to above, only instead of looking for
		 * tags it looks for PHP and JavaScript commands
		 * that are disallowed.  Rather than removing the
		 * code, it simply converts the parenthesis to entities
		 * rendering the code un-executable.
		 *
		 * For example:	eval('some code')
		 * Becomes:		eval&#40;'some code'&#41;
		 */
		$str = preg_replace(
			'#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si',
			'\\1\\2&#40;\\3&#41;',
			$str
		);

		// Final clean up
		// This adds a bit of extra precaution in case
		// something got through the above filters
		$str = $this->doNeverAllowed($str);

		/*
		 * Images are Handled in a Special Way
		 * - Essentially, we want to know that after all of the character
		 * conversion is done whether any unwanted, likely XSS, code was found.
		 * If not, we return TRUE, as the image is clean.
		 * However, if the string post-conversion does not matched the
		 * string post-removal of XSS, then it fails, as there was unwanted XSS
		 * code found and removed/changed during processing.
		 */

		if ($isImage === TRUE)
		{
			return ($str === $convertedString);
		}

		return $str;
	}
    
    public function getXssHash()
	{
		if ($this->xssHash == '')
		{
			mt_srand();
			$this->xssHash = md5(time() + mt_rand(0, 1999999999));
		}

		return $this->xssHash;
	}
    
    
    public function entityDecode($str, $charset='gbk')
	{
		if (strpos($str, '&') === FALSE)
		{
			return $str;
		}

		static $entities;

		isset($charset) OR $charset = strtoupper($this->charset);
		$flag = $this->isPhp('5.4')
			? ENT_COMPAT | ENT_HTML5
			: ENT_COMPAT;

		do
		{
			$strCompare = $str;

			// Decode standard entities, avoiding false positives
			if ($c = preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches))
			{
				if ( ! isset($entities))
				{
					$entities = array_map(
						'strtolower',
						$this->isPhp('5.3.4')
							? get_html_translation_table(HTML_ENTITIES, $flag, $charset)
							: get_html_translation_table(HTML_ENTITIES, $flag)
					);

					// If we're not on PHP 5.4+, add the possibly dangerous HTML 5
					// entities to the array manually
					if ($flag === ENT_COMPAT)
					{
						$entities[':'] = '&colon;';
						$entities['('] = '&lpar;';
						$entities[')'] = '&rpar';
						$entities["\n"] = '&newline;';
						$entities["\t"] = '&tab;';
					}
				}

				$replace = array();
				$matches = array_unique(array_map('strtolower', $matches[0]));
				for ($i = 0; $i < $c; $i++)
				{
					if (($char = array_search($matches[$i].';', $entities, TRUE)) !== FALSE)
					{
						$replace[$matches[$i]] = $char;
					}
				}

				$str = str_ireplace(array_keys($replace), array_values($replace), $str);
			}

			// Decode numeric & UTF16 two byte entities
			$str = html_entity_decode(
				preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str),
				$flag,
				$charset
			);
		}
		while ($strCompare !== $str);
		return $str;
	}

    public function isPhp($version = '5.0.0')
    {
        static $isPhp;
        $version = (string)$version;
        if ( ! isset($isPhp[$version]))
        {
            $isPhp[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
        }
        return $isPhp[$version];
    }
    
    function fetchFromArray($array, $index = '', $xssClean = FALSE)
	{
		if ( ! isset($array[$index]))
		{
			return FALSE;
		}

		if ($xssClean === TRUE)
		{
			return $this->xssClean($array[$index]);
		}

		return $array[$index];
	}
    
	public function get($index = NULL, $xssClean = FALSE)
	{
		// Check if a field has been provided
		if ($index === NULL AND ! empty($_GET))
		{
			$get = array();

			// loop through the full _GET array
			foreach (array_keys($_GET) as $key)
			{
				$get[$key] = $this->fetchFromArray($_GET, $key, $xssClean);
			}
			return $get;
		}

		return $this->fetchFromArray($_GET, $index, $xssClean);
	}



	public function post($index = NULL, $xssClean = FALSE)
	{
		// Check if a field has been provided
		if ($index === NULL AND ! empty($_POST))
		{
			$post = array();

			// Loop through the full _POST array and return it
			foreach (array_keys($_POST) as $key)
			{
				$post[$key] = $this->fetchFromArray($_POST, $key, $xssClean);
			}
			return $post;
		}

		return $this->fetchFromArray($_POST, $index, $xssClean);
	}



	public function getPost($index = '', $xssClean = FALSE)
	{
		if ( ! isset($_POST[$index]) )
		{
			return $this->get($index, $xssClean);
		}
		else
		{
			return $this->post($index, $xssClean);
		}
	}


	public function cookie($index = '', $xssClean = FALSE)
	{
		if ($index === NULL AND ! empty($_COOKIE))
		{
			$cookie = array();

			// Loop through the full _POST array and return it
			foreach (array_keys($_COOKIE) as $key)
			{
				$cookie[$key] = $this->fetchFromArray($_COOKIE, $key, $xssClean);
			}
			return $cookie;
		}

		return $this->fetchFromArray($_COOKIE, $index, $xssClean);
    
    }
    
    public function server($index = '', $xssClean = FALSE)
	{
        if ($index === NULL AND ! empty($_SERVER))
		{
			$server = array();

			// Loop through the full _POST array and return it
			foreach (array_keys($_SERVER) as $key)
			{
				$server[$key] = $this->fetchFromArray($_SERVER, $key, $xssClean);
			}
			return $server;
		}
        
		return $this->fetchFromArray($_SERVER, $index, $xssClean);
	}
    
    public function filterAll ()
    {
        $_GET = $this->get(null,true);
        $_POST = $this->post(null,true);
        $_COOKIE = $this->cookie(null,true);
        $_SERVER = $this->server(null,true);
    }
}
