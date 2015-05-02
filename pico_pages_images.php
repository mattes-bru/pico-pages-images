<?php
/**
 * Access to the images of the current page folder with {{ images }} in Pico CMS.
 *
 * @author  Nicolas Liautaud
 * @link http://nliautaud.fr
 * @link http://pico.dev7studios.com
 * @license http://opensource.org/licenses/MIT
 */

// include composer autoload
require 'vendor/autoload.php';

// import the Intervention Image Manager Class
use Intervention\Image\ImageManagerStatic as Image;




class Pico_Pages_Images
{
	private $path;
	private $root;

	private $create_thumbnails;
	private $thumbnail_path = "";
	private $images_order_by;
	private $images_order;

	// Pico hooks ---------------

	/**
	 * Register the images path.
	 */
	public function request_url(&$url)
	{
		$this->path = $this->format($url);
	}


	public function config_loaded(&$settings)
	{
		if( !empty($settings['images_path']) )
			$this->root = $this->format($settings['images_path']);
		else $this->root = 'content/';

		if( !empty($settings['thumbnail_path'])) {
			$this->create_thumbnails = true;
			$this->thumbnail_path = $settings['thumbnail_path'];
		}
		else $create_thumbnails = false;

		if( !empty($settings['images_order_by']))
			$this->images_order_bys = $settings['images_order_by'];
		else $this->images_order_by = 'alpha';


		if( !empty($settings['images_order']))
			$this->images_order = $settings['images_order'];
		else $this->images_order = 'asc';
	}

	/**
	 * Register the images data in {{ images }} Twig variable.
	 */
	public function before_render(&$twig_vars, &$twig)
	{
		$twig_vars['images'] = $this->images_list($twig_vars['base_url']);
	}


	// CORE ---------------

	/**
	 * Format given path. Remove trailing 'index' and add trailing slash if missing.
	 */
	private function format($path)
	{
		if( !$path ) return;

		$is_index = strripos($path, 'index') === strlen($path)-5;
		if( $is_index ) return substr($path, 0, -5);
		elseif( substr($path, -1) != '/' ) $path .= '/';

		return $path;
	}
	/**
	 * Return the list and infos of images in the current directory.
	 */
	private function images_list($base_url)
	{

		$images_path = $this->root . $this->path;

		$data = array();
		$pattern = '*.{[jJ][pP][gG],[jJ][pP][eE][gG],[pP][nN][gG],[gG][iI][fF]}';
		$images = glob(ROOT_DIR .'/'. $images_path . $pattern, GLOB_BRACE);

		foreach( $images as $path )
		{
			list(, $basename, $ext, $filename) = array_values(pathinfo($path));
			list($width, $height, $type, $size, $mime) = getimagesize($path);

			$thumbnail = '';
			$img = Image::make(ROOT_DIR . '/'. $images_path . $basename);
			$exif = $img->exif();
			$timestamp = array_key_exists('DateTime', $exif) ? strtotime($exif['DateTime']) : intval($exif['FileDateTime']);
			$date_formatted = date("d.m.Y - H:i", $timestamp);

			if($this->create_thumbnails){
				$thumbnail = '/'.$images_path . $this->thumbnail_path .  $filename . '.' . $ext;
				if(!file_exists(ROOT_DIR . $thumbnail )) {
					$img->resize(320, 240);
					$img->save(ROOT_DIR . $thumbnail);
				}
			}



			$data[] = array (
				'url' => '/'. $images_path . $basename,
				'path' => $images_path,
				'name' => $filename,
				'ext' => $ext,
				'width' => $width,
				'height' => $height,
				'size' => $size,
				'thumbnail_url' => $thumbnail,
				'exif' =>$exif,
				'timestamp_formatted' => $date_formatted,
				'timestamp' => $timestamp
			);
		}

		if($this->images_order_by == 'date') {
			//Sort by date
			usort($data, create_function('$a,$b', 'return $a["timestamp"] - $b["timestamp"];'));
		}

		if($this->images_order == 'desc') {
			//reverse
			$data = array_reverse($data);
		}

		

		return $data;
	}



}
?>
