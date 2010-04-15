<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Matteo  Savio <msavio@ajado.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'ajado inline gallery' for the 'ajado_inlinegallery' extension.
 *
 * @author	Matteo  Savio <msavio@ajado.com>
 * @package	TYPO3
 * @subpackage	tx_ajadoinlinegallery
 */
class tx_ajadoinlinegallery_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_ajadoinlinegallery_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_ajadoinlinegallery_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'ajado_inlinegallery';	// The extension key.
	var $pi_checkCHash = true;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->pi_initPIflexForm(); // Init and get the flexform data of the plugin
	    $this->lConf = array(); // Setup our storage array...

	    // Assign the flexform data to a local variable for easier access
	    $piFlexForm = $this->cObj->data['pi_flexform'];

	    // Traverse the entire array based on the language...
	    // and assign each configuration option to $this->lConf array...
	    foreach ( $piFlexForm['data'] as $sheet => $data ) {
	        foreach ( $data as $lang => $value ) {
	            foreach ( $value as $key => $val ) {
	                $this->lConf[$key] = $this->pi_getFFvalue($piFlexForm, $key, $sheet);
	            }
	        }
	    }
      
      // TODO: in extra Funktion auslagern
	    $GLOBALS['TSFE']->setJS('enlargeImage', "
	        var statuses = new Array();
	        var smallHeights = new Array();
	        var smallWidths = new Array();
	        var smallImgs = new Array();
	        minimizeAllButton = false;
	        
			function array_key_exists(key, search) {
   				return (typeof search[key] != 'undefined');
			}
			function changeImg(bild, w1, h1, w2, h2, simg, limg) {
				var dasBild = document.getElementById(bild);
				image = new Image();

				if((!array_key_exists(bild, statuses)) || (statuses[bild] == 'small')) {
          image.src = limg;
			    dasBild.style['height'] = h1 + 'px';
          dasBild.style['width'] = w1 + 'px';
			    statuses[bild] = 'large';
			    smallHeights[bild] = h2;
			    smallWidths[bild] = w2;
			    smallImgs[bild] = simg;
			    setMinimizeAllButton();
				}
				else {
          image.src = simg;
			    dasBild.style['height'] = h2 + 'px';
          dasBild.style['width'] = w2 + 'px';
			    statuses[bild] = 'small';
			    checkIfMinimizeAllButtonNeeded();
				}
				
				dasBild.src=image.src;
			}
			
			function setMinimizeAllButton() {
				if((minimizeAllButton == false)&&(document.getElementById('tx_ajadoinlinegallery_pi1_minimizeAllLink'))) {
				  document.getElementById('tx_ajadoinlinegallery_pi1_minimizeAllLink').innerHTML = '<a onfocus=\"blurLink(this);\" href=\"javascript:minimizeAll()\">minimize all images</a>';
				}
			}
			
			function checkIfMinimizeAllButtonNeeded() {
			  var needed = false;
			  for ( keyVar in statuses ) {
	    	  if(statuses[keyVar] != 'small') {
	          needed = true;
					}
				}
				if((!needed)&&(document.getElementById('tx_ajadoinlinegallery_pi1_minimizeAllLink'))) {
				  document.getElementById('tx_ajadoinlinegallery_pi1_minimizeAllLink').innerHTML = '';
					minimizeAllButton = false;
				}
			}
			
			function minimizeAll() {
    	  for ( keyVar in statuses ) {
	    	  if(statuses[keyVar] != 'small') {
	    	    var dasBild = document.getElementById(keyVar);
				    image = new Image();
	          image.src = smallImgs[keyVar];
				    dasBild.style['height'] = smallHeights[keyVar] + 'px';
	          dasBild.style['width'] = smallWidths[keyVar]+ 'px';
				    dasBild.src=image.src;
				    statuses[keyVar] = 'small';
					}
				}
				if(document.getElementById('tx_ajadoinlinegallery_pi1_minimizeAllLink')) {
				  document.getElementById('tx_ajadoinlinegallery_pi1_minimizeAllLink').innerHTML = '';
					minimizeAllButton = false;
				}
			}
			");
			
	    $images = t3lib_div::trimExplode(',', $this->lConf["image"]);
	    $widthheight = t3lib_div::trimExplode("\n", $this->lConf["widthheight"]);
	    $altTexts = t3lib_div::trimExplode("\n", $this->lConf["alttext"]);

			// TODO: Nur ein Width und ein Height Feld zulassen
	    foreach ($widthheight as &$value) {
    		$value = t3lib_div::intExplode('x', trim($value, '"'));
		  }

	    for($i = 0; $i < count($images); $i++) {
	    	$jsname = 'img' . $this->cObj->parentRecordNumber . '_' . $i;
	    	$altText = isset($altTexts[$i]) ? htmlspecialchars($altTexts[$i]) : '';
	    	
	    	// TODO: Upload Directory anders codieren
	    	$lname = 'uploads/tx_ajadoinlinegallery/' . $images[$i];

				$imgInfo = @getimagesize($lname);
				print_r($imgInfo); echo "-";
		    $GLOBALS['TSFE']->imagesOnPage[]=$lname;

			  $this->conf["preview."]["file."]["1."]["file"] = $lname;
			  
			  if(isset($widthheight[$i])) {
			  	if(isset($widthheight[$i][0])) {
			  		$this->conf["preview."]["file."]["2."]["width"] = $widthheight[$i][0];
			  	}
			  	if(isset($widthheight[$i][1])) {
			  		$this->conf["preview."]["file."]["2."]["height"] = $widthheight[$i][1];
			  	}
			  }
        
			  $image_prev = $this->cObj->getImgResource($this->conf["preview."]["file"], $this->conf["preview."]["file."]);
			  $image_prev[3] = t3lib_div::png_to_gif_by_imagemagick($image_prev[3]);
			  $sname=htmlspecialchars($GLOBALS['TSFE']->absRefPrefix.t3lib_div::rawUrlEncodeFP($image_prev[3]));
        
			  $GLOBALS['TSFE']->imagesOnPage[] = $sname;
        
        // TODO: Codes mit Key generieren
			  $content .= '<span class="tx_ajadoinlinegallery_pi1_image tx_ajadoinlinegallery_pi1_' . $jsname . '" >';
			  // TODO: Link mit Link-Funktion setzen
			  $content .= '<a href="javascript: changeImg(\'' . $jsname . '\', ' . $imgInfo[0] . ', ' . $imgInfo[1] . ', ' . $image_prev[0] . ', ' . $image_prev[1] . ', \'' . $sname . '\', \'' . $lname . '\');">';
        
        // TODO: Img mit Image-Funktion setzen
			  $content .= '<img src="' . $sname . '" width="' . $image_prev[0] . '" height="' . $image_prev[1] . '" ' . $this->conf["preview."]["params"] . ' alt="' . $altText . '" id="' . $jsname . '" />';
			  $content .= '</a>';
			  $content .= '</span>';
	    }
		return $this->pi_wrapInBaseClass($content);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ajado_inlinegallery/pi1/class.tx_ajadoinlinegallery_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ajado_inlinegallery/pi1/class.tx_ajadoinlinegallery_pi1.php']);
}

?>