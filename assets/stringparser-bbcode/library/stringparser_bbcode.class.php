<?php
/**
 * BB code string parsing class
 *
 * Version: 0.3.3
 *
 * @author Christian Seiler <spam@christian-seiler.de>
 * @copyright Christian Seiler 2004-2008
 * @package stringparser
 *
 * The MIT License
 *
 * Copyright (c) 2004-2008 Christian Seiler
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
 
require_once dirname(__FILE__).'/stringparser.class.php';

define ('BBCODE_CLOSETAG_FORBIDDEN', -1);
define ('BBCODE_CLOSETAG_OPTIONAL', 0);
define ('BBCODE_CLOSETAG_IMPLICIT', 1);
define ('BBCODE_CLOSETAG_IMPLICIT_ON_CLOSE_ONLY', 2);
define ('BBCODE_CLOSETAG_MUSTEXIST', 3);

define ('BBCODE_NEWLINE_PARSE', 0);
define ('BBCODE_NEWLINE_IGNORE', 1);
define ('BBCODE_NEWLINE_DROP', 2);

define ('BBCODE_PARAGRAPH_ALLOW_BREAKUP', 0);
define ('BBCODE_PARAGRAPH_ALLOW_INSIDE', 1);
define ('BBCODE_PARAGRAPH_BLOCK_ELEMENT', 2);

/**
 * BB code string parser class
 *
 * @package stringparser
 */
class StringParser_BBCode extends StringParser {
	/**
	 * String parser mode
	 *
	 * The BBCode string parser works in search mode
	 *
	 * @access protected
	 * @var int
	 * @see STRINGPARSER_MODE_SEARCH, STRINGPARSER_MODE_LOOP
	 */
	var $_parserMode = STRINGPARSER_MODE_SEARCH;
	
	/**
	 * Defined BB Codes
	 *
	 * The registered BB codes
	 *
	 * @access protected
	 * @var array
	 */
	var $_codes = array ();
	
	/**
	 * Registered parsers
	 *
	 * @access protected
	 * @var array
	 */
	var $_parsers = array ();
	
	/**
	 * Defined maximum occurrences
	 *
	 * @access protected
	 * @var array
	 */
	var $_maxOccurrences = array ();
	
	/**
	 * Root content type
	 *
	 * @access protected
	 * @var string
	 */
	var $_rootContentType = 'block';
	
	/**
	 * Do not output but return the tree
	 *
	 * @access protected
	 * @var bool
	 */
	var $_noOutput = false;
	
	/**
	 * Global setting: case sensitive
	 *
	 * @access protected
	 * @var bool
	 */
	var $_caseSensitive = true;
	
	/**
	 * Root paragraph handling enabled
	 *
	 * @access protected
	 * @var bool
	 */
	var $_rootParagraphHandling = false;
	
	/**
	 * Paragraph handling parameters
	 * @access protected
	 * @var array
	 */
	var $_paragraphHandling = array (
		'detect_string' => "\n\n",
		'start_tag' => '<p>',
		'end_tag' => "</p>\n"
	);
	
	/**
	 * Allow mixed attribute types (e.g. [code=bla attr=blub])
	 * @access private
	 * @var bool
	 */
	var $_mixedAttributeTypes = false;
	
	/**
	 * Whether to call validation function again (with $action == 'validate_auto') when closetag comes
	 * @access protected
	 * @var bool
	 */
	var $_validateAgain = false;
	
	/**
	 * Add a code
	 *
	 * @access public
	 * @param string $name The name of the code
	 * @param string $callback_type See documentation
	 * @param string $callback_func The callback function to call
	 * @param array $callback_params The callback parameters
	 * @param string $content_type See documentation
	 * @param array $allowed_within See documentation
	 * @param array $not_allowed_within See documentation
	 * @return bool
	 */
	function addCode ($name, $callback_type, $callback_func, $callback_params, $content_type, $allowed_within, $not_allowed_within) {
		if (isset ($this->_codes[$name])) {
			return false; // already exists
		}
		if (!preg_match ('/^[a-zA-Z0-9*_!+-]+$/', $name)) {
			return false; // invalid
		}
		$this->_codes[$name] = array (
			'name' => $name,
			'callback_type' => $callback_type,
			'callback_func' => $callback_func,
			'callback_params' => $callback_params,
			'content_type' => $content_type,
			'allowed_within' => $allowed_within,
			'not_allowed_within' => $not_allowed_within,
			'flags' => array ()
		);
		return true;
	}
	
	/**
	 * Remove a code
	 *
	 * @access public
	 * @param $name The code to remove
	 * @return bool
	 */
	function removeCode ($name) {
		if (isset ($this->_codes[$name])) {
			unset ($this->_codes[$name]);
			return true;
		}
		return false;
	}
	
	/**
	 * Remove all codes
	 *
	 * @access public
	 */
	function removeAllCodes () {
		$this->_codes = array ();
	}
	
	/**
	 * Set a code flag
	 *
	 * @access public
	 * @param string $name The name of the code
	 * @param string $flag The name of the flag to set
	 * @param mixed $value The value of the flag to set
	 * @return bool
	 */
	function setCodeFlag ($name, $flag, $value) {
		if (!isset ($this->_codes[$name])) {
			return false;
		}
		$this->_codes[$name]['flags'][$flag] = $value;
		return true;
	}
	
	/**
	 * Set occurrence type
	 *
	 * Example:
	 *   $bbcode->setOccurrenceType ('url', 'link');
	 *   $bbcode->setMaxOccurrences ('link', 4);
	 * Would create the situation where a link may only occur four
	 * times in the hole text.
	 *
	 * @access public
	 * @param string $code The name of the code
	 * @param string $type The name of the occurrence type to set
	 * @return bool
	 */
	function setOccurrenceType ($code, $type) {
		return $this->setCodeFlag ($code, 'occurrence_type', $type);
	}
	
	/**
	 * Set maximum number of occurrences
	 *
	 * @access public
	 * @param string $type The name of the occurrence type
	 * @param int $count The maximum number of occurrences
	 * @return bool
	 */
	function setMaxOccurrences ($type, $count) {
		settype ($count, 'integer');
		if ($count < 0) { // sorry, does not make any sense
			return false;
		}
		$this->_maxOccurrences[$type] = $count;
		return true;
	}
	
	/**
	 * Add a parser
	 *
	 * @access public
	 * @param string $type The content type for which the parser is to add
	 * @param mixed $parser The function to call
	 * @return bool
	 */
	function addParser ($type, $parser) {
		if (is_array ($type)) {
			foreach ($type as $t) {
				$this->addParser ($t, $parser);
			}
			return true;
		}
		if (!isset ($this->_parsers[$type])) {
			$this->_parsers[$type] = array ();
		}
		$this->_parsers[$type][] = $parser;
		return true;
	}
	
	/**
	 * Set root content type
	 *
	 * @access public
	 * @param string $content_type The new root content type
	 */
	function setRootContentType ($content_type) {
		$this->_rootContentType = $content_type;
	}
	
	/**
	 * Set paragraph handling on root element
	 *
	 * @access public
	 * @param bool $enabled The new status of paragraph handling on root element
	 */
	function setRootParagraphHandling ($enabled) {
		$this->_rootParagraphHandling = (bool)$enabled;
	}
	
	/**
	 * Set paragraph handling parameters
	 *
	 * @access public
	 * @param string $detect_string The string to detect
	 * @param string $start_tag The replacement for the start tag (e.g. <p>)
	 * @param string $end_tag The replacement for the start tag (e.g. </p>)
	 */
	function setParagraphHandlingParameters ($detect_string, $start_tag, $end_tag) {
		$this->_paragraphHandling = array (
			'detect_string' => $detect_string,
			'start_tag' => $start_tag,
			'end_tag' => $end_tag
		);
	}
	
	/**
	 * Set global case sensitive flag
	 *
	 * If this is set to true, the class normally is case sensitive, but
	 * the case_sensitive code flag may override this for a single code.
	 *
	 * If this is set to false, all codes are case insensitive.
	 *
	 * @access public
	 * @param bool $caseSensitive
	 */
	function setGlobalCaseSensitive ($caseSensitive) {
		$this->_caseSensitive = (bool)$caseSensitive;
	}
	
	/**
	 * Get global case sensitive flag
	 *
	 * @access public
	 * @return bool
	 */
	function globalCaseSensitive () {
		return $this->_caseSensitive;
	}
	
	/**
	 * Set mixed attribute types flag
	 *
	 * If set, [code=val1 attr=val2] will cause 2 attributes to be parsed:
	 * 'default' will have value 'val1', 'attr' will have value 'val2'.
	 * If not set, only one attribute 'default' will have the value
	 * 'val1 attr=val2' (the default and original behaviour)
	 *
	 * @access public
	 * @param bool $mixedAttributeTypes
	 */
	function setMixedAttributeTypes ($mixedAttributeTypes) {
		$this->_mixedAttributeTypes = (bool)$mixedAttributeTypes;
	}
	
	/**
	 * Get mixed attribute types flag
	 *
	 * @access public
	 * @return bool
	 */
	function mixedAttributeTypes () {
		return $this->_mixedAttributeTypes;
	}
	
	/**
	 * Set validate again flag
	 *
	 * If this is set to true, the class calls the validation function
	 * again with $action == 'validate_again' when closetag comes.
	 *
	 * @access public
	 * @param bool $validateAgain
	 */
	function setValidateAgain ($validateAgain) {
		$this->_validateAgain = (bool)$validateAgain;
	}
	
	/**
	 * Get validate again flag
	 *
	 * @access public
	 * @return bool
	 */
	function validateAgain () {
		return $this->_validateAgain;
	}
	
	/**
	 * Get a code flag
	 *
	 * @access public
	 * @param string $name The name of the code
	 * @param string $flag The name of the flag to get
	 * @param string $type The type of the return value
	 * @param mixed $default The default return value
	 * @return bool
	 */
	function getCodeFlag ($name, $flag, $type = 'mixed', $default = null) {
		if (!isset ($this->_codes[$name])) {
			return $default;
		}
		if (!array_key_exists ($flag, $this->_codes[$name]['flags'])) {
			return $default;
		}
		$return = $this->_codes[$name]['flags'][$flag];
		if ($type != 'mixed') {
			settype ($return, $type);
		}
		return $return;
	}
	
	/**
	 * Set a specific status
	 * @access protected
	 */
	function _setStatus ($status) {
		switch ($status) {
			case 0:
				$this->_charactersSearch = array ('[/', '[');
				$this->_status = $status;
				break;
			case 1:
				$this->_charactersSearch = array (']', ' = "', '="', ' = \'', '=\'', ' = ', '=', ': ', ':', ' ');
				$this->_status = $status;
				break;
			case 2:
				$this->_charactersSearch = array (']');
				$this->_status = $status;
				$this->_savedName = '';
				break;
			case 3:
				if ($this->_quoting !== null) {
					if ($this->_mixedAttributeTypes) {
						$this->_charactersSearch = array ('\\\\', '\\'.$this->_quoting, $this->_quoting.' ', $this->_quoting.']', $this->_quoting);
					} else {
						$this->_charactersSearch = array ('\\\\', '\\'.$this->_quoting, $this->_quoting.']', $this->_quoting);
					}
					$this->_status = $status;
					break;
				}
				if ($this->_mixedAttributeTypes) {
					$this->_charactersSearch = array (' ', ']');
				} else {
					$this->_charactersSearch = array (']');
				}
				$this->_status = $status;
				break;
			case 4:
				$this->_charactersSearch = array (' ', ']', '="', '=\'', '=');
				$this->_status = $status;
				$this->_savedName = '';
				$this->_savedValue = '';
				break;
			case 5:
				if ($this->_quoting !== null) {
					$this->_charactersSearch = array ('\\\\', '\\'.$this->_quoting, $this->_quoting.' ', $this->_quoting.']', $this->_quoting);
				} else {
					$this->_charactersSearch = array (' ', ']');
				}
				$this->_status = $status;
				$this->_savedValue = '';
				break;
			case 7:
				$this->_charactersSearch = array ('[/'.$this->_topNode ('name').']');
				if (!$this->_topNode ('getFlag', 'case_sensitive', 'boolean', true) || !$this->_caseSensitive) {
					$this->_charactersSearch[] = '[/';
				}
				$this->_status = $status;
				break;
			default:
				return false;
		}
		return true;
	}
	
	/**
	 * Abstract method Append text depending on current status
	 * @access protected
	 * @param string $text The text to append
	 * @return bool On success, the function returns true, else false
	 */
	function _appendText ($text) {
		if (!strlen ($text)) {
			return true;
		}
		switch ($this->_status) {
			case 0:
			case 7:
				return $this->_appendToLastTextChild ($text);
			case 1:
				return $this->_topNode ('appendToName', $text);
			case 2:
			case 4:
				$this->_savedName .= $text;
				return true;
			case 3:
				return $this->_topNode ('appendToAttribute', 'default', $text);
			case 5:
				$this->_savedValue .= $text;
				return true;
			default:
				return false;
		}
	}
	
	/**
	 * Restart parsing after current block
	 *
	 * To achieve this the current top stack object is removed from the
	 * tree. Then the current item
	 *
	 * @access protected
	 * @return bool
	 */
	function _reparseAfterCurrentBlock () {
		if ($this->_status == 2) {
			// this status will *never* call _reparseAfterCurrentBlock itself
			// so this is called if the loop ends
			// therefore, just add the [/ to the text
			
			// _savedName should be empty but just in case
			$this->_cpos -= strlen ($this->_savedName);
			$this->_savedName = '';
			$this->_status = 0;
			$this->_appendText ('[/');
			return true;
		} else {
			return parent::_reparseAfterCurrentBlock ();
		}
	}
	
	/**
	 * Apply parsers
	 */
	function _applyParsers ($type, $text) {
		if (!isset ($this->_parsers[$type])) {
			return $text;
		}
		foreach ($this->_parsers[$type] as $parser) {
			if (is_callable ($parser)) {
				$ntext = call_user_func ($parser, $text);
				if (is_string ($ntext)) {
					$text = $ntext;
				}
			}
		}
		return $text;
	}
	
	/**
	 * Handle status
	 * @access protected
	 * @param int $status The current status
	 * @param string $needle The needle that was found
	 * @return bool
	 */
	function _handleStatus ($status, $needle) {
		switch ($status) {
			case 0: // NORMAL TEXT
				if ($needle != '[' && $needle != '[/') {
					$this->_appendText ($needle);
					return true;
				}
				if ($needle == '[') {
					$node =& new StringParser_BBCode_Node_Element ($this->_cpos);
					$res = $this->_pushNode ($node);
					if (!$res) {
						return false;
					}
					$this->_setStatus (1);
				} else if ($needle == '[/') {
					if (count ($this->_stack) <= 1) {
						$this->_appendText ($needle);
						return true;
					}
					$this->_setStatus (2);
				}
				break;
			case 1: // OPEN TAG
				if ($needle == ']') {
					return $this->_openElement (0);
				} else if (trim ($needle) == ':' || trim ($needle) == '=') {
					$this->_quoting = null;
					$this->_setStatus (3); // default value parser
					break;
				} else if (trim ($needle) == '="' || trim ($needle) == '= "' || trim ($needle) == '=\'' || trim ($needle) == '= \'') {
					$this->_quoting = substr (trim ($needle), -1);
					$this->_setStatus (3); // default value parser with quotation
					break;
				} else if ($needle == ' ') {
					$this->_setStatus (4); // attribute parser
					break;
				} else {
					$this->_appendText ($needle);
					return true;
				}
				// break not necessary because every if clause contains return
			case 2: // CLOSE TAG
				if ($needle != ']') {
					$this->_appendText ($needle);
					return true;
				}
				$closecount = 0;
				if (!$this->_isCloseable ($this->_savedName, $closecount)) {
					$this->_setStatus (0);
					$this->_appendText ('[/'.$this->_savedName.$needle);
					return true;
				}
				// this validates the code(s) to be closed after the content tree of
				// that code(s) are built - if the second validation fails, we will have
				// to reparse. note that as _reparseAfterCurrentBlock will not work correctly
				// if we're in $status == 2, we will have to set our status to 0 manually
				if (!$this->_validateCloseTags ($closecount)) {
					$this->_setStatus (0);
					return $this->_reparseAfterCurrentBlock ();
				}
				$this->_setStatus (0);
				for ($i = 0; $i < $closecount; $i++) {
					if ($i == $closecount - 1) {
						$this->_topNode ('setHadCloseTag');
					}
					if (!$this->_popNode ()) {
						return false;
					}
				}
				break;
			case 3: // DEFAULT ATTRIBUTE
				if ($this->_quoting !== null) {
					if ($needle == '\\\\') {
						$this->_appendText ('\\');
						return true;
					} else if ($needle == '\\'.$this->_quoting) {
						$this->_appendText ($this->_quoting);
						return true;
					} else if ($needle == $this->_quoting.' ') {
						$this->_setStatus (4);
						return true;
					} else if ($needle == $this->_quoting.']') {
						return $this->_openElement (2);
					} else if ($needle == $this->_quoting) {
						// can't be, only ']' and ' ' allowed after quoting char
						return $this->_reparseAfterCurrentBlock ();
					} else {
						$this->_appendText ($needle);
						return true;
					}
				} else {
					if ($needle == ' ') {
						$this->_setStatus (4);
						return true;
					} else if ($needle == ']') {
						return $this->_openElement (2);
					} else {
						$this->_appendText ($needle);
						return true;
					}
				}
				// break not needed because every if clause contains return!
			case 4: // ATTRIBUTE NAME
				if ($needle == ' ') {
					if (strlen ($this->_savedName)) {
						$this->_topNode ('setAttribute', $this->_savedName, true);
					}
					// just ignore and continue in same mode
					$this->_setStatus (4); // reset parameters
					return true;
				} else if ($needle == ']') {
					if (strlen ($this->_savedName)) {
						$this->_topNode ('setAttribute', $this->_savedName, true);
					}
					return $this->_openElement (2);
				} else if ($needle == '=') {
					$this->_quoting = null;
					$this->_setStatus (5);
					return true;
				} else if ($needle == '="') {
					$this->_quoting = '"';
					$this->_setStatus (5);
					return true;
				} else if ($needle == '=\'') {
					$this->_quoting = '\'';
					$this->_setStatus (5);
					return true;
				} else {
					$this->_appendText ($needle);
					return true;
				}
				// break not needed because every if clause contains return!
			case 5: // ATTRIBUTE VALUE
				if ($this->_quoting !== null) {
					if ($needle == '\\\\') {
						$this->_appendText ('\\');
						return true;
					} else if ($needle == '\\'.$this->_quoting) {
						$this->_appendText ($this->_quoting);
						return true;
					} else if ($needle == $this->_quoting.' ') {
						$this->_topNode ('setAttribute', $this->_savedName, $this->_savedValue);
						$this->_setStatus (4);
						return true;
					} else if ($needle == $this->_quoting.']') {
						$this->_topNode ('setAttribute', $this->_savedName, $this->_savedValue);
						return $this->_openElement (2);
					} else if ($needle == $this->_quoting) {
						// can't be, only ']' and ' ' allowed after quoting char
						return $this->_reparseAfterCurrentBlock ();
					} else {
						$this->_appendText ($needle);
						return true;
					}
				} else {
					if ($needle == ' ') {
						$this->_topNode ('setAttribute', $this->_savedName, $this->_savedValue);
						$this->_setStatus (4);
						return true;
					} else if ($needle == ']') {
						$this->_topNode ('setAttribute', $this->_savedName, $this->_savedValue);
						return $this->_openElement (2);
					} else {
						$this->_appendText ($needle);
						return true;
					}
				}
				// break not needed because every if clause contains return!
			case 7:
				if ($needle == '[/') {
					// this was case insensitive match
					if (strtolower (substr ($this->_text, $this->_cpos + strlen ($needle), strlen ($this->_topNode ('name')) + 1)) == strtolower ($this->_topNode ('name').']')) {
						// this matched
						$this->_cpos += strlen ($this->_topNode ('name')) + 1;
					} else {
						// it didn't match
						$this->_appendText ($needle);
						return true;
					}
				}
				$closecount = $this->_savedCloseCount;
				if (!$this->_topNode ('validate')) {
					return $this->_reparseAfterCurrentBlock ();
				}
				// do we have to close subnodes?
				if ($closecount) {
					// get top node
					$mynode =& $this->_stack[count ($this->_stack)-1];
					// close necessary nodes
					for ($i = 0; $i <= $closecount; $i++) {
						if (!$this->_popNode ()) {
							return false;
						}
					}
					if (!$this->_pushNode ($mynode)) {
						return false;
					}
				}
				$this->_setStatus (0);
				$this->_popNode ();
				return true;
			default: 
				return false;
		}
		return true;
	}
	
	/**
	 * Open the next element
	 *
	 * @access protected
	 * @return bool
	 */
	function _openElement ($type = 0) {
		$name = $this->_getCanonicalName ($this->_topNode ('name'));
		if ($name === false) {
			return $this->_reparseAfterCurrentBlock ();
		}
		$occ_type = $this->getCodeFlag ($name, 'occurrence_type', 'string');
		if ($occ_type !== null && isset ($this->_maxOccurrences[$occ_type])) {
			$max_occs = $this->_maxOccurrences[$occ_type];
			$occs = $this->_root->getNodeCountByCriterium ('flag:occurrence_type', $occ_type);
			if ($occs >= $max_occs) {
				return $this->_reparseAfterCurrentBlock ();
			}
		}
		$closecount = 0;
		$this->_topNode ('setCodeInfo', $this->_codes[$name]);
		if (!$this->_isOpenable ($name, $closecount)) {
			return $this->_reparseAfterCurrentBlock ();
		}
		$this->_setStatus (0);
		switch ($type) {
		case 0:
			$cond = $this->_isUseContent ($this->_stack[count($this->_stack)-1], false);
			break;
		case 1:
			$cond = $this->_isUseContent ($this->_stack[count($this->_stack)-1], true);
			break;
		case 2:
			$cond = $this->_isUseContent ($this->_stack[count($this->_stack)-1], true);
			break;
		default:
			$cond = false;
			break;
		}
		if ($cond) {
			$this->_savedCloseCount = $closecount;
			$this->_setStatus (7);
			return true;
		}
		if (!$this->_topNode ('validate')) {
			return $this->_reparseAfterCurrentBlock ();
		}
		// do we have to close subnodes?
		if ($closecount) {
			// get top node
			$mynode =& $this->_stack[count ($this->_stack)-1];
			// close necessary nodes
			for ($i = 0; $i <= $closecount; $i++) {
				if (!$this->_popNode ()) {
					return false;
				}
			}
			if (!$this->_pushNode ($mynode)) {
				return false;
			}
		}
		
		if ($this->_codes[$name]['callback_type'] == 'simple_replace_single' || $this->_codes[$name]['callback_type'] == 'callback_replace_single') {
			if (!$this->_popNode ())  {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Is a node closeable?
	 *
	 * @access protected
	 * @return bool
	 */
	function _isCloseable ($name, &$closecount) {
		$node =& $this->_findNamedNode ($name, false);
		if ($node === false) {
			return false;
		}
		$scount = count ($this->_stack);
		for ($i = $scount - 1; $i > 0; $i--) {
			$closecount++;
			if ($this->_stack[$i]->equals ($node)) {
				return true;
			}
			if ($this->_stack[$i]->getFlag ('closetag', 'integer', BBCODE_CLOSETAG_IMPLICIT) == BBCODE_CLOSETAG_MUSTEXIST) {
				return false;
			}
		}
		return false;
	}
	
	/**
	 * Revalidate codes when close tags appear
	 *
	 * @access protected
	 * @return bool
	 */
	function _validateCloseTags ($closecount) {
		$scount = count ($this->_stack);
		for ($i = $scount - 1; $i >= $scount - $closecount; $i--) {
			if ($this->_validateAgain) {
				if (!$this->_stack[$i]->validate ('validate_again')) {
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * Is a node openable?
	 *
	 * @access protected
	 * @return bool
	 */
	function _isOpenable ($name, &$closecount) {
		if (!isset ($this->_codes[$name])) {
			return false;
		}
		
		$closecount = 0;
		
		$allowed_within = $this->_codes[$name]['allowed_within'];
		$not_allowed_within = $this->_codes[$name]['not_allowed_within'];
		
		$scount = count ($this->_stack);
		if ($scount == 2) { // top level element
			if (!in_array ($this->_rootContentType, $allowed_within)) {
				return false;
			}
		} else {
			if (!in_array ($this->_stack[$scount-2]->_codeInfo['content_type'], $allowed_within)) {
				return $this->_isOpenableWithClose ($name, $closecount);
			}
		}
		
		for ($i = 1; $i < $scount - 1; $i++) {
			if (in_array ($this->_stack[$i]->_codeInfo['content_type'], $not_allowed_within)) {
				return $this->_isOpenableWithClose ($name, $closecount);
			}
		}
		
		return true;
	}
	
	/**
	 * Is a node openable by closing other nodes?
	 *
	 * @access protected
	 * @return bool
	 */
	function _isOpenableWithClose ($name, &$closecount) {
		$tnname = $this->_getCanonicalName ($this->_topNode ('name'));
		if (!in_array ($this->getCodeFlag ($tnname, 'closetag', 'integer', BBCODE_CLOSETAG_IMPLICIT), array (BBCODE_CLOSETAG_FORBIDDEN, BBCODE_CLOSETAG_OPTIONAL))) {
			return false;
		}
		$node =& $this->_findNamedNode ($name, true);
		if ($node === false) {
			return false;
		}
		$scount = count ($this->_stack);
		if ($scount < 3) {
			return false;
		}
		for ($i = $scount - 2; $i > 0; $i--) {
			$closecount++;
			if ($this->_stack[$i]->equals ($node)) {
				return true;
			}
			if (in_array ($this->_stack[$i]->getFlag ('closetag', 'integer', BBCODE_CLOSETAG_IMPLICIT), array (BBCODE_CLOSETAG_IMPLICIT_ON_CLOSE_ONLY, BBCODE_CLOSETAG_MUSTEXIST))) {
				return false;
			}
			if ($this->_validateAgain) {
				if (!$this->_stack[$i]->validate ('validate_again')) {
					return false;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Abstract method: Close remaining blocks
	 * @access protected
	 */
	function _closeRemainingBlocks () {
		// everything closed
		if (count ($this->_stack) == 1) {
			return true;
		}
		// not everything close
		if ($this->strict) {
			return false;
		}
		while (count ($this->_stack) > 1) {
			if ($this->_topNode ('getFlag', 'closetag', 'integer', BBCODE_CLOSETAG_IMPLICIT) == BBCODE_CLOSETAG_MUSTEXIST) {
				return false; // sorry
			}
			$res = $this->_popNode ();
			if (!$res) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Find a node with a specific name in stack
	 *
	 * @access protected
	 * @return mixed
	 */
	function &_findNamedNode ($name, $searchdeeper = false) {
		$lname = $this->_getCanonicalName ($name);
		$case_sensitive = $this->_caseSensitive && $this->getCodeFlag ($lname, 'case_sensitive', 'boolean', true);
		if ($case_sensitive) {
			$name = strtolower ($name);
		}
		$scount = count ($this->_stack);
		if ($searchdeeper) {
			$scount--;
		}
		for ($i = $scount - 1; $i > 0; $i--) {
			if (!$case_sensitive) {
				$cmp_name = strtolower ($this->_stack[$i]->name ());
			} else {
				$cmp_name = $this->_stack[$i]->name ();
			}
			if ($cmp_name == $lname) {
				return $this->_stack[$i];
			}
		}
		$result = false;
		return $result;
	}
	
	/**
	 * Abstract method: Output tree
	 * @access protected
	 * @return bool
	 */
	function _outputTree () {
		if ($this->_noOutput) {
			return true;
		}
		$output = $this->_outputNode ($this->_root);
		if (is_string ($output)) {
			$this->_output = $this->_applyPostfilters ($output);
			unset ($output);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Output a node
	 * @access protected
	 * @return bool
	 */
	function _outputNode (&$node) {
		$output = '';
		if ($node->_type == STRINGPARSER_BBCODE_NODE_PARAGRAPH || $node->_type == STRINGPARSER_BBCODE_NODE_ELEMENT || $node->_type == STRINGPARSER_NODE_ROOT) {
			$ccount = count ($node->_children);
			for ($i = 0; $i < $ccount; $i++) {
				$suboutput = $this->_outputNode ($node->_children[$i]);
				if (!is_string ($suboutput)) {
					return false;
				}
				$output .= $suboutput;
			}
			if ($node->_type == STRINGPARSER_BBCODE_NODE_PARAGRAPH) {
				return $this->_paragraphHandling['start_tag'].$output.$this->_paragraphHandling['end_tag'];
			}
			if ($node->_type == STRINGPARSER_BBCODE_NODE_ELEMENT) {
				return $node->getReplacement ($output);
			}
			return $output;
		} else if ($node->_type == STRINGPARSER_NODE_TEXT) {
			$output = $node->content;
			$before = '';
			$after = '';
			$ol = strlen ($output);
			switch ($node->getFlag ('newlinemode.begin', 'integer', BBCODE_NEWLINE_PARSE)) {
			case BBCODE_NEWLINE_IGNORE:
				if ($ol && $output{0} == "\n") {
					$before = "\n";
				}
				// don't break!
			case BBCODE_NEWLINE_DROP:
				if ($ol && $output{0} == "\n") {
					$output = substr ($output, 1);
					$ol--;
				}
				break;
			}
			switch ($node->getFlag ('newlinemode.end', 'integer', BBCODE_NEWLINE_PARSE)) {
			case BBCODE_NEWLINE_IGNORE:
				if ($ol && $output{$ol-1} == "\n") {
					$after = "\n";
				}
				// don't break!
			case BBCODE_NEWLINE_DROP:
				if ($ol && $output{$ol-1} == "\n") {
					$output = substr ($output, 0, -1);
					$ol--;
				}
				break;
			}
			// can't do anything
			if ($node->_parent === null) {
				return $before.$output.$after;
			}
			if ($node->_parent->_type == STRINGPARSER_BBCODE_NODE_PARAGRAPH)  {
				$parent =& $node->_parent;
				unset ($node);
				$node =& $parent;
				unset ($parent);
				// if no parent for this paragraph
				if ($node->_parent === null) {
					return $before.$output.$after;
				}
			}
			if ($node->_parent->_type == STRINGPARSER_NODE_ROOT) {
				return $before.$this->_applyParsers ($this->_rootContentType, $output).$after;
			}
			if ($node->_parent->_type == STRINGPARSER_BBCODE_NODE_ELEMENT) {
				return $before.$this->_applyParsers ($node->_parent->_codeInfo['content_type'], $output).$after;
			}
			return $before.$output.$after;
		}
	}
	
	/**
	 * Abstract method: Manipulate the tree
	 * @access protected
	 * @return bool
	 */
	function _modifyTree () {
		// first pass: try to do newline handling
		$nodes =& $this->_root->getNodesByCriterium ('needsTextNodeModification', true);
		$nodes_count = count ($nodes);
		for ($i = 0; $i < $nodes_count; $i++) {
			$v = $nodes[$i]->getFlag ('opentag.before.newline', 'integer', BBCODE_NEWLINE_PARSE);
			if ($v != BBCODE_NEWLINE_PARSE) {
				$n =& $nodes[$i]->findPrevAdjentTextNode ();
				if (!is_null ($n)) {
					$n->setFlag ('newlinemode.end', $v);
				}
				unset ($n);
			}
			$v = $nodes[$i]->getFlag ('opentag.after.newline', 'integer', BBCODE_NEWLINE_PARSE);
			if ($v != BBCODE_NEWLINE_PARSE) {
				$n =& $nodes[$i]->firstChildIfText ();
				if (!is_null ($n)) {
					$n->setFlag ('newlinemode.begin', $v);
				}
				unset ($n);
			}
			$v = $nodes[$i]->getFlag ('closetag.before.newline', 'integer', BBCODE_NEWLINE_PARSE);
			if ($v != BBCODE_NEWLINE_PARSE) {
				$n =& $nodes[$i]->lastChildIfText ();
				if (!is_null ($n)) {
					$n->setFlag ('newlinemode.end', $v);
				}
				unset ($n);
			}
			$v = $nodes[$i]->getFlag ('closetag.after.newline', 'integer', BBCODE_NEWLINE_PARSE);
			if ($v != BBCODE_NEWLINE_PARSE) {
				$n =& $nodes[$i]->findNextAdjentTextNode ();
				if (!is_null ($n)) {
					$n->setFlag ('newlinemode.begin', $v);
				}
				unset ($n);
			}
		}
		
		// second pass a: do paragraph handling on root element
		if ($this->_rootParagraphHandling) {
			$res = $this->_handleParagraphs ($this->_root);
			if (!$res) {
				return false;
			}
		}
		
		// second pass b: do paragraph handling on other elements
		unset ($nodes);
		$nodes =& $this->_root->getNodesByCriterium ('flag:paragraphs', true);
		$nodes_count = count ($nodes);
		for ($i = 0; $i < $nodes_count; $i++) {
			$res = $this->_handleParagraphs ($nodes[$i]);
			if (!$res) {
				return false;
			}
		}
		
		// second pass c: search for empty paragraph nodes and remove them
		unset ($nodes);
		$nodes =& $this->_root->getNodesByCriterium ('empty', true);
		$nodes_count = count ($nodes);
		if (isset ($parent)) {
			unset ($parent); $parent = null;
		}
		for ($i = 0; $i < $nodes_count; $i++) {
			if ($nodes[$i]->_type != STRINGPARSER_BBCODE_NODE_PARAGRAPH) {
				continue;
			}
			unset ($parent);
			$parent =& $nodes[$i]->_parent;
			$parent->removeChild ($nodes[$i], true);
		}
		
		return true;
	}
	
	/**
	 * Handle paragraphs
	 * @access protected
	 * @param object $node The node to handle
	 * @return bool
	 */
	function _handleParagraphs (&$node) {
		// if this node is already a subnode of a paragraph node, do NOT 
		// do paragraph handling on this node!
		if ($this->_hasParagraphAncestor ($node)) {
			return true;
		}
		$dest_nodes = array ();
		$last_node_was_paragraph = false;
		$prevtype = STRINGPARSER_NODE_TEXT;
		$paragraph = null;
		while (count ($node->_children)) {
			$mynode =& $node->_children[0];
			$node->removeChild ($mynode);
			$subprevtype = $prevtype;
			$sub_nodes =& $this->_breakupNodeByParagraphs ($mynode);
			for ($i = 0; $i < count ($sub_nodes); $i++) {
				if (!$last_node_was_paragraph ||  ($prevtype == $sub_nodes[$i]->_type && ($i != 0 || $prevtype != STRINGPARSER_BBCODE_NODE_ELEMENT))) {
					unset ($paragraph);
					$paragraph =& new StringParser_BBCode_Node_Paragraph ();
				}
				$prevtype = $sub_nodes[$i]->_type;
				if ($sub_nodes[$i]->_type != STRINGPARSER_BBCODE_NODE_ELEMENT || $sub_nodes[$i]->getFlag ('paragraph_type', 'integer', BBCODE_PARAGRAPH_ALLOW_BREAKUP) != BBCODE_PARAGRAPH_BLOCK_ELEMENT) {
					$paragraph->appendChild ($sub_nodes[$i]);
					$dest_nodes[] =& $paragraph;
					$last_node_was_paragraph = true;
				} else {
					$dest_nodes[] =& $sub_nodes[$i];
					$last_onde_was_paragraph = false;
					unset ($paragraph);
					$paragraph =& new StringParser_BBCode_Node_Paragraph ();
				}
			}
		}
		$count = count ($dest_nodes);
		for ($i = 0; $i < $count; $i++) {
			$node->appendChild ($dest_nodes[$i]);
		}
		unset ($dest_nodes);
		unset ($paragraph);
		return true;
	}
	
	/**
	 * Search for a paragraph node in tree in upward direction
	 * @access protected
	 * @param object $node The node to analyze
	 * @return bool
	 */
	function _hasParagraphAncestor (&$node) {
		if ($node->_parent === null) {
			return false;
		}
		$parent =& $node->_parent;
		if ($parent->_type == STRINGPARSER_BBCODE_NODE_PARAGRAPH) {
			return true;
		}
		return $this->_hasParagraphAncestor ($parent);
	}
	
	/**
	 * Break up nodes
	 * @access protected
	 * @param object $node The node to break up
	 * @return array
	 */
	function &_breakupNodeByParagraphs (&$node) {
		$detect_string = $this->_paragraphHandling['detect_string'];
		$dest_nodes = array ();
		// text node => no problem
		if ($node->_type == STRINGPARSER_NODE_TEXT) {
			$cpos = 0;
			while (($npos = strpos ($node->content, $detect_string, $cpos)) !== false) {
				$subnode =& new StringParser_Node_Text (substr ($node->content, $cpos, $npos - $cpos), $node->occurredAt + $cpos);
				// copy flags
				foreach ($node->_flags as $flag => $value) {
					if ($flag == 'newlinemode.begin') {
						if ($cpos == 0) {
							$subnode->setFlag ($flag, $value);
						}
					} else if ($flag == 'newlinemode.end') {
						// do nothing
					} else {
						$subnode->setFlag ($flag, $value);
					}
				}
				$dest_nodes[] =& $subnode;
				unset ($subnode);
				$cpos = $npos + strlen ($detect_string);
			}
			$subnode =& new StringParser_Node_Text (substr ($node->content, $cpos), $node->occurredAt + $cpos);
			if ($cpos == 0) {
				$value = $node->getFlag ('newlinemode.begin', 'integer', null);
				if ($value !== null) {
					$subnode->setFlag ('newlinemode.begin', $value);
				}
			}
			$value = $node->getFlag ('newlinemode.end', 'integer', null);
			if ($value !== null) {
				$subnode->setFlag ('newlinemode.end', $value);
			}
			$dest_nodes[] =& $subnode;
			unset ($subnode);
			return $dest_nodes;
		}
		// not a text node or an element node => no way
		if ($node->_type != STRINGPARSER_BBCODE_NODE_ELEMENT) {
			$dest_nodes[] =& $node;
			return $dest_nodes;
		}
		if ($node->getFlag ('paragraph_type', 'integer', BBCODE_PARAGRAPH_ALLOW_BREAKUP) != BBCODE_PARAGRAPH_ALLOW_BREAKUP || !count ($node->_children)) {
			$dest_nodes[] =& $node;
			return $dest_nodes;
		}
		$dest_node =& $node->duplicate ();
		$nodecount = count ($node->_children);
		// now this node allows breakup - do it
		for ($i = 0; $i < $nodecount; $i++) {
			$firstnode =& $node->_children[0];
			$node->removeChild ($firstnode);
			$sub_nodes =& $this->_breakupNodeByParagraphs ($firstnode);
			for ($j = 0; $j < count ($sub_nodes); $j++) {
				if ($j != 0) {
					$dest_nodes[] =& $dest_node;
					unset ($dest_node);
					$dest_node =& $node->duplicate ();
				}
				$dest_node->appendChild ($sub_nodes[$j]);
			}
			unset ($sub_nodes);
		}
		$dest_nodes[] =& $dest_node;
		return $dest_nodes;
	}
	
	/**
	 * Is this node a usecontent node
	 * @access protected
	 * @param object $node The node to check
	 * @param bool $check_attrs Also check whether 'usecontent?'-attributes exist
	 * @return bool
	 */
	function _isUseContent (&$node, $check_attrs = false) {
		$name = $this->_getCanonicalName ($node->name ());
		// this should NOT happen
		if ($name === false) {
			return false;
		}
		if ($this->_codes[$name]['callback_type'] == 'usecontent') {
			return true;
		}
		$result = false;
		if ($this->_codes[$name]['callback_type'] == 'callback_replace?') {
			$result = true;
		} else if ($this->_codes[$name]['callback_type'] != 'usecontent?') {
			return false;
		}
		if ($check_attrs === false) {
			return !$result;
		}
		$attributes = array_keys ($this->_topNodeVar ('_attributes'));
		$p = @$this->_codes[$name]['callback_params']['usecontent_param'];
		if (is_array ($p)) {
			foreach ($p as $param) {
				if (in_array ($param, $attributes)) {
					return $result;
				}
			}
		} else {
			if (in_array ($p, $attributes)) {
				return $result;
			}
		}
		return !$result;
	}

	/**
	* Get canonical name of a code
	*
	* @access protected
	* @param string $name
	* @return string
	*/
	function _getCanonicalName ($name) {
		if (isset ($this->_codes[$name])) {
			return $name;
		}
		$found = false;
		// try to find the code in the code list
		foreach (array_keys ($this->_codes) as $rname) {
			// match
			if (strtolower ($rname) == strtolower ($name)) {
				$found = $rname;
				break;
			}
		}
		if ($found === false || ($this->_caseSensitive && $this->getCodeFlag ($found, 'case_sensitive', 'boolean', true))) {
			return false;
		}
		return $rname;
	}
}

/**
 * Node type: BBCode Element node
 * @see StringParser_BBCode_Node_Element::_type
 */
define ('STRINGPARSER_BBCODE_NODE_ELEMENT', 32);

/**
 * Node type: BBCode Paragraph node
 * @see StringParser_BBCode_Node_Paragraph::_type
 */
define ('STRINGPARSER_BBCODE_NODE_PARAGRAPH', 33);


/**
 * BBCode String parser paragraph node class
 *
 * @package stringparser
 */
class StringParser_BBCode_Node_Paragraph extends StringParser_Node {
	/**
	 * The type of this node.
	 * 
	 * This node is a bbcode paragraph node.
	 *
	 * @access protected
	 * @var int
	 * @see STRINGPARSER_BBCODE_NODE_PARAGRAPH
	 */
	var $_type = STRINGPARSER_BBCODE_NODE_PARAGRAPH;
	
	/**
	 * Determines whether a criterium matches this node
	 *
	 * @access public
	 * @param string $criterium The criterium that is to be checked
	 * @param mixed $value The value that is to be compared
	 * @return bool True if this node matches that criterium
	 */
	function matchesCriterium ($criterium, $value) {
		if ($criterium == 'empty') {
			if (!count ($this->_children)) {
				return true;
			}
			if (count ($this->_children) > 1) {
				return false;
			}
			if ($this->_children[0]->_type != STRINGPARSER_NODE_TEXT) {
				return false;
			}
			if (!strlen ($this->_children[0]->content)) {
				return true;
			}
			if (strlen ($this->_children[0]->content) > 2) {
				return false;
			}
			$f_begin = $this->_children[0]->getFlag ('newlinemode.begin', 'integer', BBCODE_NEWLINE_PARSE);
			$f_end = $this->_children[0]->getFlag ('newlinemode.end', 'integer', BBCODE_NEWLINE_PARSE);
			$content = $this->_children[0]->content;
			if ($f_begin != BBCODE_NEWLINE_PARSE && $content{0} == "\n") {
				$content = substr ($content, 1);
			}
			if ($f_end != BBCODE_NEWLINE_PARSE && $content{strlen($content)-1} == "\n") {
				$content = substr ($content, 0, -1);
			}
			if (!strlen ($content)) {
				return true;
			}
			return false;
		}
	}
}

/**
 * BBCode String parser element node class
 *
 * @package stringparser
 */
class StringParser_BBCode_Node_Element extends StringParser_Node {
	/**
	 * The type of this node.
	 * 
	 * This node is a bbcode element node.
	 *
	 * @access protected
	 * @var int
	 * @see STRINGPARSER_BBCODE_NODE_ELEMENT
	 */
	var $_type = STRINGPARSER_BBCODE_NODE_ELEMENT;
	
	/**
	 * Element name
	 *
	 * @access protected
	 * @var string
	 * @see StringParser_BBCode_Node_Element::name
	 * @see StringParser_BBCode_Node_Element::setName
	 * @see StringParser_BBCode_Node_Element::appendToName
	 */
	var $_name = '';
	
	/**
	 * Element flags
	 * 
	 * @access protected
	 * @var array
	 */
	var $_flags = array ();
	
	/**
	 * Element attributes
	 * 
	 * @access protected
	 * @var array
	 */
	var $_attributes = array ();
	
	/**
	 * Had a close tag
	 *
	 * @access protected
	 * @var bool
	 */
	var $_hadCloseTag = false;
	
	/**
	 * Was processed by paragraph handling
	 *
	 * @access protected
	 * @var bool
	 */
	var $_paragraphHandled = false;
	
	//////////////////////////////////////////////////
	
	/**
	 * Duplicate this node (but without children / parents)
	 *
	 * @access public
	 * @return object
	 */
	function &duplicate () {
		$newnode =& new StringParser_BBCode_Node_Element ($this->occurredAt);
		$newnode->_name = $this->_name;
		$newnode->_flags = $this->_flags;
		$newnode->_attributes = $this->_attributes;
		$newnode->_hadCloseTag = $this->_hadCloseTag;
		$newnode->_paragraphHandled = $this->_paragraphHandled;
		$newnode->_codeInfo = $this->_codeInfo;
		return $newnode;
	}
	
	/**
	 * Retreive name of this element
	 *
	 * @access public
	 * @return string
	 */
	function name () {
		return $this->_name;
	}
	
	/**
	 * Set name of this element
	 *
	 * @access public
	 * @param string $name The new name of the element
	 */
	function setName ($name) {
		$this->_name = $name;
		return true;
	}
	
	/**
	 * Append to name of this element
	 *
	 * @access public
	 * @param string $chars The chars to append to the name of the element
	 */
	function appendToName ($chars) {
		$this->_name .= $chars;
		return true;
	}
	
	/**
	 * Append to attribute of this element
	 *
	 * @access public
	 * @param string $name The name of the attribute
	 * @param string $chars The chars to append to the attribute of the element
	 */
	function appendToAttribute ($name, $chars) {
		if (!isset ($this->_attributes[$name])) {
			$this->_attributes[$name] = $chars;
			return true;
		}
		$this->_attributes[$name] .= $chars;
		return true;
	}
	
	/**
	 * Set attribute
	 *
	 * @access public
	 * @param string $name The name of the attribute
	 * @param string $value The new value of the attribute
	 */
	function setAttribute ($name, $value) {
		$this->_attributes[$name] = $value;
		return true;
	}
	
	/**
	 * Set code info
	 *
	 * @access public
	 * @param array $info The code info array
	 */
	function setCodeInfo ($info) {
		$this->_codeInfo = $info;
		$this->_flags = $info['flags'];
		return true;
	}
	
	/**
	 * Get attribute value
	 *
	 * @access public
	 * @param string $name The name of the attribute
	 */
	function attribute ($name) {
		if (!isset ($this->_attributes[$name])) {
			return null;
		}
		return $this->_attributes[$name];
	}
	
	/**
	 * Set flag that this element had a close tag
	 *
	 * @access public
	 */
	function setHadCloseTag () {
		$this->_hadCloseTag = true;
	}
	
	/**
	 * Set flag that this element was already processed by paragraph handling
	 *
	 * @access public
	 */
	function setParagraphHandled () {
		$this->_paragraphHandled = true;
	}
	
	/**
	 * Get flag if this element was already processed by paragraph handling
	 *
	 * @access public
	 * @return bool
	 */
	function paragraphHandled () {
		return $this->_paragraphHandled;
	}
	
	/**
	 * Get flag if this element had a close tag
	 *
	 * @access public
	 * @return bool
	 */
	function hadCloseTag () {
		return $this->_hadCloseTag;
	}
	
	/**
	 * Determines whether a criterium matches this node
	 *
	 * @access public
	 * @param string $criterium The criterium that is to be checked
	 * @param mixed $value The value that is to be compared
	 * @return bool True if this node matches that criterium
	 */
	function matchesCriterium ($criterium, $value) {
		if ($criterium == 'tagName') {
			return ($value == $this->_name);
		}
		if ($criterium == 'needsTextNodeModification') {
			return (($this->getFlag ('opentag.before.newline', 'integer', BBCODE_NEWLINE_PARSE) != BBCODE_NEWLINE_PARSE || $this->getFlag ('opentag.after.newline', 'integer', BBCODE_NEWLINE_PARSE) != BBCODE_NEWLINE_PARSE || ($this->_hadCloseTag && ($this->getFlag ('closetag.before.newline', 'integer', BBCODE_NEWLINE_PARSE) != BBCODE_NEWLINE_PARSE || $this->getFlag ('closetag.after.newline', 'integer', BBCODE_NEWLINE_PARSE) != BBCODE_NEWLINE_PARSE))) == (bool)$value);
		}
		if (substr ($criterium, 0, 5) == 'flag:') {
			$criterium = substr ($criterium, 5);
			return ($this->getFlag ($criterium) == $value);
		}
		if (substr ($criterium, 0, 6) == '!flag:') {
			$criterium = substr ($criterium, 6);
			return ($this->getFlag ($criterium) != $value);
		}
		if (substr ($criterium, 0, 6) == 'flag=:') {
			$criterium = substr ($criterium, 6);
			return ($this->getFlag ($criterium) === $value);
		}
		if (substr ($criterium, 0, 7) == '!flag=:') {
			$criterium = substr ($criterium, 7);
			return ($this->getFlag ($criterium) !== $value);
		}
		return parent::matchesCriterium ($criterium, $value);
	}
	
	/**
	 * Get first child if it is a text node
	 *
	 * @access public
	 * @return mixed
	 */
	function &firstChildIfText () {
		$ret =& $this->firstChild ();
		if (is_null ($ret)) {
			return $ret;
		}
		if ($ret->_type != STRINGPARSER_NODE_TEXT) {
			// DON'T DO $ret = null WITHOUT unset BEFORE!
			// ELSE WE WILL ERASE THE NODE ITSELF! EVIL!
			unset ($ret);
			$ret = null;
		}
		return $ret;
	}
	
	/**
	 * Get last child if it is a text node AND if this element had a close tag
	 *
	 * @access public
	 * @return mixed
	 */
	function &lastChildIfText () {
		$ret =& $this->lastChild ();
		if (is_null ($ret)) {
			return $ret;
		}
		if ($ret->_type != STRINGPARSER_NODE_TEXT || !$this->_hadCloseTag) {
			// DON'T DO $ret = null WITHOUT unset BEFORE!
			// ELSE WE WILL ERASE THE NODE ITSELF! EVIL!
			if ($ret->_type != STRINGPARSER_NODE_TEXT && !$ret->hadCloseTag ()) {
				$ret2 =& $ret->_findPrevAdjentTextNodeHelper ();
				unset ($ret);
				$ret =& $ret2;
				unset ($ret2);
			} else {
				unset ($ret);
				$ret = null;
			}
		}
		return $ret;
	}
	
	/**
	 * Find next adjent text node after close tag
	 *
	 * returns the node or null if none exists
	 *
	 * @access public
	 * @return mixed
	 */
	function &findNextAdjentTextNode () {
		$ret = null;
		if (is_null ($this->_parent)) {
			return $ret;
		}
		if (!$this->_hadCloseTag) {
			return $ret;
		}
		$ccount = count ($this->_parent->_children);
		$found = false;
		for ($i = 0; $i < $ccount; $i++) {
			if ($this->_parent->_children[$i]->equals ($this)) {
				$found = $i;
				break;
			}
		}
		if ($found === false) {
			return $ret;
		}
		if ($found < $ccount - 1) {
			if ($this->_parent->_children[$found+1]->_type == STRINGPARSER_NODE_TEXT) {
				return $this->_parent->_children[$found+1];
			}
			return $ret;
		}
		if ($this->_parent->_type == STRINGPARSER_BBCODE_NODE_ELEMENT && !$this->_parent->hadCloseTag ()) {
			$ret =& $this->_parent->findNextAdjentTextNode ();
			return $ret;
		}
		return $ret;
	}
	
	/**
	 * Find previous adjent text node before open tag
	 *
	 * returns the node or null if none exists
	 *
	 * @access public
	 * @return mixed
	 */
	function &findPrevAdjentTextNode () {
		$ret = null;
		if (is_null ($this->_parent)) {
			return $ret;
		}
		$ccount = count ($this->_parent->_children);
		$found = false;
		for ($i = 0; $i < $ccount; $i++) {
			if ($this->_parent->_children[$i]->equals ($this)) {
				$found = $i;
				break;
			}
		}
		if ($found === false) {
			return $ret;
		}
		if ($found > 0) {
			if ($this->_parent->_children[$found-1]->_type == STRINGPARSER_NODE_TEXT) {
				return $this->_parent->_children[$found-1];
			}
			if (!$this->_parent->_children[$found-1]->hadCloseTag ()) {
				$ret =& $this->_parent->_children[$found-1]->_findPrevAdjentTextNodeHelper ();
			}
			return $ret;
		}
		return $ret;
	}
	
	/**
	 * Helper function for findPrevAdjentTextNode
	 *
	 * Looks at the last child node; if it's a text node, it returns it,
	 * if the element node did not have an open tag, it calls itself
	 * recursively.
	 */
	function &_findPrevAdjentTextNodeHelper () {
		$lastnode =& $this->lastChild ();
		if ($lastnode === null || $lastnode->_type == STRINGPARSER_NODE_TEXT) {
			return $lastnode;
		}
		if (!$lastnode->hadCloseTag ()) {
			$ret =& $lastnode->_findPrevAdjentTextNodeHelper ();
		} else {
			$ret = null;
		}
		return $ret;
	}
	
	/**
	 * Get Flag
	 *
	 * @access public
	 * @param string $flag The requested flag
	 * @param string $type The requested type of the return value
	 * @param mixed $default The default return value
	 * @return mixed
	 */
	function getFlag ($flag, $type = 'mixed', $default = null) {
		if (!isset ($this->_flags[$flag])) {
			return $default;
		}
		$return = $this->_flags[$flag];
		if ($type != 'mixed') {
			settype ($return, $type);
		}
		return $return;
	}
	
	/**
	 * Set a flag
	 *
	 * @access public
	 * @param string $name The name of the flag
	 * @param mixed $value The value of the flag
	 */
	function setFlag ($name, $value) {
		$this->_flags[$name] = $value;
		return true;
	}
	
	/**
	 * Validate code
	 *
	 * @access public
	 * @param string $action The action which is to be called ('validate'
	 *                       for first validation, 'validate_again' for
	 *                       second validation (optional))
	 * @return bool
	 */
	function validate ($action = 'validate') {
		if ($action != 'validate' && $action != 'validate_again') {
			return false;
		}
		if ($this->_codeInfo['callback_type'] != 'simple_replace' && $this->_codeInfo['callback_type'] != 'simple_replace_single') {
			if (!is_callable ($this->_codeInfo['callback_func'])) {
				return false;
			}
			
			if (($this->_codeInfo['callback_type'] == 'usecontent' || $this->_codeInfo['callback_type'] == 'usecontent?' || $this->_codeInfo['callback_type'] == 'callback_replace?') && count ($this->_children) == 1 && $this->_children[0]->_type == STRINGPARSER_NODE_TEXT) {
				// we have to make sure the object gets passed on as a reference
				// if we do call_user_func(..., &$this) this will clash with PHP5
				$callArray = array ($action, $this->_attributes, $this->_children[0]->content, $this->_codeInfo['callback_params']);
				$callArray[] =& $this;
				$res = call_user_func_array ($this->_codeInfo['callback_func'], $callArray);
				if ($res) {
					// ok, now, if we've got a usecontent type, set a flag that
					// this may not be broken up by paragraph handling!
					// but PLEASE do NOT change if already set to any other setting
					// than BBCODE_PARAGRAPH_ALLOW_BREAKUP because we could
					// override e.g. BBCODE_PARAGRAPH_BLOCK_ELEMENT!
					$val = $this->getFlag ('paragraph_type', 'integer', BBCODE_PARAGRAPH_ALLOW_BREAKUP);
					if ($val == BBCODE_PARAGRAPH_ALLOW_BREAKUP) {
						$this->_flags['paragraph_type'] = BBCODE_PARAGRAPH_ALLOW_INSIDE;
					}
				}
				return $res;
			}
			
			// we have to make sure the object gets passed on as a reference
			// if we do call_user_func(..., &$this) this will clash with PHP5
			$callArray = array ($action, $this->_attributes, null, $this->_codeInfo['callback_params']);
			$callArray[] =& $this;
			return call_user_func_array ($this->_codeInfo['callback_func'], $callArray);
		}
		return (bool)(!count ($this->_attributes));
	}
	
	/**
	 * Get replacement for this code
	 *
	 * @access public
	 * @param string $subcontent The content of all sub-nodes
	 * @return string
	 */
	function getReplacement ($subcontent) {
		if ($this->_codeInfo['callback_type'] == 'simple_replace' || $this->_codeInfo['callback_type'] == 'simple_replace_single') {
			if ($this->_codeInfo['callback_type'] == 'simple_replace_single') {
				if (strlen ($subcontent)) { // can't be!
					return false;
				}
				return $this->_codeInfo['callback_params']['start_tag'];
			}
			return $this->_codeInfo['callback_params']['start_tag'].$subcontent.$this->_codeInfo['callback_params']['end_tag'];
		}
		// else usecontent, usecontent? or callback_replace or callback_replace_single
		// => call function (the function is callable, determined in validate()!)
		
		// we have to make sure the object gets passed on as a reference
		// if we do call_user_func(..., &$this) this will clash with PHP5
		$callArray = array ('output', $this->_attributes, $subcontent, $this->_codeInfo['callback_params']);
		$callArray[] =& $this;
		return call_user_func_array ($this->_codeInfo['callback_func'], $callArray);
	}
	
	/**
	 * Dump this node to a string
	 *
	 * @access protected
	 * @return string
	 */
	function _dumpToString () {
		$str = "bbcode \"".substr (preg_replace ('/\s+/', ' ', $this->_name), 0, 40)."\"";
		if (count ($this->_attributes)) {
			$attribs = array_keys ($this->_attributes);
			sort ($attribs);
			$str .= ' (';
			$i = 0;
			foreach ($attribs as $attrib) {
				if ($i != 0) {
					$str .= ', ';
				}
				$str .= $attrib.'="';
				$str .= substr (preg_replace ('/\s+/', ' ', $this->_attributes[$attrib]), 0, 10);
				$str .= '"';
				$i++;
			}
			$str .= ')';
		}
		return $str;
	}
}

?>