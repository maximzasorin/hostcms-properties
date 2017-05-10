<?php

/**
 * Тест для класса Core_Entity_Observer_Properties.
 */
class Core_Entity_Observer_PropertiesTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @var Site_Model
	 */
	protected $site;

	/**
	 * Настройка.
	 *
	 * @return void
	 */
	public function setUp()
	{
		// Инициализируем базовые константы для работы HostCMS
		Testing_Bootstrap::defineConstants();

		// Кастомный конфиг для БД
		Testing_Core_Config::setCustomConfig(array(
			'core_database' => array(
				'default' => array (
					'driver' => 'pdo',
					'host' => 'localhost',
					'username' => 'hostcms-history',
					'password' => 'hostcms-history',
					'database' => 'hostcms-history'
				)
			))
		);

		// Инциализируем ядро
		Testing_Core::init();

		// 
		Core_Entity_Observer_Properties::attach();

		// Интернет-магазин
		$oSite = Core_Entity::factory('Site');
		$oSite->name = 'test site';
		$oSite->admin_email = 'test@example.com';
		$oSite->save();

		$this->site = $oSite;
	}

	/**
	 * Сносит ненужные записи.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		$this->site->delete();
	}

	/**
	 * Тестирует тип доп. свойства 
	 *
	 * @dataProvider dataPropertyType
	 *
	 * @param  string  $entityName
	 * @param  string  $propertyType
	 * @param  array  $aValues
	 * @return void
	 */
	public function testPropertyType($entityName, $propertyType, $aValues)
	{
		$oEntity = $this->createEntity($entityName);

		// defset => customget
		$oProperty = $this->createProperty($oEntity, $propertyType);
		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->value = $aValues[0];
		$oPropertyValue->save();

		$actualValue = $oEntity->get($oProperty->tag_name);

		$oProperty->delete();

		$this->assertEquals($aValues[0], $actualValue);


		// defset => customgetAll
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->value = $aValues[0];
		$oPropertyValue->save();

		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->value = $aValues[1];
		$oPropertyValue->save();

		$aActualValue = $oEntity->getAll($oProperty->tag_name);

		$oProperty->delete();

		$this->assertEquals(array($aValues[0], $aValues[1]), $aActualValue);


		// customset => customget
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$oEntity->set($oProperty->tag_name, $aValues[0]);
		$actualValue = $oEntity->get($oProperty->tag_name);

		$this->assertEquals($aValues[0], $actualValue);


		// customset => defget
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$oEntity->set($oProperty->tag_name, $aValues[0]);
		$aoPropertyValues = $oEntity->getPropertyValues(FALSE, array($oProperty->id));

		$this->assertTrue(count($aoPropertyValues) == 1);
		$this->assertEquals($aValues[0], $aoPropertyValues[0]->value);


		// customset => customgetAll
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$oEntity->set($oProperty->tag_name, $aValues[0]);
		$aActualValue = $oEntity->getAll($oProperty->tag_name);

		$this->assertEquals(array($aValues[0]), $aActualValue);
	}

	/**
	 * Возвращает данные для тестов.
	 *
	 * @return array
	 */
	public function dataPropertyType()
	{
		return array(
			array('Shop_Item', 0, array(1337, 1336)),
		);
	}

	/**
	 * Создает сущность для теста.
	 *
	 * @param  string  $entityName
	 * @return Core_Entity|NULL
	 */
	protected function createEntity($entityName)
	{
		switch ($entityName)
		{
			case 'Shop_Item':
				$oShop = Core_Entity::factory('Shop');
				$oShop->name = 'test shop';
				$oShop->email = 'test@example.com';
				$oShop->add($this->site);

				// Создадим товар
				$oShopItem = Core_Entity::factory('Shop_Item');
				$oShopItem->datetime = Core_Date::timestamp2sql(date('now'));
				$oShopItem->start_datetime = Core_Date::timestamp2sql(date('now'));
				$oShopItem->end_datetime = Core_Date::timestamp2sql(date('now'));
				$oShopItem->add($oShop);

				return $oShopItem;
			break;
		}

		return NULL;
	}

	/**
	 * Создает доп. свойство для сущности.
	 *
	 * @param  Core_Entity  $oCoreEntity
	 * @param  integer  $propertyType
	 * @return Property_Model
	 */
	protected function createProperty(Core_Entity $oCoreEntity, $propertyType)
	{
		$modelName = $oCoreEntity->getModelName();

		// Находим родительскую сущность
		$parentName = mb_substr($modelName, 0, mb_stripos($modelName, '_'));
		$oParentEntity = $oCoreEntity->{$parentName};

		// Находим список доп. свойств сущности
		$oModelPropertyList = Core_Entity::factory($modelName . '_Property_List', $oParentEntity->id);

		$propertyName = uniqid();

		$oProperty = Core_Entity::factory('Property');
		$oProperty->name = $propertyName;
		$oProperty->tag_name = $propertyName;
		$oProperty->type = $propertyType;

		$oModelPropertyList->add($oProperty);

		return $oProperty;
	}
}