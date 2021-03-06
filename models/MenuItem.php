<?php

namespace bttree\smymenu\models;

use Yii;
use \yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\Request;

/**
 * This is the model class for table "menu_item".
 *
 * @property integer      $id
 * @property string       $title
 * @property string       $url
 * @property string       $options
 * @property string       $before_label
 * @property string       $after_label
 * @property string       $template
 * @property integer      $sort
 * @property integer      $parent_id
 * @property integer      $status
 * @property string       $submenuTemplate
 *
 * @property MenuItemRole[] $roles
 */
class MenuItem extends ActiveRecord
{
    const STATUS_DISABLED = 0;
    const STATUS_HIDDEN   = 1;
    const STATUS_ACTIVE   = 2;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'menu_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'url', 'menu_id'], 'required'],
            [['sort', 'parent_id', 'status'], 'integer'],
            [['title', 'url', 'options', 'before_label', 'after_label', 'template', 'submenuTemplate'], 'string', 'max' => 255],
//            [
//                ['id'],
//                'exist',
//                'skipOnError'     => true,
//                'targetClass'     => MenuItemRole::className(),
//                'targetAttribute' => ['id' => 'menu_item_id']
//            ],
        ];
    }
    public function behaviors()
    {
        return [
            'sort' => [
                'class' => '\bttree\smymenu\behaviors\Sort',
                'attributeName' => 'sort'
            ]
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'              => Yii::t('smy.menu', 'ID'),
            'title'           => Yii::t('smy.menu', 'Title'),
            'url'             => Yii::t('smy.menu', 'Url'),
            'options'         => Yii::t('smy.menu', 'Options'),
            'before_label'    => Yii::t('smy.menu', 'Before Label'),
            'after_label'     => Yii::t('smy.menu', 'After Label'),
            'sort'            => Yii::t('smy.menu', 'Sort'),
            'parent_id'       => Yii::t('smy.menu', 'Parent'),
            'menu_id'         => Yii::t('smy.menu', 'Menu'),
            'status'          => Yii::t('smy.menu', 'Status'),
            'template'        => Yii::t('smy.menu', 'Template'),
            'submenuTemplate' => Yii::t('smy.menu', 'Submenu Template'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenu()
    {
        return $this->hasOne(Menu::className(), ['id' => 'menu_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasMany(MenuItemRole::className(), ['menu_item_id' => 'id']);
    }

    /**
     * @param integer|null $id
     * @return array
     */
    public static function getAllArrayForSelect($id = null)
    {
        $query = self::find();
        if (!is_null($id)) {
            $query->where(['!=', 'id', $id]);
        }

        return ArrayHelper::map($query->where(['!=', 'status', self::STATUS_DISABLED])->orderBy('id')->asArray()->all(), 'id', 'title');
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (empty($this->status)) {
            $this->status = self::STATUS_DISABLED;
        }
        //todo сделать более гибко!

        $before_label = $this->before_label;
        if ($before_label == strip_tags($before_label) && strpos($before_label, 'fa-') !== false) {
            $this->before_label = '<span class="menu-icon"><i  aria-hidden="true" class="fa '. $before_label .'"></i></span>';
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenuChild()
    {
        return $this->hasMany(MenuItem::className(), ['parent_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(MenuItem::className(), ['id' => 'parent_id']);
    }

    /*
     * Проверяем пункт меню на текущий/нет
     * Если true - проверям с action иначе - все экшены контроллера
     */
    public function isActive($withAction = true)
    {
        if($withAction) {
            if (Yii::$app->request->baseUrl . "/" . Yii::$app->request->getPathInfo() == Yii::$app->urlManager->createUrl($this->url)) {
                return true;
            }
        }
        else{
            $controller = Yii::$app->controller->id;
            $module     = Yii::$app->controller->module->id;

            $request = new Request();
            $request->setPathinfo($this->url);
            $arr = explode('/',Yii::$app->urlManager->parseRequest($request)[0]);

            if(count($arr)==2 && ($arr[0]==$controller || ($arr[0]==$module && $arr[1]==$controller)))
            {
                return true;
            }
            else if(count($arr)==3 && $arr[0]==$module && $arr[1]==$controller)
            {
                return true;
            }
        }
        return false;
    }
    /**
     * @return array
     */
    public static function getStatusArray()
    {
        return [
            self::STATUS_HIDDEN   => Yii::t('smy.menu', 'Hidden'),
            self::STATUS_DISABLED => Yii::t('smy.menu', 'Disabled'),
            self::STATUS_ACTIVE   => Yii::t('smy.menu', 'Active'),
        ];
    }
}