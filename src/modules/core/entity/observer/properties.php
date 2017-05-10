<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Наблюдатель для упрощенной работы с доп. свойствами.
 */
class Core_Entity_Observer_Properties
{
	/**
	 * Сущности, с которыми работает наблюдатель.
	 *
	 * @var array
	 */
	static protected $_aEntities = array(
		'shop_item',
		'shop_group',
	);

	/**
	 * Регистрирует обработчики событий.
	 *
	 * @return void
	 */
	static public function attach()
	{
		foreach (self::$_aEntities as $entity)
		{
			Core_Event::attach($entity . '.onCallget', array(__CLASS__, 'onCallget'));
			Core_Event::attach($entity . '.onCallgetAll', array(__CLASS__, 'onCallgetAll'));
			Core_Event::attach($entity . '.onCallset', array(__CLASS__, 'onCallset'));
		}
	}

	/**
	 * Возвращает значение доп. свойства сущности.
	 *
	 * $time = $oShopItem->get('time', '10:00', TRUE); // 1
	 *
	 * @param  Core_Entity  $oCoreEntity
	 * @param  array  $aArgs
	 * @return mixed
	 */
	static public function onCallget(Core_Entity $oCoreEntity, array $aArgs)
	{
		list($propertyTag, $defaultValue, $bCache) = self::_parseArgs($aArgs, array('', NULL, FALSE));

		$aValues = self::_getValues($oCoreEntity, $propertyTag, $bCache);

		return count($aValues)
			? $aValues[0]
			: $defaultValue;
	}

	/**
	 * Возвращает все значения доп. свойств сущности.
	 *
	 * $aTimes = $oShopItem->getAll('time'); // array(1, 2, 3)
	 *
	 * @param  Core_Entity  $oCoreEntity
	 * @param  array  $aArgs
	 * @return array
	 */
	static public function onCallgetAll(Core_Entity $oCoreEntity, array $aArgs)
	{
		list($propertyTag, $bCache) = self::_parseArgs($aArgs, array('', FALSE));

		return self::_getValues($oCoreEntity, $propertyTag, $bCache);
	}

	/**
	 * Устанавливает значение доп. свойства для сущности.
	 *
	 * $oShopItem->set('time', '12:00'); // self
	 *
	 * @param  Core_Entity  $oCoreEntity
	 * @param  array  $aArgs
	 * @return array
	 */
	static public function onCallset(Core_Entity $oCoreEntity, array $aArgs)
	{
		list($propertyTag, $value) = self::_parseArgs($aArgs, array('', NULL));

		$aPropertyIds = self::_getPropertyIds($oCoreEntity, $propertyTag);

		if (!count($aPropertyIds))
		{
			return;
		}

		$oProperty = Core_Entity::factory('Property', $aPropertyIds[0]);
		$aoPropertyValues = $oCoreEntity->getPropertyValues(FALSE, array($aPropertyIds[0]));

		$oPropertyValue = count($aoPropertyValues)
			? $aoPropertyValues[0]
			: $oProperty->createNewValue($oCoreEntity->id);

		$oPropertyValue->value = $value;
		$oPropertyValue->save();

		return $oCoreEntity;
	}

	/**
	 * Возвращает значения доп. свойства сущности.
	 *
	 * @param  Core_Entity  $oCoreEntity
	 * @param  string  $propertyTag
	 * @return array
	 */
	static protected function _getValues(Core_Entity $oCoreEntity, $propertyTag, $bCache)
	{
		$aPropertyIds = self::_getPropertyIds($oCoreEntity, $propertyTag);

		if (!count($aPropertyIds))
		{
			return array();
		}

		// Находим все значения доп. свойств
		$aoPropertyValues = $oCoreEntity->getPropertyValues($bCache, $aPropertyIds);

		usort($aoPropertyValues, function ($a, $b) {
			return $a->id >= $b->id ? 1 : -1;
		});

		// Составляем массив из значений
		$aValues = array();

		foreach ($aoPropertyValues as $oPropertyValue)
		{
			$aValues[] = self::_getPropertyValue($oPropertyValue);
		}

		return $aValues;
	}

	/**
	 * Возвращает значение доп. свойства.
	 *
	 * @param  Core_Entity  $oPropertyValue
	 * @return mixed
	 */
	static protected function _getPropertyValue(Core_Entity $oPropertyValue)
	{
		switch (get_class($oPropertyValue))
		{
			case 'Property_Value_Int_Model':
				if (Core::moduleIsActive('list') && $oPropertyValue->Property->type == 3)
				{
					if ($oPropertyValue->value != 0)
					{
						$oListItem = $oPropertyValue->List_Item;

						if ($oListItem->id)
						{
							return $oListItem->value;
						}
					}
				}

				return intval($oPropertyValue->value);
			break;

			case 'Property_Value_Datetime_Model':
			case 'Property_Value_Float_Model':
			case 'Property_Value_String_Model':
			case 'Property_Value_Text_Model':
				return $oPropertyValue->value;
			break;

			case 'Property_Value_File_Model':
				return array(
					'large' => $oPropertyValue->image_large,
					'small' =>$oPropertyValue->image_small
				);
			break;
		}

		return NULL;
	}

	/**
	 * Возвращает массив идентификторов доп. свойств сущности с заданным тег-неймом.
	 *
	 * @param  Core_Entity  $oCoreEntity
	 * @param  string  $propertyTag
	 * @return array
	 */
	static protected function _getPropertyIds(Core_Entity $oCoreEntity, $propertyTag)
	{
		$modelName = $oCoreEntity->getModelName();

		// Находим родительскую сущность
		$parentName = mb_substr($modelName, 0, mb_stripos($modelName, '_'));
		$oParentEntity = $oCoreEntity->{$parentName};

		// Находим список доп. свойств сущности
		$oModelPropertyList = Core_Entity::factory($modelName . '_Property_List', $oParentEntity->id);
		$oModelProperties = $oModelPropertyList->Properties;

		// Находим нужные доп. свойства
		$oModelProperties->queryBuilder()
			->where('properties.tag_name', '=', $propertyTag);

		$aoProperties = $oModelProperties->findAll();
		$aPropertyIds = array();

		foreach ($aoProperties as $oProperty)
		{
			$aPropertyIds[] = $oProperty->id;
		}

		$aPropertyIds = array_unique($aPropertyIds);

		return $aPropertyIds;
	}

	/**
	 * Возвращает значения аргументов, объединяя их со значениями по умолчанию.
	 *
	 * @param  array  $aArgs
	 * @param  array  $aDefaultValues
	 * @return void
	 */
	static protected function _parseArgs($aArgs, $aDefaultValues)
	{
		$aValues = array();

		foreach ($aDefaultValues as $index => $defaultValue)
		{
			$aValues[] = Core_Array::get($aArgs, $index)
				? Core_Array::get($aArgs, $index)
				: $defaultValue;
		}

		return $aValues;
	}
}