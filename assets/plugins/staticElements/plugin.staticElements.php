<?php

/** @var  $elementsPath string */
/** @var  $configFileName string */
/** @var  $onlyAdmin string */
/** @var  $showDebug string */


if($onlyAdmin==1 && empty($_SESSION['mgrShortname'])){
    return ;
}
require_once MODX_BASE_PATH.'assets/lib/Helpers/FS.php';
if(is_writable($_SERVER['DOCUMENT_ROOT'].'/')===false){
    $modx->logEvent(2002,3,'Нельзя писать в корень сайта','Нельзя писать в корень сайта');
    return;
}

$start = microtime(true);
$elementsPathRelative = '/'.$elementsPath;
$elementsPath = $_SERVER['DOCUMENT_ROOT'].'/'.$elementsPath;

$debug = [];

if(!empty($_SESSION['mgrShortname']) && $showDebug==1 && $modx->isFrontend() && $modx->event->name!='OnPageNotFound'){

    if(!empty($_SESSION['static-debug'])){

        $debugFile = $_SESSION['static-debug'];

        $modx->regClientHTMLBlock( '
    <div id="static-debug">
    <div class="static-title">Static Elements</div>
    Последнее обновление '.$debugFile['work'].'<br>
    Время обновление '.$debugFile['time'].' секунд <br>
    <a href="/static-debug" target="_blank">Детальней</a>
</div>
    
    
    <style>
    #static-debug{
        position: fixed;
        bottom: 20px;
        right: 20px;
        font-size:12px;
        background: rgba(0, 0, 0, 0.16);
        padding:10px;
            z-index: 9999;
    }
</style>
    ');
    }
}
global $categoryData;
$C = $modx->getFullTableName('categories');
$sql = 'SELECT * FROM ' . $C ;
$result = $modx->db->query($sql);
$categoryDateResp = $modx->db->makeArray($result);
foreach ($categoryDateResp as $el) {
    $categoryData[$el['category']] = $el;
}

$expansions = array(
    'chunks'=>'tpl',
    'templates'=>'tpl',
    'snippets'=>'php',
    'plugins'=>'php',
);
if(!function_exists('translit')) {
    function translit($str)
    {
        global $modx;
        $str = str_replace('/','-',$str);
        $str = str_replace('\\','-',$str);
        //$rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
        //$lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
        //return str_replace($rus, $lat, $str);
        return $str;
    }
}

if(!function_exists('str_replace_once')) {
    function str_replace_once($search, $replace, $text)
    {
        $pos = strpos($text, $search);
        return $pos !== false ? substr_replace($text, $replace, $pos, strlen($search)) : $text;
    }
}

if(!function_exists('filePars')) {
    function filePars($file)
    {


        $name = $file;
        $name = basename($name, ".php");
        $name = basename($name, ".tpl");


        $fileContent = file_get_contents($file, FILE_USE_INCLUDE_PATH);
        $pos = strpos($fileContent, '======');
        if ($pos === false) { // нема
            $startText = 'name:' . $name . PHP_EOL;
            $startText .= 'description:' . $name . PHP_EOL;
            $startText .= '======' . PHP_EOL;
            $fileContent = $startText . $fileContent;
            file_put_contents($file, $fileContent, FILE_USE_INCLUDE_PATH);

        }

        $fileArray = explode('======', $fileContent);

        $scriptHeadParamsStr = $fileArray[0];
        $scriptHeadParams = explode("\n", $scriptHeadParamsStr);

        $scriptHeadParamsFormat = array();
        foreach ($scriptHeadParams as $scriptHeadParam) {
            if (empty($scriptHeadParam)) {
                continue;
            }
            $ar = explode(':', $scriptHeadParam);
            $ar[0] = trim($ar[0]);
            $ar[1] = trim($ar[1]);

            $scriptHeadParamsFormat[$ar[0]] = $ar[1];
        }
        $scriptBody = $fileArray[1];

        if (empty($scriptHeadParamsFormat['name'])) {
            $scriptHeadParamsFormat['name'] = $name;

            $startText = 'name:' . $name . PHP_EOL;
            $startText .= 'description:' . $name . PHP_EOL;
            $startText .= '======' . PHP_EOL;
            $fileContent = $startText . $scriptBody;
            file_put_contents($file, $fileContent, FILE_USE_INCLUDE_PATH);


        }
        return array(
            'head' => $scriptHeadParamsFormat,
            'body' => $scriptBody,
        );

    }
}
if(!function_exists('GetListFiles')) {
    function GetListFiles($folder, &$all_files)
    {
        $fp = opendir($folder);
        while ($cv_file = readdir($fp)) {
            if (is_file($folder . "/" . $cv_file)) {
                $all_files[] = $folder . "/" . $cv_file;
            } elseif ($cv_file != "." && $cv_file != ".." && is_dir($folder . "/" . $cv_file)) {
                GetListFiles($folder . "/" . $cv_file, $all_files);
            }
        }
        closedir($fp);
    }
}
if(!function_exists('categoryCheck')) {
    function categoryCheck($category)
    {
        global $modx;
        global $categoryData;
        $C = $modx->getFullTableName('categories');
        $cat = $modx->db->escape($category);
        if (empty($categoryData[$category])) {
            $fields = array('category' => $cat);
            $newCategoryId = $modx->db->insert($fields, $C);
            $categoryData[$newCategoryId] = [
                'id'=>$newCategoryId,
                'category'=>$category,
                'rank'=>0,
            ];
        }
        return $categoryData[$category];
    }
}
if(!function_exists('fileNameParse')) {
    function fileNameParse($elementPath, $fileName)
    {

        $clear = str_replace($elementPath . '/', '', $fileName);
        $level = mb_substr_count($clear, '/');
        if ($level <= 1) {
            $arr = explode('/', $clear);

            if (count($arr) == 1) {
                return array('category' => NULL, 'fileName' => $arr[0]);
            } else {
                return array('category' => $arr[0], 'fileName' => $arr[1]);
            }
        } else {
            return NULL;
        }
    }
}
if(!function_exists('fileWrite')) {
    function fileWrite($file, $data)
    {
        //$file = iconv("UTF-8", "cp1251", $file); //имена файлов кириллицей
        if ($handle = @fopen($file, 'w+')) {
            flock($handle, LOCK_EX);
            fwrite($handle, $data);
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
if(!function_exists('searchInArray')) {
    function searchInArray($arr, $val)
    {

        foreach ($arr as $key => $ar) {
            if ($ar['fileName'] == $val) {

                $data = $ar;
                $data['elementId'] = $key;
                return $data;
            }
        }
        return false;
    }
}
if(!function_exists('fileRead')) {
    function fileRead($fileFull)
    {
        $str = '';
        $fd = fopen($fileFull, 'r+') or die("Ошибка открытия файла");

        if (flock($fd, LOCK_EX)) // установка исключительной блокировки на запись
        {
            while (!feof($fd)) {
                $str .= fgetss($fd);
            }
            flock($fd, LOCK_UN); // снятие блокировки
        }
        fclose($fd);
        return $str;
    }
}
if(!function_exists('getCategoryName')) {
    function getCategoryName($categoryId,$newCategoryName = '')
    {
        global $modx;

        $C = $modx->getFullTableName('categories');

        $sql = 'SELECT * FROM ' . $C . ' where `id`="' . $categoryId . '"';
        $result = $modx->db->query($sql);
        $result = $modx->db->getRow($result);
        $result = str_replace(['/'],'_',$result);
//        var_dump($result);
        return $result;

    }
}
if(!function_exists('getFieldNames')) {
    function getFieldNames($element)
    {
        //chunks,templates,snippets,plugins
        global $modx;

        switch ($element) {
            case 'chunks':
                $fieldNames = array(
                    'tableName' => $modx->getFullTableName('site_htmlsnippets'),
                    'type' => 'chunk',
                    'name' => 'name',
                    'description' => 'description',
                    'code' => 'snippet',
                    'category' => 'category',
                    'id' => 'id'
                );
                break;
            case 'templates':
                $fieldNames = array(
                    'tableName' => $modx->getFullTableName('site_templates'),
                    'type' => 'template',
                    'name' => 'templatename',
                    'description' => 'description',
                    'code' => 'content',
                    'category' => 'category',
                    'id' => 'id'
                );
                break;
            case 'snippets':
                $fieldNames = array(
                    'tableName' => $modx->getFullTableName('site_snippets'),
                    'type' => 'snippet',
                    'name' => 'name',
                    'description' => 'description',
                    'code' => 'snippet',
                    'category' => 'category',
                    'id' => 'id'
                );
                break;
            case 'plugins':
                $fieldNames = array(
                    'tableName' => $modx->getFullTableName('site_plugins'),
                    'type' => 'plugin',
                    'name' => 'name',
                    'description' => 'description',
                    'code' => 'plugincode',
                    'category' => 'category',
                    'id' => 'id'

                );
                break;
        }
        return $fieldNames;
    }
}
if(!function_exists('removeDirectory')) {
    function removeDirectory($dir)
    {
        if ($objs = glob($dir . "/*")) {
            foreach ($objs as $obj) {
                is_dir($obj) ? removeDirectory($obj) : unlink($obj);
            }
        }
        rmdir($dir);
    }
}
if(!function_exists('checkWritableFolder')) {
    function checkWritableFolder($dir){
        $all = ['/'];
        $dirs = explode('/',$dir);
        foreach ($dirs as $dir) {
            if(empty($dir)) continue;
            $all[] = $all[count($all)-1].$dir.'/';
        }

        //так как в корня может не быть прав на запись а в доп папке быть обертаем масив
        $all = array_reverse($all);
        foreach ($all as $dir) {
            $full  = $_SERVER['DOCUMENT_ROOT'].$dir;
            if(is_dir($full) === false) continue; //папки нет не надо проверять права

            return is_writable($full);
        }
        return true;
    }
}

$statusCheck = false;
$eventName = $modx->event->name;
$eventParams = $modx->Event->params;
$configPluginFileName = 'config.json';

$FS = \Helpers\FS::getInstance();

//конфиг  плагина

if (!file_exists($elementsPath)) {
    $check = checkWritableFolder($elementsPathRelative);
    if($check === false){
        $modx->logEvent(2002,3,'Нехватает прав для создания папрки '.$elementsPathRelative,'Нехватает прав для создания папрки '.$elementsPathRelative);
        return ;
    }
    if (!mkdir($elementsPath, 0755)) {
        $modx->logEvent(2002, 3, 'Не створена папка для елементів', 'Не вдалось створити папку');
        return false;
    }
//    mkdir($elementsPath, 0755);
}

if (!file_exists($elementsPath . $configPluginFileName)) {
    $conf = [
        'path'=>$elementsPath,
        'domain'=>$_SERVER['HTTP_HOST'],
    ];
    file_put_contents($elementsPath . $configPluginFileName,json_encode($conf));
}
else{
    $conf = file_get_contents($elementsPath . $configPluginFileName);
    $conf = json_decode($conf,true);
    if($conf['path']!=$elementsPath || $conf['domain']!=$_SERVER['HTTP_HOST']){
        //'новый сервер';

        $delete = [
            $elementsPath.'chunks',
            $elementsPath.'snippets',
            $elementsPath.'templates',
            $elementsPath.'plugins',
        ];
        foreach ($delete as $e){
            if(file_exists($e)){
                removeDirectory($e);
            }
        }
        if(file_exists($elementsPath.'config.php')){
            unlink($elementsPath.'config.php');
        }


        //новая конфигурация
        $conf = [
            'path'=>$elementsPath,
            'domain'=>$_SERVER['HTTP_HOST'],
        ];
        file_put_contents($elementsPath . $configPluginFileName,json_encode($conf));
        //echo 'новый сервер';
//        die();
    }
}

if (!file_exists($elementsPath . $configFileName)) { //перший старт скрипта
    $statusCheck = true;
    $config = array(
        'chunks' => array(),
        'templates' => array(),
        'snippets' => array(),
        'plugins' => array(),
    );
    if (!file_exists($elementsPath)) {
        if (!mkdir($elementsPath, 0755)) {
            $modx->logEvent(2002, 3, 'Не створена папка для елементів', 'Не вдалось створити папку');
            return false;
        }
    }


    foreach ($config as $key => $el) {
        $element = $key;


        if (!file_exists($elementsPath . $element)) {  // створюєм папку для елементу
            if (!mkdir($elementsPath . $element, 0755)) {
                $modx->logEvent(2002, 3, 'Не створена папка для елементу ' . $element, 'Не вдалось створити папку');
                return false;
            }
        }

        $responseField = getFieldNames($element);
        $tableName = $responseField['tableName'];

        $sql = "select * from $tableName";
        $q = $modx->db->query($sql);
        $res = $modx->db->makeArray($q);


        $expansion = $expansions[$element];
        foreach ($res as $re) {
            $elemId = $re[$responseField['id']];
            $elemName = $re[$responseField['name']];
            $elemDescription = $re[$responseField['description']];
            $elemCode = $re[$responseField['code']];
            $elemCategory = $re[$responseField['category']];

            $fileName = translit($elemName) . '.' . $expansion;
            //$modx->logEvent(1,1,$elemName,'Заголовок лога'); 
            //$fileName = $elemName . '.' . $expansion;

            $fileText = 'name:' . $elemName . PHP_EOL;
            $fileText .= 'description:' . $elemDescription . PHP_EOL;
            $fileText .= '======' . PHP_EOL;
            if (in_array($element, array('snippets', 'plugins'))) {
                $fileText .= '<?php' . PHP_EOL;
            }
            $fileText .= $elemCode;



            $categoryCheckResp = getCategoryName($elemCategory);

            $filePathFull = $elementsPath . $element . '/';
            if ($elemCategory > 0) { // якщо елемент має категорію
                $categoryName = $categoryCheckResp['category'];
                $filePathFull .= $categoryName . '/';
            }

            if (!file_exists($filePathFull)) {  // створюєм папку для категорії
                if (!mkdir($filePathFull, 0755)) {
                    $modx->logEvent(2002, 3, 'Не створена папка для категоії ' . $categoryName . ' елемент ' . $element, 'Не вдалось створити папку');
                    return false;
                }
            }


            fileWrite($filePathFull . $fileName, $fileText); //збрегшаєм файл
            $config[$element][$elemId] = array(
                'elementName' => $elemName,
                'fileName' => $fileName,
                'categoryId' => $elemCategory,
                'category' => $categoryName,
                'date' => time()
            );

        }


    }
    fileWrite($elementsPath . $configFileName, json_encode($config));
}

$config = fileRead($elementsPath . $configFileName);
$config = json_decode($config, true);

//$_GET['q']

if ($eventName == 'OnWebPageInit' || $eventName=='OnManagerPageInit' || $eventName=='OnPageNotFound') {

    if(is_array($config)) {

        foreach ($config as $key => $el) {

            $element = $key; // нахва едеменьу
            $elementPath = $elementsPath . $element;

            if (!file_exists($elementPath)) {
                mkdir($elementPath, 0700, true);
            }

            $files = array();
            $parseFiles = array();
            GetListFiles($elementPath, $files);
            foreach ($files as $file) {
                $resp = fileNameParse($elementPath, $file);
                if (isset($resp)) {
                    $parseFiles[] = $resp;
                }
            }


            $fieldNames = getFieldNames($element);


            foreach ($parseFiles as $file) {

                $categoryId = NULL;

                if (isset($file['category'])) {
                    $categoryId = categoryCheck($file['category']);
                    $categoryId = $categoryId['id'];
                }

                $response = searchInArray($config[$element], $file['fileName']);
                //echo $file['fileName'] .' - '.!empty($response).'<br>';
                if (!empty($response)) { //файл уже є


                    $fileFull = $elementPath;
                    if (!empty($file['category'])) {
                        $fileFull .= '/' . $file['category'];
                    }
                    $fileFull .= '/' . $file['fileName'];
                    $fileFullParams = filePars($fileFull);


                    if (filemtime($fileFull) != $response['date'] && isset($fileFullParams)) {
                        // echo 'файл оновлено';
                        $statusCheck = true;

                        $fieldName = $fieldNames['name'];
                        $elementTable = $fieldNames['tableName'];
                        $code = $modx->db->escape($fileFullParams['body']);
                        $code = str_replace_once('<?php', '', $code);

                        $removeFirst = ['\r', '\n'];
                        foreach ($removeFirst as $char) {
                            if (substr($code, 0, 2) == $char) {
                                $code = substr($code, 2);
                            }

                        }
                        $fields = array(
                            $fieldNames['name'] => $modx->db->escape($fileFullParams['head']['name']),
                            $fieldNames['description'] => $modx->db->escape($fileFullParams['head']['description']),
                            $fieldNames['code'] => $code,
                            $fieldNames['category'] => 0,
                        );

                        if (!empty($categoryId)) {
                            $fields['category'] = $categoryId;
                        }
                        $debug['update'][$fieldNames['type']][] = [
                            'name' => $fileFullParams['head']['name'],
                        ];
                        $modx->db->update($fields, $elementTable, 'id = "' . $response['elementId'] . '"');
                        $config[$element][$response['elementId']]['date'] = filemtime($fileFull); // обновляем дату
                    }


                } else { //новий файл

                    $statusCheck = true;
                    //echo 'новий файл';
                    $fileFull = $elementPath;
                    if (!empty($file['category'])) {
                        $fileFull .= '/' . $file['category'];
                    }
                    $fileFull .= '/' . $file['fileName'];

                    $fileFullParams = filePars($fileFull);
                    if (isset($fileFullParams)) {

                        $code = $modx->db->escape($fileFullParams['body']);
                        $code = str_replace_once('<?php', '', $code);

                        $fields = array(
                            $fieldNames['name'] => $fileFullParams['head']['name'],
                            $fieldNames['description'] => $fileFullParams['head']['description'],
                            $fieldNames['code'] => $code,
                            $fieldNames['category'] => 0
                        );
                        if (!empty($categoryId)) {
                            $fields['category'] = $categoryId;
                        }
                        $name = $modx->db->escape($fileFullParams['head']['name']);
                        $fieldName = $fieldNames['name'];
                        $elementTable = $fieldNames['tableName'];

                        $elementId = $modx->db->getValue($modx->db->query("select `id` from $elementTable where $fieldName ='" . $name . "'"));

                        //echo $elementId . ' ' . $file['fileName'] . '<br>';
                        //новие файлы
                        $debug['new'][$fieldNames['type']][] = [
                            'name' => $fileFullParams['head']['name'],
                        ];

                        if (empty($elementId)) {
                            $modx->db->insert($fields, $elementTable);
                            $elementId = $modx->db->getValue($modx->db->query("select `id` from $elementTable where $fieldName ='" . $name . "'"));
                        } else {
                            $modx->db->update($fields, $elementTable, 'id = "' . $elementId . '"');
                        }
                        $config[$element][$elementId] = array('elementName' => $fileFullParams['head']['name'], 'fileName' => $file['fileName'], 'categoryId' => $categoryId, 'category' => $file['category'], 'date' => time());
                    }
                }
            }
        }

    }

}
elseif(in_array($eventName,array('OnSnipFormSave','OnChunkFormSave','OnTempFormSave','OnPluginFormSave'))){

    $debugFile = $_SERVER['DOCUMENT_ROOT'].'/debug.txt';

    //chunks,templates,snippets,plugins
    switch($eventName){
        case 'OnSnipFormSave':
            $element = 'snippets';
            $table = $modx->getFullTableName('site_snippets');
            break;
        case 'OnChunkFormSave':
            $element = 'chunks';
            $table = $modx->getFullTableName('site_htmlsnippets');
            break;
        case 'OnTempFormSave':
            $element = 'templates';
            $table = $modx->getFullTableName('site_templates');
            break;
        case 'OnPluginFormSave':
            $element = 'plugins';
            $table = $modx->getFullTableName('site_plugins');
            break;
        default:
            $element = 'plugins';
            $table = $modx->getFullTableName('site_plugins');
            break;
    }

    $fieldNames = getFieldNames($element);

    $elemId = $eventParams['id'];
    $elemName = $_POST[$fieldNames['name']];
    $elemDescription = $_POST[$fieldNames['description']];
    $elemCode = $_POST['post'];

    $elemCode = str_replace("\r","", $elemCode);

    $elemCategory = $modx->db->getValue($modx->db->query("select category from $table where id = ".$modx->event->params['id']));

    $expansion = $expansions[$element];
    $fileName = translit($elemName) . '.' . $expansion;
    //$fileName = $elemName . '.' . $expansion;



    $fileText = 'name:' . $elemName . PHP_EOL;
    $fileText .= 'description:' . $elemDescription . PHP_EOL;
    $fileText .= '======' . PHP_EOL;
    if (in_array($element, array('snippets', 'plugins'))) {

        //$modx->logEvent(1,1,$elemCode,'Заголовок лога');

        if(strpos($elemCode,'<?php') === false) {
            $fileText .= '<?php' . PHP_EOL;
        }

        else $fileText .= PHP_EOL;
    }
    $fileText .= $elemCode;


    $categoryCheckResp = getCategoryName($elemCategory);

    $categoryName = $categoryCheckResp['category'];
    $filePathFull = $elementsPath . $element . '/';
    if ($elemCategory > 0) { // якщо елемент має категорію

        $filePathFull .= $categoryName . '/';
    }

    if (!file_exists($filePathFull)) {  // створюєм папку для категорії
        if (!mkdir($filePathFull, 0755)) {
            $modx->logEvent(2002, 3, 'Не створена папка для категоії ' . $categoryName . ' елемент ' . $element, 'Не вдалось створити папку');
            return false;
        }
    }

    if(isset($config[$element][$elemId]['categoryId'])){
        // отримуэм стару категорію
        $oldPathFull = $elementsPath . $element . '/';
        $oldFileName = $config[$element][$elemId]['fileName'];
        if($config[$element][$elemId]['categoryId']>0){
            $oldPathFull .= $config[$element][$elemId]['category'] . '/';
        }
    }

    if(isset($config[$element][$elemId]['categoryId']) && ($elemCategory!=$config[$element][$elemId]['categoryId'] || $fileName!=$config[$element][$elemId]['fileName'])){
        //видаляєм файл при зміні категорії чи назви
        unlink($oldPathFull . $oldFileName);

    }


    fileWrite($filePathFull . $fileName, $fileText); //збрегшаєм файл
    $config[$element][$elemId] = array(
        'elementName' => $elemName,
        'fileName' => $fileName,
        'categoryId' => $elemCategory,
        'category' => $categoryName,
        'date' => time()
    );

    //file_put_contents($debugFile,$elemId);
    $statusCheck = false;
}
elseif(in_array($eventName,array('OnSnipFormDelete','OnChunkFormDelete','OnTempFormDelete','OnPluginFormDelete'))){
    $debugFile = $_SERVER['DOCUMENT_ROOT'].'/debug.txt';

    $elemId = $eventParams['id'];

    switch($eventName){
        case 'OnSnipFormDelete':
            $element = 'snippets';
            break;
        case 'OnChunkFormDelete':
            $element = 'chunks';
            break;
        case 'OnTempFormDelete':
            $element = 'templates';
            break;
        case 'OnPluginFormDelete':
            $element = 'plugins';
            break;
        default:
            $element = 'plugins';
    }

    $elementConfig = $config[$element][$elemId];

    $deleteElemPath = $elementsPath.$element.'/';

    if(!empty($elementConfig['category'])){
        $deleteElemPath = $deleteElemPath.$elementConfig['category'].'/';
    }
    $deleteElemFull = $deleteElemPath. $elementConfig['fileName'];
    if($FS->checkFile($deleteElemFull) === true){
        unlink($deleteElemFull);
    }
    file_put_contents($debugFile,$deleteElemFull);
    $statusCheck = false;
}

if($eventName=='OnPageNotFound' && $_GET['q']=='static-debug' && !empty($_SESSION['mgrShortname'])){

    if(empty($_SESSION['static-debug'])){
        return '';
    }
    $debug = $_SESSION['static-debug'];

    echo 'Последнее обновление '.$debug['work'].'<br>';
    echo 'Время обновление '.$debug['time'].' секунд <br>';

    if(is_array($debug['new'])){
        echo 'Новие файлы <br>';
        foreach ($debug['new'] as $key=>  $type){
            echo '-- '.$key.'<br>';
            foreach ($type as $e){

                echo '------- '.$e['name'].'<br>';
            }
            echo '<br>';

        }
    }
    if(is_array($debug['update'])){
        echo 'Обновленние файлы <br>';
        foreach ($debug['update'] as $key=>  $type){
            echo '-- '.$key.'<br>';
            foreach ($type as $e){

                echo '------- '.$e['name'].'<br>';
            }
            echo '<br>';

        }
    }
    die();
}
fileWrite($elementsPath . $configFileName, json_encode($config));



$time = microtime(true) - $start;
$time =  sprintf('%.4F',$time);

$debug['time']=$time;
$debug['work']=date('d-m-Y h:i:s');



if($statusCheck){
    $modx->clearCache('full');
    $_SESSION['static-debug']=$debug;
    $redUrl = $_SERVER['REQUEST_URI'];
    if(IN_MANAGER_MODE){
        echo '<script>
        location.reload()
        </script>';
        die();
    }
    if(!empty($_POST)){
        $output = '<form method="post" action="'.$redUrl.'" id="myf-form">';
        foreach($_POST as $key => $value){
            $output .= '<textarea style="display:none" name="'.$key.'" >'.$value.'</textarea>';
        }
        $output .= '</form>
        <script>
        document.getElementById("myf-form").submit();
        </script>
';
        echo $output;die();
    }
    header("Location: $redUrl");
    die();

}