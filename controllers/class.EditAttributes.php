<?php
/**
 * Implementation of EditAttributes controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for editing the version attributes
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2025 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_EditAttributes extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$document = $this->params['document'];
		$version = $this->params['version'];

		if(false === $this->callHook('preEditAttributes')) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_preEditAttributes_failed';
			return null;
		}

		$result = $this->callHook('editAttributes', $version);
		if($result === null) {
			$attributes = $this->params['attributes'];
			$oldattributes = $version->getAttributes();
			if($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					if($attrdef = $dms->getAttributeDefinition($attrdefid)) {
						if(null === ($ret = $this->callHook('validateAttribute', $attrdef, $attribute))) {
						if($attribute) {
							switch($attrdef->getType()) {
							case SeedDMS_Core_AttributeDefinition::type_date:
								if(is_array($attribute))
									$attribute = array_map(fn($value): string => date('Y-m-d', makeTsFromDate($value)), $attribute);
								else
									$attribute = date('Y-m-d', makeTsFromDate($attribute));
								break;
							case SeedDMS_Core_AttributeDefinition::type_folder:
								if(is_array($attribute))
									$attribute = array_map(fn($value): object => $dms->getFolder((int) $value), $attribute);
								else
									$attribute = $dms->getFolder((int) $attribute);
								break;
							case SeedDMS_Core_AttributeDefinition::type_document:
								if(is_array($attribute))
									$attribute = array_map(fn($value): object => $dms->getDocument((int) $value), $attribute);
								else
									$attribute = $dms->getDocument((int) $attribute);
								break;
							case SeedDMS_Core_AttributeDefinition::type_user:
								if(is_array($attribute))
									$attribute = array_map(fn($value): object => $dms->getUser((int) $value), $attribute);
								else
									$attribute = $dms->getUser((int) $attribute);
								break;
							case SeedDMS_Core_AttributeDefinition::type_group:
								if(is_array($attribute))
									$attribute = array_map(fn($value): object => $dms->getGroup((int) $value), $attribute);
								else
									$attribute = $dms->getGroup((int) $attribute);
								break;
							}
							if(!$attrdef->validate($attribute, $version, false)) {
								$this->errormsg = getAttributeValidationText($attrdef->getValidationError(), $attrdef->getName(), $attribute);
								return false;
							}

							if(!isset($oldattributes[$attrdefid]) || $attribute != $oldattributes[$attrdefid]->getValue()) {
								if(!$version->setAttributeValue($dms->getAttributeDefinition($attrdefid), $attribute)) {
									//UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
									return false;
								}
							}
						} elseif($attrdef->getMinValues() > 0) {
							$this->errormsg = array("attr_min_values", array("attrname"=>$attrdef->getName()));
							return false;
						} elseif(isset($oldattributes[$attrdefid])) {
							if(!$version->removeAttribute($dms->getAttributeDefinition($attrdefid)))
							//	UI::exitError(getMLText("document_title", array("documentname" => $folder->getName())),getMLText("error_occured"));
								return false;
						}
						} else {
							if($ret === false)
								return false;
						}
					}
				}
			}
			foreach($oldattributes as $attrdefid=>$oldattribute) {
				if(!isset($attributes[$attrdefid])) {
					if(!$version->removeAttribute($dms->getAttributeDefinition($attrdefid)))
						return false;
				}
			}
 
		} elseif($result === false) {
			if(empty($this->errormsg))
				$this->errormsg = 'hook_editAttributes_failed';
			return false;
		}

		if(false === $this->callHook('postEditAttributes')) {
		}

		return true;
	}
}
