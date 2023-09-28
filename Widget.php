<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class Widget extends CBitrixComponent {
    private const ELEMENT_COUNT = 5;
    private const IBLOCK_ID = 15;

    /**
     * @param array $params
     * @return array
     */
    public function onPrepareComponentParams($params) {
        $params['COUNT'] = self::ELEMENT_COUNT; 
        return $params; 
    }

    public function executeComponent() { 
        if (!$this->checkValid()) {
            return false;
        }

        if($this->startResultCache(false, $this->getCacheKey())) { 
            $this->appendElements();
            $this->setElementsCount();
            $this->appendPicturesToElements();
            $this->appendUsersToElements();

            if (empty($this->arResult['WIDGET'])) {
                 $this->abortResultCache(); 
                 return false; 
            }
            
            $this->setResultCacheKeys([]);
            $this->IncludeComponentTemplate(); 
        } 
    } 

    /**
     * @return bool
     */
    private function checkValid() {
        if(empty($this->arParams['SIDEBAR'])){
            return false; 
        }

        return true;
    }

    /**
     * @return array
     */
    private function getCacheKey() {
        global $ARTICLE, $USER;

        $arCache = !empty($ARTICLE['USER']['UF_BANNER']) ? $ARTICLE['USER']['UF_BANNER'] : [];

        return [$arCache, $USER->GetGroups()];
    }

    private function appendElements() {
        $arFilter = [
            'IBLOCK_ID' => self::IBLOCK_ID, 
            'ACTIVE' => 'Y', 
            [
                'LOGIC' => 'OR', 
                [">DATE_ACTIVE_TO" => ConvertTimeStamp(time(), "FULL")],
                ['DATE_ACTIVE_TO' => false] 
            ],
            'PROPERTY_SIDEBAR' => $this->arParams['SIDEBAR'], 
        ];
        $arSelect = [
            'ID', 
            'IBLOCK_ID', 
            'NAME', 
            'SORT', 
            'PREVIEW_TEXT', 
            'PREVIEW_PICTURE', 
            'PROPERTY_USERS', 
        ];

        $res = CIBlockElement::GetList(['SORT' => 'asc'], $arFilter, false, false, $arSelect ); 

        while ($arItem = $res->GetNext()) {
            $this->arResult['WIDGET'][$arItem['ID']] = $arItem;
        }
    }

    private function appendPicturesToElements() {
        $arrPicturesIds = $arrFilesById = [];

        foreach($this->arResult['WIDGET'] as $arItem) {
            if (!empty($arItem["PREVIEW_PICTURE"])) {
                $arrPicturesIds[$arItem["PREVIEW_PICTURE"]] = $arItem["PREVIEW_PICTURE"];
            }
        }

        if (!empty($arrPicturesIds)) {
            $rsFiles = CFile::GetList(["ID" => "asc"], ["@ID" => implode(',',$arrPicturesIds)]);
            while($arFile = $rsFiles->GetNext()) {
                $arrFilesById[$arFile['ID']] = COption::GetOptionString("main", "upload_dir", "upload") 
                    . "/" . $arFile["SUBDIR"] 
                    . "/" . $arFile["FILE_NAME"];
            }

            foreach($this->arResult['WIDGET'] as $itemId => $arItem) {
                $this->arResult['WIDGET'][$itemId]["PICTURE"]["SRC"] = null;

                if (!empty($arItem["PREVIEW_PICTURE"])) {
                    $this->arResult['WIDGET'][$itemId]["PICTURE"]["SRC"] = $arrFilesById[$arItem["PREVIEW_PICTURE"]];
                }
            }
        }
    }

    private function setElementsCount() {
        $this->arResult['COUNT'] = sizeof($this->arResult['WIDGET']);
    }

    private function appendUsersToElements() {
        $arrUsersIds = $arrUsersById = [];

        foreach($this->arResult['WIDGET'] as $arItem) {
            foreach ($arItem['PROPERTY_USERS_VALUE'] as $user) { 
                $arrUsersIds[$user['ID']] = $user['ID'];
            }
        }

        if (!empty(arrUsersIds)) {
            $rsUsers = CUser::GetList(($by="id"), ($order="asc"), ['@ID' implode(',',$arrUsersIds)]);
            while($arUser = $rsUsers->Fetch()) {
                $arrUsersById[$arUser['ID']] = $arUser;
            }

            foreach($this->arResult['WIDGET'] as $itemId => $arItem) {
                if (!isset($this->arResult['WIDGET'][$itemId]["PICTURE"]["USER"])) {
                    $this->arResult['WIDGET'][$itemId]["PICTURE"]["USER"] = [];
                }
                
                foreach ($arItem['PROPERTY_USERS_VALUE'] as $user) { 
                    $this->arResult['WIDGET'][$itemId]["PICTURE"]["USER"][] = $arrUsersById[$user['ID']];
                }
            }
        }
    }
}
