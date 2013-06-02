<?php
/**
 * Mass Product Linker
 *
 * Copyright (C) 2013  Llian
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the 
 * GNU General Public License as published by the Free Software Foundation, either version 3 of the 
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.  If not, 
 * see <http://www.gnu.org/licenses/gpl.html>.
 *
 * @category  Llian
 * @package   Llian_MassProductLinker
 * @author    Llian <info@llian.de>
 * @copyright 2013 Llian (http://www.llian.de). All rights served.
 * @license   http://www.gnu.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */

/**
 * Adminhtml controller to perform a number of mass actions in the manage products grid
 *
 * @category  Llian
 * @package   Llian_MassProductLinker
 */
class Llian_MassProductLinker_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController
{

	const LINK_TYPE_RELATED     = 1;
	const LINK_TYPE_UPSELL      = 4;
	const LINK_TYPE_CROSSSELL   = 5;

	protected function _construct()
	{
		parent::_construct();
	}

	/**
	 * Completely removes links of one kind (cross-sell, up-sell, or related) from the selected products.
	 *
	 * @param int 		$linkType 		specifies what type of links to removed (one of LINK_TYPE_)
	 * @param string 	$successString	success message that is displayed if the operation succeeds
	 */
	protected function _unlinkProducts($linkType, $successString)
	{
		$prodIDs = $this->getRequest()->getParam('product');
		if (!is_array($prodIDs) or (count($prodIDs) < 1)) {
			$this->_getSession()->addError($this->__('Please select at least one product.'));
		}
		else {
			try {
				foreach ($prodIDs as $prodID) {
					$product = Mage::getModel('catalog/product')->load($prodID);
					$this->_setLinkData($product, array(), $linkType);
					$product->save();
				}
				$this->_getSession()->addSuccess(
						$this->__($successString, count($prodIDs))
				);

			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}

	/**
	 * Removes links of one kind (cross-sell, up-sell, or related) between the selected products.
	 *
	 * @param int 		$linkType 		specifies what type of links to removed
	 * @param string 	$successString	success message that is displayed if the operation succeeds
	 */
	protected function _unlinkProductsToEachOther($linkType, $successString)
	{
		$prodIDs = $this->getRequest()->getParam('product');
		if (!is_array($prodIDs) or (count($prodIDs) < 2)) {
			$this->_getSession()->addError($this->__('Please select at least two products.'));
		}
		elseif (count($prodIDs) > 50) {
			$this->_getSession()->addError($this->__('Too many products selected. Please select 50 or less.'));
		}
		else {
			try {
				$removedLinkCount = 0;
				foreach ($prodIDs as $prodID) {
					$product = Mage::getModel('catalog/product')->load($prodID);
					$links = $this->_getLinkData($product, $linkType);
					foreach($prodIDs as $currentID) {
						if(array_key_exists($currentID, $links)) {
							unset($links[$currentID]);
							$removedLinkCount++;
						}
					}
					$this->_setLinkData($product, $links, $linkType);
					$product->save();
				}
				$this->_getSession()->addSuccess(
						$this->__($successString, count($prodIDs), $removedLinkCount)
				);

			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}

	/**
	 * Links selected products to each other using cross-sells, up-sells, or related products.
	 *
	 * @param int 		$linkType 		specifies what type of links to add
	 * @param string 	$successString	success message that is displayed if the operation succeeds
	 */
	protected function _linkProductsToEachOther($linkType, $successString)
	{
		$prodIDs = $this->getRequest()->getParam('product');
		if (!is_array($prodIDs) or (count($prodIDs) < 2)) {
			$this->_getSession()->addError($this->__('Please select at least two products.'));
		}
		elseif (count($prodIDs) > 50) {
			$this->_getSession()->addError($this->__('Too many products selected. Please select 50 or less.'));
		}
		else {
			try {
				$newLinkCount = 0;
				foreach ($prodIDs as $prodID) {
					$product = Mage::getModel('catalog/product')->load($prodID);
					$links = $this->_getLinkData($product, $linkType);
					foreach($prodIDs as $newLinkID) {
						if(($newLinkID != $prodID) and !array_key_exists($newLinkID, $links)) {
							$links[$newLinkID] = array('position' => null);
							$newLinkCount++;
						}
					}
					$this->_setLinkData($product, $links, $linkType);
					$product->save();
				}
				$existingLinkCount = count($prodIDs) * (count($prodIDs) - 1) - $newLinkCount;
				$this->_getSession()->addSuccess(
						$this->__($successString, count($prodIDs), $newLinkCount, $existingLinkCount)
				);
			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}

	/**
	 * Links selected products to other specified products using cross-sells, up-sells, or related products.
	 *
	 * @param int 		$linkType 		specifies what type of links to add
	 * @param string 	$successString	success message that is displayed if the operation succeeds
	 */
	protected function _linkProductsTo($linkType, $successString)
	{
		$prodIDs = $this->getRequest()->getParam('product');
		$linkToStr = $this->getRequest()->getParam('link_to');
		$linkToIDs = array();
		foreach (filter_var_array(explode(',', $linkToStr), FILTER_VALIDATE_INT) as $rawID) {
			if ($rawID) {
				$linkToIDs[] = $rawID;
			}
		}
		$collection = Mage::getResourceModel('catalog/product_collection')
		->addFieldToFilter('entity_id',array('in'=>$linkToIDs));
		$linkToIDs = array_values($collection->getAllIds());
		if (!is_array($prodIDs) or (count($prodIDs) < 1)) {
			$this->_getSession()->addError($this->__('Please select at least one product.'));
		}
		if (count($linkToIDs) < 1) {
			$this->_getSession()->addError($this->__('Please enter at least one product to link to. Enter product IDs (not SKUs) delimited by commas.'));
		}
		else {
			try {
				$newLinkCount = 0;
				foreach ($prodIDs as $prodID) {
					$product = Mage::getModel('catalog/product')->load($prodID);
					$links = $this->_getLinkData($product, $linkType);
					foreach($linkToIDs as $linkToID) {
						if(($linkToID != $prodID) and !array_key_exists($linkToID, $links)) {
							$links[$linkToID] = array('position' => null);
							$newLinkCount++;
						}
					}
					$this->_setLinkData($product, $links, $linkType);
					$product->save();
				}
				$existingLinkCount = count($prodIDs) * count($linkToIDs) - $newLinkCount;
				$this->_getSession()->addSuccess(
						$this->__($successString, count($prodIDs), count($linkToIDs), $newLinkCount, $existingLinkCount)
				);
			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}

	/**
	 * Returns the current link data for one kind of links for a given product.
	 *
	 * @param Mage_Catalog_Model_Product $product product
	 * @param int 		$linkType 		specifies what type of links to add
	 * @return mixed[]
	 */
	protected function _getLinkData($product, $linkType)
	{
		$data = array();
		if ($linkType == self::LINK_TYPE_RELATED)
			$product->getLinkInstance()->useRelatedLinks();
		elseif ($linkType == self::LINK_TYPE_UPSELL)
		$product->getLinkInstance()->useUpSellLinks();
		elseif ($linkType == self::LINK_TYPE_CROSSSELL)
		$product->getLinkInstance()->useCrossSellLinks();
		$attributes = array();
		foreach ($product->getLinkInstance()->getAttributes() as $_attribute) {
			if (isset($_attribute['code'])) {
				$attributes[] = $_attribute['code'];
			}
		}
		$collection = null;
		if ($linkType == self::LINK_TYPE_RELATED)
			$collection = $product->getRelatedLinkCollection();
		elseif ($linkType == self::LINK_TYPE_UPSELL)
		$collection = $product->getUpSellLinkCollection();
		elseif ($linkType == self::LINK_TYPE_CROSSSELL)
		$collection = $product->getCrossSellLinkCollection();
		foreach ($collection as $_link) {
			$data[$_link->getLinkedProductId()] = $_link->toArray($attributes);
		}
		return $data;
	}

	/**
	 * Sets the link data for one kind of links for a given product.
	 *
	 * @param Mage_Catalog_Model_Product $product product
	 * @param mixed[] 	$data 			link data
	 * @param int 		$linkType 		specifies what type of links to add
	 */
	protected function _setLinkData($product, $data, $linkType)
	{
		if ($linkType == self::LINK_TYPE_RELATED)
			$product->setRelatedLinkData($data);
		elseif ($linkType == self::LINK_TYPE_UPSELL)
		$product->setUpSellLinkData($data);
		elseif ($linkType == self::LINK_TYPE_CROSSSELL)
		$product->setCrossSellLinkData($data);
	}

	/**
	 * Remove all cross-selling links from selected products.
	 */
	public function massUnCrossSellAction()
	{
		$this->_unlinkProducts(self::LINK_TYPE_CROSSSELL, 'Successfully removed cross-selling links from %d products.');
	}

	/**
	 * Remove cross-selling links to each other from selected products.
	 */
	public function massUnCrossSellToEachOtherAction()
	{
		$this->_unlinkProductsToEachOther(self::LINK_TYPE_CROSSSELL, 'Successfully removed cross-selling links to each other from %d products (total: %d removed links).');
	}

	/**
	 * Add cross-selling links to other products for selected products.
	 */
	public function massCrossSellToEachOtherAction()
	{
		$this->_linkProductsToEachOther(self::LINK_TYPE_CROSSSELL, 'Successfully linked %d products for cross-selling to each other (%d new links, %d already existed).');
	}

	/**
	 * Un-relate selected products to each other.
	 */
	public function massCrossSellToAction()
	{
		$this->_linkProductsTo(self::LINK_TYPE_CROSSSELL, 'Successfully linked %d product(s) for cross-selling to %d other product(s) (%d new links, %d already existed).');
	}

	/**
	 * Remove all up-selling links from selected products.
	 */
	public function massUnUpSellAction()
	{
		$this->_unlinkProducts(self::LINK_TYPE_UPSELL, 'Successfully removed up-selling links from %d products.');
	}

	/**
	 * Remove up-selling links to each other from selected products.
	 */
	public function massUnUpSellToEachOtherAction()
	{
		$this->_unlinkProductsToEachOther(self::LINK_TYPE_UPSELL, 'Successfully removed up-selling links to each other from %d products (total: %d removed links).');
	}

	/**
	 * Add up-selling links to each other for selected products.
	 */
	public function massUpSellToEachOtherAction()
	{
		$this->_linkProductsToEachOther(self::LINK_TYPE_UPSELL, 'Successfully linked %d products for up-selling to each other (%d new links, %d already existed).');
	}

	/**
	 * Add up-selling links to other products for selected products.
	 */
	public function massUpSellToAction()
	{
		$this->_linkProductsTo(self::LINK_TYPE_UPSELL, 'Successfully linked %d product(s) for up-selling to %d other product(s) (%d new links, %d already existed).');
	}

	/**
	 * Remove all links to related products from selected products.
	 */
	public function massUnRelateAction()
	{
		$this->_unlinkProducts(self::LINK_TYPE_RELATED, 'Successfully removed links to related products from %d products.');
	}

	/**
	 * Un-relate selected products to each other.
	 */
	public function massUnRelateToEachOtherAction()
	{
		$this->_unlinkProductsToEachOther(self::LINK_TYPE_RELATED, 'Successfully un-related %d products to each other (total: %d removed links).');
	}

	/**
	 * Relate selected products to each other.
	 */
	public function massRelateToEachOtherAction()
	{
		$this->_linkProductsToEachOther(self::LINK_TYPE_RELATED, 'Successfully related %d products to each other (%d new links, %d already existed).');
	}

	/**
	 * Relate selected products to other products.
	 */
	public function massRelateToAction() {
		$this->_linkProductsTo(self::LINK_TYPE_RELATED, 'Successfully related %d product(s) to %d other product(s) (%d new links, %d already existed).');
	}

}

?>