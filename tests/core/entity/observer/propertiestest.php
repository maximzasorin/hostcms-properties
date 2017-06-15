<?php

/**
 * Тест для класса Core_Entity_Observer_Properties.
 */
class Core_Entity_Observer_PropertiesTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Сайт.
	 *
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
					'username' => 'hostcms',
					'password' => 'hostcms',
					'database' => 'hostcms'
				)
			))
		);

		// Инциализируем ядро
		Testing_Core::init();

		// Прикрпеляем наблюдатель
		Core_Entity_Observer_Properties::attach();

		// Сайт
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
	 * Тестирует доп. свойства базовых типов.
	 *
	 * @dataProvider dataSimpleProperties
	 *
	 * @param  string  $entityName
	 * @param  string  $propertyType
	 * @param  array  $aValues
	 * @return void
	 */
	public function testSimpleProperties($entityName, $propertyType, $aRegularValues, $aCustomValues)
	{
		$oEntity = $this->createEntity($entityName);

		// defset => customget
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->value = $aRegularValues[0];
		$oPropertyValue->save();

		$actualValue = $oEntity->get($oProperty->tag_name);

		$this->assertSame($aCustomValues[0], $actualValue);

		$oProperty->delete();


		// defset => customgetAll
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->value = $aRegularValues[0];
		$oPropertyValue->save();

		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->value = $aRegularValues[1];
		$oPropertyValue->save();

		$aActualValue = $oEntity->getAll($oProperty->tag_name);

		$this->assertSame($aCustomValues, $aActualValue);

		$oProperty->delete();


		// customset => customget
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$oEntity->set($oProperty->tag_name, $aCustomValues[0]);
		$actualValue = $oEntity->get($oProperty->tag_name);

		$this->assertSame($aCustomValues[0], $actualValue);

		$oProperty->delete();


		// customset => defget
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$oEntity->set($oProperty->tag_name, $aCustomValues[0]);
		$aoPropertyValues = $oEntity->getPropertyValues(FALSE, array($oProperty->id));

		$this->assertTrue(count($aoPropertyValues) == 1);
		$this->assertEquals($aRegularValues[0], $aoPropertyValues[0]->value);

		$oProperty->delete();


		// customset => customgetAll
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$oEntity->set($oProperty->tag_name, $aCustomValues[0]);
		$aActualValue = $oEntity->getAll($oProperty->tag_name);

		$this->assertTrue(count($aActualValue) == 1);
		$this->assertSame(array($aCustomValues[0]), $aActualValue);

		$oProperty->delete();
	}

	/**
	 * Данные для теста базовых доп. свойств.
	 *
	 * @return array
	 */
	public function dataSimpleProperties()
	{
		$now = strtotime('now');
		$nowSql = date('Y-m-d H:i:s', $now);

		$next = strtotime('+1 day');
		$nextSql = date('Y-m-d H:i:s', $next);

		return array(
			array('Shop_Item', 0, array(1337, 1336), array(1337, 1336)),
			array('Shop_Item', 1, array('one string', ''), array('one string', '')),
			array('Shop_Item', 4, array('one string', ''), array('one string', '')),
			array('Shop_Item', 6, array('one string', ''), array('one string', '')),
			array('Shop_Item', 7, array(TRUE, FALSE), array(TRUE, FALSE)),
			array('Shop_Item', 8, array($nowSql, $nextSql), array($now, $next)),
			array('Shop_Item', 9, array($nowSql, $nextSql), array($now, $next)),
			array('Shop_Item', 10, array('1337', 'one string'), array('1337', 'one string')),
			array('Shop_Item', 11, array(1337.0, 1336.0), array(1337.0, 1336.0)),
		);
	}

	/**
	 * Тестирует доп. свойства типа Интернет-магазин и Информационная система.
	 *
	 * @dataProvider dataRelationProperties
	 *
	 * @param  string  $entityName
	 * @param  integer  $propertyId
	 * @param  string  $propertyEntityName
	 * @return void
	 */
	public function testRelationProperties($entityName, $propertyId, $propertyEntityName)
	{
		$oEntity = $this->createEntity($entityName);

		$oPropertyEntity = $this->createEntity($propertyEntityName);
		$oPropertyEntity2 = $this->createEntity($propertyEntityName);


		// defset => customget
		$oProperty = $this->createProperty($oEntity, $propertyId);

		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->value = $oPropertyEntity->id;
		$oPropertyValue->save();

		$oActualValue = $oEntity->get($oProperty->tag_name);

		$this->assertEquals($oPropertyEntity->id, $oActualValue->id);

		$oProperty->delete();


		// defset => customgetAll
		$oProperty = $this->createProperty($oEntity, $propertyId);

		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->value = $oPropertyEntity->id;
		$oPropertyValue->save();

		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->value = $oPropertyEntity2->id;
		$oPropertyValue->save();

		$aoActualValues = $oEntity->getAll($oProperty->tag_name);

		$this->assertSame($oPropertyEntity->id, $aoActualValues[0]->id);
		$this->assertSame($oPropertyEntity2->id, $aoActualValues[1]->id);
		
		$oProperty->delete();


		// customset => customget
		$oProperty = $this->createProperty($oEntity, $propertyId);

		$oEntity->set($oProperty->tag_name, $oPropertyEntity);
		$oActualValue = $oEntity->get($oProperty->tag_name);

		$this->assertSame($oPropertyEntity->id, $oActualValue->id);

		$oProperty->delete();


		// customset => defget
		$oProperty = $this->createProperty($oEntity, $propertyId);

		$oEntity->set($oProperty->tag_name, $oPropertyEntity);
		$aoPropertyValues = $oEntity->getPropertyValues(FALSE, array($oProperty->id));

		$this->assertTrue(count($aoPropertyValues) == 1);
		$this->assertEquals($oPropertyEntity->id, $aoPropertyValues[0]->value);

		$oProperty->delete();


		// customset => customgetAll
		$oProperty = $this->createProperty($oEntity, $propertyId);

		$oEntity->set($oProperty->tag_name, $oPropertyEntity);
		$aoActualValues = $oEntity->getAll($oProperty->tag_name);

		$this->assertTrue(count($aoActualValues) == 1);
		$this->assertEquals($oPropertyEntity->id, $aoActualValues[0]->id);

		$oProperty->delete();


		$oPropertyEntity->delete();
		$oPropertyEntity2->delete();
	}

	/**
	 * Данные для теста доп. свойств типа Интернет-магазин и Информационная система.
	 *
	 * @return array
	 */
	public function dataRelationProperties()
	{
		return array(
			array('Shop_Item', 5, 'Informationsystem_Item'),
			array('Shop_Item', 12, 'Shop_Item'),
		);
	}

	/**
	 * Тестирует файловое доп. свойство.
	 *
	 * @dataProvider dataFileProperty
	 *
	 * @param  string  $entityName
	 * @return void
	 */
	public function testFileProperty($entityName, $hrefMethod)
	{
		$oEntity = $this->createEntity($entityName);
		$propertyType = 2;

		// defset => customget
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$filename = strtolower($entityName) . '_property.png';

		$oPropertyValue = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue->file = $filename;
		$oPropertyValue->file_small = 'small_' . $filename;
		$oPropertyValue->save();
		$oPropertyValue->setHref($oEntity->{$hrefMethod}());

		$actualValue = $oEntity->get($oProperty->tag_name);

		$expectedValue = new StdClass;
		$expectedValue->file = $oPropertyValue->getLargeFileHref();
		$expectedValue->file_small = $oPropertyValue->getSmallFileHref();

		$this->assertSame($expectedValue->file, $actualValue->file);
		$this->assertSame($expectedValue->file_small, $actualValue->file_small);

		$oProperty->delete();

		// defset => customgetAll
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$filename = strtolower($entityName) . '_property.png';
		$filename2 = strtolower($entityName) . '_property2.png';

		$oPropertyValue1 = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue1->file = $filename;
		$oPropertyValue1->file_small = 'small_' . $filename;
		$oPropertyValue1->save();
		$oPropertyValue1->setHref($oEntity->{$hrefMethod}());

		$oPropertyValue2 = $oProperty->createNewValue($oEntity->id);
		$oPropertyValue2->file = $filename2;
		$oPropertyValue2->file_small = 'small_' . $filename2;
		$oPropertyValue2->save();
		$oPropertyValue2->setHref($oEntity->{$hrefMethod}());

		$aActualValue = $oEntity->getAll($oProperty->tag_name);

		$aExpectedValue[0] = new StdClass;
		$aExpectedValue[0]->file = $oPropertyValue1->getLargeFileHref();
		$aExpectedValue[0]->file_small = $oPropertyValue1->getSmallFileHref();

		$aExpectedValue[1] = new StdClass;
		$aExpectedValue[1]->file = $oPropertyValue2->getLargeFileHref();
		$aExpectedValue[1]->file_small = $oPropertyValue2->getSmallFileHref();

		$this->assertSame($aExpectedValue[0]->file, $aActualValue[0]->file);
		$this->assertSame($aExpectedValue[0]->file_small, $aActualValue[0]->file_small);
		$this->assertSame($aExpectedValue[1]->file, $aActualValue[1]->file);
		$this->assertSame($aExpectedValue[1]->file_small, $aActualValue[1]->file_small);

		$oProperty->delete();


		// customset => customget
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$aCustomValues = array(
			array(
				'name' => 'test_file.png',
				'type' => 'image/png',
				'size' => filesize(__DIR__ . '/test_file.png'),
				'tmp_name' => __DIR__ . '/test_file.png',
				'error' => UPLOAD_ERR_OK,
			),
			array(),
		);

		$oEntity->set($oProperty->tag_name, $aCustomValues[0]);
		$actualValue = $oEntity->get($oProperty->tag_name);

		$this->assertSame(preg_match('/\.png$/', $actualValue->file), 1);
		$this->assertSame(preg_match('/\.png$/', $actualValue->file_small), 1);

		$oProperty->delete();


		// customset => defget
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$aCustomValues = array(
			array(
				'name' => 'test_file.png',
				'type' => 'image/png',
				'size' => filesize(__DIR__ . '/test_file.png'),
				'tmp_name' => __DIR__ . '/test_file.png',
				'error' => UPLOAD_ERR_OK,
			),
			array(),
		);

		$oEntity->set($oProperty->tag_name, $aCustomValues[0]);
		$aoPropertyValues = $oEntity->getPropertyValues(FALSE, array($oProperty->id));

		$this->assertTrue(count($aoPropertyValues) == 1);
		$this->assertSame(preg_match('/\.png$/', $aoPropertyValues[0]->getLargeFileHref()), 1);
		$this->assertSame(preg_match('/\.png$/', $aoPropertyValues[0]->getSmallFileHref()), 1);

		$oProperty->delete();


		// customset => customgetAll
		$oProperty = $this->createProperty($oEntity, $propertyType);

		$aCustomValues = array(
			array(
				'name' => 'test_file.png',
				'type' => 'image/png',
				'size' => filesize(__DIR__ . '/test_file.png'),
				'tmp_name' => __DIR__ . '/test_file.png',
				'error' => UPLOAD_ERR_OK,
			),
			array(),
		);

		$oEntity->set($oProperty->tag_name, $aCustomValues[0]);

		$aActualValue = $oEntity->getAll($oProperty->tag_name);

		$this->assertTrue(count($aActualValue) == 1);
		$this->assertSame(preg_match('/\.png$/', $aActualValue[0]->file), 1);
		$this->assertSame(preg_match('/\.png$/', $aActualValue[0]->file_small), 1);
	}

	/**
	 * Данные для теста файлового доп. свойства.
	 *
	 * @return array
	 */
	public function dataFileProperty()
	{
		return array(
			array('Shop_Item', 'getItemHref'),
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
			// Товар
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

			// Инфоэлемент
			case 'Informationsystem_Item':
				$oInformationsystem = Core_Entity::factory('Informationsystem');
				$oInformationsystem->name = 'test informationsystem';
				$oInformationsystem->add($this->site);

				// Создадим информационный элемент
				$oInformationsystemItem = Core_Entity::factory('Informationsystem_Item');
				$oInformationsystemItem->datetime = Core_Date::timestamp2sql(date('now'));
				$oInformationsystemItem->start_datetime = Core_Date::timestamp2sql(date('now'));
				$oInformationsystemItem->end_datetime = Core_Date::timestamp2sql(date('now'));
				$oInformationsystemItem->add($oInformationsystem);

				return $oInformationsystemItem;
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