<?php
namespace Wikia\Template;

/**
 * Template FileSystem-based engine for Wikia Templating System.
 *
 * @package Wikia\Template
 * @author Federico "Lox" Lucignano <federico@wikia-inc.com>
 */

class TemplateEngine {
	protected $values = [];
	protected $prefix = '';

	/**
	 * Checks if a template exists.
	 *
	 * @param string $template The name of the template, will be combined with
	 * the value passed to TemplateEngine::setPrefix().
	 * In case of FileSystem-based engines it should be the filename
	 * either alone (TemplateEngine::setPrefix()) or the full path, both need to
	 * include the file's extension.
	 *
	 * @return bool Wether the template was found or not
	 */
	public function exists( $template ) {
		//wfProfileIn( __METHOD__ );

		$found = file_exists( $this->prefix == '' ? $template : $this->prefix . DIRECTORY_SEPARATOR . $template );

		//wfProfileOut( __METHOD__ );
		return $found;
	}

	/**
	 * Renders the template as a string.
	 *
	 * @param string $template The name of the template, will be combined with
	 * the value passed to TemplateEngine::setPrefix().
	 * In case of FileSystem-based engines it should be the filename
	 * either alone (TemplateEngine::setPrefix()) or the full path, both need to
	 * include the file's extension.
	 *
	 * @return string The rendered template
	 */
	public function render( $template ) {
		//wfProfileIn( __METHOD__ );

		$path = $this->prefix == '' ? $template : $this->prefix . DIRECTORY_SEPARATOR . $template;

		//wfProfileIn( __METHOD__ . " - template: {$path}" );
		$contents = \TemplateService::getInstance()->render( $path, $this->values );
		//wfProfileOut( __METHOD__ . " - template: {$path}" );

		//wfProfileOut( __METHOD__ );
		return $contents;
	}

	/**
	 * Sets the base path for this instance.
	 *
	 * @param string $prefix The prefix to append to the template
	 * name passed as a parameter to TemplateEngine::render(), e.g. the path
	 * to a folder containing template files for filesystem-based
	 * engines.
	 *
	 * @return TemplateEngine The current instance
	 */
	public function setPrefix ( $prefix ) {
		$this->prefix = (string) $prefix;
		return $this;
	}

	/**
	 * Returns the base path set for this instance.
	 *
	 * @return string|null
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * Sets multiple values to be passed to a template at once.
	 *
	 * @param array $values The values to be passed to a template
	 * in the form of an associative array, i.e. [IDENTIFIER => VALUE]
	 *
	 * @return TemplateEngine The current instance
	 *
	 * @see TemplateEngine::setVal() if you need to set only one value
	 */
	public function setData( Array $values ) {
		$this->values = $values;
		return $this;
	}

	/**
	 * Add/overwrites multiple values in the collection of the current instance
	 * to be passed to a template.
	 *
	 * @param array $values The values to be added/updated in the form of an
	 * associative array, i.e. [IDENTIFIER => VALUE]
	 *
	 * @return TemplateEngine The current instance
	 *
	 * * @see TemplateEngine::setVal() if you need to add/overwrite only one value
	 */
	public function updateData( Array $values ) {
		wfProfileIn( __METHOD__ );

		$this->values = array_merge( $this->values, $values );

		wfProfileOut( __METHOD__ );
		return $this;
	}

	/**
	 * Empties the collection of values stored in an instance
	 *
	 * @return TemplateEngine The current instance
	 *
	 * @see TemplateEngine::clearVal() if you need to clear only one value
	 */
	public function clearData(){
		$this->values = [];
		return $this;
	}

	/**
	 * Returns the values set for this instance to be passed to a template.
	 *
	 * @return array The values set for this instance, null if the collection
	 * wasn't set
	 *
	 * @see TemplateEngine::getVal() if you need to get only one value
	 */
	public function getData() {
		return $this->values;
	}

	/**
	 * Sets/add a value in the collection to be passed to a template.
	 *
	 * @param string $name The name of the value
	 * @param mixed $value The real value
	 *
	 * @return TemplateEngine The current instance
	 *
	 * @see TemplateEngine::setData() if you need to set multiple values instead
	 * of calling this method multiple times
	 */
	public function setVal( $name, $value ) {
		$this->values[$name] = $value;
		return $this;
	}

	/**
	 * Removed a value from he collection to be passed to a template.
	 *
	 * @param string $name The name of the value
	 *
	 * @return TemplateEngine The current instance
	 *
	 * @see TemplateEngine::clearData() if you need to clear the whole collection
	 * instead of calling this method multiple times
	 */
	public function clearVal( $name ){
		unset( $this->values[$name] );
		return $this;
	}

	/**
	 * Returns a value in the collection to be passed to a template.
	 *
	 * @param string $name The name of the value
	 *
	 * @return mixed|null The current instance
	 *
	 * @see TemplateEngine::setData() if you need to get multiple values instead
	 * of calling this method multiple times
	 */
	public function getVal( $name ) {
		return isset($this->values[$name]) ? $this->values[$name]: null;
	}
};
