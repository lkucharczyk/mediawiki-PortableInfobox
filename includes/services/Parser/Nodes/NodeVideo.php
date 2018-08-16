<?php
namespace PortableInfobox\Parser\Nodes;

class NodeVideo extends NodeMedia {
	/*
	 * @return bool
	 */
	protected function allowImage() {
		return false;
	}

	/*
	 * @return bool
	 */
	protected function allowVideo() {
		return true;
	}

	/*
	 * @return bool
	 */
	protected function allowAudio() {
		return false;
	}
}
