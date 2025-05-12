<?php
/*Remarks :  To Retrieve specific record details*/
function getRecordDetails($crmid,$module){
	 global $adb;
	 $query = $adb->pquery("SELECT crmid FROM vtiger_crmentity  where vtiger_crmentity.crmid = ? and vtiger_crmentity.deleted=0",array($crmid));
	 $count = $adb->num_rows($query);
		if($count==1){
			$recordModel = Vtiger_Record_Model::getInstanceById($crmid);
			$array =['label','tags','id'];
			$fieldModelList = $recordModel->getModule()->getFields();
			
			foreach ($fieldModelList as $fieldName => $fieldModel) {
                //For not converting craetedtime and modified time to user format
				$uiType = $fieldModel->get('uitype');
				$actual_value = $recordModel->get($fieldName);
                if ($uiType == 70) {
                    $fieldValue = $recordModel->get($fieldName);
                } else {
                    $fieldValue = $fieldModel->getUITypeModel()->getDisplayValue($recordModel->get($fieldName));
                }
                $fieldDataType = $fieldModel->getFieldDataType();
                if ($fieldDataType == 'time') {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recdata[$fieldName] = $fieldValue;
				}
			
				
				$recdata[$fieldName] = ['display_value'=>strip_tags($fieldValue),'actual_value'=>$actual_value];
			}
			return ['data'=>$recdata];
		}else{
			return ['error' => "This record is removed from CRM"];
		}
}
/*Remarks : Get Enabled module list from profile*/
function getEnabledModulesList(){
	global $adb,$current_user;
	$accessibleModules = [];
	$allModules  = Vtiger_Module_Model::getAll(array(0, 2));// 'true' means only active modules
	foreach ($allModules as $module) {
		$moduleName = $module->name;
		// Check if the user has access
		if (isPermitted($moduleName, 'index', '', $current_user->id) == 'yes') {
			$accessibleModules[$moduleName] = vtranslate($moduleName,$moduleName);
		}
	}
	return $accessibleModules;
}
/*Remarks : get CustomViews of a module*/
function getCustomViews($module){
				global $adb;
				$response = [];

				$CUSTOM_VIEWS = CustomView_Record_Model::getAllByGroup($module);
				if(php7_count($CUSTOM_VIEWS)){
					$response = [];
					$response['customviews'] = [];
					foreach ($CUSTOM_VIEWS as $GROUP_LABEL => $GROUP_CUSTOM_VIEWS) {
						if ($GROUP_LABEL == 'Mine') {
							$response['customviews']['mine'] = [];
							foreach ($GROUP_CUSTOM_VIEWS as $CUSTOM_VIEW) {
								$response['customviews']['mine'][] = [
									'id' => $CUSTOM_VIEW->get('cvid'),
									'name' => vtranslate($CUSTOM_VIEW->get('viewname'), $module),
									'owner' => $CUSTOM_VIEW->getOwnerName()
								];
							}
						} else {
							foreach ($GROUP_CUSTOM_VIEWS as $CUSTOM_VIEW) {
								$response['customviews'][$GROUP_LABEL][] = [
									'id' => $CUSTOM_VIEW->get('cvid'),
									'name' => vtranslate($CUSTOM_VIEW->get('viewname'), $module),
									'owner' => $CUSTOM_VIEW->getOwnerName()
								];
							}
						}
					}
				}
				else{
					$response = ['error' => 'No custom views found'];
				}
				return $response;
}
/*Remarks : get all records list of selected user*/
function getAllRecords($modulename, $userid, $cvid = null, $page = 0, $pageLimit = 20){ 
	global $adb, $current_user;
    $listHeaders = [];

    // Get module model and listview model
    $moduleModel = Vtiger_Module_Model::getInstance($modulename);
    $listViewModel = Vtiger_ListView_Model::getInstance($modulename, $cvid);

    // Set pagination
    $pagingModel = new Vtiger_Paging_Model();
    $pagingModel->set('page', $page);
    $pagingModel->set('limit', $pageLimit);

    // Get headers
    $listViewHeaders = $listViewModel->getListViewHeaders();
    foreach ($listViewHeaders as $header) {
        $listHeaders[] = $header->get('name');
    }

    // Get entries (records)
    $listViewEntries = $listViewModel->getListViewEntries($pagingModel);

	foreach ($listViewEntries as $recordId => $recordModel) {
    	$recordData = [];
   		foreach ($listHeaders as $fieldName) {
			//$fieldModel = $recordModel->getModule()->getField($fieldName);
			$recordData[$fieldName] = $recordModel->get($fieldName);
		}
		$cleanedEntries[] = $recordData;
	}

    return [
        'headers' => $listHeaders,
        'records' => $listViewEntries,
		'display_values' => $cleanedEntries,
        'page' => $page,
        'pageLimit' => $pageLimit
    ];
}

function getSpecificFieldName($colname,$tabid){
   global $adb;
   $fieldinfo = $adb->fetchByAssoc($adb->pquery("select fieldname,fieldlabel from vtiger_field where columnname = ? and tabid = ?",[$colname,$tabid]));
   return $fieldinfo;
}
?>