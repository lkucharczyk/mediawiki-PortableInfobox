<?php

namespace PortableInfobox\Sanitizers;

use PortableInfobox\Helpers\HtmlHelper;
use MediaWiki\Logger\LoggerFactory;

abstract class NodeSanitizer implements NodeTypeSanitizerInterface {
	private $rootNodeTag = 'body';

	/**
	 * Sanitizer configuration
	 * Can be overridden by child classes
	 */
	// these are selectors for explicitly allowed tags, like 'a'
	protected $allowedTags = [];
	// these are valid 'internal' node names (per libxml convention, i.e. a, #text, etc)
	protected $validNodeNames = [ '#text' ];
	// these are selectors that describe nodes containing text that should be padded with whitespace
	protected $selectorsWrappingTextToPad = [];
	// these are selectors that describe root nodes of known features that should not be sanitized
	protected $selectorsWrappingAllowedFeatures = [];
	// these are selectors that describe nodes for full removal
	protected $selectorsForFullRemoval = [];

	/**
	 * Sanitize a single text element, i.e. title or label
	 *
	 * @param string $elementText
	 * @return string
	 */
	protected function sanitizeElementData( $elementText ) {
		$dom = HtmlHelper::createDOMDocumentFromText( $this->prepareValidXML( $elementText ) );

		$elementTextAfterTrim = trim( $this->cleanUpDOM( $dom ) );

		if ( $elementTextAfterTrim !== $elementText ) {
			LoggerFactory::getInstance( 'PortableInfobox' )->info(
				'Stripping HTML tags from infobox element: [ originalText: "{originalText}", trimmedText: "{trimmedText}" ]',
				[
					'originalText' => $elementText,
					'trimmedText' => $elementTextAfterTrim
				]
			);
			$elementText = $elementTextAfterTrim;
		}
		return $elementText;
	}

	/**
	 * Wraps text in root node and prefixes with XML header providing explicit encoding
	 *
	 * @param string $elementText
	 * @return string
	 */
	protected function prepareValidXML( $elementText ) {
		$wrappedText = implode( '', [
			\Xml::openElement( $this->rootNodeTag ),
			$elementText,
			\Xml::closeElement( $this->rootNodeTag )
		] );
		return $wrappedText;
	}

	/**
	 * Removes nodes that do not need to remain in the resulting output. By default leaves only text nodes
	 *
	 * @param \DOMDocument $dom
	 * @return string
	 */
	protected function cleanUpDOM( $dom ) {
		$xpath = new \DOMXPath( $dom );
		$this->removeNodesBySelector( $xpath, $this->selectorsForFullRemoval );

		$nodes = $this->extractNeededNodes( $xpath );

		return $this->normalizeWhitespace( $this->generateHTML( $nodes, $dom ) );
	}

	/**
	 * Produces sanitized HTML markup from DOMNode array
	 *
	 * @param \DOMNode[] $nodes
	 * @param \DOMDocument $dom
	 * @return string
	 */
	protected function generateHTML( $nodes, $dom ) {
		$result = [];
		foreach ( $nodes as $node ) {
			$outputHtml = $rawHtml = $dom->saveHTML( $node );
			if ( $node->nodeName === '#text' ) {
				// As the input text is already escaped, we make sure that our output will be escaped too
				$outputHtml = htmlspecialchars( $rawHtml, ENT_QUOTES );
			}
			if ( $node->parentNode && in_array( $node->parentNode->nodeName, $this->selectorsWrappingTextToPad ) ) {
				$outputHtml = sprintf( ' %s ', $rawHtml );
			}

			$result[] = $outputHtml;

		}
		return implode( '', $result );
	}

	/**
	 * Replaces multiple whitespaces with single ones.
	 * Transparent from non-preformatted HTML point of view
	 *
	 * @param string $text
	 * @return string
	 */
	protected function normalizeWhitespace( $text ) {
		return mbereg_replace( "\s+", " ", $text );
	}

	/**
	 * Returns xpath string covering all legal tag and text nodes concerning the sanitizer
	 * @return string
	 */
	protected function getAllNodesXPath() {
		$xpathExpressions = [];
		foreach ( $this->selectorsWrappingAllowedFeatures as $selector ) {
			$xpathExpressions[] = sprintf( '//%s//%s', $this->rootNodeTag, $selector );
		}
		foreach ( $this->allowedTags as $selector ) {
			$xpathExpressions[] = sprintf( '//%s//%s', $this->rootNodeTag, $selector );
		}
		$xpathExpressions[] = sprintf( '//%s//text()', $this->rootNodeTag );

		return implode( ' | ', $xpathExpressions );
	}

	/**
	 * @param \DOMNode $DOMnode
	 * @return bool
	 */
	protected function shouldNodeBeRemoved( $DOMnode ) {
		return ( $DOMnode && $DOMnode->nodeName === 'a' && $DOMnode->nodeValue === '' );
	}

	/**
	 *
	 * @param \DOMNode $node
	 * @return bool
	 */
	protected function isNodeAllowedByTag( $node ) {
		// tags that are explicitly allowed
		if ( in_array( $node->nodeName, array_merge( $this->validNodeNames, $this->allowedTags ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Removes nodes specified by tag completely
	 * @param \DOMXPath $xpath
	 * @param array $selectorsToRemove
	 */
	protected function removeNodesBySelector( $xpath, $selectorsToRemove = [] ) {
		foreach ( $selectorsToRemove as $selector ) {
			$nodesToRemove = $xpath->query( sprintf( '//%s//%s', $this->rootNodeTag, $selector ) );
			foreach ( $nodesToRemove as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}
	}

	/**
	 * Returns nodes that have contents allowed by current sanitizer's config
	 *
	 * @param \DOMXPath $xpath DOMXPath
	 * @return \DOMNode[]
	 */
	protected function extractNeededNodes( $xpath ) {
		$nodes = [];
		$featureNodes = [];
		$allNodes = $xpath->query( $this->getAllNodesXPath() );
		foreach ( $allNodes as $node ) {
			if ( $this->shouldNodeBeProcessed( $node, $nodes, $featureNodes, $xpath ) ) {
				$nodes[] = $node;

				// Store the information that a given feature node was processed
				if ( $this->isAllowedFeatureNode( $node, $xpath ) ) {
					$featureNodes[] = $node;
				}
			}
		}
		return $nodes;
	}

	/**
	 * Checks if current node is a wrapping tag for a feature
	 *
	 * @param \DOMNode $node
	 * @param \DOMXPath $xpath
	 * @return bool
	 */
	protected function isAllowedFeatureNode( $node, $xpath ) {
		foreach ( $this->selectorsWrappingAllowedFeatures as $selector ) {
			$nodeQueryResult = $xpath->query( sprintf( '//%s//%s', $this->rootNodeTag, $selector ), $node );
			if ( $nodeQueryResult->length ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Used for skipping processing on subnodes of feature nodes (i.e. citation)
	 *
	 * @param \DOMNode $node
	 * @param \DOMNode[] $featureNodes
	 * @return bool
	 */
	protected function isDescendantOfProcessedFeatureNode( $node, $featureNodes ) {
		$parent = $node->parentNode;
		while ( $parent ) {
			if ( in_array( $parent, $featureNodes, true ) ) {
				return true;
			}
			$parent = $parent->parentNode;
		}

		return false;
	}

	/**
	 * @param \DOMNode $node
	 * @param \DOMNode[] $nodes
	 * @return bool
	 */
	protected function isChildOfProcessedTagNode( $node, $nodes ) {
		return (
			in_array( $node->parentNode, $nodes, true )
			&& $node->parentNode
			&& $node->parentNode->childNodes->length === 1
		);
	}

	/**
	 * Returns whether a node should be fully processed based on multiple factors
	 * captured in submethods; the key two criteria are:
	 * - is it an allowed node (by tag or a selector)
	 * - is it excluded from processing because of its state (i.e. empty) or already processed ancestor nodes
	 *
	 * @param \DOMNode $node
	 * @param \DOMNode[] $simpleTagNodes
	 * @param \DOMNode[] $featureNodes
	 * @param \DOMXPath $xpath
	 * @return bool
	 */
	protected function shouldNodeBeProcessed( $node, $simpleTagNodes, $featureNodes, $xpath ) {
		return (
			$this->isNodeAllowedByTag( $node, $this->allowedTags )
			|| $this->isAllowedFeatureNode( $node, $xpath )
		)
		&& !$this->shouldNodeBeRemoved( $node )
		&& !$this->isChildOfProcessedTagNode( $node, $simpleTagNodes )
		&& !$this->isDescendantOfProcessedFeatureNode( $node, $featureNodes );
	}

}
