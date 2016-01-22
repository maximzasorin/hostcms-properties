<?php

/*

// Наблюдатели для работы с доп. свойствами
Core_Event::attach('shop_item.onCallget', array('Shop_Item_PropertiesObserver', 'onCallget'));
Core_Event::attach('shop_item.onCallgetAll', array('Shop_Item_PropertiesObserver', 'onCallgetAll'));
Core_Event::attach('shop_item.onCallset', array('Shop_Item_PropertiesObserver', 'onCallset'));
Core_Event::attach('shop_item.onCallhas', array('Shop_Item_PropertiesObserver', 'onCallhas'));

*/

class Shop_Item_PropertiesObserver
{
	/**
	 * Возвращает первое найденное значение дополнительного свойства
	 * 
	 * Настоящий прототип:
	 * public function get($identifier, $default = null, $bCache = true)
	 * 
	 * Пример:
	 * if ($oShop_Item->get('alternative') == 'expected value')
	 * {
	 *		$aTitle[] = $oShop_Item->get('alternative');
	 * }
	 *
	 * @param Shop_Item_Model $object
	 * @param array $args
	 * @return mixed
	 */
	static public function onCallget(Shop_Item_Model $object, $args)
	{
		if (count($args) == 0)
		{
			throw new InvalidArgumentException('At least 1 argument will be transfered.');
		}

		$identifier = Core_Array::get($args, 0);
		$default = Core_Array::get($args, 1, null);
		$bCache = Core_Array::get($args, 2, true);

		if (is_int($identifier))
		{
			$oProperty = Core_Entity::factory('Property', $identifier);
		}
		else
		{
			$shop_id = $object->Shop->id;
			$oShop_Item_Property_List = Core_Entity::factory('Shop_Item_Property_List', $shop_id);	
			$oProperties = $oShop_Item_Property_List->Properties;
			$oProperties->queryBuilder()
				->where('properties.tag_name', '=', $identifier)
				->where('properties.deleted', '=', 0)
				->orderBy('properties.sorting', 'ASC');
			$aoProperties = $oProperties->findAll();

			if (count($aoProperties) == 0)
			{
				return $default;
			}

			$oProperty = $aoProperties[0];
		}

		$aoProperty_Values = $object->getPropertyValues($bCache, array($oProperty->id));

		if (count($aoProperty_Values) != 0)
		{
			$oProperty_Value = $aoProperty_Values[0];

			switch (get_class($oProperty_Value))
			{
				case 'Property_Value_Int_Model':
					if ($oProperty->type == 3 && Core::moduleIsActive('list'))
					{
						if ($oProperty_Value->value != 0)
						{
							$oList_Item = $oProperty_Value->List_Item;

							if ($oList_Item->id)
							{
								return $oList_Item->value;
							}
						}
					}
					return $oProperty_Value->value;
				break;
				case 'Property_Value_Datetime_Model':
				case 'Property_Value_Float_Model':
				case 'Property_Value_String_Model':
				case 'Property_Value_Text_Model':
					return $oProperty_Value->value;
				break;

				case 'Property_Value_File_Model':
					return $oProperty_Value->getLargeFilePath();
				break;
			}
		}
		
		return $default;
	}

	/**
	 * Возвращает все значения дополнительного свойства
	 * 
	 * Настоящий прототип:
	 * public function getAll($identifier, $bCache)
	 *
	 * Пример:
	 * $aImages = $oShop_Item->getAll('image');
	 * foreach ($aImages as $image)
	 * {
	 *		print $image; // output: /var/www/example.com/www/upload/image.jpg
	 * }
	 * 
	 * @param Shop_Item_Model $object
	 * @param array $object
	 * @return mixed
	 */
	static public function onCallgetAll(Shop_Item_Model $object, $args)
	{

	}

	/**
	 * Устанавливает значение дополнительного свойства
	 * 
	 * Настоящий прототип:
	 * public function set($identifier, $value)
	 *
	 * Пример:
	 * $oShop_Item->set('color', 'Красный');
	 * 
	 * @param Shop_Item_Model $object
	 * @param array $args
	 * @return mixed
	 */
	static public function onCallset(Shop_Item_Model $object, $args)
	{

	}

	/**
	 * Проверяет заполнение дополнительного свойства свойства
	 * 
	 * Пример:
	 * 
	 * if ($oShop_Item->has('mega-shop-item'))
	 * {
	 *		// ...
	 * }
	 *
	 * @param Shop_Item_Model $object
	 * @param array $args
	 * @return boolean
	 */
	static public function onCallhas(Shop_Item_Model $object, $args)
	{

	}
}