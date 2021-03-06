<?php

/**
 * Contains core Q functionality.
 * @module Q
 * @main Q
 */
/**
 * Core Q platform functionality
 * @class Q
 * @static
 */

class Q
{
	/**
	 * Used for shorthand for avoiding when you don't want to write
	 * (isset($some_long_expression) ? $some_long_expression: null)
	 * when you want to avoid possible "undefined variable" errors.
	 * @method ifset
	 * @param {&mixed} $ref
	 *  The reference to test. Only lvalues can be passed.
	 *  If $ref is an array or object, it can be followed by one or more strings or numbers
	 *  which will be used to index deeper into the contained arrays or objects.
	 * @param {mixed} $def=null
	 *  The default, if the reference isn't set
	 * @return {mixed}
	 */
	static function ifset(& $ref, $def = null)
	{
		$count = func_num_args();
		if ($count <= 2) {
			return isset($ref) ? $ref : $def;
		}
		$args = func_get_args();
		$ref2 = $ref;
		$def = end($args);
		for ($i=1; $i<$count-1; ++$i) {
			$key = $args[$i];
			if (!is_array($key)) {
				$key = array($key);
			}
			if (is_array($ref2)) {
				foreach ($key as $k) {
					if (array_key_exists($k, $ref2)) {
						$ref2 = $ref2[$k];
						continue 2;
					}
				}
				return $def;
			} else if (is_object($ref2)) {
				foreach ($key as $k) {
					if (isset($ref2->$k)) {
						$ref2 = $ref2->$k;
						continue 2;
					}
				}
				return $def;
			} else {
				return $def;
			}
		}
		return $ref2;
	}


	/**
	 * Returns the number of milliseconds since the
	 * first call to this function (i.e. since script started).
	 * @method milliseconds
	 * @param {Boolean} $sinceEpoch
	 *  Defaults to false. If true, just returns the number of milliseconds in the UNIX timestamp.
	 * @return {float}
	 *  The number of milliseconds, with fractional part
	 */
	static function milliseconds ($sinceEpoch = false)
	{
		$result = microtime(true)*1000;
		if ($sinceEpoch) {
			return $result;
		}

		static $microtime_start;
		if (empty($microtime_start)) {
			$microtime_start = $result;
		}
		return $result - $microtime_start;
	}

	/**
	 * Default exception handler for Q
	 * @method exceptionHandler
	 * @param {Exception} $exception
	 */
	static function exceptionHandler (
	 $exception)
	{
		try {
			/**
			 * @event Q/exception
			 * @param {Exception} $exception
			 */
			self::event('Q/exception', compact('exception'));
		} catch (Exception $e) {
			/**
			 * @event Q/exception/native
			 * @param {Exception} $exception
			 */
			// Looks like the app's custom Q/exception handler threw
			// an exception itself. Just show Q's native exception handler.
			self::event('Q/exception/native', compact('exception'));
		}
	}

	/**
	 * Error handler
	 * @method errorHandler
	 * @param {integer} $errno
	 * @param {string} $errstr
	 * @param {string} $errfile
	 * @param {integer} $errline
	 * @param {array} $errcontext
	 */
	static function errorHandler (
		$errno,
		$errstr,
		$errfile,
		$errline,
		$errcontext)
	{
	    if (!(error_reporting() & $errno)) {
	        // This error code is not included in error_reporting
			// just continue on with execution, if possible.
			// this situation can also happen when
			// someone has used the @ operator.
	        return;
	    }
		switch ($errno) {
			case E_WARNING:
			case E_NOTICE:
			case E_USER_WARNING:
			case E_USER_NOTICE:
				$context = var_export($errcontext, true);
				$log = <<<EOT
PHP ($errno): $errstr
FILE: $errfile
LINE: $errline
CONTEXT: $context
EOT;

				Q::log($log);
				$type = 'warning';
				break;
			default:
				$type = 'error';
				break;
		}
		/**
		 * @event Q/error
		 * @param {integer} 'errno'
		 * @param {string} 'errstr'
		 * @param {string} 'errfile'
		 * @param {integer} 'errline'
		 * @param {array} 'errcontext'
		 */
		self::event("Q/error", compact(
			'type','errno','errstr','errfile','errline','errcontext'
		));
	}

	/**
	 * Goes through the params and replaces any references
	 * to their names in the string with their value.
	 * References are expected to be of the form $varname.
	 * However, dollar signs prefixed with backslashes will not be replaced.
	 * @method expandString
	 * @static
	 * @param {string} $expression
	 *  The string to expand.
	 * @param {array} $params=array()
	 *  An array of parameters to the expression.
	 *  Variable names in the expression can refer to them.
	 * @return {mixed}
	 *  The result of the expression
	 */
	static function interpolate(
		$expression,
		$params = array())
	{
		$keys = array_keys($params);
		usort($keys, array(__CLASS__, 'reverseLengthCompare'));
		$expression = str_replace('\\$', '\\REAL_DOLLAR_SIGN\\', $expression);
		foreach ($keys as $key) {
			$p = (is_array($params[$key]) or is_object($params[$key]))
				? substr(Q::json_encode($params[$key]), 0, 100)
				: (string)$params[$key];
			$expression = str_replace('$'.$key, $p, $expression);
		}
		$expression = str_replace('\\REAL_DOLLAR_SIGN\\', '\\$', $expression);
		return $expression;
	}
	
	/**
	 * Evaluates a string containing an expression,
	 * with possible references to parameters.
	 * CAUTION: make sure the expression is safe!!
	 * @method evalExpression
	 * @static
	 * @param {string} $expression
	 *  The code to eval.
	 * @param {array} $params=array()
	 *  Optional. An array of parameters to the expression.
	 *  Variable names in the expression can refer to them.
	 * @return {mixed}
	 *  The result of the expression
	 */
	static function evalExpression(
	 $expression,
	 $params = array())
	{
		if (is_array($params)) {
			extract($params);
		}
		@eval('$value = ' . $expression . ';');
		extract($params);
		/**
		 * @var $value
		 */
		return $value;
	}

	/**
	 * Use for surrounding text, so it can later be processed throughout.
	 * @method t
	 * @static
	 * @param {string} $test
	 * @return {string}
	 */
	static function t($text)
	{
		/**
		 * @event Q/t {before}
		 * @return {string}
		 */
		$text = Q::event('Q/t', array(), 'before', false, $text);
		return $text;
	}

	/**
	 * Check if a file exists in the include path
	 * And if it does, return the absolute path.
	 * @method realPath
	 * @static
	 * @param {string} $filename
	 *  Name of the file to look for
	 * @param {boolean} $ignoreCache=false
	 *  Defaults to false. If true, then this function ignores
	 *  the cached value, if any, and always attempts to search
	 *  for the file. It will cache the new value.
	 * @return {string|false}
	 *  The absolute path if file exists, false if it does not
	 */
	static function realPath (
		$filename,
		$ignoreCache = false)
	{
		$filename = str_replace('/', DS, $filename);
		if (!$ignoreCache) {
			// Try the extended cache mechanism, if any
			$result = Q::event('Q/realPath', array(), 'before');
			if (isset($result)) {
				return $result;
			}
			// Try the native cache mechanism
			$result = Q_Cache::get("Q::realPath\t$filename");
			if (isset($result)) {
				return $result;
			}
		}

		// Do a search for the file
	    $paths = explode(PS, get_include_path());
		array_unshift($paths, "");
		$result = false;
	    foreach ($paths as $path) {
			if (substr($path, -1) == DS) {
	        	$fullpath = $path.$filename;
			} else {
				$fullpath = ($path ? $path . DS : "") . $filename;
			}
			// Note: the following call to the OS may take some time:
			$realpath = realpath($fullpath);
			if ($realpath && file_exists($realpath)) {
	            $result = $realpath;
				break;
	        }
	    }

		// Notify the cache mechanism, if any
		Q_Cache::set("Q::realPath\t$filename", $result);
		/**
		 * @event Q/realPath {after}
		 * @param {string} $result
		 */
		Q::event('Q/realPath', compact('result'), 'after');

	    return $result;

	}


	/**
	 * Includes a file and evaluates code from it
	 * @method includeFile
	 * @static
	 * @param {string} $filename
	 *  The filename to include
	 * @param {array} $params=array()
	 *  Optional. Extracts this array before including the file.
	 * @param boolean $once=false
	 *  Optional. Whether to use include_once instead of include.
	 * @param {boolean} $get_vars=false
	 *  Optional. Set to true to return result of get_defined_vars()
	 *  at the end.
	 * @return {mixed}
	 *  Optional. If true, returns the result of get_defined_vars() at the end.
	 *  Otherwise, returns whatever the file returned.
	 * @throws {Q_Exception_MissingFile}
	 *  May throw a Q_Exception_MissingFile exception.
	 */
	static function includeFile(
	 $filename,
	 array $params = array(),
	 $once = false,
	 $get_vars = false)
	{
		/**
		 * Skips includes to prevent recursion
		 * @event Q/includeFile {before}
		 * @param {string} 'filename'
		 *  The filename to include
		 * @param {array} 'params'
		 *  Optional. Extracts this array before including the file.
		 * @param {boolean} 'once'
		 *  Optional. Whether to use include_once instead of include.
		 * @param {boolean} 'get_vars'
		 *  Optional. Set to true to return result of get_defined_vars()
		 *  at the end.
		 * @return {mixed}
		 *  Optional. If set, override method return
		 */
		$result = self::event(
			'Q/includeFile',
			compact('filename', 'params', 'once', 'get_vars'),
			'before',
			true
		);
		if (isset($result)) {
			// return this result instead
			return $result;
		}

		$abs_filename = self::realPath($filename);

		if (!$abs_filename) {
			$include_path = get_include_path();
			require_once(Q_CLASSES_DIR.DS.'Q'.DS.'Exception'.DS.'MissingFile.php');
			throw new Q_Exception_MissingFile(compact('filename', 'include_path'));
		}
		if (is_dir($abs_filename)) {
			$include_path = get_include_path();
			require_once(Q_CLASSES_DIR.DS.'Q'.DS.'Exception'.DS.'MissingFile.php');
			throw new Q_Exception_MissingFile(compact('filename', 'include_path'));
		}

		extract($params);
		if ($get_vars === true) {
			if ($once) {
				if (!isset(self::$included_files[$filename])) {
					self::$included_files[$filename] = true;
					include_once($abs_filename);
				}
			} else {
				include($abs_filename);
			}
			return get_defined_vars();
		} else {
			if ($once) {
				if (!isset(self::$included_files[$filename])) {
					self::$included_files[$filename] = true;
					include_once($abs_filename);
				}
			} else {
				return include($abs_filename);
			}
		}
	}

	/**
	 * Default autoloader for Q
	 * @method autoload
	 * @static
	 * @param {string} $class_name
	 * @throws {Q_Exception_MissingClass}
	 *	If requested class is missing
	 */
	static function autoload(
	 $class_name)
	{
		if (class_exists($class_name, false)) {
			return;
		}
		try {
			$filename = self::event('Q/autoload', compact('class_name'), 'before');

			if (!isset($filename)) {
				$class_name_parts = explode('_', $class_name);
				$filename = 'classes'.DS.implode(DS, $class_name_parts).'.php';
			}

			// Workaround for Zend Framework, because it has require_once
			// in various places, instead of just relying on autoloading.
			// As a result, we need to add some more directories to the path.
			// The trigger is that we will be loading a file beginning with "classes/Zend".
			// This is handled natively inside this method for the purpose of speed.
			$paths = array('classes/Zend/' => 'classes');
			static $added_paths = array();
			foreach ($paths as $prefix => $new_path) {
				if (substr($filename, 0, strlen($prefix)) != $prefix) {
					continue;
				}
				if (isset($added_paths[$new_path])) {
					break;
				}
				$abs_filename = self::realPath($filename);
				$new_path_parts = array();
				$prev_part = null;
				foreach (explode(DS, $abs_filename) as $part) {
					if ($prev_part == 'classes' and $part == 'Zend') {
						break;
					}
					$prev_part = $part;
					$new_path_parts[] = $part;
				}
				$new_path = implode(DS, $new_path_parts);
		        $paths = array($new_path, get_include_path());
		        set_include_path(implode(PS, $paths));
				$added_paths[$new_path] = true;
			}

			// Now we can include the file
			try {
				self::includeFile($filename);
			} catch (Q_Exception_MissingFile $e) {
				// the file doesn't exist
				// and you will get an error if you try to use the class
			}

			// if (!class_exists($class_name) && !interface_exists($class_name)) {
			// 	require_once(Q_CLASSES_DIR.DS.'Q'.DS.'Exception'.DS.'MissingClass.php');
			// 	throw new Q_Exception_MissingClass(compact('class_name'));
			// }

			/**
			 * @event Q/autoload {after}
			 * @param {string} 'class_name'
			 * @param {string} 'filename'
			 */
			self::event('Q/autoload', compact('class_name', 'filename'), 'after');

		} catch (Exception $exception) {
			/**
			 * @event Q/exception
			 * @param {Exception} 'exception'
			 */
			self::event('Q/exception', compact('exception'));
		}
	}

	/**
	 * Renders a particular view
	 * @method view
	 * @static
	 * @param {string} $view_name
	 *  The full name of the view
	 * @param {array} $params=array()
	 *  Parameters to pass to the view
	 * @return {string}
	 *  The rendered content of the view
	 * @throws {Q_Exception_MissingFile}
	 */
	static function view(
	 $view_name,
	 $params = array())
	{
		require_once(Q_CLASSES_DIR.DS.'Q'.DS.'Exception'.DS.'MissingFile.php');

		if (empty($params)) $params = array();

		$view_name = implode(DS, explode('/', $view_name));

		$fields = Q_Config::get('Q', 'views', 'fields', null);
		if ($fields) {
			$params = array_merge($fields, $params);
		}

		/**
		 * @event {before} Q/view
		 * @param {string} 'view_name'
		 * @param {string} 'params'
		 * @return {string}
		 *  Optional. If set, override method return
		 */
		$result = self::event('Q/view', compact('view_name', 'params'), 'before');
		if (isset($result)) {
			return $result;
		}

		try {
			$ob = new Q_OutputBuffer();
			self::includeFile('views'.DS.$view_name, $params);
			return $ob->getClean();
		} catch (Q_Exception_MissingFile $e) {
			$ob->flushHigherBuffers();
			/**
			 * Renders 'Missing View' page
			 * @event Q/missingView
			 * @param {string} view_name
			 * @return {string}
			 */
			return self::event('Q/missingView', compact('view_name'));
		}
	}

	/**
	 * Instantiates a particular tool.
	 * Also generates javascript around it.
	 * @method tool
	 * @static
	 * @param {string} $name
	 *  The name of the tool, of the form "$moduleName/$toolName"
	 *  The handler is found in handlers/$moduleName/tool/$toolName
	 *  Also can be an array of $toolName => $toolOptions, in which case the
	 *  following parameter, $options, is skipped.
	 * @param {array} $options=array()
	 *  The options passed to the tool (or array of options arrays passed to the tools).
	 * @param {array} $extra=array()
	 *  Options used by Q when rendering the tool. Can include:<br/>
	 *  "id" =>
	 *    an additional ID to distinguish tools instantiated
	 *    side-by-side from each other. Usually numeric.<br/>
	 *  "cache" =>
	 *    if true, then the Q front end will not replace existing tools with same id
	 *    during Q.loadUrl when this tool appears in the rendered HTML
	 * @return {string}
	 *  The rendered content of the tool
	 * @throws {Q_Exception_WrongType}
	 * @throws {Q_Exception_MissingFile}
	 */
	static function tool(
	 $name,
	 $options = array(),
	 $extra = array())
	{
		if (is_string($name)) {
			$info = array($name => $options);
		} else {
			$info = $name;
			$extra = $options;
		}
		
		$oldToolName = self::$toolName;
		
		/**
		 * @event Q/tool/render {before}
		 * @param {string} 'info'
		 *  An array of $toolName => $options pairs
		 * @param {array} 'extra'
		 *  Options used by Q when rendering the tool. Can include:<br/>
		 *  "id" =>
		 *    an additional ID to distinguish tools instantiated
		 *    side-by-side from each other. Usually numeric.<br/>
		 *  "cache" =>
		 *    if true, then the Q front end will not replace existing tools with same id
		 *    during Q.loadUrl when this tool appears in the rendered HTML
		 * @return {string|null}
		 *  If set, override the method return
		 */
		$returned = Q::event(
			'Q/tool/render',
			array('info' => $info, 'extra' => &$extra),
			'before'
		);
		$result = '';
		$exception = null;
		foreach ($info as $name => $options) {
			Q::$toolName = $name;
			$toolHandler = "$name/tool";
			$options = is_array($options) ? $options : array();
			if (is_array($returned)) {
				$options = array_merge($returned, $options);
			}
			try {
				/**
				 * Renders the tool
				 * @event $toolHandler
				 * @param {array} $options
				 *  The options passed to the tool
				 * @return {string}
				 *	The rendered tool content
				 */
				$result .= Q::event($toolHandler, $options); // render the tool
			} catch (Q_Exception_MissingFile $e) {
				/**
				 * Renders the 'Missing Tool' content
				 * @event Q/missingTool
				 * @param {array} 'name'
				 *  The name of the tool
				 * @return {string}
				 *	The rendered content
				 */
				$params = $e->params();
				if ($params['filename'] === "handlers/$toolHandler.php") {
					$result .= self::event('Q/missingTool', compact('name', 'options'));
				} else {
					$exception = $e;
				}
			} catch (Exception $e) {
				$exception = $e;
			}
			if ($exception) {
				Q::log($exception);
				$result .= $exception->getMessage();
			}
			Q::$toolName = $name; // restore the current tool name
		}
		// Even if the tool rendering throws an exception,
		// it is important to run the "after" handlers
		/**
		 * @event Q/tool/render {after}
		 * @param {string} 'info'
		 *  An array of $toolName => $options pairs
		 * @param {array} 'extra'
		 *  Options used by Q when rendering the tool. Can include:<br/>
		 *  "id" =>
		 *    an additional ID to distinguish tools instantiated
		 *    side-by-side from each other. Usually numeric.<br/>
		 *  "cache" =>
		 *    if true, then the Q front end will not replace existing tools with same id
		 *    during Q.loadUrl when this tool appears in the rendered HTML
		 */
		Q::event(
			'Q/tool/render',
			compact('info', 'extra'),
			'after',
			false,
			$result
		);
		Q::$toolName = $oldToolName;
		return $result;
	}

	/**
	 * Fires a particular event.
	 * Might result in several handlers being called.
	 * @method event
	 * @static
	 * @param {string} $event_name
	 *  The name of the event
	 * @param {array} $params=array()
	 *  Parameters to pass to the event
	 * @param {boolean} $no_handler=false
	 *  Defaults to false.
	 *  If true, the handler of the same name is not invoked.
	 *  Put true here if you just want to fire a pure event,
	 *  without any default behavior.
	 *  If 'before', only runs the "before" handlers, if any.
	 *  If 'after', only runs the "after" handlers, if any.
	 *  You'd want to signal events with 'before' and 'after'
	 *  before and after some "default behavior" happens.
	 *  Check for a non-null return value on "before",
	 *  and cancel the default behavior if it is present.
	 * @param {boolean} $skip_includes=false
	 *  Defaults to false.
	 *  If true, no new files are loaded. Only handlers which have
	 *  already been defined as functions are run.
	 * @param {reference} $result=null
	 *  Defaults to null. You can pass here a reference to a variable.
	 *  It will be returned by this function when event handling
	 *  has finished, or has been aborted by an event handler.
	 *  It is passed to all the event handlers, which can modify it.
	 * @return {mixed}
	 *  Whatever the default event handler returned, or the final
	 *  value of $result if it is modified by any event handlers.
	 * @throws {Q_Exception_Recursion}
	 * @throws {Q_Exception_MissingFile}
	 * @throws {Q_Exception_MissingFunction}
	 */
	static function event(
	 $event_name,
	 $params = array(),
	 $no_handler = false,
	 $skip_includes = false,
	 &$result = null)
	{
		// for now, handle only event names which are strings
		if (!is_string($event_name)) {
			return;
		}
		if (!is_array($params)) {
			$params = array();
		}

		static $event_stack_limit = null;
		if (!isset($event_stack_limit)) {
			$event_stack_limit = Q_Config::get('Q', 'eventStackLimit', 100);
		}
		self::$event_stack[] = compact('event_name', 'params', 'no_handler', 'skip_includes');
		++self::$event_stack_length;
		if (self::$event_stack_length > $event_stack_limit) {
			if (!class_exists('Q_Exception_Recursion', false)) {
				include(dirname(__FILE__).DS.'Q'.DS.'Exception'.DS.'Recursion.php');
			}
			throw new Q_Exception_Recursion(array('function_name' => "Q::event($event_name)"));
		}

		try {
			if ($no_handler !== 'after') {
				// execute the "before" handlers
				$handlers = Q_Config::get('Q', 'handlersBeforeEvent', $event_name, array());
				if (is_string($handlers)) {
					$handlers = array($handlers); // be nice
				}
				if (is_array($handlers)) {
					foreach ($handlers as $handler) {
						if (false === self::handle($handler, $params, $skip_includes, $result)) {
							// return this result instead
							return $result;
						}
					}
				}
			}

			// Execute the primary handler, wherever that is
			if (!$no_handler) {
				// If none of the "after" handlers return anything,
				// the following result will be returned:
				$result = self::handle($event_name, $params, $skip_includes, $result);
			}

			if ($no_handler !== 'before') {
				// execute the "after" handlers
				$handlers = Q_Config::get('Q', 'handlersAfterEvent', $event_name, array());
				if (is_string($handlers)) {
					$handlers = array($handlers); // be nice
				}
				if (is_array($handlers)) {
					foreach ($handlers as $handler) {
						if (false === self::handle($handler, $params, $skip_includes, $result)) {
							// return this result instead
							return $result;
						}
					}
				}
			}
			array_pop(self::$event_stack);
			--self::$event_stack_length;
		} catch (Exception $e) {
			array_pop(self::$event_stack);
			--self::$event_stack_length;
			throw $e;
		}

		// If no handlers ran, the $result is still unchanged.
		return $result;
	}

	/**
	 * Tests whether a particular handler exists
	 * @method canHandle
	 * @static
	 * @param {string} $handler_name
	 *  The name of the handler. The handler can be overridden
	 *  via the include path, but an exception is thrown if it is missing.
	 * @param {boolean} $skip_include=false
	 *  Defaults to false. If true, no file is loaded;
	 *  the handler is executed only if the function is already defined;
	 *  otherwise, null is returned.
	 * @return {boolean}
	 *  Whether the handler exists
	 * @throws {Q_Exception_MissingFile}
	 */
	static function canHandle(
	 $handler_name,
	 $skip_include = false)
	{
		if (!isset($handler_name) || isset(self::$event_empty[$handler_name])) {
			return false;
		}
		$handler_name_parts = explode('/', $handler_name);
		$function_name = implode('_', $handler_name_parts);
		if (function_exists($function_name))
		 	return true;
		if ($skip_include)
			return false;
		// try to load appropriate file using relative filename
		// (may search multiple include paths)
		$filename = 'handlers'.DS.implode(DS, $handler_name_parts).'.php';
		try {
			self::includeFile($filename, array(), true);
		} catch (Q_Exception_MissingFile $e) {
			self::$event_empty[$handler_name] = true;
			return false;
		}
		if (function_exists($function_name))
			return true;
		return false;
	}

	/**
	 * Executes a particular handler
	 * @method handle
	 * @static
	 * @param {string} $handler_name
	 *  The name of the handler. The handler can be overridden
	 *  via the include path, but an exception is thrown if it is missing.
	 * @param {array} $params=array()
	 *  Parameters to pass to the handler
	 * @param {boolean} $skip_include=false
	 *  Defaults to false. If true, no file is loaded;
	 *  the handler is executed only if the function is already defined;
	 *  otherwise, null is returned.
	 * @param {&mixed} $result=null
	 *  Optional. Lets handlers modify return values of events.
	 * @return {mixed}
	 *  Whatever the particular handler returned, or null otherwise;
	 * @throws {Q_Exception_MissingFunction}
	 */
	protected static function handle(
	 $handler_name,
	 $params = array(),
	 $skip_include = false,
	 &$result = null)
	{
		if (!isset($handler_name)) {
			return null;
		}
		$handler_name_parts = explode('/', $handler_name);
		$function_name = implode('_', $handler_name_parts);
		if (!is_array($params)) {
			$params = array();
		}
		if (!function_exists($function_name)) {
			if ($skip_include) {
				return null;
			}
			// try to load appropriate file using relative filename
			// (may search multiple include paths)
			$filename = 'handlers'.DS.implode(DS, $handler_name_parts).'.php';
			self::includeFile($filename, $params, true);
			if (!function_exists($function_name)) {
				require_once(Q_CLASSES_DIR.DS.'Q'.DS.'Exception'.DS.'MissingFunction.php');
				throw new Q_Exception_MissingFunction(compact('function_name'));
			}
		}
		// The following avoids the bug in PHP where
		// call_user_func doesn't work with references being passed
		$args = array($params, &$result);
		return call_user_func_array($function_name, $args);
	}

	/**
	 * A replacement for call_user_func_array
	 * that implements some conveniences.
	 * @method call
	 * @static
	 * @param {callable} $callback
	 * @param {array} $params=array()
	 * @return {mixed}
	 *  Returns whatever the function returned.
	 * @throws {Q_Exception_MissingFunction}
	 */
	static function call(
		$callback,
		$params = array())
	{
		if ($callback === 'echo' or $callback === 'print') {
			foreach ($params as $p) {
				echo $p;
			}
			return;
		}
		if (!is_array($callback)) {
			$parts = explode('::', $callback);
			if (count($parts) > 1) {
				$callback = array($parts[0], $parts[1]);
			}
		}
		if (!is_callable($callback)) {
			$function_name = $callback;
			if (is_array($function_name)) {
				$function_name = implode('::', $function_name);
			}
			throw new Q_Exception_MissingFunction(compact('function_name'));
		}
		return call_user_func_array($callback, $params);
	}
	
	/**
	 * @method take
	 * @param {array|object} $source An array or object from which to take things.
	 * @param {array} $fields An array of fields to take or an object of fieldname => default pairs
	 * @param {array|object} &$dest Optional reference to an array or object in which we will set values.
	 *  Otherwise an empty array is used.
	 * @return {array|object} The $dest array or object, otherwise an array that has been filled with values.
	 */
	static function take($source, $fields, &$dest = null, $keys = array())
	{
		if (!is_array($fields)) {
			$fields = array($fields);
		}
		if (!isset($dest)) {
			$dest = array();
		}
		if (Q::isAssociative($fields)) {
			if (is_array($source)) {
				if (is_array($dest)) {
					foreach ($fields as $k => $v) {
						$dest[$k] = array_key_exists($k, $source) ? $source[$k] : $v;
					}
				} else {
					foreach ($fields as $k => $v) {
						$dest->k = array_key_exists($k, $source) ? $source[$k] : $v;
					}
				}
			} else if (is_object($source)) {
				if (is_array($dest)) {
					foreach ($fields as $k => $v) {
						$dest[$k] = property_exists($source, $k) ? $source->$k : $v;
				 	}
				} else {
					foreach ($fields as $k => $v) {
						$dest->$k = property_exists($source, $k) ? $source->$k : $v;
				 	}	
				}
			} else {
				if (is_array($dest)) {
					foreach ($fields as $k => $v) {
						$dest[$k] = $v;
					}
				} else {
					foreach ($fields as $k => $v) {
						$dest->k = $v;
					}
				}
			}
		} else {
			if (is_array($source)) {
				if (is_array($dest)) {
					foreach ($fields as $k) {
						if (array_key_exists($k, $source)) {
							$dest[$k] = $source[$k];
						}
					}
				} else {
					foreach ($fields as $k) {
						if (array_key_exists($k, $source)) {
							$dest->$k = $source[$k];
						}
					}
				}
			} else if (is_object($source)) {
				if (is_array($dest)) {
					foreach ($fields as $k) {
						if (property_exists($source, $k)) {
							$dest->$k = $source->k;
						}
					}
				} else {
					foreach ($fields as $k) {
						if (property_exists($source, $k)) {
							$dest->$k = $source->k;
						}
					}
				}
			}
		}
		return $dest;
	}
	
	/**
	 * Determine whether a PHP array if associative or not
	 * Might be slow as it has to iterate through the array
	 * @param {array}
	 */
	static function isAssociative($array)
	{
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}

	/**
	 * Append a message to the main log
	 * @method log
	 * @static
	 * @param {mixed} $message
	 *  the message to append. Usually a string.
	 * @param {string} $key=null
	 *  The name of log file. Defaults to "$app_name.log"
	 * @param {bool} $timestamp=true
	 *  whether to prepend the current timestamp
	 * @throws {Q_Exception_MissingFile}
	 *	If unable to create directory or file for the log
	 */
	static function log (
		$message,
		$key = null,
		$timestamp = true)
	{
		if (false === Q::event('Q/log', compact('message', 'timestamp', 'error_log_arguments'), 'before'))
			return;

		if (!is_string($message)) {
			if (!is_object($message)) {
				$message = Q::var_dump($message, 3, '$', 'text');
			}
			elseif (!is_callable(array($message, '__toString'))) {
				$message = Q::var_dump($message, null, '$', 'text');
			}
		}

		$app = Q_Config::get('Q', 'app', null);
		if (!isset($app)) {
			$app = defined('APP_DIR') ? basename(APP_DIR) : 'Q App';
		}
		$message = "(".(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : "cli").") $app: $message";
		$max_len = Q_Config::get('Q', 'log', 'maxLength', ini_get('log_errors_max_len'));
		$path = (defined('APP_FILES_DIR') ? APP_FILES_DIR : Q_FILES_DIR)
			.DS.'Q'.DS.Q_Config::get('Q', 'internal', 'logDir', 'logs');

		$mask = umask(0000);
		if (!($realPath = Q::realPath($path))
		and !($realPath = Q::realPath($path, true))) {
			if (!@mkdir($path, 0777, true)) {
				throw new Q_Exception_FilePermissions(array('action' => 'create', 'filename' => $path, 'recommendation' => ' Please set the app files directory to be writable.'));
			}
			$realPath = Q::realPath($path, true);
		}
		$filename = (isset($key) ? $key : $app).'.log';
		$to_save = "\n".($timestamp ? '['.date('Y-m-d h:i:s') . '] ' : '') .substr($message, 0, $max_len);
		file_put_contents($realPath.DS.$filename, $to_save, FILE_APPEND);
		umask($mask);
	}

	/**
	 * Check if Q is ran as script
	 * @method textMode
	 * @static
	 * @return {boolean}
	 */
	static function textMode()
	{
		if (!isset($_SERVER['HTTP_HOST'])) {
			return true;
		}
		return false;
	}
	
	/**
	 * Helper function for cutting an array or object to a specific depth
	 * for stuff like json_encode in PHP versions < 5.5
	 * @param {mixed} $value to json encode
	 * @param {array} $options passed to json_encode
	 * @param {integer} $depth defaults to 10
	 * @param {mixed} $replace what to replace the cut off values with
	 */
	static function cutoff($value, $depth = 10, $replace = array())
	{
		if (!is_array($value) and !is_object($value)) {
			return $value;
		}
		$to_encode = array();
		foreach ($value as $k => $v) {
			$to_encode[$k] = ($depth > 0 ? self::cutoff($v, $depth-1) : $replace);
		}
		return $to_encode;
	}

	/**
	 * Dumps a variable.
	 * Note: cannot show protected or private members of classes.
	 * @method var_dump
	 * @static
	 * @param {mixed} $var
	 *  the variable to dump
	 * @param {integer} $max_levels=null
	 *  the maximum number of levels to recurse
	 * @param {string} $label='$'
	 *  optional - label of the dumped variable. Defaults to $.
	 * @param {boolean} $return_content=null
	 *  if true, returns the content instead of dumping it.
	 *  You can also set to "true" to return text instead of HTML
	 * @return {string|null}
	 */
	static function var_dump (
		$var,
		$max_levels = null,
		$label = '$',
		$return_content = null)
	{
		if ($return_content === 'text') {
			$as_text = true;
		} else {
			$as_text = Q::textMode();
		}

		$scope = false;
		$prefix = 'unique';
		$suffix = 'value';

		if ($scope) {
			$vals = $scope;
		} else {
			$vals = $GLOBALS;
		}

		$old = $var;
		$var = $new = $prefix . rand() . $suffix;
		$vname = FALSE;
		foreach ($vals as $key => $val)
			if ($val === $new) // ingenious way of finding a global var :)
				$vname = $key;
		$var = $old;

		if ($return_content) {
			ob_start();
		}
		if ($as_text) {
			echo PHP_EOL;
		} else {
			echo "<pre style='margin: 0px 0px 10px 0px; display: block; background: white; color: black; font-family: Verdana; border: 1px solid #cccccc; padding: 5px; font-size: 10px; line-height: 13px;'>";
		}
		if (!isset(self::$var_dump_max_levels)) {
			self::$var_dump_max_levels = Q_Config::get('Q', 'var_dump_max_levels', 5);
		}
		$current_levels = self::$var_dump_max_levels;
		if (isset($max_levels)) {
			self::$var_dump_max_levels = $max_levels;
		}
		self::do_dump($var, $label . $vname, null, null, $as_text);
		if (isset($max_levels)) {
			self::$var_dump_max_levels = $current_levels;
		}
		if ($as_text) {
			echo PHP_EOL;
		} else {
			echo "</pre>";
		}

		if ($return_content) {
			return ob_get_clean();
		}
		return null;
	}
	
	/**
	 * A wrapper for json_encode
	 */
	static function json_encode()
	{
		$result = call_user_func_array('json_encode', func_get_args());
		return str_replace("\\/", '/', $result);
	}

	/**
	 * Exports a simple variable into something that looks nice, nothing fancy (for now)
	 * Does not preserve order of array keys.
	 * @method var_export
	 * @static
	 * @param {&mixed} $var
	 *  the variable to export
	 * @return {string}
	 */
	static function var_export (&$var)
	{
		if (is_string($var)) {
			$var_2 = addslashes($var);
			return "'$var_2'";
		} elseif (is_array($var)) {
			$indexed_values_quoted = array();
			$keyed_values_quoted = array();
			foreach ($var as $key => $value) {
				$value = self::var_export($value);
				if (is_string($key)) {
					$keyed_values_quoted[] = "'" . addslashes($key) . "' => $value";
				} else {
					$indexed_values_quoted[] = $value;
				}
			}
			$parts = array();
			if (! empty($indexed_values_quoted))
				$parts['indexed'] = implode(', ', $indexed_values_quoted);
			if (! empty($keyed_values_quoted))
				$parts['keyed'] = implode(', ', $keyed_values_quoted);
			$exported = 'array(' . implode(", ".PHP_EOL, $parts) . ')';
			return $exported;
		} else {
			return var_export($var, true);
		}
	}

	/**
	 * Dumps as a table
	 * @method dump_table
	 * @static
	 * @param {array} $rows
	 */
	static function dump_table ($rows)
	{
		$first_row = true;
		$keys = array();
		$lengths = array();
		foreach ($rows as $row) {
			foreach ($row as $key => $value) {
				if ($first_row) {
					$keys[] = $key;
					$lengths[$key] = strlen($key);
				}
				$val_len = strlen((string)$value);
				if ($val_len > $lengths[$key])
					$lengths[$key] = $val_len;
			}
			$first_row = false;
		}
		foreach ($keys as $i => $key) {
			$key_len = strlen($key);
			if ($key_len < $lengths[$key]) {
				$keys[$i] .= str_repeat(' ', $lengths[$key] - $key_len);
			}
		}
		echo PHP_EOL;
		echo implode("\t", $keys);
		echo PHP_EOL;
		foreach ($rows as $i => $row) {
			foreach ($row as $key => $value) {
				$val_len = strlen((string)$value);
				if ($val_len < $lengths[$key]) {
					$row[$key] .= str_repeat(' ', $lengths[$key] - $val_len);
				}
			}
			echo implode("\t", $row);
			echo PHP_EOL;
		}
	}
	
	/**
	 * Parses a querystring without converting some characters to underscores like PHP's version does
	 * @method parse_str
	 * @static
	 * @param {string} $str
	 * @param {reference} $arr reference to an array to fill, just like in parse_str
	 * @return {array} the resulting array of $field => $value pairs
	 */
	static function parse_str ($str, &$arr = null)
	{
		static $s = null, $r = null;
		if (!$s) {
			$s = array('.', ' ');
			$r = array('____DOT____', '____SPACE____');
			for ($i=128; $i<=159; ++$i) {
				$s[] = chr($i);
				$r[] = "____{$i}____";
			}
		}
		parse_str(str_replace($s, $r, $str), $arr);
		return $arr = self::arrayReplace($r, $s, $arr);
	}
	
	/**
	 * Replaces strings in all keys and values of an array, and nested arrays
	 * @method parse_str
	 * @static
	 * @param {string} $search the first parameter to pass to str_replace
	 * @param {string} $replace the first parameter to pass to str_replace
	 * @param {array} $source the array in which the values are found
	 * @return {array} the resulting array
	 */
	static function arrayReplace($search, $replace, $source)
	{
		if (!is_array($source)) {
			return str_replace($search, $replace, $source);
		}
		$result = array();
		foreach ($source as $k => $v) {
			$k2 = str_replace($search, $replace, $k);
			$v2 = is_array($v)
				? self::arrayReplace($search, $replace, $v)
				: str_replace($search, $replace, $v);
			$result[$k2] = $v2;
		}
		return $result;
	}

	/**
	 * Returns stack of events currently being executed.
	 * @method eventStack
	 * @static
	 * @param {string} $event_name=null
	 *  Optional. If supplied, searches event stack for this event name.
	 *  If found, returns the latest call with this event name.
	 *  Otherwise, returns false
	 *
	 * @return {array|false}
	 */
	static function eventStack($event_name = null)
	{
		if (!isset($event_name)) {
			return self::$event_stack;
		}
		foreach (self::$event_stack as $key => $ei) {
			if ($ei['event_name'] === $event_name) {
				return $ei;
			}
		}
		return false;
	}

	/**
	 * Return backtrace
	 * @method backtrace
	 * @static
	 * @param {string} $pattern='$class::$function&#32;(from&#32;line&#32;$line)'
	 * @param {integer} $skip=2
	 */
	static function backtrace($pattern = '$class::$function (from line $line)', $skip = 2)
	{
		$result = array();
		$i = 0;
		foreach (debug_backtrace() as $entry) {
			if (++$i < $skip) {
				continue;
			}
			$entry['i'] = $i;
			foreach (array('class', 'line') as $k) {
				if (empty($entry[$k])) {
					$entry[$k] = '';
				}
			}
			$result[] = self::interpolate($pattern, $entry);
		}
		return $result;
	}

	/**
	 * Backtrace as html
	 * @method b
	 * @static
	 * @param {string} $separator=",&#32;<br>\n"
	 * @return {string}
	 */
	static function b($separator = ", <br>\n")
	{
		return implode($separator, Q::backtrace('$i) $class::$function (from line $line)', 3));
	}

	/**
	 * @method test
	 * @param {string} $pattern
	 */
	static function test($pattern)
	{
		if (!is_string($pattern)) {
			return false;
		}
		Q::var_dump(glob($pattern));
		// TODO: implement
		exit;
	}

	/**
	 * Compares version strings in the format A.B.C...
	 * @method compare_version
	 * @static
	 * @param {string} $a
	 * @param {string} $b
	 * @return {-1|0|1}
	 */
	static function compare_version($a, $b)
	{
		if ($a && !$b) return 1;
		if ($b && !$a) return -1;
		if (!$a && !$b) return 0;
	    $a = explode(".", $a);
	    $b = explode(".", $b);
	    foreach ($a as $depth => $aVal)  {
	    	if (!isset($b[$depth])) $b[$depth] = "0";
            if ($aVal > $b[$depth]) return 1;
            else if ($aVal < $b[$depth]) return -1;
	    }
	    return (count($a) < count($b)) ? -1 : 0;
	}

	/**
	 * @method do_dump
	 * @static
	 * @private
	 * @param {&mixed} $var
	 * @param {string} $var_name=null
	 * @param {string} $indent=null
	 * @param {string} $reference=null
	 * @param {boolean} $as_text=false
	 */
	static private function do_dump (
		&$var,
		$var_name = NULL,
		$indent = NULL,
		$reference = NULL,
		$as_text = false)
	{
		static $n = null;
		if (!isset($n)) {
			$n = Q_Config::get('Q', 'newline', "
");
		}
		$do_dump_indent = $as_text
			? "  "
			: "<span style='color:#eeeeee;'>|</span> &nbsp;&nbsp; ";
		$reference = $reference . $var_name;
		$keyvar = 'the_do_dump_recursion_protection_scheme';
		$keyname = 'referenced_object_name';

		$max_indent = self::$var_dump_max_levels;
		if (strlen($indent) >= strlen($do_dump_indent) * $max_indent) {
			echo $indent . $var_name . " (...)$n";
			return;
		}

		if (is_array($var) && isset($var[$keyvar])) {
			$real_var = &$var[$keyvar];
			$real_name = &$var[$keyname];
			$type = ucfirst(gettype($real_var));
			if ($as_text) {
				echo "$indent$var_name<$type> = $real_name$n";
			} else {
				echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
			}
		} else {
			$var = array($keyvar => $var, $keyname => $reference);
			$avar = &$var[$keyvar];

			$type = ucfirst(gettype($avar));
			if ($type == "String") {
				$type_color = "green";
			} elseif ($type == "Integer") {
				$type_color = "red";
			} elseif ($type == "Double") {
				$type_color = "#0099c5";
				$type = "Float";
			} elseif ($type == "Boolean") {
				$type_color = "#92008d";
			} elseif ($type == "NULL") {
				$type_color = "black";
			} else {
				$type_color = '#92008d';
			}

			if (is_array($avar)) {
				$count = count($avar);
				if ($as_text) {
					echo "$indent" . ($var_name ? "$var_name => " : "")
						. "<$type>($count)$n$indent($n";
				} else {
					echo "$indent" . ($var_name ? "$var_name => " : "")
						. "<span style='color:#a2a2a2'>$type ($count)</span><br>$indent(<br>";
				}
				$keys = array_keys($avar);
				foreach ($keys as $name) {
					$value = &$avar[$name];
					$displayName = is_string($name)
						? "['" . addslashes($name) . "']"
						: "[$name]";
					self::do_dump($value, $displayName,
						$indent . $do_dump_indent, $reference, $as_text);
				}
				if ($as_text) {
					echo "$indent)$n";
				} else {
					echo "$indent)<br>";
				}
			} elseif (is_object($avar)) {
				$class = get_class($avar);
				if ($as_text) {
					echo "$indent$var_name<$type>[$class]$n$indent($n";
				} else {
					echo "$indent$var_name <span style='color:$type_color'>$type [$class]</span><br>$indent(<br>";
				}
				if ($avar instanceof Exception) {
					$code = $avar->getCode();
					$message = addslashes($avar->getMessage());
					echo "$indent$do_dump_indent"."code: $code, message: \"$message\"";
					if ($avar instanceof Q_Exception) {
						echo " inputFields: " . implode(', ', $avar->inputFIelds());
					}
					echo ($as_text ? $n : "<br />");
				}

				if (class_exists('Q_Tree')
				 and $avar instanceof Q_Tree) {
						$getall = $avar->getAll();
						self::do_dump($getall, "",
						$indent . $do_dump_indent, $reference, $as_text);
				} else if ($avar instanceof Q_Uri) {
					$arr = $avar->toArray();
					self::do_dump($arr, 'fields',
						$indent . $do_dump_indent, $reference, $as_text);
					self::do_dump($route_pattern, 'route_pattern',
						$indent . $do_dump_indent, $reference, $as_text);
				}

				if ($avar instanceof Db_Row) {
					foreach ($avar as $name => $value) {
						$modified = $avar->wasModified($name) ? "<span style='color:blue'>*</span>:" : '';
						self::do_dump($value, "$name$modified",
							$indent . $do_dump_indent, $reference, $as_text);
					}
				} else {
					foreach ($avar as $name => $value) {
						self::do_dump($value, "$name",
							$indent . $do_dump_indent, $reference, $as_text);
					}
				}

				if ($as_text) {
					echo "$indent)$n";
				} else {
					echo "$indent)<br>";
				}
			} elseif (is_int($avar)) {
				$avar_len = strlen((string)$avar);
				if ($as_text) {
					echo sprintf("$indent$var_name = <$type(%d)>$avar$n", $avar_len);
				} else {
					echo sprintf(
						"$indent$var_name = <span style='color:#a2a2a2'>$type(%d)</span>"
						. " <span style='color:$type_color'>$avar</span><br>",
						$avar_len
					);
				}
			} elseif (is_string($avar)) {
				$avar_len = strlen($avar);
				if ($as_text) {
					echo sprintf("$indent$var_name = <$type(%d)> ", $avar_len),
						$avar, "$n";
				} else {
					echo sprintf("$indent$var_name = <span style='color:#a2a2a2'>$type(%d)</span>",
						$avar_len)
						. " <span style='color:$type_color'>"
						. Q_Html::text($avar)
						. "</span><br>";
				}
			} elseif (is_float($avar)) {
				$avar_len = strlen((string)$avar);
				if ($as_text) {
					echo sprintf("$indent$var_name = <$type(%d)>$avar$n", $avar_len);
				} else {
					echo sprintf(
						"$indent$var_name = <span style='color:#a2a2a2'>$type(%d)</span>"
						. " <span style='color:$type_color'>$avar</span><br>",
						$avar_len);
				}
			} elseif (is_bool($avar)) {
				$v = ($avar == 1 ? "TRUE" : "FALSE");
				if ($as_text) {
					echo "$indent$var_name = <$type>$v$n";
				} else {
					echo "$indent$var_name = <span style='color:#a2a2a2'>$type</span>"
						. " <span style='color:$type_color'>$v</span><br>";
				}
			} elseif (is_null($avar)) {
				if ($as_text) {
					echo "$indent$var_name = NULL$n";
				} else {
					echo "$indent$var_name = "
						. " <span style='color:$type_color'>NULL</span><br>";
				}
			} else {
				$avar_len = strlen((string)$avar);
				if ($as_text) {
					echo sprintf("$indent$var_name = <$type(%d)>$avar$n", $avar_len);
				} else {
					echo sprintf("$indent$var_name = <span style='color:#a2a2a2'>$type(%d)</span>",
						$avar_len)
						. " <span style='color:$type_color'>"
						. gettype($avar)
						. "</span><br>";
				}
			}

			$var = $var[$keyvar];
		}
	}
	
	/**
	 * @method reverseLengthCompare
	 * @private
	 * @return {integer}
	 */
	protected static function reverseLengthCompare($a, $b)
	{
		return strlen($b)-strlen($a);
	}

	/**
	 * @property $included_files
	 * @type array
	 * @static
	 * @protected
	 */
	protected static $included_files = array();
	/**
	 * @property $var_dump_max_levels
	 * @type integer
	 * @static
	 * @protected
	 */
	protected static $var_dump_max_levels;

	/**
	 * @property $event_stack
	 * @type array
	 * @static
	 * @protected
	 */
	protected static $event_stack = array();
	/**
	 * @property $event_stack_length
	 * @type integer
	 * @static
	 * @protected
	 */
	protected static $event_stack_length = 0;
	/**
	 * @property $event_empty
	 * @type array
	 * @static
	 * @protected
	 */
	protected static $event_empty = array();

	/**
	 * @property $controller
	 * @type array
	 * @static
	 */
	static $controller = null;

	/**
	 * @property $state
	 * @type array
	 * @static
	 */
	static $state = array();
	/**
	 * @property $cache
	 * @type array
	 * @static
	 */
	public static $cache = array();
	
	/**
	 * @property $toolName
	 * @type string
	 * @static
	 */
	public static $toolName = null;
	
	/**
	 * @property $toolWasRendered
	 * @type array
	 * @static
	 */
	public static $toolWasRendered = array();
}

/// { aggregate classes for production
/// Q/*.php
/// Q/Exception/MissingFile.php
/// }
