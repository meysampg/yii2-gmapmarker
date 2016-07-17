<?php

namespace meysampg\gmap;

use yii\web\AssetBundle;
use yii\base\InvalidConfigException;

class GMapAsset extends AssetBundle
{
	/**
	 * Easily you can set the asset needed values with as mentioned on http://www.yiiframework.com/doc-2.0/guide-structure-assets.html#customizing-asset-bundles. 
	 * For example You can use this code on you `web.config`:
	 * ```
	 * return [
     * 		 // ...
     * 		 'components' => [
     *   	 	 'assetManager' => [
     *     		 	 'bundles' => [
     *           	 	 'meysampg\gmap\GMapAsset' => [
     *               	 	 'key' => 'YOU_API_KEY',
     *						 'language' => 'en'
     *           	 	 ],
     *       	 	 ],
     * 		 	 ],
     * 		 ],
	 * ];
	 * ```
	 */

	// You can a obtain an API key from https://console.developers.google.com/apis/library
	public $key;

	// You can find a list of supported language on https://developers.google.com/maps/faq#languagesupport
	public $language;

	public function init()
	{
		parent::init();

		if (!$this->key) {
			throw new InvalidConfigException('API Key Must Be Set on Componenets. Read README!');
		}

		if (!$this->language) {
			$this->language = 'en';
		}

		$this->js[] = "//maps.googleapis.com/maps/api/js?key={$this->key}&language={$this->language}";
	}
}
