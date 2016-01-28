<?php
    
	/**********************************
	**		관리자 뷰 클래스		 **
	***********************************/

    class gradeupAdminView extends gradeup {

        //초기화
        function init() {
			//모듈정보구함
			$args->module = 'gradeup'; //쿼리에 모듈명 변수전달
			$oModuleModel = &getModel('module');
			$oGradeupModel = &getModel('gradeup');
            $this->module_info = $oGradeupModel->getModuleInfo($args);
            $this->module_config = $oModuleModel->getModuleConfig('gradeup');		
			//모듈정보세팅
			Context::set('module_config', $this->module_config);
            Context::set('module_info', $this->module_info);
			// 관리자 템플릿 파일의 경로 설정 (tpl)
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

		//스킨관리 
		function dispGradeupAdminSkinInfo() {
			$oModuleAdminModel = &getAdminModel('module');
			$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
			Context::set('skin_content', $skin_content);
			// 템플릿 파일 지정			
			$this->setTemplateFile('skin_info');
        }	
		
		//권한관리
		function dispGradeupAdminGrantInfo() {
			$oModuleAdminModel = &getAdminModel('module');
			$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
			Context::set('grant_content', $grant_content);
			//템플릿 파일 지정
			$this->setTemplateFile('grant_list');
		}
		
        //관리자모듈설정
        function dispGradeupAdminModuleInfo() {
			// 모듈 카테고리 목록 구함
			$oModuleModel = &getModel('module');
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);
			// 스킨 목록 구함
            $skin_list = $oModuleModel->getSkins($this->module_path);
			$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
            Context::set('skin_list',$skin_list);
			Context::set('mskin_list',$mskin_list);
			// 레이아웃 목록 구함
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
			$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
            Context::set('layout_list', $layout_list);
			Context::set('mlayout_list', $mobile_layout_list);
			//템플릿 파일 지정
            $this->setTemplateFile('index');
		}
		
		//등업설정
		function dispGradeupAdminGradeConfig(){
			//회원그룹 리스트 구함  
			$oMemberModel = &getModel('member');			 
			$group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);
			
			//포인트(레벨) 정보 구함
			$oModuleModel = &getModel('module');
			$point_config = $oModuleModel->getModuleConfig('point');
			Context::set('max_level', $point_config->max_level);	
			
			//템플릿 파일 지정
			$this->setTemplateFile('grade_config');
		}
		
		//승인제등업관리
		function dispGradeupAdminConfirmGroup(){
			//검색기간설정
			$search_day = Context::get('search_day'); 
			if(!$search_day) $search_day = 'all';
			if($search_day == 'last'){
				$day_last = Context::get('day_last');  
                if(!$day_last) $day_last = 7;
				$args->regdate_more = date('Ymd',strtotime(sprintf('-%s day', $day_last)));
			}else if($search_day == 'moreless'){
				$day_more = Context::get('day_more');  
				$day_less = Context::get('day_less');  
				if(	$day_more != '') $args->regdate_more = date('Ymd',strtotime($day_more));
				if(	$day_less != '') $args->regdate_less = date('Ymd',strtotime($day_less)). '235959';
			}
			//검색대상설정
			$args->search_target = Context::get('search_target');
			$args->search_keyword = Context::get('search_keyword');
			$search_target = trim($args->search_target);
			$search_keyword = trim($args->search_keyword);
			//검색결과 변수에 넣음
			if($search_target && $search_keyword) {
				switch($search_target) {
					case 'log_srl' :
						$args->s_log_srl = $search_keyword;
						break; 
					case 'member_srl' :
						$args->s_member_srl = $search_keyword;
						break;
					case 'not_member_srl' :
						$args->not_member_srl = $search_keyword;
						break;	
					case 'regdate' :
						$args->s_regdate = $search_keyword;
						break;	
					case 'condition_result' :
						if($search_keyword == 'X' || $search_keyword == 'no'){
							$args->s_condition_result = 'no';
						}elseif($search_keyword == 'O' || $search_keyword == 'ok'){
							$args->s_condition_result = 'ok';
						}
						break;	
					case 'state' :
						if($search_keyword == '대기' || $search_keyword == 'ready'){
							$args->s_state = 'ready';
						}elseif($search_keyword == '승인' || $search_keyword == 'ok'){
							$args->s_state = 'ok';
						}elseif($search_keyword == '거절' || $search_keyword == 'no'){
							$args->s_state = 'no';
						}
						break;	
				}
			}
			
			//목록수
			$args->list_count = Context::get('list_count');
			
			//페이지
			$args->page = Context::get('page');
			$args->order_type = Context::get('order_type');
			if(!$args->order_type) $args->order_type = 'desc';
			
			//로그구함
            $oGradeupModel = &getModel('gradeup');
            $output = $oGradeupModel->getConfirmGroupLog($args);
			
			//그룹 보기 편하기 정리 (group_srl -> group_title)
			$oMemberModel = &getModel('member');  
			foreach($output->data as $key => $val){
				$group_srl = explode('@', $val->old_group_srl);
				$output->data[$key]->old_group_srl = null;
				foreach($group_srl as $val){
					$group_info = $oMemberModel->getGroup($val);
					if(!$output->data[$key]->old_group_srl){
						$output->data[$key]->old_group_srl = $group_info->title;
					}else{
						$output->data[$key]->old_group_srl = $group_info->title.', '.$output->data[$key]->old_group_srl;
					}
				}
			}
			foreach($output->data as $key => $val){
				$group_info = $oMemberModel->getGroup($val->add_group_srl);
				$val->add_group_srl = $group_info->title;
			}
			
			//결과값 세팅
			Context::set('log_info',$output->data);
			
			//페이지 세팅
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page_list', $output->data);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);
			
			//템플릿 파일 지정
			$this->setTemplateFile('confirm_group');
		}
		
		//기간제등업관리
		function dispGradeupAdminTermGroup(){
			//검색기간설정
			$search_day = Context::get('search_day'); 
			if(!$search_day) $search_day = 'all';
			if($search_day == 'last'){
				$day_last = Context::get('day_last');  
                if(!$day_last) $day_last = 7;
				$args->regdate_more = date('Ymd',strtotime(sprintf('-%s day', $day_last)));
			}else if($search_day == 'moreless'){
				$day_more = Context::get('day_more');  
				$day_less = Context::get('day_less');  
				if(	$day_more != '') $args->regdate_more = date('Ymd',strtotime($day_more));
				if(	$day_less != '') $args->regdate_less = date('Ymd',strtotime($day_less)). '235959';
			}
			//검색대상설정
			$args->search_target = Context::get('search_target');
			$args->search_keyword = Context::get('search_keyword');
			$search_target = trim($args->search_target);
			$search_keyword = trim($args->search_keyword);
			//검색결과 변수에 넣음
			if($search_target && $search_keyword) {
				switch($search_target) {
					case 'log_srl' :
						$args->s_log_srl = $search_keyword;
						break; 
					case 'member_srl' :
						$args->s_member_srl = $search_keyword;
						break;
					case 'not_member_srl' :
						$args->not_member_srl = $search_keyword;
						break;	
					case 'regdate' :
						$args->s_regdate = $search_keyword;
						break;	
					case 'remain_date' :
						$args->s_remain_date = $search_keyword;
						break;	
				}
			}
			
			//목록수
			$args->list_count = Context::get('list_count');
			
			//페이지
			$args->page = Context::get('page');
			$args->order_type = Context::get('order_type');
			if(!$args->order_type) $args->order_type = 'desc';
			
			//로그구함
            $oGradeupModel = &getModel('gradeup');
            $output = $oGradeupModel->getTermGroupLog($args);
			
			//그룹 보기 편하기 정리 (group_srl -> group_title)
			$oMemberModel = &getModel('member');  
			foreach($output->data as $key => $val){
				$group_srl = explode('@', $val->old_group_srl);
				$output->data[$key]->old_group_srl = null;
				foreach($group_srl as $val){
					$group_info = $oMemberModel->getGroup($val);
					if(!$output->data[$key]->old_group_srl){
						$output->data[$key]->old_group_srl = $group_info->title;
					}else{
						$output->data[$key]->old_group_srl = $group_info->title.', '.$output->data[$key]->old_group_srl;
					}
				}
			}
			foreach($output->data as $key => $val){
				$group_info = $oMemberModel->getGroup($val->add_group_srl);
				$val->add_group_srl = $group_info->title;
			}
			
			//결과값 세팅
			Context::set('log_info',$output->data);
			
			//페이지 세팅
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page_list', $output->data);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);
			
			//템플릿 파일 지정
			$this->setTemplateFile('term_group');
		}
		
		//기간제등업회원 추가
		function dispGradeupAdminTermGroupAdd(){
			//회원그룹 리스트 구함  
			$oMemberModel = &getModel('member');			 
			$group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);
			
			//템플릿 파일 지정
			$this->setTemplateFile('term_group_add');
		}
		
		
		//기간제등업정보 수정
		function dispGradeupAdminTermGroupModify(){
			//로그번호가져옴
			$log_srl = Context::get('log_srl');
			//로그번호 없을시 리턴
			if(!$log_srl) return;
			//변수세팅
			$args = new stdClass();
			$args->log_srl = $log_srl;
			//정보 가져옴
			$oGradeupModel = &getModel('gradeup');
			$output = $oGradeupModel->getTermGroupLog($args);
			//그룹 보기 편하기 정리 (group_srl -> group_title)
			$oMemberModel = &getModel('member');  
			foreach($output->data as $key => $val){
				$group_srl = explode('@', $val->old_group_srl);
				$output->data[$key]->old_group_srl = null;
				foreach($group_srl as $val){
					$group_info = $oMemberModel->getGroup($val);
					if(!$output->data[$key]->old_group_srl){
						$output->data[$key]->old_group_srl = $group_info->title;
					}else{
						$output->data[$key]->old_group_srl = $group_info->title.', '.$output->data[$key]->old_group_srl;
					}
				}
			}
			foreach($output->data as $key => $val){
				$group_info = $oMemberModel->getGroup($val->add_group_srl);
				$val->add_group_srl = $group_info->title;
			}
			//변수세팅
			Context::set('info',$output->data[1]);
			//템플릿 파일 지정
			$this->setTemplateFile('term_group_modify');
		}
		
		//등업로그
		function dispGradeupAdminGradeLog(){
			//검색기간설정
			$search_day = Context::get('search_day'); 
			if(!$search_day) $search_day = 'all';
			if($search_day == 'last'){
				$day_last = Context::get('day_last');  
                if(!$day_last) $day_last = 7;
				$args->regdate_more = date('Ymd',strtotime(sprintf('-%s day', $day_last)));
			}else if($search_day == 'moreless'){
				$day_more = Context::get('day_more');  
				$day_less = Context::get('day_less');  
				if(	$day_more != '') $args->regdate_more = date('Ymd',strtotime($day_more));
				if(	$day_less != '') $args->regdate_less = date('Ymd',strtotime($day_less)). '235959';
			}
			//검색대상설정
			$args->search_target = Context::get('search_target');
			$args->search_keyword = Context::get('search_keyword');
			$search_target = trim($args->search_target);
			$search_keyword = trim($args->search_keyword);
			//검색결과 변수에 넣음
			if($search_target && $search_keyword) {
				switch($search_target) {
					case 'log_srl' :
						$args->s_log_srl = $search_keyword;
						break; 
					case 'member_srl' :
						$args->s_member_srl = $search_keyword;
						break;
					case 'not_member_srl' :
						$args->not_member_srl = $search_keyword;
						break;	
					case 'gradeup_type' :
						if($search_keyword == '기간제등업' || $search_keyword == 'term'){
							$args->s_gradeup_type = 'term';
						}elseif($search_keyword == '자동등업' || $search_keyword == 'auto'){
							$args->s_gradeup_type = 'auto';
						}elseif($search_keyword == '승인등업' || $search_keyword == 'confirm'){
							$args->s_gradeup_type = 'confirm';
						}elseif($search_keyword == '기간만료' || $search_keyword == 'restore'){
							$args->s_gradeup_type = 'restore';
						}
						break;	
					case 'gradeup_add_type' :
						if($search_keyword == '그룹추가' || $search_keyword == 'add'){
							$args->s_gradeup_add_type = 'add';
						}elseif($search_keyword == '초기화' || $search_keyword == 'reset'){
							$args->s_gradeup_add_type = 'reset';
						}
						break;	
					case 'regdate' :
						$args->s_regdate = $search_keyword;
						break;	
					case 'remain_date' :
						$args->s_remain_date = $search_keyword;
						break;	
					case 'ipaddress' :
						$args->s_ipaddress = $search_keyword;
						break;
				}
			}
			
			//목록수
			$args->list_count = Context::get('list_count');
			
			//페이지
			$args->page = Context::get('page');
			$args->order_type = Context::get('order_type');
			if(!$args->order_type) $args->order_type = 'desc';
			
			//로그구함
            $oGradeupModel = &getModel('gradeup');
            $output = $oGradeupModel->getGradeLog($args);
			
			//그룹 보기 편하기 정리 (group_srl -> group_title)
			$oMemberModel = &getModel('member');  
			foreach($output->data as $key => $val){
				$group_srl = explode('@', $val->old_group_srl);
				$output->data[$key]->old_group_srl = null;
				foreach($group_srl as $val){
					$group_info = $oMemberModel->getGroup($val);
					if(!$output->data[$key]->old_group_srl){
						$output->data[$key]->old_group_srl = $group_info->title;
					}else{
						$output->data[$key]->old_group_srl = $group_info->title.', '.$output->data[$key]->old_group_srl;
					}
				}
			}
			foreach($output->data as $key => $val){
				$group_srl = explode('@', $val->new_group_srl);
				$output->data[$key]->new_group_srl = null;
				foreach($group_srl as $val){
					$group_info = $oMemberModel->getGroup($val);
					if(!$output->data[$key]->new_group_srl){
						$output->data[$key]->new_group_srl = $group_info->title;
					}else{
						$output->data[$key]->new_group_srl = $group_info->title.', '.$output->data[$key]->new_group_srl;
					}
				}
			}
			foreach($output->data as $key => $val){
				$group_info = $oMemberModel->getGroup($val->add_group_srl);
				$val->add_group_srl = $group_info->title;
			}
			
			//결과값 세팅
			Context::set('log_info',$output->data);
			
			//페이지 세팅
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page_list', $output->data);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);
			
			//템플릿 파일 지정
			$this->setTemplateFile('grade_log');
		}
	}
?>