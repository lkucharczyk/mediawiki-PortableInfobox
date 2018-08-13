<?php
namespace PortableInfobox\Parser\Nodes;

class NodeAudio extends NodeMedia {
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
		return false;
	}

	/*
	 * @return bool
	 */
	protected function allowAudio() {
		return true;
	}
}