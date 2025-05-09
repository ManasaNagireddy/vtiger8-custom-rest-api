<?php
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
?>