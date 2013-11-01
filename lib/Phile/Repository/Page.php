<?php

namespace Phile\Repository;
use Phile\Exception;
use Phile\Registry;


/**
 * the Repository class for pages
 * @author Frank Nägler
 * @package Phile\Repository
 */
class Page {
	const   ORDER_ASC   = 'asc';
	const   ORDER_DESC  = 'desc';

	/**
	 * find a page by path
	 *
	 * @param $path
	 * @return null|\Phile\Model\Page
	 */
	public function findByPath($path) {
		$config     = Registry::get('Phile_Settings');
		$path       = str_replace($config['install_path'], '', $path);
		$file = null;
		if (file_exists(CONTENT_DIR . $path . CONTENT_EXT)) {
			$file = CONTENT_DIR . $path . CONTENT_EXT;
		}
		if ($file == null) {
			if (file_exists(CONTENT_DIR . $path . '/index' . CONTENT_EXT)) {
				$file = CONTENT_DIR . $path . '/index' . CONTENT_EXT;
			}
		}

		if ($file !== null) {
			return new \Phile\Model\Page($file);
		}

		return null;
	}

	/**
	 * find all pages (*.md) files and returns an array of Page models
	 * @return array of \Phile\Model\Page objects
	 */
	public function findAll(array $options = null) {
		$dir        = new \RecursiveDirectoryIterator(CONTENT_DIR);
		$ite        = new \RecursiveIteratorIterator($dir);
		$files      = new \RegexIterator($ite, '/^.*\\'.CONTENT_EXT.'/', \RegexIterator::GET_MATCH);
		$pages      = array();
		foreach ($files as $file) {
			if (str_replace(CONTENT_DIR, '', $file[0]) == '404.md') {
				// jump to next page if file is the 404 page
				continue;
			}
			$pages[]    = new \Phile\Model\Page($file[0]);
		}

		if ($options !== null && isset($options['order_by'])) {
			switch (strtolower($options['order_by'])) {
				case 'date':
					error_log('the key date for sorting is deprecated');
					$date_id = 0;
					$sorted_pages = array();
					foreach ($pages as $page) {
						if ($page->getMeta()->getDate() !== null) {
							$sorted_pages[$page->getMeta()->getDate().$date_id] = $page;
							$date_id++;
						} else {
							$sorted_pages[] = $page;
						}
					}
					if (!isset($options['order'])) {
						$options['order'] = self::ORDER_ASC;
					}
					if ($options['order'] == self::ORDER_ASC) {
						ksort($sorted_pages);
					}
					if ($options['order'] == self::ORDER_DESC) {
						krsort($sorted_pages);
					}
					unset($pages);
					$pages = $sorted_pages;
				break;
				case 'alpha':
				case 'title':
					if (strtolower($options['order_by']) == 'alpha') {
						error_log('the key alpha for sorting is deprecated, use title instead');
					}
					if (!isset($options['order'])) {
						$options['order'] = self::ORDER_ASC;
					}
					if ($options['order'] == self::ORDER_ASC) {
						usort($pages, array($this, "compareByTitleAsc"));
					}
					if ($options['order'] == self::ORDER_DESC) {
						usort($pages, array($this, "compareByTitleDesc"));
					}
				break;
				default:
					throw new Exception("unknown key '{$options['order_by']}' for order_by");
				break;
			}
		}
		return $pages;
	}

	protected function compareByTitleAsc($a, $b) {
		$al = strtolower($a->getMeta()->getTitle());
		$bl = strtolower($b->getMeta()->getTitle());
		if ($al == $bl) {
			return 0;
		}
		return ($al > $bl) ? +1 : -1;
	}

	protected function compareByTitleDesc($a, $b) {
		$al = strtolower($a->getMeta()->getTitle());
		$bl = strtolower($b->getMeta()->getTitle());
		if ($al == $bl) {
			return 0;
		}
		return ($al < $bl) ? +1 : -1;
	}
}