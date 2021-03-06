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
		'shop_item'
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

		switch ($oProperty->type)
		{
			// Файл
			case 2:
				if (!isset($value['tmp_name'])) {
					break;
				}

				$oPropertyValue->save();

				$modelName = $oCoreEntity->getModelName();
				$modelType = mb_substr($modelName, mb_stripos($modelName, '_') + 1);

				$entityPath = $oCoreEntity->{"get{$modelType}Path"}();
				$propertyFile = $modelName . '_property_file_' .
					$oCoreEntity->id . '_' . $oPropertyValue->id .
					'.' . Core_File::getExtension($value['tmp_name']);

				$aParams = array();
				$aParams['large_image_source'] = $value['tmp_name'];
				$aParams['small_image_source'] = '';
				$aParams['large_image_name'] = $value['name'];
				$aParams['small_image_name'] = $value['name'];
				$aParams['large_image_target'] = $entityPath . $propertyFile;
				$aParams['small_image_target'] = $entityPath . 'small_' . $propertyFile;

				$aParams['create_small_image_from_large'] = TRUE;

				$aParams['large_image_max_width'] = $oProperty->image_large_max_width;
				$aParams['large_image_max_height'] = $oProperty->image_large_max_height;
				$aParams['small_image_max_width'] = $oProperty->image_small_max_width;
				$aParams['small_image_max_height'] = $oProperty->image_small_max_height;
				// $aParams['watermark_file_path'] = $oProperty->watermark_file_path;
				// $aParams['watermark_position_x'] = $oProperty->watermark_position_x;
				// $aParams['watermark_position_y'] = $oProperty->watermark_position_y;
				// $aParams['large_image_watermark'] = $oProperty->large_image_watermark;
				// $aParams['small_image_watermark'] = $oProperty->small_image_watermark;
				// $aParams['large_image_isset'] = '';
				// $aParams['large_image_preserve_aspect_ratio'] = '';
				// $aParams['small_image_preserve_aspect_ratio'] = '';

				$aResult = Core_File::adminUpload($aParams);

				$oPropertyValue->file = $aResult['large_image'];
				$oPropertyValue->file_small = $aResult['small_image'];

				if ($aResult['large_image'])
				{
					$oPropertyValue->file = $propertyFile;
				}

				if ($aResult['small_image'])
				{
					$oPropertyValue->file_small = 'small_' . $propertyFile;
				}
			break;

			// Инфоэлемент, товар
			case 5:
			case 12:
				$oPropertyValue->value = $value->id;
			break;

			// Дата, дата-время
			case 8:
			case 9:
				$oPropertyValue->value = Core_Date::timestamp2sql($value);
			break;

			default:
				$oPropertyValue->value = $value;
			break;
		}

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
			$oProperty = $oPropertyValue->Property;

			switch ($oProperty->type)
			{
				// Целое число
				case 0:
					$aValues[] = intval($oPropertyValue->value);
				break;

				// Строка, большое текстовое поле, виз. редактор
				case 1:
				case 4:
				case 6:
					$aValues[] = strval($oPropertyValue->value);
				break;

				// Файл
				case 2:
					$oFileObject = new StdClass;
					$oFileObject->file = $oPropertyValue->getLargeFileHref();
					$oFileObject->file_small = $oPropertyValue->getSmallFileHref();
					// $oFileObject->original = $oPropertyValue;

					$aValues[] = $oFileObject;
				break;

				// Список
				case 3:
					$aValues[] = NULL;

					// if (Core::moduleIsActive('list'))
					// {
					// 	$aValues[] = Core_Entity::factory('List_Item')->getById($oProperty->value);
					// }
				break;

				// Чекбокс
				case 7:
					$aValues[] = boolval($oPropertyValue->value);
				break;

				// Дата, дата-время
				case 8:
				case 9:
					$aValues[] = Core_Date::sql2timestamp($oPropertyValue->value);
				break;

				// Скрытое поле
				case 10:
					$aValues[] = $oPropertyValue->value;
				break;

				// Число с плавающей запятой
				case 11:
					$aValues[] = floatval($oPropertyValue->value);
				break;

				// Информационная система
				case 5:
					$aValues[] = Core_Entity::factory('Informationsystem_Item')->getById($oPropertyValue->value);
				break;

				// Товар
				case 12:
					$aValues[] = Core_Entity::factory('Shop_Item')->getById($oPropertyValue->value);
				break;

				default:
					$aValues[] = NULL;
				break;
			}
		}

		return $aValues;
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