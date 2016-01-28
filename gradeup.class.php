<?php
    
	class gradeup extends ModuleObject {

        /*****************************************
         * @brief 설치시 추가 작업이 필요할시 구현
        ******************************************/
		function moduleInstall() {
			
			return new Object();
        }

        /************************************************
         * @brief 설치가 이상이 없는지 체크하는 method
         ************************************************/
        function checkUpdate() {
		
			// 모듈정보가 없으면 업데이트
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByMid('gradeup');
            if(!$module_info) return true;
			
			return false;
        }

        /****************************************************
         * @brief 업데이트 실행
         ****************************************************/
        function moduleUpdate() {
			
			//모듈 생성
			$oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
			$module_info = $oModuleModel->getModuleInfoByMid('gradeup');
            if(!$module_info->module_srl) {
				$args = null;
				$args->module = 'gradeup';
				$args->mid = 'gradeup';
				$args->browser_title = '등업관리 모듈';
				$oModuleController->insertModule($args);
            }
			
			//트리거 설치 (로그인시 자동등업,기간제등업)
			if(!$oModuleModel->getTrigger('member.doLogin', 'gradeup', 'controller', 'triggerDoLoginAfter','after')){
				$oModuleController->insertTrigger('member.doLogin', 'gradeup', 'controller', 'triggerDoLoginAfter','after');
			}
			
			return new Object(0, 'success_updated');
        }

        /*****************************************************
         * @brief 캐시 파일 재생성
         *****************************************************/
        function recompileCache() {
        
		}

    }
?>