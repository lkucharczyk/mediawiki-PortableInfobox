<?php
namespace PortableInfobox\Parser\Nodes;

class NodeImage extends NodeMedia {
	/*
	 * @return bool
	 */
	protected function allowImage() {
		return true;
	}

	/*
	 * @return bool
	 */
	protected function allowAudio() {
		return false;
	}
}