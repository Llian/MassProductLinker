<?php
/**
 * Mass Product Linker
 *
 * Copyright (C) 2012  Llian
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
 * @copyright 2012 Llian (http://www.llian.de). All rights served.
 * @license   http://www.gnu.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */

/**
 * Observer class to add mass actions to the manage products grid
 *
 * @category  Llian
 * @package   Llian_MassProductLinker
 */
class Llian_MassProductLinker_Model_Observer
{

	/**
	 * Adds an item to the drop down menu
	 *
	 * @param mixed		$block 	block object of the dropdown
	 * @param string	$name	internal name for the item
	 * @param string	$label	label for the item
	 **/
	protected function _addLinkItem($block, $name, $label)
	{
		$block->addItem($name, array(
				'label' => $block->__($label),
				'url' => $block->getUrl('adminhtml/*/' . $name)
		));
	}

	/**
	 * Adds an item to the drop down menu with an additional input field
	 *
	 * @param mixed		$block 	block object of the dropdown
	 * @param string	$name	internal name for the item
	 * @param string	$label	label for the item
	 **/
	protected function _addLinkItemWithLinkTo($block, $name, $label)
	{
		$block->addItem($name, array(
				'label' => $block->__($label),
				'url' => $block->getUrl('adminhtml/*/' . $name),
				'additional' => array(
						'visibility' => array(
								'name' => 'link_to',
								'type' => 'text',
								'class' => 'required-entry',
								'label' =>$block->__('Product IDs')
						)
				)
		));
	}

	/**
	 * Checks if a the store config key is set to enabled
	 *
	 * @param string	$key	name of the key
	 * @return boolean
	 *
	 */
	protected function _isEnabled($key)
	{
		return (int)Mage::getStoreConfig('catalog/massproductlinker/' . $key) === 1;
	}

	/**
	 * Adds a divider to the drop down menu
	 *
	 * @param mixed		$block 	block object of the dropdown
	 * @param string	$name	internal name for the divider
	 * @param string	$label	label for the divider
	 */
	protected function _addDivider($block, $name, $label)
	{
		$block->addItem($name, array(
				'label' => '------ ' . $block->__($label) . ' ------',
				'url'   => $block->getUrl('adminhtml/*/index')
		));
	}

	/**
	 * Add mass actions to the drop down menu of the manage products grid in adminhtml
	 *
	 * @param mixed		$observer 		observer
	 */
	public function addMassAction($observer)
	{
		$block = $observer->getEvent()->getBlock();

		if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
				&& $block->getRequest()->getControllerName() == 'catalog_product') {

			if ($this->_isEnabled('cross_sell_to_each_other') or $this->_isEnabled('cross_sell_to') or
					$this->_isEnabled('un_cross_sell_to_each_other') or $this->_isEnabled('un_cross_sell')) {
				$this->_addDivider($block, 'crossSellDivider', 'Cross-Selling');
				if ($this->_isEnabled('cross_sell_to_each_other')) {
					$this->_addLinkItem($block, 'massCrossSellToEachOther', 'Cross-sell to each other');
				}
				if ($this->_isEnabled('cross_sell_to')) {
					$this->_addLinkItemWithLinkTo($block, 'massCrossSellTo', 'Add cross-selling...');
				}
				if ($this->_isEnabled('un_cross_sell_to_each_other')) {
					$this->_addLinkItem($block, 'massUnCrossSellToEachOther', 'Stop cross-selling to each other');
				}
				if ($this->_isEnabled('un_cross_sell')) {
					$this->_addLinkItem($block, 'massUnCrossSell', 'Completely remove cross-selling');
				}
			}

			if ($this->_isEnabled('up_sell_to_each_other') or $this->_isEnabled('up_sell_to') or
					$this->_isEnabled('un_up_sell_to_each_other') or $this->_isEnabled('un_up_sell')) {
				$this->_addDivider($block, 'upSellDivider', 'Up-Selling');
				if ($this->_isEnabled('up_sell_to_each_other')) {
					$this->_addLinkItem($block, 'massUpSellToEachOther', 'Up-sell to each other');
				}
				if ($this->_isEnabled('up_sell_to')) {
					$this->_addLinkItemWithLinkTo($block, 'massUpSellTo', 'Add up-selling...');
				}
				if ($this->_isEnabled('un_up_sell_to_each_other')) {
					$this->_addLinkItem($block, 'massUnUpSellToEachOther', 'Stop up-selling to each other');
				}
				if ($this->_isEnabled('un_up_sell')) {
					$this->_addLinkItem($block, 'massUnUpSell', 'Completely remove up-selling');
				}
			}

			if ($this->_isEnabled('relate_to_each_other') or $this->_isEnabled('relate_to') or
					$this->_isEnabled('un_relate_to_each_other') or $this->_isEnabled('un_relate')) {
				$this->_addDivider($block, 'relateDivider', 'Relate');
				if ($this->_isEnabled('relate_to_each_other')) {
					$this->_addLinkItem($block, 'massRelateToEachOther', 'Relate to each other');
				}
				if ($this->_isEnabled('relate_to')) {
					$this->_addLinkItemWithLinkTo($block, 'massRelateTo', 'Relate to...');
				}
				if ($this->_isEnabled('un_relate_to_each_other')) {
					$this->_addLinkItem($block, 'massUnRelateToEachOther', 'Stop relating to each other');
				}
				if ($this->_isEnabled('un_relate')) {
					$this->_addLinkItem($block, 'massUnRelate', 'Completely remove relating');
				}
			}

		}
	}
}

?>