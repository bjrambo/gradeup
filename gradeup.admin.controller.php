<?php
    
	/*************************************
	**		관리자 컨트롤러 클래스	 	**
	**************************************/

    class gradeupAdminController extends gradeup {

        //초기화
        function init() {           
        }

		//관리자 모듈설정저장
		function procGradeupAdminModuleInfo(){
			//입력값을 모두 받음
            $args = Context::getRequestVars();
			$args->module = 'gradeup';
			//모듈등록 유무에 따라 insert/update
			$oModuleController = &getController('module');
			if(!$args->module_srl){
				$output = $oModuleController->insertModule($args); //모듈insert
				$this->setMessage('success_registed');
			}else{ 
				$output = $oModuleController->updateModule($args); //모듈update
				$this->setMessage('success_updated');
			}
            if(!$output->toBool()) return $output;
			//모듈시작 화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispGradeupAdminModuleInfo')); 
		}		
		
		//등업설정
		function procGradeupAdminGradeConfig(){
			//모듈설정 가져오기
            $oModuleModel = &getModel('module');
            $module_config = $oModuleModel->getModuleConfig('gradeup');
			
			//입력값을 모두 받음
            $args = Context::getRequestVars();
			
			//모듈설정저장
			$oModuleController = &getController('module');
			$module_config->module = 'gradeup';	//모듈명
			$module_config->gradeup_use = $args->gradeup_use; 					//등업기능 사용여부
			//등업순위,만료일 빈값일경우 강제지정
			foreach($args->gradeup_condition as $key => $val){
				if(!$val['gradeup_priority']) $args->gradeup_condition[$key]['gradeup_priority'] = '1';	//등업순위
				if(!$val['gradeup_remain_date']) $args->gradeup_condition[$key]['gradeup_remain_date'] = '30';	//만료일
			}
			$module_config->gradeup_condition = $args->gradeup_condition; 		//그룹별 등업조건
			$module_config->gradeup_msg_use = $args->gradeup_msg_use; 			//등업 축하쪽지 사용여부
			$module_config->garadeup_msg_member_srl = $args->garadeup_msg_member_srl ? $args->garadeup_msg_member_srl : 4; 			//쪽지 보내는사람 member_srl
			$module_config->garadeup_msg_title = $args->garadeup_msg_title ? $args->garadeup_msg_title : '축하합니다.'; 			//등업 축하쪽지 제목
			$module_config->gradeup_auto_msg = $args->gradeup_auto_msg ? $args->gradeup_auto_msg : '[nick_name]님. [group_name]으로 등업되셨습니다.[enter]앞으로도 많은 활동 부탁드립니다.' ;			//자동등업 메세지
			$module_config->gradeup_confirm_msg = $args->gradeup_confirm_msg ? $args->gradeup_confirm_msg : '[nick_name]님. [group_name]으로 등업되셨습니다.[enter]앞으로도 많은 활동 부탁드립니다.' ;	//수동등업 메세지
			$module_config->gradeup_term_msg = $args->gradeup_term_msg ? $args->gradeup_term_msg : '[nick_name]님. [remain_date]까지 [group_name]으로 등업되셨습니다.[enter]앞으로도 많은 활동 부탁드립니다.' ;	//기간제등업 메세지
			$oModuleController->insertModuleConfig('gradeup', $module_config);
			
			//설정화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispGradeupAdminGradeConfig')); 
		}
		
		//로그 표시항목
		function procGradeupAdminLogList(){
		
			//모듈설정 가져오기
            $oModuleModel = &getModel('module');
            $module_config = $oModuleModel->getModuleConfig('gradeup');
			
			//변수정리
            $args = Context::getRequestVars();
			$module_config->view_list = $args->view_list ? $args->view_list : array('member_srl','nick_name','gradeup_type','add_group_srl','old_group_srl','new_group_srl','gradeup_add_type','regdate','remain_date','ipaddress');
			
			//설정저장
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('gradeup', $module_config);

			//성공메세지
            $this->setMessage('success_updated');
			
			//로그화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispGradeupAdminGradeLog')); 
		}
		
		//기간제 등업 정보 수정
		function procGradeupAdminTermGroupModify(){
			//입력값을 모두 받음
            $obj = Context::getRequestVars();
			
			//변수세팅
			$args = new stdClass();
			$args->log_srl = $obj->log_srl;
			$args->remain_date = $obj->remain_date.$obj->remain_date_h.$obj->remain_date_i.$obj->remain_date_s;
			
			//정보수정
			executeQuery('gradeup.updateTermGroupLog',$args);
			
			//이전화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispGradeupAdminTermGroupModify','log_srl',$obj->log_srl)); 
		}
		
		//기간제 등업 회원 추가
		function procGradeupAdminTermGroupAdd(){
			//입력값을 모두 받음
            $obj = Context::getRequestVars();
			
			//member_srl 없을시 리턴
			if(!$obj->member_srl) return;
			
			//모델호출
			$oGradeupModel = &getModel('gradeup'); 
			
			//변수설정
			$args = new stdClass();
			$args->member_srl = $obj->member_srl;
			$args->add_group_srl = $obj->group_srl;
			$args->gradeup_add_type = $obj->gradeup_add_type;
			$args->regdate = $obj->regdate.$obj->regdate_h.$obj->regdate_i.$obj->regdate_s;
			$args->remain_date = $obj->remain_date.$obj->remain_date_h.$obj->remain_date_i.$obj->remain_date_s;
			$args->old_group_srl = $oGradeupModel->getMemberGroupSrl($obj->member_srl);
			
			//회원추가 
			executeQuery('gradeup.insertGradeUpTermGroup',$args);
			
			//로그화면으로 돌아감
			$this->setRedirectUrl(getNotEncodedUrl('','module','admin','act','dispGradeupAdminTermGroup')); 
		}
		
		
		
		///////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////////////////  로 그 삭 제 ////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////
		
		//선택로그 삭제
        function procGradeupAdminLogDelete() {
            $log_srls = Context::get('log_srls');
            if(!$log_srls) return new Object(-1,'선택 대상이 없습니다');
			
			//로그분리
            $log_srl_list = explode("@",$log_srls);
            foreach($log_srl_list as $key => $val) {
                // 루프돌면서 선택된 로그 삭제
                $args = null;
                $args->log_srl = $val;
                $this->DeleteLog($args);
            }
            $this->setMessage('success_deleted');
        }
        //전체로그 삭제
        function procGradeupAdminLogDeleteAll() {
            $this->DeleteLogAll();
			//auto_increment 초기화 테이블지정 필수
			$oDB = &DB::getInstance();
			$query = sprintf("alter table %sgradeup_log auto_increment=1",$oDB->prefix);
			$query = $oDB->_query($query);
			$oDB->_fetch($query);
            $this->setMessage('success_deleted');
        }
		
		//선택로그 삭제 (기간제등업)
        function procGradeupAdminTermLogDelete() {
            $log_srls = Context::get('log_srls');
            if(!$log_srls) return new Object(-1,'선택 대상이 없습니다');
			
			//로그분리
            $log_srl_list = explode("@",$log_srls);
            foreach($log_srl_list as $key => $val) {
                // 루프돌면서 선택된 로그 삭제
                $args = null;
                $args->log_srl = $val;
                $this->DeleteTermLog($args);
            }
            $this->setMessage('success_deleted');
        }
        //전체로그 삭제 (기간제등업)
        function procGradeupAdminTermLogDeleteAll() {
            $this->DeleteTermLogAll();
			//auto_increment 초기화 테이블지정 필수
			$oDB = &DB::getInstance();
			$query = sprintf("alter table %sgradeup_term_group auto_increment=1",$oDB->prefix);
			$query = $oDB->_query($query);
			$oDB->_fetch($query);
            $this->setMessage('success_deleted');
        }
		
		//선택로그 삭제 (승인등업)
        function procGradeupAdminConfirmLogDelete() {
            $log_srls = Context::get('log_srls');
            if(!$log_srls) return new Object(-1,'선택 대상이 없습니다');
			
			//로그분리
            $log_srl_list = explode("@",$log_srls);
            foreach($log_srl_list as $key => $val) {
                // 루프돌면서 선택된 로그 삭제
                $args = null;
                $args->log_srl = $val;
                $this->DeleteConfirmLog($args);
            }
            $this->setMessage('success_deleted');
        }
        //전체로그 삭제 (승인등업)
        function procGradeupAdminConfirmLogDeleteAll() {
            $this->DeleteConfirmLogAll();
			//auto_increment 초기화 테이블지정 필수
			$oDB = &DB::getInstance();
			$query = sprintf("alter table %sgradeup_confirm_group auto_increment=1",$oDB->prefix);
			$query = $oDB->_query($query);
			$oDB->_fetch($query);
            $this->setMessage('success_deleted');
        }
		
		////////////// 로그삭제를 위한 메서드, module.xml에 등록하지 않음 ★시작★ ////////////////////
       
		//등업로그삭제
	    function DeleteLog($args) { 	//log_srl
            $output = executeQuery('gradeup.deleteLog',$args);
            if(!$output->toBool()) return $output;
        }
        function DeleteLogAll() {
            $output = executeQuery('gradeup.deleteLog');
            if(!$output->toBool()) return $output;
        }
		
		//기간제등업로그삭제
		function DeleteTermLog($args) { 	//log_srl
            $output = executeQuery('gradeup.deleteTermLog',$args);
            if(!$output->toBool()) return $output;
        }
        function DeleteTermLogAll() {
            $output = executeQuery('gradeup.deleteTermLog');
            if(!$output->toBool()) return $output;
        }
		
		//승인등업로그삭제
		function DeleteConfirmLog($args) { 	//log_srl
            $output = executeQuery('gradeup.deleteConfirmLog',$args);
            if(!$output->toBool()) return $output;
        }
        function DeleteConfirmLogAll() {
            $output = executeQuery('gradeup.deleteConfirmLog');
            if(!$output->toBool()) return $output;
        }
		
		////////////// 로그삭제를 위한 메서드, module.xml에 등록하지 않음 ★끝★  ////////////////////
		
	}
?>