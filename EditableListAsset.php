<?php

namespace mdm\relation;

/**
 * Description of DetailListViewAsset
 *
 * @author Misbahul D Munir (mdmunir) <misbahuldmunir@gmail.com>
 */
class EditableListAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@mdm/relation/assets';
    public $js = [
        'mdm.editableList.js'
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];

}