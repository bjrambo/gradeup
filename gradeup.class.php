<?php

class gradeup extends ModuleObject
{
	var $triggers = array(
		array('member.doLogin', 'gradeup', 'controller', 'triggerDoLoginAfter','after')
	);

	function moduleInstall()
	{
		return new BaseObject();
	}


	function checkUpdate()
	{
		// 모듈정보가 없으면 업데이트
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('gradeup');
		if(!$module_info) return true;
		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}
		return false;
	}

	function moduleUpdate()
	{

		//모듈 생성
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$module_info = $oModuleModel->getModuleInfoByMid('gradeup');
		if(!$module_info->module_srl)
		{
			$args = new stdClass;
			$args->module = 'gradeup';
			$args->mid = 'gradeup';
			$args->browser_title = '등업관리 모듈';
			$oModuleController->insertModule($args);
		}

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new BaseObject(0, 'success_updated');
	}

	function recompileCache()
	{
	}
}
