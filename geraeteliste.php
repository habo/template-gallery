<?php
/**
 * Geräteliste MediaWiki extension.
 *
 * This extension implements a <geraeteliste> tag creating a gallery of all images in
 * a category.
 *
 * by habo
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Extensions
 */
if( !defined( 'MEDIAWIKI' ) ) {
        echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
        die( 1 );
}
$wgExtensionCredits['parserhook'][] = array(
        'path' => __FILE__,
        'name' => 'Geräteliste',
        'author' => 'habo',
        'url' => 'http://wiki.dingfabrik.de/index.php/Extention/Geräteliste',
        'description' => 'Adds <nowiki><geraeteliste></nowiki> tag',
        'version' => '1.1.0'
);
$wgExtensionFunctions[] = "CategoryGallery::categoryGallerySetHook";
class CategoryGallery {
        public static function categoryGallerySetHook() {
                global $wgParser;
                $wgParser->setHook( "geraeteliste", "CategoryGallery::renderCategoryGallery" );
                $wgParser->setHook( "projektliste", "CategoryGallery::renderCategoryGallery" );
        }
        public static function renderCategoryGallery( $input, $params, $parser ) {
                global $wgBedellPenDragonResident;
                $parser->disableCache();
                $dbr = wfGetDB( DB_SLAVE );
		$showstat=false;
                if ( isset( $params['stat'] ) ) { // show statistics
			$showstat=true;
		}
                if ( !isset( $params['cat'] ) ) { // No category selected
			$cat="Gerät";
	        } else {
			$cat=$params['cat'];
	        }
                if ( !isset( $params['noimg'] ) ) { // noimage attribute missing
			$noimg="Device-level-yellow.png";
	        } else {
			$noimg=$params['noimg'];
	        }

                $res = $dbr->select( 'categorylinks', 'cl_from',
                        array (
                               'cl_to' => $cat,
			       'cl_type' => "page",
                        )
                );
                $ids = array();
                foreach ( $res as $row ) {
                        $ids[] = $row->cl_from;
                }
                $text = '';
		$count_total=0;
		$count_bild=0;

                foreach ( $ids as $id ) {
                        $page = WikiPage::newFromId( $id );
                        $title = Title::newFromID ( $id );
                        $tkey = $title->getPrefixedDBKey();
                        $tclean = str_replace("_"," ",$title->getPrefixedDBKey());
			$content = $page->getText();
			$isgpage=strstr($content,"{{Gerätekarte") || strstr($content,"{{Projektkarte");
			if (!$isgpage){
				continue;
			}
			preg_match('/Bild.*=(.*)/', $content, $str);
			$bild = $noimg;
			if ( !empty(trim($str[1]))) {
                        	$bild = $str[1];
				$count_bild++;
			}
			$count_total++;
                        $text .= $bild . "|[[".$tkey."|".$tclean."]]|link=".$tkey;
                        $text .= "\n";
		}
                $output = $parser->renderImageGallery( $text, $params );
		if($showstat){
			$output.= "<div>Geräte: ".$count_total;
			$output.= ", mit Bild: ".$count_bild."</div>";
		}
                return $output;
        }
}

