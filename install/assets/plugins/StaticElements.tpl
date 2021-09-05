//<?php
/**
 * StaticElements
 * 
 * StaticElements plugin
 *
 * @category    plugin
 * @version     5.0.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @properties &elementsPath=папка элементов;string;assets/elements/&configFileName=Название конфиг файла;string;config.php&onlyAdmin=Только для админа;string;1&showDebug=Дебаг;string;0
 * @internal    @events OnManagerPageInit,OnPageNotFound,OnWebPageInit,OnChunkFormDelete,OnChunkFormSave,OnPluginFormDelete,OnPluginFormSave,OnSnipFormDelete,OnSnipFormSave,OnTempFormDelete,OnTempFormSave
 * @internal    @modx_category system
 * @internal    @legacy_names StaticElements
 * @internal    @properties {"elementsPath":[{"label":"папкаэлементов","type":"string","value":"assets/elements/","default":"assets/elements/","desc":""}],"configFileName":[{"label":"Названиеконфигфайла","type":"string","value":"config.php","default":"config.php","desc":""}],"translit":[{"label":"Транслитвименахфайловскирилицей","type":"list","value":"нет","options":"да,нет","default":"да","desc":"Еслифайлыскирилицейвкривойкодировкетонадовключить"}],"onlyAdmin":[{"label":"Толькодляадмина","type":"string","value":"1","default":"1","desc":""}],"showDebug":[{"label":"Дебаг","type":"string","value":"0","default":"0","desc":""}]}
 * @internal    @installset base
 *
 * @author Grishin Alexander (lagacy from Dzhuryn Volodymyr)


 */

//&elementsPath=папка элементов;string;assets/elements/
//&configFileName=Название конфиг файла;string;config.php
//&onlyAdmin=Только для админа;string;1
//&showDebug=Дебаг;string;0

require MODX_BASE_PATH.'assets/plugins/staticElements/plugin.staticElements.php';
